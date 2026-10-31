<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TreatmentSuppliesTest extends TestCase
{
    use RefreshDatabase;

    private function treatmentSession(): Appointment
    {
        $this->actingAs(User::factory()->create(['user_type' => 'staff']));
        $service = Service::create(['name' => 'Facial', 'category' => 'Skin', 'price' => 100, 'session_count' => 1, 'is_active' => true]);
        return Appointment::create(['user_id' => User::factory()->create()->id, 'service_id' => $service->id, 'treatment_course_id' => (string) Str::uuid(), 'session_number' => 1, 'scheduled_at' => now(), 'status' => 'scheduled']);
    }

    private function product(string $type, int $stock = 10): Product
    {
        return Product::create(['name' => $type === 'supply' ? 'Treatment serum' : 'Take-home cleanser', 'type' => $type, 'stock_quantity' => $stock, 'selling_price' => 50, 'cost_price' => 10, 'is_active' => true]);
    }

    public function test_supplies_reduce_stock_and_appear_on_receipts_without_becoming_charged_items(): void
    {
        $session = $this->treatmentSession();
        $supply = $this->product('supply');
        $retail = $this->product('retail');
        $this->get('/studio/appointments')->assertOk()->assertSee('Items used in treatment')->assertSee('Treatment serum');
        $payload = ['amount' => 100, 'method' => 'cash', 'products' => [['id' => $retail->id, 'quantity' => 1]], 'supplies' => [['id' => $supply->id, 'quantity' => 0.25]]];
        $this->patch(route('studio.appointments.complete', $session), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('completed', $session->fresh()->status);
        $this->assertEquals(9.75, $supply->fresh()->stock_quantity);
        $this->assertEquals(9, $retail->fresh()->stock_quantity);
        $this->assertDatabaseHas('inventory_movements', ['appointment_id' => $session->id, 'product_id' => $supply->id, 'quantity' => -0.25, 'type' => 'treatment_usage']);
        $this->assertDatabaseMissing('payment_items', ['product_id' => $supply->id]);
        $this->assertDatabaseHas('payment_items', ['product_id' => $retail->id]);
        $payment = $session->fresh()->payment;
        $this->assertSame('150.00', $payment->amount);
        $this->get(route('studio.payments.receipt', $payment))->assertOk()->assertSee('Take-home cleanser')
            ->assertSee('Treatment supplies used')->assertSee('Treatment serum')->assertSee('Qty: 0.25')->assertSee('no additional charge');
        $this->get('/studio/appointments')->assertOk()->assertSee('Treatment supplies used');
        $this->patch(route('studio.appointments.complete', $session), $payload)->assertStatus(422);
        $this->assertDatabaseCount('payments', 1);
        $this->assertEquals(9.75, $supply->fresh()->stock_quantity);
        $member = User::findOrFail($session->user_id);
        $this->actingAs($member)->get(route('member.appointments.receipt', $session))->assertOk()
            ->assertSee('Treatment serum')->assertSee('Qty: 0.25')->assertSee('PHP 150.00');
    }

    public function test_receipt_totals_include_multiple_retail_units_and_flag_historical_mismatches(): void
    {
        $session = $this->treatmentSession();
        $retail = $this->product('retail');
        $retail->update(['selling_price' => 150]);
        $this->patch(route('studio.appointments.complete', $session), ['amount' => 100, 'method' => 'cash', 'products' => [['id' => $retail->id, 'quantity' => 10]]])->assertSessionHasNoErrors();
        $payment = $session->fresh()->payment;
        $this->assertSame('1600.00', $payment->amount);
        $this->get(route('studio.payments.receipt', $payment))->assertOk()->assertSee('PHP 1,600.00')->assertDontSee('recorded payment differs')->assertDontSee('Treatment supplies used');
        $payment->update(['amount' => 100]);
        $this->get(route('studio.payments.receipt', $payment))->assertOk()->assertSee('Item total')->assertSee('PHP 1,600.00')->assertSee('PHP 100.00')->assertSee('recorded payment differs');
        $this->assertSame('100.00', $payment->fresh()->amount);
    }

    public function test_insufficient_stock_does_not_complete_or_charge_the_session(): void
    {
        $session = $this->treatmentSession();
        $supply = $this->product('supply', 1);
        $retail = $this->product('retail');
        $this->from('/studio/appointments')->patch(route('studio.appointments.complete', $session), [
            'amount' => 150, 'method' => 'cash', 'products' => [['id' => $retail->id, 'quantity' => 1]], 'supplies' => [['id' => $supply->id, 'quantity' => 2]],
        ])->assertSessionHasErrors('supplies.0.quantity')->assertSessionHasInput('_completing_appointment_id', (string) $session->id);
        $this->assertSame('scheduled', $session->fresh()->status);
        $this->assertEquals(10, $retail->fresh()->stock_quantity);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
        $this->get('/studio/appointments')->assertOk()->assertSee('Insufficient stock');
    }

    public function test_discount_is_deducted_from_service_and_retail_and_shown_on_receipt(): void
    {
        $session = $this->treatmentSession();
        $retail = $this->product('retail');
        $this->patch(route('studio.appointments.complete', $session), [
            'amount' => 1, 'method' => 'cash', 'discount_amount' => '25.50',
            'discount_reason' => 'Loyalty reward', 'products' => [['id' => $retail->id, 'quantity' => 2]],
        ])->assertSessionHasNoErrors();
        $payment = $session->fresh()->payment;
        $this->assertSame('174.50', $payment->amount);
        $this->assertSame('25.50', $payment->discount_amount);
        $this->get(route('studio.payments.receipt', $payment))->assertOk()
            ->assertSee('Subtotal')->assertSee('PHP 200.00')->assertSee('Loyalty reward')
            ->assertSee('-PHP 25.50')->assertSee('PHP 174.50')->assertDontSee('recorded payment differs');
    }

    public function test_invalid_discounts_do_not_charge_and_full_discounts_are_allowed(): void
    {
        $session = $this->treatmentSession();
        foreach ([-1, 100.01, '1.001'] as $discount) {
            $this->patch(route('studio.appointments.complete', $session), [
                'amount' => 0, 'method' => 'cash', 'discount_amount' => $discount,
            ])->assertSessionHasErrors('discount_amount');
        }
        $this->assertDatabaseCount('payments', 0);
        $this->assertSame('scheduled', $session->fresh()->status);
        $this->patch(route('studio.appointments.complete', $session), [
            'amount' => 0, 'method' => 'cash', 'discount_amount' => 100,
        ])->assertSessionHasNoErrors();
        $this->assertSame('0.00', $session->fresh()->payment->amount);
    }

    public function test_wrong_item_types_inactive_and_duplicate_supplies_are_rejected(): void
    {
        $session = $this->treatmentSession();
        $retail = $this->product('retail');
        $supply = $this->product('supply');
        $endpoint = route('studio.appointments.complete', $session);
        $this->patch($endpoint, ['amount' => 100, 'method' => 'cash', 'supplies' => [['id' => $retail->id, 'quantity' => 1]]])->assertSessionHasErrors('supplies.0.id');
        $this->patch($endpoint, ['amount' => 100, 'method' => 'cash', 'products' => [['id' => $supply->id, 'quantity' => 1]]])->assertSessionHasErrors('products.0.id');
        $this->patch($endpoint, ['amount' => 100, 'method' => 'cash', 'supplies' => [['id' => $supply->id, 'quantity' => 6], ['id' => $supply->id, 'quantity' => 6]]])->assertSessionHasErrors('supplies.0.id');
        $supply->update(['is_active' => false]);
        $this->patch($endpoint, ['amount' => 100, 'method' => 'cash', 'supplies' => [['id' => $supply->id, 'quantity' => 1]]])->assertSessionHasErrors('supplies.0.id');
        $this->assertSame('scheduled', $session->fresh()->status);
        $this->assertDatabaseCount('payments', 0);
    }
}
