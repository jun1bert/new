<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SalesStaffTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_credits_selected_sales_staff_and_owner_can_see_totals(): void
    {
        $owner = User::factory()->create(['user_type' => 'administrator']);
        $seller = User::factory()->create(['user_type' => 'staff', 'name' => 'Sales Person']);
        $provider = User::factory()->create(['user_type' => 'staff']);
        $client = User::factory()->create();
        $service = Service::create(['name' => 'Facial', 'category' => 'Skin', 'price' => 500, 'session_count' => 1, 'is_active' => true]);
        $appointment = Appointment::create(['treatment_course_id' => (string) Str::uuid(), 'user_id' => $client->id, 'service_id' => $service->id, 'provider_id' => $provider->id, 'session_number' => 1, 'scheduled_at' => now(), 'status' => 'scheduled']);
        $this->actingAs($owner)->patch(route('studio.appointments.complete', $appointment), ['amount' => 450, 'discount_amount' => 50, 'method' => 'cash', 'sales_staff_id' => $client->id])->assertSessionHasErrors('sales_staff_id');
        $this->assertDatabaseCount('payments', 0);
        $this->patch(route('studio.appointments.complete', $appointment), ['amount' => 450, 'discount_amount' => 50, 'method' => 'cash', 'sales_staff_id' => $seller->id])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('payments', ['appointment_id' => $appointment->id, 'sales_staff_id' => $seller->id, 'amount' => 450]);
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'provider_id' => $provider->id]);
        $this->get(route('admin.reports.index'))->assertOk()->assertSee('Sales Person')->assertSee('450.00');
        $this->actingAs($seller)->get(route('admin.reports.index'))->assertForbidden();
    }
}
