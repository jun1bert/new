<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_members_can_open_their_workspace_without_management_links(): void
    {
        $member = User::factory()->create();
        $this->actingAs($member)->get('/dashboard')->assertOk()
            ->assertSee('My care space')->assertSee('Recent appointments')
            ->assertDontSee(route('admin.users.index'), false);
        $this->get('/profile')->assertOk()->assertSee('Profile &amp; security', false);
        $this->get('/claim-past-appointment')->assertOk();
        $this->get('/admin/users')->assertForbidden();
        $this->get('/studio/appointments')->assertForbidden();
    }

    public function test_staff_can_open_the_studio_screens(): void
    {
        $this->actingAs(User::factory()->create(['user_type' => 'staff']));
        foreach (['/dashboard', '/studio/appointments', '/studio/clients', '/studio/gallery'] as $path) {
            $this->get($path)->assertOk();
        }
        $this->get('/admin/users')->assertForbidden();
    }

    public function test_member_booking_errors_reopen_the_form_and_preserve_their_entries(): void
    {
        $member = User::factory()->create();
        $service = Service::create(['name' => 'Facial', 'category' => 'Skin', 'price' => 500, 'session_count' => 1, 'is_active' => true]);
        $date = now()->addDay()->toDateString();
        $this->actingAs($member)->from('/dashboard')->post(route('appointments.request'), [
            'full_name' => $member->name, 'email' => $member->email, 'contact_number' => '09171234567',
            'date' => $date, 'time' => 'invalid', 'notes' => 'Please keep this note.',
            'participants' => [['name' => $member->name, 'service_ids' => [$service->id]]],
        ])->assertRedirect('/dashboard')->assertSessionHasErrors('time');
        $this->get('/dashboard')->assertOk()->assertSee('bookingOpen: true', false)
            ->assertSee('Please check your request.')->assertSee('value="09171234567"', false)
            ->assertSee('value="'.$date.'"', false)->assertSee('Please keep this note.')
            ->assertSee('value="'.$service->id.'" checked', false);
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_administrators_can_open_all_management_screens(): void
    {
        $this->actingAs(User::factory()->create(['user_type' => 'administrator']));
        foreach (['/dashboard', '/admin/users', '/admin/services', '/admin/products', '/admin/claim-requests', '/studio/appointments', '/studio/clients', '/studio/gallery'] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_dashboard_counts_pending_and_scheduled_sessions_separately(): void
    {
        $admin = User::factory()->create(['user_type' => 'administrator']);
        $member = User::factory()->create();
        $service = Service::create(['name' => 'Facial', 'category' => 'Skin', 'price' => 500, 'duration' => 60, 'session_count' => 1, 'is_active' => true]);
        foreach (['pending', 'pending', 'scheduled', 'completed', 'declined'] as $status) {
            Appointment::create([
                'treatment_course_id' => (string) Str::uuid(), 'user_id' => $member->id,
                'service_id' => $service->id, 'session_number' => 1,
                'scheduled_at' => now()->addDay(), 'status' => $status,
            ]);
        }
        $this->actingAs($admin)->get('/dashboard')->assertOk()
            ->assertViewHas('stats', ['total' => 5, 'pending' => 2, 'scheduled' => 1, 'completed' => 1]);
    }

    public function test_service_editor_preserves_invalid_edits_and_saves_valid_changes(): void
    {
        $admin = User::factory()->create(['user_type' => 'administrator']);
        $service = Service::create(['name' => 'Facial', 'category' => 'Skin', 'price' => 500, 'session_count' => 1, 'is_active' => true]);
        $this->actingAs($admin)->get('/admin/services')->assertOk()
            ->assertSee('edit-service-'.$service->id.'-title', false)->assertSee('aria-haspopup="dialog"', false);
        $data = ['name' => 'Updated facial', 'category' => 'Skin', 'price' => -1, 'session_count' => 1];
        $this->from('/admin/services')->patch(route('admin.services.update', $service), $data)
            ->assertRedirect('/admin/services')->assertSessionHasErrors('price')
            ->assertSessionHasInput('_editing_service_id', (string) $service->id);
        $this->get('/admin/services')->assertOk()->assertSee('value="Updated facial"', false)
            ->assertSee('visible: true', false);
        $this->patch(route('admin.services.update', $service), array_replace($data, ['price' => 600]))
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('services', ['id' => $service->id, 'name' => 'Updated facial', 'price' => 600]);
    }
}
