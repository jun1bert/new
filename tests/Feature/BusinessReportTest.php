<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BusinessReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_administrators_can_view_business_reports(): void
    {
        $this->get('/admin/reports')->assertRedirect('/login');
        foreach (['member', 'staff'] as $role) {
            $this->actingAs(User::factory()->create(['user_type' => $role]))->get('/admin/reports')->assertForbidden();
            $this->get('/dashboard')->assertOk()->assertDontSee('href="'.route('admin.reports.index').'"', false);
        }
        $this->actingAs(User::factory()->create(['user_type' => 'administrator']))
            ->get('/admin/reports')->assertOk()->assertSee('Business overview')->assertSee('No payments recorded in this period.');
    }

    public function test_report_uses_payment_dates_and_philippine_day_boundaries_without_counting_services_as_visits(): void
    {
        $admin = User::factory()->create(['user_type' => 'administrator']);
        $member = User::factory()->create();
        $service = Service::create(['name' => 'Facial', 'category' => 'Skin', 'price' => 1000, 'session_count' => 1]);
        $extra = Service::create(['name' => 'Massage', 'category' => 'Body', 'price' => 500, 'session_count' => 1]);
        $product = Product::create(['name' => 'Serum', 'type' => 'retail', 'selling_price' => 200,
            'stock_quantity' => 2, 'reorder_level' => 3, 'is_active' => true]);
        $appointment = Appointment::create(['user_id' => $member->id, 'service_id' => $service->id,
            'treatment_course_id' => (string) Str::uuid(), 'session_number' => 1,
            'scheduled_at' => '2026-10-15 02:00:00', 'status' => 'completed']);
        $appointment->services()->sync([$service->id => ['session_count' => 1], $extra->id => ['session_count' => 1]]);
        $payment = Payment::create(['appointment_id' => $appointment->id, 'recorded_by' => $admin->id,
            'amount' => 1600, 'discount_amount' => 100, 'method' => 'gcash', 'paid_at' => '2026-10-14 16:00:00']);
        $payment->items()->createMany([
            ['description' => 'Facial', 'quantity' => 1, 'unit_price' => 1000],
            ['description' => 'Massage', 'quantity' => 1, 'unit_price' => 500],
            ['product_id' => $product->id, 'description' => 'Serum', 'quantity' => 1, 'unit_price' => 200],
        ]);
        // A separate payment is in the previous Philippine day.
        $previousVisit = $appointment->replicate();
        $previousVisit->treatment_course_id = (string) Str::uuid();
        $previousVisit->scheduled_at = '2026-10-14 02:00:00';
        $previousVisit->save();
        Payment::create(['appointment_id' => $previousVisit->id, 'recorded_by' => $admin->id,
            'amount' => 800, 'method' => 'cash', 'paid_at' => '2026-10-14 15:59:59']);

        $this->actingAs($admin)->get('/admin/reports?from=2026-10-15&to=2026-10-15')->assertOk()
            ->assertViewHas('collectedCents', 160000)->assertViewHas('discountCents', 10000)
            ->assertViewHas('paymentCount', 1)->assertViewHas('averageCents', 160000)
            ->assertViewHas('visitCount', 1)->assertViewHas('clientCount', 1)->assertViewHas('change', 100.0)
            ->assertViewHas('retailGross', fn ($gross) => (float) $gross === 200.0)
            ->assertViewHas('trend', fn ($trend) => $trend->sum('cents') === 160000)
            ->assertViewHas('topServices', fn ($services) => $services->count() === 2 && (float) $services->sum('gross') === 1500.0)
            ->assertViewHas('lowStockCount', 1)->assertSee('1,600.00')->assertSee('Serum');
        $this->get('/admin/reports?from=2026-10-16&to=2026-10-16')->assertOk()
            ->assertViewHas('paymentCount', 0)->assertViewHas('visitCount', 0)->assertViewHas('collectedCents', 0);
    }

    public function test_invalid_and_excessively_long_ranges_are_rejected(): void
    {
        $this->actingAs(User::factory()->create(['user_type' => 'administrator']));
        foreach (['from=invalid', 'from=2026-10-20&to=2026-10-01', 'from=2024-01-01&to=2026-10-01'] as $query) {
            $this->from('/admin/reports')->get('/admin/reports?'.$query)->assertRedirect('/admin/reports')->assertSessionHasErrors();
        }
    }

    public function test_completed_sales_and_inventory_changes_flow_into_reports(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-15 02:00:00', 'UTC'));
        $admin = User::factory()->create(['user_type' => 'administrator']);
        $this->actingAs($admin);
        $service = Service::create(['name' => 'Facial', 'category' => 'Skin', 'price' => 100, 'session_count' => 1]);
        $retail = Product::create(['name' => 'Retail cleanser', 'type' => 'retail', 'stock_quantity' => 10, 'cost_price' => 10, 'selling_price' => 50]);
        $supply = Product::create(['name' => 'Treatment serum', 'type' => 'supply', 'stock_quantity' => 5, 'cost_price' => 20, 'selling_price' => 0]);
        $appointment = Appointment::create(['user_id' => User::factory()->create()->id, 'service_id' => $service->id,
            'treatment_course_id' => (string) Str::uuid(), 'session_number' => 1, 'scheduled_at' => now(), 'status' => 'scheduled']);
        $this->patch(route('studio.appointments.complete', $appointment), [
            'amount' => 180, 'discount_amount' => 20, 'method' => 'cash',
            'products' => [['id' => $retail->id, 'quantity' => 2]],
            'supplies' => [['id' => $supply->id, 'quantity' => 0.25]],
        ])->assertSessionHasNoErrors();
        $this->travelBack();
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-14 02:00:00', 'UTC'));
        $this->patch(route('admin.products.stock.adjust', $retail), ['quantity' => 5])->assertSessionHasNoErrors();
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-15 02:00:00', 'UTC'));
        $this->patch(route('admin.products.stock.adjust', $retail), ['quantity' => 3])->assertSessionHasNoErrors();
        $retail->update(['selling_price' => 999]);
        $this->get('/admin/reports?from=2026-10-15&to=2026-10-15')->assertOk()
            ->assertSee('Inventory and product sales')->assertViewHas('collectedCents', 18000)
            ->assertViewHas('retailGross', fn ($gross) => (float) $gross === 100.0)
            ->assertViewHas('inventorySummary', fn ($summary) => (int) $summary->item_count === 2 && (float) $summary->stock_cost === 255.0)
            ->assertViewHas('inventoryRows', function ($rows) use ($retail, $supply) {
                $sold = $rows->firstWhere('id', $retail->id);
                $used = $rows->firstWhere('id', $supply->id);
                return (float) $sold->stock_quantity === 16.0 && (float) $sold->sold_units === 2.0
                    && (float) $sold->sales_gross === 100.0 && (float) $sold->stock_received === 3.0
                    && (float) $used->treatment_used === 0.25 && (float) $used->stock_quantity === 4.75
                    && (float) $used->sales_gross === 0.0;
            });
        $this->get('/admin/reports?from=2026-10-16&to=2026-10-16')->assertOk()
            ->assertViewHas('retailGross', fn ($gross) => (float) $gross === 0.0)
            ->assertViewHas('inventoryRows', fn ($rows) => (float) $rows->sum('sold_units') === 0.0
                && (float) $rows->sum('stock_received') === 0.0 && (float) $rows->sum('treatment_used') === 0.0
                && (float) $rows->firstWhere('id', $retail->id)->stock_quantity === 16.0);
        $retail->update(['is_active' => false]);
        $this->get('/admin/reports?from=2026-10-15&to=2026-10-15')->assertOk()->assertSee('Inactive')
            ->assertViewHas('inventorySummary', fn ($summary) => (int) $summary->item_count === 1)
            ->assertViewHas('retailGross', fn ($gross) => (float) $gross === 100.0);
        $this->travelBack();
    }
}
