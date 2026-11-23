<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SchedulingController extends Controller
{
    public function settings()
    {
        return view('admin.settings.index', [
            'providers' => $this->providers(),
            'hours' => json_decode(DB::table('scheduling_settings')->where('id', 1)->value('hours') ?? 'null', true),
            'shifts' => DB::table('staff_schedules')->get()->keyBy('user_id'),
        ]);
    }

    public function availability(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.now('Asia/Manila')->toDateString()],
            'provider_id' => ['required', 'integer', Rule::exists('users', 'id')->where('user_type', 'staff')->where('is_active', true)],
            'service_ids' => ['nullable', 'array', 'max:30'], 'service_ids.*' => ['integer', 'distinct', Rule::exists('services', 'id')->where('is_active', true)],
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'mode' => ['nullable', 'in:reschedule,next'], 'client_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);
        $appointment = !empty($data['appointment_id']) ? Appointment::findOrFail($data['appointment_id']) : null;
        $services = $appointment ? $appointment->booked_services : \App\Models\Service::whereIn('id', $data['service_ids'] ?? [])->get();
        $isNext = ($data['mode'] ?? null) === 'next';
        if ($appointment && $isNext) $services = $services->filter(fn ($service) => ($service->pivot?->session_count ?? $service->session_count) > $appointment->session_number);
        if ($services->isEmpty()) throw ValidationException::withMessages(['service_ids' => 'Choose at least one service first.']);
        return response()->json(app(\App\Services\AppointmentScheduler::class)->availableTimes(
            Carbon::parse($data['date'], 'Asia/Manila'), $services, (int) $data['provider_id'], $appointment?->user_id ?? ($data['client_id'] ?? null),
            $isNext ? null : $appointment?->id, $isNext ? null : $appointment?->duration_minutes,
        ))->header('Cache-Control', 'no-store');
    }

    public function index(Request $request)
    {
        return redirect(route('dashboard', $request->only(['date', 'view', 'provider_id'])).'#calendar');
    }

    public function calendarData(Request $request): array
    {
        $data = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'view' => ['nullable', 'in:day,week'],
            'provider_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);
        $date = Carbon::parse($data['date'] ?? now('Asia/Manila')->toDateString(), 'Asia/Manila');
        $mode = $data['view'] ?? 'week';
        $start = $mode === 'week' ? $date->copy()->startOfWeek() : $date->copy()->startOfDay();
        $days = collect(range(0, $mode === 'week' ? 6 : 0))->map(fn ($offset) => $start->copy()->addDays($offset));
        $end = $days->last()->copy()->endOfDay();
        $appointments = Appointment::with(['client', 'provider', 'services', 'service'])
            ->whereBetween('scheduled_at', [$start->copy()->timezone(config('app.timezone')), $end->copy()->timezone(config('app.timezone'))])
            ->whereIn('status', ['pending', 'scheduled', 'completed', 'cancelled', 'no_show'])
            ->when($data['provider_id'] ?? null, fn ($q, $id) => $q->where('provider_id', $id))
            ->orderBy('scheduled_at')->get();

        return [
            'days' => $days, 'date' => $date, 'mode' => $mode, 'start' => $start,
            'appointments' => $appointments, 'providers' => $this->providers(),
            'providerId' => $data['provider_id'] ?? null,
            'hours' => json_decode(DB::table('scheduling_settings')->where('id', 1)->value('hours') ?? 'null', true),
            'shifts' => DB::table('staff_schedules')->get()->keyBy('user_id'),
        ];
    }

    public function updateHours(Request $request)
    {
        $data = $request->validate([
            'provider_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('user_type', 'staff')->where('is_active', true)],
            'hours' => ['required', 'array', 'size:7'],
            'hours.*' => ['required', 'array:open,close,enabled'],
            'hours.*.open' => ['required', 'date_format:H:i'],
            'hours.*.close' => ['required', 'date_format:H:i'],
            'hours.*.enabled' => ['nullable', 'in:1'],
        ]);
        $hours = [];
        foreach (range(0, 6) as $day) {
            $row = $data['hours'][$day] ?? null;
            if (!$row || ($row['enabled'] ?? false) && $row['close'] <= $row['open']) {
                throw ValidationException::withMessages(['hours' => 'Each enabled day needs a closing time after its opening time.']);
            }
            $hours[$day] = isset($row['enabled']) ? ['open' => $row['open'], 'close' => $row['close']] : null;
        }
        DB::transaction(function () use ($data, $hours) {
            DB::table('scheduling_settings')->where('id', 1)->lockForUpdate()->first();
            if (!empty($data['provider_id'])) {
                DB::table('staff_schedules')->updateOrInsert(['user_id' => $data['provider_id']], ['hours' => json_encode($hours)]);
            } else {
                DB::table('scheduling_settings')->where('id', 1)->update(['hours' => json_encode($hours)]);
            }
        });
        return back()->with('status', 'Working hours saved. Existing appointments are unchanged; new reservations use these hours.');
    }

    public function assign(Request $request, Appointment $appointment)
    {
        $data = $request->validate(['provider_id' => ['required', 'integer', 'exists:users,id']]);
        DB::transaction(function () use ($data, $appointment) {
            DB::table('scheduling_settings')->where('id', 1)->lockForUpdate()->first();
            $booking = Appointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            abort_unless($booking->status === 'scheduled', 422);
            $duration = app(\App\Services\AppointmentScheduler::class)->reserve($booking->scheduled_at, $booking->booked_services, $data['provider_id'], $booking->user_id, $booking->id, $booking->duration_minutes);
            $booking->update(['provider_id' => $data['provider_id'], 'duration_minutes' => $duration]);
        });
        return back()->with('status', 'Treatment provider assigned.');
    }

    private function providers()
    {
        return User::where('is_active', true)->where('user_type', 'staff')->orderBy('name')->get();
    }
}
