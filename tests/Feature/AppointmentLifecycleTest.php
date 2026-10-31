<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AppointmentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-03 01:00:00', 'UTC'));
        $this->staff = User::factory()->create(['user_type' => 'staff']);
        $this->service = Service::create(['name' => 'Facial', 'price' => 100, 'category' => 'Skin', 'duration' => 60, 'session_count' => 3]);
        $this->actingAs($this->staff);
    }

    private function booking(array $data = []): Appointment
    {
        return Appointment::create(array_replace(['user_id' => User::factory()->create()->id, 'service_id' => $this->service->id,
            'treatment_course_id' => (string) Str::uuid(), 'session_number' => 1, 'status' => 'scheduled',
            'scheduled_at' => '2026-11-03 02:00:00', 'provider_id' => $this->staff->id, 'duration_minutes' => 60], $data));
    }

    private function move(Appointment $booking, array $data = [])
    {
        return $this->patch(route('studio.appointments.reschedule', $booking), array_replace([
            'scheduled_at' => '2026-11-03T13:00', 'provider_id' => $this->staff->id,
            'reason' => 'Client requested an afternoon appointment.', '_appointment_action' => 'reschedule',
        ], $data));
    }

    public function test_rescheduling_preserves_session_and_duration_and_records_before_and_after(): void
    {
        $booking = $this->booking();
        $this->service->update(['duration' => 15]);
        $this->move($booking)->assertSessionHasNoErrors();
        $saved = $booking->fresh();
        $this->assertSame('2026-11-03 05:00:00', $saved->scheduled_at->format('Y-m-d H:i:s'));
        $this->assertEquals(60, $saved->duration_minutes);
        $this->assertSame($booking->treatment_course_id, $saved->treatment_course_id);
        $this->assertSame(1, $saved->session_number);
        $history = $saved->changeHistory->first();
        $this->assertSame('rescheduled', $history->action);
        $this->assertSame($this->staff->id, $history->actor_id);
        $this->assertSame('2026-11-03 02:00:00', $history->before['scheduled_at']);
        $this->assertSame('2026-11-03 05:00:00', $history->after['scheduled_at']);
        $this->assertSame('Client requested an afternoon appointment.', $history->reason);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
        $this->get('/studio/appointments?appointment_id='.$booking->id)->assertOk()->assertSee('Session history')->assertSee($history->reason);
    }

    public function test_conflicts_and_closed_hours_roll_back_the_entire_change(): void
    {
        $booking = $this->booking();
        $this->booking(['scheduled_at' => '2026-11-03 05:00:00']);
        $this->move($booking)->assertSessionHasErrors('scheduled_at');
        DB::table('scheduling_settings')->where('id', 1)->update(['hours' => json_encode(array_fill(0, 7, ['open' => '09:00', 'close' => '12:00']))]);
        $this->move($booking, ['scheduled_at' => '2026-11-03T14:00'])->assertSessionHasErrors('scheduled_at');
        $this->assertSame('2026-11-03 02:00:00', $booking->fresh()->scheduled_at->format('Y-m-d H:i:s'));
        $this->assertSame(1, $booking->changeHistory()->count());
    }

    public function test_cancelling_releases_the_slot_and_requires_a_reason(): void
    {
        $booking = $this->booking();
        $this->patch(route('studio.appointments.cancel', $booking), [])->assertSessionHasErrors('reason');
        $this->patch(route('studio.appointments.cancel', $booking), ['reason' => 'Client unavailable.'])->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertSame('Client unavailable.', $booking->changeHistory()->first()->reason);
        $this->post(route('studio.appointments.store'), ['user_id' => $booking->user_id,
            'service_ids' => [$this->service->id], 'provider_id' => $this->staff->id, 'scheduled_at' => '2026-11-03T10:00'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
        $this->get('/studio/appointments?status=cancelled')->assertOk()->assertSee('Client unavailable.');
        $this->get('/dashboard?date=2026-11-03&view=day')->assertOk()->assertViewHas('appointments', fn ($rows) => $rows->contains(fn ($row) => $row->id === $booking->id && $row->status === 'cancelled'));
    }

    public function test_no_show_is_rejected_for_future_sessions_and_can_be_rescheduled_after_start(): void
    {
        $booking = $this->booking();
        $this->patch(route('studio.appointments.no-show', $booking), ['reason' => 'Did not arrive.'])->assertSessionHasErrors('reason');
        $this->travelTo(Carbon::parse('2026-11-03 03:00:00', 'UTC'));
        $this->patch(route('studio.appointments.no-show', $booking), ['reason' => 'Did not arrive.'])->assertSessionHasNoErrors();
        $this->assertSame('no_show', $booking->fresh()->status);
        $this->move($booking)->assertSessionHasNoErrors();
        $this->assertSame('scheduled', $booking->fresh()->status);
        $this->assertSame(3, $booking->changeHistory()->count());
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_cancelled_sessions_can_be_rescheduled_without_creating_another_session(): void
    {
        $booking = $this->booking(['status' => 'cancelled']);
        $this->move($booking)->assertSessionHasNoErrors();
        $this->assertSame('scheduled', $booking->fresh()->status);
        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_completed_sessions_cannot_be_changed_and_members_cannot_manage_sessions(): void
    {
        $booking = $this->booking(['status' => 'completed']);
        $this->move($booking)->assertUnprocessable();
        foreach (['cancel', 'no-show'] as $action) $this->patch(route('studio.appointments.'.$action, $booking), ['reason' => 'Test'])->assertUnprocessable();
        $this->actingAs(User::findOrFail($booking->user_id));
        $this->move($booking)->assertForbidden();
        foreach (['cancel', 'no-show'] as $action) $this->patch(route('studio.appointments.'.$action, $booking), ['reason' => 'Test'])->assertForbidden();
        $this->assertSame(1, $booking->changeHistory()->count());
    }

    public function test_admins_can_reschedule_to_staff_but_never_to_themselves(): void
    {
        $booking = $this->booking();
        $admin = User::factory()->create(['user_type' => 'administrator']);
        $this->actingAs($admin);
        $this->move($booking, ['provider_id' => $admin->id])->assertSessionHasErrors('provider_id');
        $this->move($booking)->assertSessionHasNoErrors();
        $this->assertSame($admin->id, $booking->changeHistory()->first()->actor_id);
        $this->assertSame($this->staff->id, $booking->fresh()->provider_id);
    }

    public function test_past_times_and_no_change_are_rejected_without_new_history(): void
    {
        $booking = $this->booking();
        $this->move($booking, ['scheduled_at' => '2026-11-03T08:00'])->assertSessionHasErrors('scheduled_at');
        $this->move($booking, ['scheduled_at' => '2026-11-03T10:00'])->assertSessionHasErrors('scheduled_at');
        $this->assertSame(1, $booking->changeHistory()->count());
    }
}
