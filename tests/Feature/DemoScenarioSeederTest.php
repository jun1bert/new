<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DemoScenarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoScenarioSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_scenarios_render_and_repeat_seeding_preserves_data(): void
    {
        $existing = User::factory()->create(['name' => 'Existing client']);
        $this->seed(DemoScenarioSeeder::class);
        $this->assertDatabaseCount('services', 17);
        $this->assertDatabaseCount('appointments', 43);
        $this->assertDatabaseCount('payments', 16);
        $this->assertDatabaseCount('consent_forms', 16);
        $this->assertDatabaseCount('client_claim_requests', 1);
        $this->assertSame('Existing client', $existing->fresh()->name);
        $admin = User::where('email', 'admin@demo.shafers.test')->firstOrFail();
        $this->assertTrue(Hash::check(DemoScenarioSeeder::PASSWORD, $admin->password));
        $this->actingAs($admin);
        foreach (['/dashboard', '/admin/reports', '/studio/appointments', '/studio/clients', '/admin/products', '/admin/claim-requests'] as $path) {
            $this->get($path)->assertOk();
        }
        foreach (Payment::with('items')->get() as $payment) {
            $itemCents = $payment->items->sum(fn ($item) => (int) round((float) $item->unit_price * 100) * (int) $item->quantity);
            $this->assertEquals($itemCents - round((float) $payment->discount_amount * 100), round((float) $payment->amount * 100));
            $this->get(route('studio.payments.receipt', $payment))->assertOk()->assertDontSee('recorded payment differs');
        }
        $guestAppointments = Appointment::where('status', 'pending')->where('contact_number', '09170000002')->get();
        $this->assertCount(2, $guestAppointments);
        $this->assertEquals($guestAppointments[0]->scheduled_at, $guestAppointments[1]->scheduled_at);
        foreach ($guestAppointments as $appointment) { $this->assertCount(2, $appointment->services); }
        $member = User::where('email', 'mia@demo.shafers.test')->firstOrFail();
        $this->actingAs($member)->get('/dashboard')->assertOk()->assertSee('Demo Laser Treatment Course');
        $scheduled = $member->appointments()->where('status', 'scheduled')->where('session_number', 1)->firstOrFail();
        $this->get(route('member.consents.create', $scheduled))->assertOk()
            ->assertSee('Signature Facial')->assertSee('Relaxation Massage');

        $stock = Product::pluck('stock_quantity', 'id')->all();
        $scheduled->update(['notes' => 'My manual demo change']);
        $this->seed(DemoScenarioSeeder::class);
        $this->assertDatabaseCount('services', 17);
        $this->assertDatabaseCount('appointments', 43);
        $this->assertDatabaseCount('payments', 16);
        $this->assertDatabaseCount('inventory_movements', 24);
        $this->assertDatabaseCount('client_claim_requests', 1);
        $this->assertSame($stock, Product::pluck('stock_quantity', 'id')->all());
        $this->assertSame('My manual demo change', $scheduled->fresh()->notes);
    }
}
