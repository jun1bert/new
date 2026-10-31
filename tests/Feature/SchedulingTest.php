<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class SchedulingTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;
    private User $client;
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 11, 2)->startOfDay());
        $this->staff = User::factory()->create(['user_type' => 'staff']);
        $this->client = User::factory()->create();
        $this->service = Service::create(['name' => 'Facial', 'category' => 'Skin', 'price' => 500, 'duration' => 60, 'session_count' => 2, 'is_active' => true]);
        $this->actingAs($this->staff);
    }

    private function booking(array $attributes = []): Appointment
    {
        return Appointment::create(array_replace([
            'treatment_course_id' => (string) Str::uuid(), 'user_id' => $this->client->id,
            'service_id' => $this->service->id, 'scheduled_at' => \Illuminate\Support\Carbon::parse('2026-11-03 10:00', 'Asia/Manila')->timezone(config('app.timezone')),
            'status' => 'scheduled', 'provider_id' => $this->staff->id, 'duration_minutes' => 60, 'session_number' => 1,
        ], $attributes));
    }

    private function schedule(string $time, array $extra = [])
    {
        return $this->post(route('studio.appointments.store'), array_replace([
            'user_id' => $this->client->id, 'service_ids' => [$this->service->id],
            'scheduled_at' => '2026-11-03T'.$time, 'provider_id' => $this->staff->id,
        ], $extra));
    }

    public function test_calendar_access_and_philippine_dates(): void
    {
        $this->get('/studio/calendar?date=2026-11-03&view=day')->assertRedirect(route('dashboard', ['date' => '2026-11-03', 'view' => 'day']).'#calendar');
        $this->booking();
        $this->get('/dashboard?date=2026-11-03&view=day')->assertOk()->assertSee('10:00 AM')->assertSee($this->staff->name);
        $this->get('/dashboard?date=2026-11-03&view=week')->assertOk()->assertSee('Tue, Nov 3');
        $this->actingAs($this->client)->get('/dashboard')->assertOk()->assertDontSee('calendar-surface', false);
    }

    public function test_provider_overlaps_are_blocked_but_adjacent_slots_are_allowed(): void
    {
        $this->booking();
        $other = User::factory()->create();
        $this->schedule('10:30', ['user_id' => $other->id])->assertSessionHasErrors('scheduled_at');
        $this->schedule('09:30', ['user_id' => $other->id])->assertSessionHasErrors('scheduled_at');
        $this->schedule('11:00', ['user_id' => $other->id])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('appointments', 2);
    }

    public function test_different_providers_can_work_at_the_same_time_but_client_cannot_overlap(): void
    {
        $this->booking();
        $other = User::factory()->create(['user_type' => 'staff']);
        $this->schedule('10:00', ['provider_id' => $other->id])->assertSessionHasErrors('scheduled_at');
        $client = User::factory()->create();
        $this->schedule('10:00', ['provider_id' => $other->id, 'user_id' => $client->id])->assertSessionHasNoErrors();
    }

    public function test_pending_and_declined_requests_do_not_reserve_time(): void
    {
        $this->booking(['status' => 'pending']);
        $this->booking(['status' => 'declined']);
        $this->schedule('10:00')->assertSessionHasNoErrors();
    }

    public function test_approval_checks_conflicts_and_preserves_pending_status_on_failure(): void
    {
        $this->booking();
        $pending = $this->booking(['status' => 'pending']);
        $this->patch(route('studio.appointments.accept', $pending), ['provider_id' => $this->staff->id])->assertSessionHasErrors('scheduled_at');
        $this->assertSame('pending', $pending->fresh()->status);
    }

    public function test_staff_can_choose_another_time_while_approving_a_conflicting_request(): void
    {
        $this->booking();
        $pending = $this->booking(['status' => 'pending', 'user_id' => User::factory()->create()->id]);
        $this->from('/studio/appointments?status=pending')->patch(route('studio.appointments.accept', $pending), ['provider_id' => $this->staff->id])
            ->assertSessionHasErrors('scheduled_at')->assertSessionHasInput('_approving_appointment_id', (string) $pending->id);
        $this->get('/studio/appointments?status=pending')->assertOk()->assertSee('approve-request-title-'.$pending->id)
            ->assertSee('Choose a different date or time')->assertSee('changeTime: true', false)->assertSee('scheduleOpen: false', false);
        $this->from('/studio/appointments?status=pending')->patch(route('studio.appointments.accept', $pending), [
            'provider_id' => $this->staff->id, 'scheduled_at' => '2026-11-03T11:00', 'reason' => 'Client agreed to the next available slot.',
        ])->assertSessionHasNoErrors();
        $this->assertSame('scheduled', $pending->fresh()->status);
        $this->assertSame('11:00', $pending->fresh()->scheduled_at->timezone('Asia/Manila')->format('H:i'));
        $history = $pending->changeHistory()->first();
        $this->assertSame('Client agreed to the next available slot.', $history->reason);
        $this->assertNotSame($history->before['scheduled_at'], $history->after['scheduled_at']);
        $this->assertDatabaseCount('appointments', 2);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_a_past_pending_request_can_be_approved_at_a_new_future_time(): void
    {
        $pending = $this->booking(['status' => 'pending', 'scheduled_at' => now()->subDay()]);
        $this->patch(route('studio.appointments.accept', $pending), ['provider_id' => $this->staff->id])->assertSessionHasErrors('scheduled_at');
        $this->patch(route('studio.appointments.accept', $pending), ['provider_id' => $this->staff->id, 'scheduled_at' => '2026-11-03T11:00'])->assertSessionHasNoErrors();
        $this->assertSame('scheduled', $pending->fresh()->status);
    }

    public function test_multi_service_duration_is_reserved_and_snapshotted(): void
    {
        $second = Service::create(['name' => 'Massage', 'category' => 'Body', 'price' => 300, 'duration' => 30, 'is_active' => true]);
        $this->schedule('10:00', ['service_ids' => [$this->service->id, $second->id]])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('appointments', ['provider_id' => $this->staff->id, 'duration_minutes' => 90]);
        $this->service->update(['duration' => 15]);
        $this->schedule('11:15')->assertSessionHasErrors('scheduled_at');
        $this->schedule('11:30')->assertSessionHasNoErrors();
    }

    public function test_business_hours_and_staff_shifts_require_the_entire_duration_to_fit(): void
    {
        $hours = array_fill(0, 7, ['open' => '09:00', 'close' => '18:00']);
        $hours[3] = null;
        DB::table('scheduling_settings')->where('id', 1)->update(['hours' => json_encode($hours)]);
        DB::table('staff_schedules')->insert(['user_id' => $this->staff->id, 'hours' => json_encode(array_fill(0, 7, ['open' => '10:00', 'close' => '16:00']))]);
        $this->schedule('09:00')->assertSessionHasErrors('scheduled_at');
        $this->schedule('15:30')->assertSessionHasErrors('scheduled_at');
        $this->schedule('15:00')->assertSessionHasNoErrors();
        $this->schedule('10:00', ['scheduled_at' => '2026-11-04T10:00'])->assertSessionHasErrors('scheduled_at');
    }

    public function test_only_administrators_can_save_valid_working_hours(): void
    {
        $hours = array_fill(0, 7, ['enabled' => '1', 'open' => '09:00', 'close' => '18:00']);
        $this->put(route('admin.scheduling.hours'), ['hours' => $hours])->assertForbidden();
        $admin = User::factory()->create(['user_type' => 'administrator']);
        $this->actingAs($admin)->put(route('admin.scheduling.hours'), ['hours' => $hours])->assertSessionHasNoErrors();
        $this->get('/dashboard')->assertOk()->assertSee('09:00 - 18:00');
        $hours[1]['close'] = '08:00';
        $this->put(route('admin.scheduling.hours'), ['hours' => $hours])->assertSessionHasErrors('hours');
    }

    public function test_public_requests_respect_business_hours_without_creating_clients_on_failure(): void
    {
        DB::table('scheduling_settings')->where('id', 1)->update(['hours' => json_encode(array_fill(0, 7, ['open' => '09:00', 'close' => '18:00']))]);
        $this->post(route('appointments.request'), [
            'full_name' => 'Guest', 'contact_number' => '09171234567', 'date' => '2026-11-03', 'time' => '17:30',
            'participants' => [['name' => 'Guest', 'service_ids' => [$this->service->id]]],
        ])->assertSessionHasErrors('scheduled_at');
        $this->assertDatabaseCount('appointments', 0);
        $this->assertDatabaseCount('users', 2);
    }

    public function test_follow_up_sessions_use_the_same_conflict_checks(): void
    {
        $completed = $this->booking(['status' => 'completed']);
        $this->booking(['user_id' => User::factory()->create()->id]);
        $this->post(route('studio.appointments.next-session', $completed), ['scheduled_at' => '2026-11-03T10:30'])->assertSessionHasErrors('scheduled_at');
        $this->post(route('studio.appointments.next-session', $completed), ['scheduled_at' => '2026-11-03T11:00'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('appointments', ['treatment_course_id' => $completed->treatment_course_id, 'session_number' => 2, 'provider_id' => $this->staff->id]);
    }

    public function test_provider_assignment_preserves_reserved_duration_and_booking_operator(): void
    {
        $booking = $this->booking(['provider_id' => null, 'staff_id' => $this->staff->id]);
        $this->get('/dashboard?date=2026-11-03')->assertOk()->assertDontSee(route('studio.appointments.provider', $booking), false)->assertDontSee('hours-target', false);
        $this->get('/studio/appointments?appointment_id='.$booking->id)->assertOk()->assertSee('Assign treatment provider')->assertSee(route('studio.appointments.provider', $booking), false);
        $other = User::factory()->create(['user_type' => 'staff']);
        $this->service->update(['duration' => 15]);
        $this->patch(route('studio.appointments.provider', $booking), ['provider_id' => $other->id])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('appointments', ['id' => $booking->id, 'provider_id' => $other->id, 'staff_id' => $this->staff->id, 'duration_minutes' => 60]);
        $conflict = $this->booking(['user_id' => User::factory()->create()->id]);
        $this->patch(route('studio.appointments.provider', $conflict), ['provider_id' => $other->id])->assertSessionHasErrors('scheduled_at');
        $this->assertSame($this->staff->id, $conflict->fresh()->provider_id);
    }

    public function test_members_and_inactive_staff_cannot_be_assigned_as_providers(): void
    {
        $this->schedule('10:00', ['provider_id' => $this->client->id])->assertSessionHasErrors('provider_id');
        $inactive = User::factory()->create(['user_type' => 'staff', 'is_active' => false]);
        $this->schedule('10:00', ['provider_id' => $inactive->id])->assertSessionHasErrors('provider_id');
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_administrators_manage_bookings_but_cannot_be_treatment_providers(): void
    {
        $admin = User::factory()->create(['user_type' => 'administrator']);
        $this->actingAs($admin);
        $this->get('/studio/appointments')->assertOk()->assertViewHas('providers', fn ($providers) => !$providers->contains('id', $admin->id) && $providers->contains('id', $this->staff->id));
        $this->schedule('10:00', ['provider_id' => $admin->id])->assertSessionHasErrors('provider_id');
        $this->post(route('studio.appointments.store'), ['user_id' => $this->client->id, 'service_ids' => [$this->service->id], 'scheduled_at' => '2026-11-03T10:00'])->assertSessionHasErrors('provider_id');
        $pending = $this->booking(['status' => 'pending']);
        $this->patch(route('studio.appointments.accept', $pending), ['provider_id' => $admin->id])->assertSessionHasErrors('provider_id');
        $this->assertSame('pending', $pending->fresh()->status);
        $this->patch(route('studio.appointments.accept', $pending), ['provider_id' => $this->staff->id])->assertSessionHasNoErrors();
        $this->assertSame($admin->id, $pending->fresh()->staff_id);
        $this->assertSame($this->staff->id, $pending->fresh()->provider_id);
        $this->patch(route('studio.appointments.provider', $pending), ['provider_id' => $admin->id])->assertSessionHasErrors('provider_id');
        $hours = array_fill(0, 7, ['enabled' => '1', 'open' => '09:00', 'close' => '18:00']);
        $this->put(route('admin.scheduling.hours'), ['provider_id' => $admin->id, 'hours' => $hours])->assertSessionHasErrors('provider_id');
        $this->staff->update(['user_type' => 'administrator']);
        $this->assertNull($pending->fresh()->provider_id);
    }

    public function test_calendar_filters_providers_and_offers_legacy_assignments(): void
    {
        $other = User::factory()->create(['user_type' => 'staff']);
        $this->booking();
        $this->booking(['provider_id' => null]);
        $this->get('/dashboard?date=2026-11-03&view=day')->assertOk()->assertSee('Provider unassigned');
        $this->get('/dashboard?date=2026-11-03&view=day&provider_id='.$other->id)->assertOk()->assertViewHas('appointments', fn ($bookings) => $bookings->isEmpty());
        $booking = Appointment::first();
        $this->get('/studio/appointments?appointment_id='.$booking->id)->assertOk()->assertViewHas('appointments', fn ($bookings) => $bookings->count() === 1 && $bookings->first()->id === $booking->id);
    }

    public function test_available_times_fit_the_provider_shift_and_exclude_overlapping_bookings(): void
    {
        DB::table('scheduling_settings')->where('id', 1)->update(['hours' => json_encode(array_fill(0, 7, ['open' => '09:00', 'close' => '18:00']))]);
        DB::table('staff_schedules')->insert(['user_id' => $this->staff->id, 'hours' => json_encode(array_fill(0, 7, ['open' => '10:00', 'close' => '16:00']))]);
        $this->booking(['scheduled_at' => \Illuminate\Support\Carbon::parse('2026-11-03 10:30', 'Asia/Manila')->timezone(config('app.timezone'))]);
        $response = $this->getJson(route('studio.appointments.availability', ['date' => '2026-11-03', 'provider_id' => $this->staff->id, 'service_ids' => [$this->service->id]]))
            ->assertOk()->assertJsonPath('duration', 60)->assertJsonPath('hoursConfigured', true);
        $times = collect($response->json('slots'))->pluck('value');
        $this->assertFalse($times->contains('09:00'));
        $this->assertFalse($times->contains('10:00'));
        $this->assertFalse($times->contains('11:15'));
        $this->assertTrue($times->contains('11:30'));
        $this->assertTrue($times->contains('15:00'));
        $this->assertFalse($times->contains('15:15'));
        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_availability_explains_closed_days_and_provider_days_off(): void
    {
        $hours = array_fill(0, 7, ['open' => '09:00', 'close' => '18:00']);
        $hours[2] = null;
        DB::table('scheduling_settings')->where('id', 1)->update(['hours' => json_encode($hours)]);
        $url = route('studio.appointments.availability', ['date' => '2026-11-03', 'provider_id' => $this->staff->id, 'service_ids' => [$this->service->id]]);
        $this->getJson($url)->assertOk()->assertJsonCount(0, 'slots')->assertJsonPath('message', 'The studio is closed on Tuesday, Nov 3. Choose another date. Administrators can update Working hours in Sessions.');
        DB::table('scheduling_settings')->where('id', 1)->update(['hours' => json_encode(array_fill(0, 7, ['open' => '09:00', 'close' => '18:00']))]);
        DB::table('staff_schedules')->insert(['user_id' => $this->staff->id, 'hours' => json_encode($hours)]);
        $this->getJson($url)->assertOk()->assertJsonCount(0, 'slots')->assertJsonPath('message', 'This staff provider is off on Tuesday, Nov 3. Choose another provider or date.');
    }

    public function test_rescheduling_slots_ignore_the_same_appointment_and_use_its_reserved_duration(): void
    {
        $booking = $this->booking(['duration_minutes' => 90]);
        $this->service->update(['duration' => 15]);
        $this->getJson(route('studio.appointments.availability', ['date' => '2026-11-03', 'provider_id' => $this->staff->id, 'appointment_id' => $booking->id, 'mode' => 'reschedule']))
            ->assertOk()->assertJsonPath('duration', 90)->assertJsonFragment(['value' => '10:00', 'label' => '10:00 AM - 11:30 AM']);
    }

    public function test_follow_up_times_only_include_services_with_remaining_sessions(): void
    {
        $booking = $this->booking(['status' => 'completed']);
        $finished = Service::create(['name' => 'Single visit', 'category' => 'Skin', 'price' => 100, 'duration' => 120, 'session_count' => 1]);
        $booking->services()->sync([$this->service->id => ['session_count' => 2], $finished->id => ['session_count' => 1]]);
        $this->getJson(route('studio.appointments.availability', ['date' => '2026-11-04', 'provider_id' => $this->staff->id, 'appointment_id' => $booking->id, 'mode' => 'next']))
            ->assertOk()->assertJsonPath('duration', 60);
    }

    public function test_availability_rejects_non_staff_providers_and_is_not_accessible_to_members(): void
    {
        $admin = User::factory()->create(['user_type' => 'administrator']);
        $this->getJson(route('studio.appointments.availability', ['date' => '2026-11-03', 'provider_id' => $admin->id, 'service_ids' => [$this->service->id]]))->assertUnprocessable()->assertJsonValidationErrors('provider_id');
        $this->actingAs($this->client)->getJson(route('studio.appointments.availability', ['date' => '2026-11-03', 'provider_id' => $this->staff->id, 'service_ids' => [$this->service->id]]))->assertForbidden();
    }
}
