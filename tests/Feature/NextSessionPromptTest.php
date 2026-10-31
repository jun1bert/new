<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class NextSessionPromptTest extends TestCase
{
    use RefreshDatabase;

    private function makeSession(int $count): Appointment
    {
        $this->travelTo(Carbon::parse('2026-11-03 03:00:00', 'UTC'));
        $staff = User::factory()->create(['user_type' => 'staff']);
        $this->actingAs($staff);
        $service = Service::create(['name' => 'Skin course', 'category' => 'Skin', 'price' => 100, 'duration' => 30, 'session_count' => $count]);
        return Appointment::create(['treatment_course_id' => (string) Str::uuid(), 'user_id' => User::factory()->create()->id,
            'service_id' => $service->id, 'session_number' => 1, 'provider_id' => $staff->id, 'duration_minutes' => 30,
            'scheduled_at' => now()->subHour(), 'status' => 'scheduled']);
    }

    public function test_completion_prompts_for_the_next_session_even_when_the_completed_record_is_filtered_out(): void
    {
        $booking = $this->makeSession(2);
        $this->from('/studio/appointments?status=scheduled')->patch(route('studio.appointments.complete', $booking), ['amount' => 100, 'method' => 'cash'])
            ->assertSessionHasNoErrors()->assertSessionHas('schedule_next_appointment_id', $booking->id)->assertRedirect('/studio/appointments?status=scheduled');
        $this->get('/studio/appointments?status=scheduled')->assertOk()->assertSee('schedule-next-session-title')
            ->assertSee('Schedule session 2')->assertSee($booking->client->name)->assertSee('Schedule later')
            ->assertDontSee('feedback-success-title')->assertViewHas('appointments', fn ($rows) => $rows->isEmpty())
            ->assertViewHas('nextSessionSource', fn ($source) => $source->id === $booking->id);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('appointments', 1);
        $this->from('/studio/appointments?status=scheduled')->post(route('studio.appointments.next-session', $booking), ['scheduled_at' => '2026-11-04T10:00', 'provider_id' => $booking->provider_id])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('appointments', ['treatment_course_id' => $booking->treatment_course_id, 'session_number' => 2, 'status' => 'scheduled']);
        $this->assertDatabaseCount('payments', 1);
        $this->get('/studio/appointments?status=scheduled')->assertOk()->assertViewHas('nextSessionSource', null);
    }

    public function test_invalid_follow_up_reopens_the_prompt_without_repeating_payment(): void
    {
        $booking = $this->makeSession(3);
        $this->from('/studio/appointments?status=scheduled')->patch(route('studio.appointments.complete', $booking), ['amount' => 100, 'method' => 'cash'])->assertSessionHasNoErrors();
        $this->get('/studio/appointments?status=scheduled')->assertOk();
        $this->from('/studio/appointments?status=scheduled')->post(route('studio.appointments.next-session', $booking), ['scheduled_at' => '2026-11-01T10:00', 'provider_id' => $booking->provider_id])
            ->assertSessionHasErrors('scheduled_at')->assertSessionHasInput('_next_session_appointment_id', (string) $booking->id);
        $this->get('/studio/appointments?status=scheduled')->assertOk()->assertSee('schedule-next-session-title')
            ->assertSee('value="2026-11-01T10:00"', false)->assertSee('scheduleOpen: false', false)
            ->assertViewHas('nextSessionSource', fn ($source) => $source->id === $booking->id);
        $this->assertSame('completed', $booking->fresh()->status);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_last_session_completion_does_not_prompt_for_another_session(): void
    {
        $booking = $this->makeSession(1);
        $this->from('/studio/appointments')->patch(route('studio.appointments.complete', $booking), ['amount' => 100, 'method' => 'cash'])
            ->assertSessionHasNoErrors()->assertSessionMissing('schedule_next_appointment_id');
        $this->get('/studio/appointments')->assertOk()->assertDontSee('schedule-next-session-title')->assertSee('feedback-success-title');
    }

    public function test_first_session_does_not_offer_to_schedule_an_existing_follow_up(): void
    {
        $first = $this->makeSession(2);
        $first->update(['status' => 'completed']);
        foreach (['scheduled', 'completed', 'cancelled', 'no_show'] as $status) {
            $next = Appointment::firstOrCreate(['treatment_course_id' => $first->treatment_course_id, 'session_number' => 2], [
                'user_id' => $first->user_id, 'service_id' => $first->service_id, 'provider_id' => $first->provider_id,
                'scheduled_at' => now()->addDay(), 'status' => $status,
            ]);
            $next->update(['status' => $status]);
            $this->get('/studio/appointments?appointment_id='.$first->id)->assertOk()
                ->assertDontSee('Schedule session 2')->assertSee('View session 2')->assertSee('Session 2 is already '.str_replace('_', ' ', $status));
            $this->get('/studio/appointments?appointment_id='.$first->id.'&status=completed')->assertOk()->assertDontSee('Schedule session 2');
        }
        $this->assertDatabaseCount('appointments', 2);
    }

    public function test_completion_does_not_auto_prompt_when_a_follow_up_already_exists(): void
    {
        $first = $this->makeSession(2);
        Appointment::create(['treatment_course_id' => $first->treatment_course_id, 'session_number' => 2,
            'user_id' => $first->user_id, 'service_id' => $first->service_id, 'provider_id' => $first->provider_id,
            'scheduled_at' => now()->subDay(), 'status' => 'completed']);
        $this->from('/studio/appointments')->patch(route('studio.appointments.complete', $first), ['amount' => 100, 'method' => 'cash'])
            ->assertSessionHasNoErrors()->assertSessionMissing('schedule_next_appointment_id');
        $this->get('/studio/appointments')->assertOk()->assertDontSee('schedule-next-session-title')->assertDontSee('Schedule session 2');
    }
}
