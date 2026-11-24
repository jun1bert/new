<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ClientClaimRequest;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DeploymentSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function service(): Service
    {
        return Service::create(['name' => 'Facial', 'category' => 'Skin', 'price' => 500, 'session_count' => 1, 'is_active' => true]);
    }

    private function visit(User $client, Service $service, string $status = 'completed'): Appointment
    {
        return Appointment::create(['user_id' => $client->id, 'service_id' => $service->id, 'treatment_course_id' => (string) Str::uuid(),
            'session_number' => 1, 'status' => $status, 'scheduled_at' => '2026-10-15 02:00:00']);
    }

    public function test_guest_contact_information_never_attaches_to_an_existing_account(): void
    {
        $victim = User::factory()->create(['name' => 'Existing client']);
        $service = $this->service();
        $this->visit($victim, $service)->update(['contact_number' => '09170000000']);
        $payload = ['full_name' => $victim->name, 'email' => $victim->email, 'contact_number' => '09170000000',
            'date' => now('Asia/Manila')->addDay()->toDateString(), 'time' => '10:00',
            'participants' => [['name' => $victim->name, 'service_ids' => [$service->id]]]];
        $this->post(route('appointments.request'), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $created = Appointment::latest('id')->firstOrFail();
        $this->assertNotEquals($victim->id, $created->user_id);
        $this->assertStringStartsWith('guest-', $created->client->email);
        $this->assertSame($victim->email, $created->booking_email);
        $this->assertSame(1, $victim->appointments()->count());
        $this->post(route('appointments.request'), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertNotEquals($created->user_id, Appointment::latest('id')->firstOrFail()->user_id);
    }

    public function test_deactivated_accounts_are_logged_out_of_existing_sessions(): void
    {
        $user = User::factory()->create(['is_active' => false, 'user_type' => 'administrator']);
        $this->actingAs($user)->get('/admin/reports')->assertRedirect('/login');
        $this->assertGuest();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_guest_claim_cannot_move_a_registered_clients_history(): void
    {
        $service = $this->service();
        $owner = User::factory()->create();
        $claimant = User::factory()->create();
        $visit = $this->visit($owner, $service);
        $claim = ClientClaimRequest::create(['claimant_id' => $claimant->id, 'name_used' => $owner->name,
            'appointment_date' => '2026-10-15', 'service_name' => $service->name, 'status' => 'pending']);
        $this->actingAs(User::factory()->create(['user_type' => 'administrator']))
            ->patch(route('admin.claims.approve', $claim), ['appointment_id' => $visit->id])->assertStatus(422);
        $this->assertEquals($owner->id, $visit->fresh()->user_id);
        $this->assertSame('pending', $claim->fresh()->status);
    }

    public function test_guest_claim_links_only_the_verified_course(): void
    {
        $service = $this->service();
        $guest = User::factory()->create(['email' => 'guest-scenario@booking.local']);
        $claimant = User::factory()->create();
        $verified = $this->visit($guest, $service);
        $other = $this->visit($guest, $service);
        $next = $this->visit($guest, $service, 'scheduled');
        $next->update(['treatment_course_id' => $verified->treatment_course_id, 'session_number' => 2]);
        $claim = ClientClaimRequest::create(['claimant_id' => $claimant->id, 'name_used' => $guest->name,
            'appointment_date' => '2026-10-15', 'service_name' => $service->name, 'status' => 'pending']);
        $this->actingAs(User::factory()->create(['user_type' => 'administrator']))
            ->patch(route('admin.claims.approve', $claim), ['appointment_id' => $verified->id])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertEquals($claimant->id, $verified->fresh()->user_id);
        $this->assertEquals($claimant->id, $next->fresh()->user_id);
        $this->assertEquals($guest->id, $other->fresh()->user_id);
        $this->assertSame('approved', $claim->fresh()->status);
        $this->patch(route('admin.claims.approve', $claim), ['appointment_id' => $other->id])->assertStatus(422);
    }

    public function test_public_booking_uses_philippine_time_and_preserves_month_boundary_in_calendar(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-31 15:00:00', 'UTC'));
        $service = $this->service();
        $this->post(route('appointments.request'), ['full_name' => 'Guest client', 'contact_number' => '09170000000',
            'date' => '2026-11-01', 'time' => '01:00', 'participants' => [['name' => 'Guest client', 'service_ids' => [$service->id]]]])
            ->assertSessionHasNoErrors()->assertRedirect();
        $visit = Appointment::firstOrFail();
        $this->assertSame('2026-10-31 17:00:00', $visit->scheduled_at->format('Y-m-d H:i:s'));
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-31 16:30:00', 'UTC'));
        $this->actingAs(User::factory()->create(['user_type' => 'staff']))->get('/dashboard')->assertOk()
            ->assertViewHas('appointments', fn ($appointments) => $appointments->contains('id', $visit->id));
        $this->patch(route('studio.appointments.accept', $visit))->assertSessionHasNoErrors()->assertRedirect();
        $this->travelBack();
    }

    public function test_stock_adjustments_reject_underflow_and_fractional_precision_without_writing_movements(): void
    {
        $product = Product::create(['name' => 'Serum', 'type' => 'retail', 'stock_quantity' => 1, 'selling_price' => 100]);
        $this->actingAs(User::factory()->create(['user_type' => 'administrator']));
        foreach ([-2, '0.001', 99999999.99] as $quantity) {
            $this->patch(route('admin.products.stock.adjust', $product), ['quantity' => $quantity])->assertSessionHasErrors('quantity');
        }
        $this->assertSame('1.00', $product->fresh()->stock_quantity);
        $this->assertDatabaseCount('inventory_movements', 0);
        $this->patch(route('admin.products.stock.adjust', $product), ['quantity' => '-0.25'])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('0.75', $product->fresh()->stock_quantity);
        $this->assertDatabaseCount('inventory_movements', 1);
    }

    public function test_demo_seeder_is_blocked_in_production_before_creating_accounts(): void
    {
        $this->app['env'] = 'production';
        try {
            app(\Database\Seeders\DemoScenarioSeeder::class)->run();
            $this->fail('Production must not seed demo accounts.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Demo scenarios are disabled', $exception->getMessage());
            $this->assertDatabaseCount('users', 0);
        }
    }

    public function test_required_consent_cannot_be_bypassed_when_completing_an_appointment(): void
    {
        $service = $this->service();
        $service->update(['requires_consent' => true, 'consent_content' => 'Please read this treatment consent.']);
        $client = User::factory()->create();
        $appointment = $this->visit($client, $service, 'scheduled');
        $staff = User::factory()->create(['user_type' => 'staff']);
        $this->actingAs($staff)->patch(route('studio.appointments.complete', $appointment), ['amount' => 500, 'method' => 'cash'])
            ->assertSessionHasErrors('consent');
        $this->assertDatabaseCount('payments', 0);
        $this->assertSame('scheduled', $appointment->fresh()->status);
        $this->actingAs($client)->post(route('member.consents.store', $appointment), [
            'acknowledged' => 1, 'signature' => json_encode([[[20, 60], [80, 100], [130, 40], [200, 90]]]),
            'content_hash' => hash('sha256', $service->name."\n".$service->consent_content),
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->actingAs($staff)->patch(route('studio.appointments.complete', $appointment), ['amount' => 500, 'method' => 'cash'])
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_owner_setup_uses_interactive_credentials_and_does_not_replace_existing_accounts(): void
    {
        $this->artisan('app:create-owner')->expectsQuestion('Owner name', 'Studio Owner')
            ->expectsQuestion('Owner email', 'owner@studio.test')
            ->expectsQuestion('Password (at least 12 characters)', 'UniqueOwnerPassword!2026')
            ->expectsQuestion('Confirm password', 'UniqueOwnerPassword!2026')->assertSuccessful();
        $owner = User::where('email', 'owner@studio.test')->firstOrFail();
        $this->assertSame('owner', $owner->user_type);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('UniqueOwnerPassword!2026', $owner->password));
        $this->artisan('app:create-owner')->expectsQuestion('Owner name', 'Replacement')
            ->expectsQuestion('Owner email', 'owner@studio.test')
            ->expectsQuestion('Password (at least 12 characters)', 'AnotherOwnerPassword!2026')
            ->expectsQuestion('Confirm password', 'AnotherOwnerPassword!2026')->assertFailed();
        $this->assertSame('Studio Owner', $owner->fresh()->name);
    }

    public function test_deployment_check_reports_local_configuration_without_mutating_data(): void
    {
        $this->artisan('app:deployment-check')->expectsOutputToContain('Production environment')->assertFailed();
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('appointments', 0);
    }
}
