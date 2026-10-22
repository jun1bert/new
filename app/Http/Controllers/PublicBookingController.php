<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PublicBookingController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'contact_number' => ['required', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'time' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'participants' => ['required', 'array', 'min:1', 'max:10'],
            'participants.*.name' => ['required', 'string', 'max:120'],
            'participants.*.service_ids' => ['required', 'array', 'min:1'],
            'participants.*.service_ids.*' => ['required', 'integer', 'exists:services,id'],
        ]);

        $scheduledAt = Carbon::createFromFormat('Y-m-d H:i', "{$data['date']} {$data['time']}");
        if ($scheduledAt->isPast()) {
            return back()->withErrors(['date' => 'Please choose a future appointment time.'])->withInput();
        }

        $services = Service::query()->where('is_active', true)->whereIn('id', collect($data['participants'])->pluck('service_ids')->flatten())->get()->keyBy('id');
        $primaryClient = $request->user() ?? $this->findOrCreateClient($data['full_name'], $data['email'] ?? null, $data['contact_number']);

        foreach ($data['participants'] as $index => $participant) {
            $client = $index === 0 ? $primaryClient : $this->findOrCreateClient($participant['name'], null, $data['contact_number']);
            foreach ($participant['service_ids'] as $serviceId) {
                abort_unless($services->has($serviceId), 422, 'A selected service is unavailable.');
                Appointment::create([
                    'treatment_course_id' => (string) Str::uuid(),
                    'user_id' => $client->id,
                    'service_id' => $serviceId,
                    'session_number' => 1,
                    'scheduled_at' => $scheduledAt,
                    'contact_number' => $data['contact_number'],
                    'notes' => $data['notes'] ?? null,
                    'status' => 'pending',
                ]);
            }
        }

        if ($request->user()) {
            return redirect()->route('dashboard')->with('success', 'Your appointment request has been sent for staff approval.');
        }

        return redirect('/?booking=success#book')->with('success', 'Your appointment request has been sent for staff approval.');
    }

    private function findOrCreateClient(string $name, ?string $email, string $contactNumber): User
    {
        if ($email && ($client = User::where('email', $email)->first())) {
            return $client;
        }

        $matchingClient = User::query()
            ->where('name', $name)
            ->whereHas('appointments', fn ($query) => $query->where('contact_number', $contactNumber))
            ->latest('id')
            ->first();

        if ($matchingClient) {
            return $matchingClient;
        }

        return User::create([
            'name' => $name,
            'email' => $email ?: 'guest-'.Str::uuid().'@booking.local',
            'user_type' => 'member',
            'password' => Hash::make(Str::random(40)),
        ]);
    }
}
