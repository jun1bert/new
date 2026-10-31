<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ConsentForm;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiServiceAppointmentTest extends TestCase
{
    use RefreshDatabase;

    private function service(string $name, int $sessions = 1): Service
    {
        return Service::create(['name' => $name, 'category' => 'Skin', 'price' => 100.25,
            'duration' => 30, 'session_count' => $sessions, 'is_active' => true]);
    }

    private function schedule(array $services): Appointment
    {
        $this->actingAs(User::factory()->create(['user_type' => 'staff']));
        $this->post(route('studio.appointments.store'), [
            'walk_in_name' => 'Walk-in client', 'service_ids' => array_column($services, 'id'),
            'scheduled_at' => now()->addDay()->toDateTimeString(),
        ])->assertSessionHasNoErrors()->assertRedirect();
        return Appointment::latest('id')->firstOrFail();
    }

    public function test_staff_books_multiple_services_in_one_appointment_and_collects_one_discounted_payment(): void
    {
        $facial = $this->service('Facial');
        $laser = $this->service('Laser');
        $appointment = $this->schedule([$facial, $laser]);
        $this->assertDatabaseCount('appointments', 1);
        $this->assertCount(2, $appointment->services);
        $this->assertSame(60, $appointment->total_duration);
        $this->get('/studio/appointments?search=Laser')->assertOk()->assertSee('Facial, Laser');
        $this->patch(route('studio.appointments.complete', $appointment), [
            'amount' => 0, 'method' => 'cash', 'discount_amount' => 20,
        ])->assertSessionHasNoErrors();
        $payment = $appointment->fresh()->payment;
        $this->assertSame('180.50', $payment->amount);
        $this->assertDatabaseCount('payments', 1);
        $this->assertCount(2, $payment->items);
        $this->get(route('studio.payments.receipt', $payment))->assertOk()
            ->assertSee('Facial')->assertSee('Laser')->assertSee('PHP 200.50')
            ->assertSee('PHP 180.50')->assertDontSee('recorded payment differs');
        $this->actingAs($appointment->client)->get(route('member.appointments.show', $appointment))
            ->assertOk()->assertSee('Facial, Laser')->assertSee('60 minutes');
        $this->get(route('member.appointments.receipt', $appointment))->assertOk()->assertSee('Laser');
    }

    public function test_public_booking_creates_one_appointment_per_participant_with_their_selected_services(): void
    {
        $facial = $this->service('Facial');
        $laser = $this->service('Laser', 3);
        $this->post(route('appointments.request'), [
            'full_name' => 'Primary client', 'contact_number' => '09171234567',
            'date' => now()->addDay()->toDateString(), 'time' => '10:00',
            'participants' => [
                ['name' => 'Primary client', 'service_ids' => [$facial->id, $laser->id]],
                ['name' => 'Guest client', 'service_ids' => [$facial->id]],
            ],
        ])->assertSessionHasNoErrors()->assertRedirect();
        $appointments = Appointment::orderBy('id')->get();
        $this->assertCount(2, $appointments);
        $this->assertCount(2, $appointments[0]->services);
        $this->assertCount(1, $appointments[1]->services);
        $this->assertNotSame($appointments[0]->user_id, $appointments[1]->user_id);
        $this->assertSame('pending', $appointments[0]->status);
    }

    public function test_follow_up_sessions_include_only_unfinished_services_and_keep_original_session_counts(): void
    {
        $single = $this->service('Single facial');
        $course = $this->service('Laser course', 3);
        $appointment = $this->schedule([$single, $course]);
        $course->update(['session_count' => 1]);
        for ($session = 1; $session <= 3; $session++) {
            $this->patch(route('studio.appointments.complete', $appointment), ['amount' => 0, 'method' => 'cash'])
                ->assertSessionHasNoErrors();
            $this->assertSame($session === 1 ? '200.50' : '100.25', $appointment->fresh()->payment->amount);
            $payload = ['scheduled_at' => now()->addDays($session + 1)->toDateTimeString()];
            if ($session === 3) {
                $this->post(route('studio.appointments.next-session', $appointment), $payload)->assertStatus(422);
                break;
            }
            $this->post(route('studio.appointments.next-session', $appointment), $payload)->assertSessionHasNoErrors()->assertRedirect();
            $this->post(route('studio.appointments.next-session', $appointment), $payload)->assertSessionHasNoErrors()->assertRedirect();
            $next = Appointment::latest('id')->firstOrFail();
            $this->assertSame($appointment->treatment_course_id, $next->treatment_course_id);
            $this->assertEquals($session + 1, $next->session_number);
            $this->assertEquals([$course->id], $next->services->pluck('id')->all());
            $this->assertSame(3, $next->session_count);
            $appointment = $next;
        }
        $this->assertDatabaseCount('appointments', 3);
        $this->assertDatabaseCount('payments', 3);
    }

    public function test_duplicates_inactive_and_missing_services_are_rejected_without_partial_bookings(): void
    {
        $service = $this->service('Facial');
        $staff = User::factory()->create(['user_type' => 'staff']);
        $this->actingAs($staff);
        foreach ([[], [$service->id, $service->id], [99999]] as $ids) {
            $this->post(route('studio.appointments.store'), ['walk_in_name' => 'Client', 'service_ids' => $ids,
                'scheduled_at' => now()->addDay()->toDateTimeString()])->assertSessionHasErrors();
        }
        $service->update(['is_active' => false]);
        $this->post(route('studio.appointments.store'), ['walk_in_name' => 'Client', 'service_ids' => [$service->id],
            'scheduled_at' => now()->addDay()->toDateTimeString()])->assertSessionHasErrors('service_ids');
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_combined_consent_covers_all_required_services_and_detects_changed_wording(): void
    {
        $facial = $this->service('Facial');
        $laser = $this->service('Laser');
        foreach ([$facial, $laser] as $service) {
            $service->update(['requires_consent' => true, 'consent_content' => $service->name.' original wording']);
        }
        $appointment = $this->schedule([$facial, $laser]);
        $this->actingAs($appointment->client);
        $this->get(route('member.consents.create', $appointment))->assertOk()
            ->assertSee('Facial original wording')->assertSee('Laser original wording');
        $combined = $appointment->consent_service;
        $data = ['acknowledged' => 1, 'signature' => json_encode([[[20, 60], [80, 100], [130, 40], [200, 90]]]),
            'content_hash' => hash('sha256', $combined->name."\n".$combined->consent_content)];
        $laser->update(['consent_content' => 'Laser updated wording']);
        $this->post(route('member.consents.store', $appointment), $data)->assertSessionHasErrors('content_hash');
        $appointment->unsetRelation('services');
        $combined = $appointment->consent_service;
        $data['content_hash'] = hash('sha256', $combined->name."\n".$combined->consent_content);
        $this->post(route('member.consents.store', $appointment), $data)->assertSessionHasNoErrors()->assertRedirect();
        $consent = ConsentForm::firstOrFail();
        $this->assertSame('Facial, Laser', $consent->service_name);
        $this->assertStringContainsString('Facial original wording', $consent->content_snapshot);
        $this->assertStringContainsString('Laser updated wording', $consent->content_snapshot);
    }

    public function test_tablet_includes_consent_for_an_additional_service_when_primary_service_needs_none(): void
    {
        $facial = $this->service('Basic facial');
        $laser = $this->service('Laser');
        $laser->update(['requires_consent' => true, 'consent_content' => 'Laser treatment consent wording']);
        $appointment = $this->schedule([$facial, $laser]);
        $this->post(route('studio.consents.start', $appointment))->assertRedirect(route('studio.consents.tablet'));
        $this->get(route('studio.consents.tablet'))->assertOk()->assertSee('Laser treatment consent wording');
        $combined = $appointment->consent_service;
        $this->post(route('studio.consents.sign'), [
            'acknowledged' => 1, 'signature' => json_encode([[[20, 60], [80, 100], [130, 40], [200, 90]]]),
            'content_hash' => hash('sha256', $combined->name."\n".$combined->consent_content),
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('consent_forms', ['appointment_id' => $appointment->id, 'service_id' => $laser->id]);
    }
}
