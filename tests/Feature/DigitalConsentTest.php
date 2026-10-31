<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ConsentForm;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DigitalConsentTest extends TestCase
{
    use RefreshDatabase;

    private function booking(): Appointment
    {
        $service = Service::create(['name' => 'Facial', 'category' => 'Skin', 'price' => 500, 'session_count' => 1,
            'requires_consent' => true, 'consent_content' => "Existing studio form.\nPlease read this text."]);

        return Appointment::create(['treatment_course_id' => (string) \Illuminate\Support\Str::uuid(), 'user_id' => User::factory()->create()->id, 'service_id' => $service->id,
            'session_number' => 1, 'scheduled_at' => now()->addDay(), 'status' => 'scheduled']);
    }

    private function signature(Appointment $appointment): array
    {
        return ['signed_name' => 'Client Name', 'acknowledged' => '1',
            'signature' => json_encode([[[20, 60], [80, 100], [130, 40], [200, 90]]]),
            'content_hash' => hash('sha256', $appointment->service->name."\n".$appointment->service->consent_content)];
    }

    public function test_member_signs_once_and_keeps_original_wording_after_template_changes(): void
    {
        $appointment = $this->booking();
        $this->actingAs($appointment->client)->get(route('member.appointments.show', $appointment))->assertOk()->assertSee('Read and sign');
        $this->get(route('member.consents.create', $appointment))->assertOk()->assertSee('Existing studio form.');
        $data = $this->signature($appointment);
        $this->post(route('member.consents.store', $appointment), $data)->assertSessionHasNoErrors()->assertRedirect();
        $consent = ConsentForm::firstOrFail();
        $appointment->service->update(['name' => 'New name', 'consent_content' => 'New wording.']);
        $this->post(route('member.consents.store', $appointment), $data)->assertRedirect(route('consents.show', $consent));
        $this->assertDatabaseCount('consent_forms', 1);
        $this->get(route('consents.show', $consent))->assertOk()->assertSee('Existing studio form.')->assertSee($appointment->client->name)->assertSee('saved-signature', false)->assertDontSee('New wording.');
        $this->get(route('member.consents.create', $appointment))->assertRedirect(route('consents.show', $consent));
    }

    public function test_other_members_cannot_read_or_sign_another_clients_form(): void
    {
        $appointment = $this->booking();
        $this->actingAs(User::factory()->create());
        $this->get(route('member.consents.create', $appointment))->assertForbidden();
        $this->post(route('member.consents.store', $appointment), $this->signature($appointment))->assertForbidden();
        $this->get(route('admin.consents.index'))->assertForbidden();
        $this->assertDatabaseCount('consent_forms', 0);
    }

    public function test_staff_tablet_collects_client_consent_and_stays_locked_until_password_is_entered(): void
    {
        $appointment = $this->booking();
        $staff = User::factory()->create(['user_type' => 'staff', 'password' => \Illuminate\Support\Facades\Hash::make('tablet-password')]);
        $this->actingAs($staff)->post(route('studio.consents.start', $appointment))
            ->assertRedirect(route('studio.consents.tablet'))->assertSessionHas('consent_tablet.appointment_id', $appointment->id);
        $this->get(route('studio.consents.tablet'))->assertOk()->assertSee($appointment->client->name)
            ->assertSee('Name from booking')->assertSee('data-signature-canvas', false)->assertDontSee('Workspace navigation');
        $this->get('/dashboard')->assertRedirect(route('studio.consents.tablet'));
        $this->post(route('studio.consents.sign'), array_merge($this->signature($appointment), ['user_id' => $staff->id]))
            ->assertSessionHasNoErrors()->assertRedirect(route('studio.consents.tablet'));
        $this->assertAuthenticatedAs($staff);
        $consent = ConsentForm::firstOrFail();
        $this->assertSame($appointment->user_id, $consent->user_id);
        $this->assertSame($staff->id, $consent->recorded_by);
        $this->get(route('studio.consents.tablet'))->assertOk()->assertSee('Please hand the tablet back');
        $this->post(route('studio.consents.sign'), $this->signature($appointment))->assertRedirect();
        $this->assertDatabaseCount('consent_forms', 1);
        $this->get('/studio/clients')->assertRedirect(route('studio.consents.tablet'));
        $this->from(route('studio.consents.tablet'))->post(route('studio.consents.unlock'), ['password' => 'wrong'])
            ->assertSessionHasErrorsIn('tabletUnlock', 'password')->assertSessionHas('consent_tablet');
        $this->post(route('studio.consents.unlock'), ['password' => 'tablet-password'])
            ->assertRedirect(route('studio.appointments.index'))->assertSessionMissing('consent_tablet');
        $this->get('/studio/clients')->assertOk();
        $this->get(route('consents.show', $consent))->assertOk()->assertSee('Form presented by');
    }

    public function test_tablet_lock_blocks_management_posts_and_cannot_be_opened_by_members(): void
    {
        $appointment = $this->booking();
        $this->actingAs($appointment->client)->post(route('studio.consents.start', $appointment))->assertForbidden();
        $this->actingAs(User::factory()->create(['user_type' => 'administrator']));
        $this->post(route('studio.consents.start', $appointment))->assertRedirect();
        $this->patch(route('admin.consents.update', $appointment->service), ['requires_consent' => 0, 'consent_content' => 'Changed'])
            ->assertRedirect(route('studio.consents.tablet'));
        $this->assertDatabaseHas('services', ['id' => $appointment->service_id, 'requires_consent' => true,
            'consent_content' => "Existing studio form.\nPlease read this text."]);
    }

    public function test_tablet_requires_current_wording_and_shows_staff_unlock_if_booking_closes(): void
    {
        $appointment = $this->booking();
        $data = $this->signature($appointment);
        $this->actingAs(User::factory()->create(['user_type' => 'staff']));
        $this->post(route('studio.consents.sign'), $data)->assertForbidden();
        $this->post(route('studio.consents.start', $appointment))->assertRedirect();
        $appointment->service->update(['consent_content' => 'New consent wording.']);
        $this->post(route('studio.consents.sign'), $data)->assertSessionHasErrors('content_hash');
        $this->assertDatabaseCount('consent_forms', 0);
        $appointment->update(['status' => 'declined']);
        $this->get(route('studio.consents.tablet'))->assertOk()->assertSee('no longer available')->assertSee('Staff: return to management');
        $this->post(route('studio.consents.sign'), $data)->assertStatus(409);
        $this->assertDatabaseCount('consent_forms', 0);
    }

    public function test_signature_requires_acknowledgement_and_current_wording(): void
    {
        $appointment = $this->booking();
        $this->actingAs($appointment->client);
        $data = $this->signature($appointment);
        $this->post(route('member.consents.store', $appointment), array_replace($data, ['acknowledged' => '0']))->assertSessionHasErrors('acknowledged');
        $appointment->service->update(['consent_content' => 'Updated form.']);
        $this->post(route('member.consents.store', $appointment), $data)->assertSessionHasErrors('content_hash');
        $this->assertDatabaseCount('consent_forms', 0);
    }

    public function test_blank_or_malformed_drawings_are_rejected_and_booking_name_is_used(): void
    {
        $appointment = $this->booking();
        $this->actingAs($appointment->client);
        $data = $this->signature($appointment);
        foreach (['', '[]', 'not-json', '[[[10,10],[10,10]]]', '[[[10,10],[50,500]]]', '[[["<script>",10],[50,60]]]'] as $drawing) {
            $this->post(route('member.consents.store', $appointment), array_replace($data, ['signature' => $drawing]))
                ->assertSessionHasErrors('signature');
        }
        $this->assertDatabaseCount('consent_forms', 0);
        $this->post(route('member.consents.store', $appointment), $data)->assertSessionHasNoErrors()->assertRedirect();
        $consent = ConsentForm::firstOrFail();
        $this->assertSame($appointment->client->name, $consent->signed_name);
        $this->assertEquals(json_decode($data['signature'], true), $consent->signature_strokes);
        $appointment->client->update(['name' => 'Changed profile name']);
        $this->assertNotSame($appointment->client->name, $consent->fresh()->signed_name);
    }

    public function test_closed_appointments_cannot_be_signed(): void
    {
        $appointment = $this->booking();
        $appointment->update(['status' => 'completed']);
        $this->actingAs($appointment->client)->post(route('member.consents.store', $appointment), $this->signature($appointment))->assertStatus(409);
        $this->assertDatabaseCount('consent_forms', 0);
    }

    public function test_only_admin_can_publish_nonempty_consent_wording_and_staff_can_view_signed_records(): void
    {
        $appointment = $this->booking();
        $this->actingAs($appointment->client)->post(route('member.consents.store', $appointment), $this->signature($appointment));
        $consent = ConsentForm::firstOrFail();
        $this->actingAs(User::factory()->create())->get(route('consents.show', $consent))->assertForbidden();
        $this->actingAs(User::factory()->create(['user_type' => 'staff']));
        $this->get(route('consents.show', $consent))->assertOk();
        $this->patch(route('admin.consents.update', $appointment->service), ['requires_consent' => 1, 'consent_content' => 'Unauthorized'])->assertForbidden();
        $this->actingAs(User::factory()->create(['user_type' => 'administrator']));
        $this->get(route('admin.consents.index'))->assertOk();
        $this->patch(route('admin.consents.update', $appointment->service), ['requires_consent' => 1, 'consent_content' => '   '])->assertSessionHasErrors('consent_content');
        $this->patch(route('admin.consents.update', $appointment->service), ['requires_consent' => 1, 'consent_content' => 'Approved studio wording.'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('services', ['id' => $appointment->service_id, 'consent_content' => 'Approved studio wording.']);
        $this->assertDatabaseHas('consent_forms', ['id' => $consent->id, 'content_snapshot' => "Existing studio form.\nPlease read this text."]);
    }
}
