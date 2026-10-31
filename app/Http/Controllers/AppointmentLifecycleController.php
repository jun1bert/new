<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Services\AppointmentScheduler;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentLifecycleController extends Controller
{
    public function reschedule(Request $request, Appointment $appointment)
    {
        $request->merge(['_changing_appointment_id' => (string) $appointment->id]);
        $data = $request->validate([
            'scheduled_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'provider_id' => ['required', 'integer', 'exists:users,id'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);
        $start = Carbon::createFromFormat('Y-m-d\TH:i', $data['scheduled_at'], 'Asia/Manila')->startOfMinute()->timezone(config('app.timezone'));
        if ($start->lte(now())) throw ValidationException::withMessages(['scheduled_at' => 'Choose a future appointment time in Philippine time.']);
        DB::transaction(function () use ($appointment, $data, $start) {
            DB::table('scheduling_settings')->where('id', 1)->lockForUpdate()->first();
            $booking = Appointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($booking->status, ['scheduled', 'cancelled', 'no_show'], true), 422);
            $duration = app(AppointmentScheduler::class)->reserve($start, $booking->booked_services, $data['provider_id'], $booking->user_id, $booking->id, $booking->duration_minutes);
            if ($booking->status === 'scheduled' && $booking->scheduled_at->eq($start) && $booking->provider_id === (int) $data['provider_id']) {
                throw ValidationException::withMessages(['scheduled_at' => 'Choose a different time or treatment provider.']);
            }
            $booking->historyReason = $data['reason'];
            $booking->update(['scheduled_at' => $start, 'provider_id' => $data['provider_id'], 'duration_minutes' => $duration, 'status' => 'scheduled']);
        });
        return back()->with('status', 'Session rescheduled. The previous slot has been released.');
    }

    public function cancel(Request $request, Appointment $appointment)
    {
        return $this->closeSession($request, $appointment, 'cancelled');
    }

    public function noShow(Request $request, Appointment $appointment)
    {
        return $this->closeSession($request, $appointment, 'no_show');
    }

    private function closeSession(Request $request, Appointment $appointment, string $status)
    {
        $request->merge(['_changing_appointment_id' => (string) $appointment->id]);
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        DB::transaction(function () use ($appointment, $data, $status) {
            DB::table('scheduling_settings')->where('id', 1)->lockForUpdate()->first();
            $booking = Appointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            abort_unless($booking->status === 'scheduled' || ($status === 'cancelled' && $booking->status === 'pending'), 422);
            if ($status === 'no_show' && $booking->scheduled_at->gt(now())) {
                throw ValidationException::withMessages(['reason' => 'A future appointment cannot be marked as a no-show.']);
            }
            $booking->historyReason = $data['reason'];
            $booking->update(['status' => $status]);
        });
        return back()->with('status', $status === 'cancelled' ? 'Session cancelled. Its reserved slot has been released.' : 'Session marked as a no-show.');
    }
}
