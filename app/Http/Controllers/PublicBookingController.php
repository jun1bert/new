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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublicBookingController extends Controller
{
    public function availability(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.now('Asia/Manila')->toDateString()],
            'service_ids' => ['required', 'array', 'min:1', 'max:30'],
            'service_ids.*' => ['required', 'integer', 'distinct', \Illuminate\Validation\Rule::exists('services', 'id')->where('is_active', true)],
        ]);
        $services = Service::whereIn('id', $data['service_ids'])->get();
        if (DB::table('scheduling_settings')->where('id', 1)->value('hours') === null) {
            return response()->json(['slots' => [], 'message' => 'Shop hours are not configured yet. Please contact the studio.'])->header('Cache-Control', 'no-store');
        }
        $day = Carbon::parse($data['date'], 'Asia/Manila');
        $slots = collect();
        foreach (User::where('user_type', 'staff')->where('is_active', true)->pluck('id') as $providerId) {
            $result = app(\App\Services\AppointmentScheduler::class)->availableTimes($day, $services, $providerId, $request->user()->id);
            $slots = $slots->concat($result['slots']);
        }
        return response()->json(['slots' => $slots->unique('value')->sortBy('value')->values()])->header('Cache-Control', 'no-store');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'contact_number' => ['required', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'time' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'participants' => ['required', 'array', 'list', 'min:1', 'max:10'],
            'participants.*.name' => ['required', 'string', 'max:120'],
            'participants.*.service_ids' => ['required', 'array', 'list', 'min:1', 'max:30'],
            'participants.*.service_ids.*' => ['required', 'integer', 'exists:services,id'],
        ]);

        $scheduledAt = Carbon::createFromFormat('Y-m-d H:i', "{$data['date']} {$data['time']}", 'Asia/Manila')->startOfMinute()->timezone(config('app.timezone'));
        if ($scheduledAt->isPast()) {
            return back()->withErrors(['date' => 'Please choose a future appointment time.'])->withInput();
        }

        $services = Service::query()->where('is_active', true)->whereIn('id', collect($data['participants'])->pluck('service_ids')->flatten())->get()->keyBy('id');
        foreach ($data['participants'] as $index => $participant) {
            if (count(array_unique($participant['service_ids'])) !== count($participant['service_ids']) || collect($participant['service_ids'])->contains(fn ($id) => !$services->has($id))) {
                throw ValidationException::withMessages(["participants.{$index}.service_ids" => 'Select available services once per client.']);
            }
        }
        if ($request->user()?->user_type === 'member' && $request->boolean('member_booking')) {
            if (DB::table('scheduling_settings')->where('id', 1)->value('hours') === null) {
                throw ValidationException::withMessages(['time' => 'Shop hours are not configured yet. Please contact the studio.']);
            }
            $day = Carbon::parse($data['date'], 'Asia/Manila');
            foreach ($data['participants'] as $participant) {
                $available = User::where('user_type', 'staff')->where('is_active', true)->pluck('id')->contains(function ($providerId) use ($day, $services, $participant, $request, $data) {
                    $result = app(\App\Services\AppointmentScheduler::class)->availableTimes($day, $services->only($participant['service_ids']), $providerId, $request->user()->id);
                    return collect($result['slots'])->contains('value', $data['time']);
                });
                if (! $available) {
                    throw ValidationException::withMessages(['time' => 'That time is no longer available. Please choose another time.']);
                }
            }
        }
        DB::transaction(function () use ($request, $data, $services, $scheduledAt) {
            foreach ($data['participants'] as $participant) {
                app(\App\Services\AppointmentScheduler::class)->checkRequest($scheduledAt, $services->only($participant['service_ids']));
            }
            $primaryClient = $request->user() ?? $this->createGuest($data['participants'][0]['name']);

            foreach ($data['participants'] as $index => $participant) {
                $client = $index === 0 ? $primaryClient : $this->createGuest($participant['name']);
                $appointment = Appointment::create([
                    'treatment_course_id' => (string) Str::uuid(),
                    'user_id' => $client->id,
                    'service_id' => $participant['service_ids'][0],
                    'session_number' => 1,
                    'scheduled_at' => $scheduledAt,
                    'contact_number' => $data['contact_number'],
                    'booking_email' => $data['email'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'status' => 'pending',
                ]);
                $appointment->services()->sync(collect($participant['service_ids'])->mapWithKeys(fn ($id) => [$id => ['session_count' => $services[$id]->session_count]])->all());
            }
        });

        if ($request->user()) {
            return redirect()->route('dashboard')->with('success', 'Your appointment request has been sent for staff approval.');
        }

        return redirect('/?booking=success#book')->with('success', 'Your appointment request has been sent for staff approval.');
    }

    private function createGuest(string $name): User
    {
        // Contact information is not proof of ownership of an existing account.
        return User::create([
            'name' => $name,
            'email' => 'guest-'.Str::uuid().'@booking.local',
            'user_type' => 'member',
            'password' => Hash::make(Str::random(40)),
        ]);
    }
}
