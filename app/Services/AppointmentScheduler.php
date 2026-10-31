<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentScheduler
{
    public function availableTimes(Carbon $day, Collection $services, int $providerId, ?int $clientId, ?int $ignore = null, ?int $reservedMinutes = null): array
    {
        $duration = $reservedMinutes ?? $services->sum(fn ($service) => max(1, (int) ($service->duration ?: 60)));
        $studioHours = DB::table('scheduling_settings')->where('id', 1)->value('hours');
        $providerHours = DB::table('staff_schedules')->where('user_id', $providerId)->value('hours');
        if ($studioHours !== null && !(json_decode($studioHours, true)[$day->dayOfWeek] ?? null)) {
            return ['slots' => [], 'duration' => $duration, 'hoursConfigured' => true,
                'message' => 'The studio is closed on '.$day->format('l, M j').'. Choose another date. Administrators can update Working hours in Sessions.'];
        }
        if ($providerHours !== null && !(json_decode($providerHours, true)[$day->dayOfWeek] ?? null)) {
            return ['slots' => [], 'duration' => $duration, 'hoursConfigured' => $studioHours !== null,
                'message' => 'This staff provider is off on '.$day->format('l, M j').'. Choose another provider or date.'];
        }
        $bookings = Appointment::with(['service', 'services'])->whereIn('status', ['scheduled', 'completed'])
            ->when($ignore, fn ($q) => $q->where('id', '!=', $ignore))
            ->where(fn ($q) => $q->where('provider_id', $providerId)->when($clientId, fn ($q) => $q->orWhere('user_id', $clientId)))
            ->where('scheduled_at', '<', $day->copy()->endOfDay()->addMinutes($duration)->timezone(config('app.timezone')))->get();
        $slots = [];
        for ($minute = 0; $minute < 1440; $minute += 15) {
            $start = $day->copy()->startOfDay()->addMinutes($minute)->timezone(config('app.timezone'));
            if ($start->lte(now())) continue;
            $end = $start->copy()->addMinutes($duration);
            try {
                $this->checkHours($start, $end, $studioHours, 'studio');
                $this->checkHours($start, $end, $providerHours, 'provider');
            } catch (ValidationException) { continue; }
            if ($bookings->contains(function ($booking) use ($start, $end) {
                $minutes = $booking->duration_minutes ?? $booking->booked_services->sum(fn ($service) => max(1, (int) ($service->duration ?: 60)));
                return $booking->scheduled_at->lt($end) && $booking->scheduled_at->copy()->addMinutes($minutes)->gt($start);
            })) continue;
            $local = $start->copy()->timezone('Asia/Manila');
            $slots[] = ['value' => $local->format('H:i'), 'label' => $local->format('g:i A').' - '.$end->copy()->timezone('Asia/Manila')->format('g:i A')];
        }
        return ['slots' => $slots, 'duration' => $duration, 'hoursConfigured' => $studioHours !== null];
    }

    public function checkRequest(Carbon $start, Collection $services): void
    {
        $settings = DB::table('scheduling_settings')->where('id', 1)->lockForUpdate()->first();
        $minutes = $services->sum(fn ($service) => max(1, (int) ($service->duration ?: 60)));
        $this->checkHours($start, $start->copy()->addMinutes($minutes), $settings->hours, 'studio');
    }

    // Called inside the booking transaction. One shared lock serializes reservations
    // and availability changes, including reservations for previously empty slots.
    public function reserve(Carbon $start, Collection $services, int $providerId, int $clientId, ?int $ignore = null, ?int $reservedMinutes = null): int
    {
        $settings = DB::table('scheduling_settings')->where('id', 1)->lockForUpdate()->first();
        $provider = User::whereKey($providerId)->where('is_active', true)->where('user_type', 'staff')->first();
        if (!$provider) {
            throw ValidationException::withMessages(['provider_id' => 'Choose an active staff member as treatment provider.']);
        }
        $duration = $reservedMinutes ?? $services->sum(fn ($service) => max(1, (int) ($service->duration ?: 60)));
        $end = $start->copy()->addMinutes($duration);
        $this->checkHours($start, $end, $settings->hours, 'studio');
        $shift = DB::table('staff_schedules')->where('user_id', $providerId)->value('hours');
        $this->checkHours($start, $end, $shift, 'provider');
        $bookings = Appointment::with(['services', 'service'])
            ->whereIn('status', ['scheduled', 'completed'])
            ->when($ignore, fn ($q) => $q->where('id', '!=', $ignore))
            ->where(fn ($q) => $q->where('provider_id', $providerId)->orWhere('user_id', $clientId))
            ->where('scheduled_at', '<', $end)->get();
        foreach ($bookings as $booking) {
            $minutes = $booking->duration_minutes ?? $booking->booked_services->sum(fn ($s) => max(1, (int) ($s->duration ?: 60)));
            if ($booking->scheduled_at->copy()->addMinutes($minutes)->gt($start)) {
                throw ValidationException::withMessages(['scheduled_at' => 'This time overlaps an existing appointment for the provider or client. Choose another time or provider.']);
            }
        }
        return $duration;
    }

    private function checkHours(Carbon $start, Carbon $end, ?string $json, string $label): void
    {
        if ($json === null) return;
        $local = $start->copy()->timezone('Asia/Manila');
        $finish = $end->copy()->timezone('Asia/Manila');
        $hours = json_decode($json, true)[$local->dayOfWeek] ?? null;
        if (!$hours || !$local->isSameDay($finish) || $local->lt($local->copy()->setTimeFromTimeString($hours['open'])) || $finish->gt($local->copy()->setTimeFromTimeString($hours['close']))) {
            throw ValidationException::withMessages(['scheduled_at' => "The entire appointment must fit within the {$label}'s working hours (Philippine time)."]);
        }
    }
}
