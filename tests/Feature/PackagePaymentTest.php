<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PackagePaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_courses_require_owner_review_before_package_payments(): void
    {
        $owner = User::factory()->create(['user_type' => 'administrator']);
        $staff = User::factory()->create(['user_type' => 'staff']);
        $client = User::factory()->create();
        $service = Service::create(['name' => 'Old package', 'category' => 'Skin', 'price' => 3000, 'session_count' => 2, 'is_active' => true]);
        $course = (string) Str::uuid();
        $first = Appointment::create(['treatment_course_id' => $course, 'user_id' => $client->id, 'service_id' => $service->id, 'session_number' => 1, 'scheduled_at' => now(), 'status' => 'completed']);
        \App\Models\Payment::create(['appointment_id' => $first->id, 'amount' => 1000, 'method' => 'cash', 'paid_at' => now()]);
        $next = Appointment::create(['treatment_course_id' => $course, 'user_id' => $client->id, 'service_id' => $service->id, 'session_number' => 2, 'scheduled_at' => now(), 'status' => 'scheduled']);
        $this->actingAs($staff)->patch(route('studio.appointments.complete', $next), ['amount' => 100, 'method' => 'cash'])->assertSessionHasErrors('amount');
        $this->post(route('admin.appointments.review-legacy-balance', $next), ['total' => 3000, 'opening_credit' => 1000])->assertForbidden();
        $this->actingAs($owner)->get(route('studio.appointments.index'))->assertOk()->assertSee('Review existing course payments');
        $this->post(route('admin.appointments.review-legacy-balance', $next), ['total' => 3000, 'opening_credit' => 1000])->assertSessionHasNoErrors();
        $this->assertSame(200000, $next->fresh()->invoice->balance_cents);
        $this->patch(route('studio.appointments.complete', $next), ['amount' => 500, 'method' => 'cash'])->assertSessionHasNoErrors();
        $this->assertSame(150000, $next->fresh()->invoice->balance_cents);
        $this->assertDatabaseCount('payments', 2);
    }

    public function test_installments_share_a_package_balance_across_sessions(): void
    {
        $owner = User::factory()->create(['user_type' => 'administrator']);
        $client = User::factory()->create();
        $service = Service::create(['name' => 'Three session package', 'category' => 'Skin', 'price' => 3000, 'session_count' => 3, 'is_active' => true]);
        $course = (string) Str::uuid();
        $first = Appointment::create(['treatment_course_id' => $course, 'user_id' => $client->id, 'service_id' => $service->id, 'session_number' => 1, 'scheduled_at' => now(), 'status' => 'scheduled']);
        $this->actingAs($owner)->patch(route('studio.appointments.complete', $first), ['amount' => 1000, 'method' => 'cash'])->assertSessionHasNoErrors();
        $this->assertSame(200000, $first->invoice->balance_cents);
        $this->get(route('admin.reports.index'))->assertOk()->assertSee($client->name)->assertSee('2,000.00');
        $this->post(route('studio.appointments.balance-payments', $first), ['amount' => 2500, 'method' => 'cash'])->assertSessionHasErrors('amount');
        $this->post(route('studio.appointments.balance-payments', $first), ['amount' => 500, 'method' => 'cash'])->assertSessionHasNoErrors();
        $second = Appointment::create(['treatment_course_id' => $course, 'user_id' => $client->id, 'service_id' => $service->id, 'session_number' => 2, 'scheduled_at' => now(), 'status' => 'scheduled']);
        $this->patch(route('studio.appointments.complete', $second), ['amount' => 1500, 'method' => 'cash'])->assertSessionHasNoErrors();
        $this->assertSame(0, $second->invoice->balance_cents);
        $this->assertSame('3000.00', $second->invoice->total);
        $this->assertDatabaseCount('payments', 3);
        $this->get(route('studio.appointments.index'))->assertOk()->assertSee('Balance: PHP');
        $this->get(route('admin.reports.index'))->assertOk()->assertSee('Outstanding package balances');
        $this->get(route('studio.payments.receipt', $first->payments()->first()))->assertOk()->assertSee('Current balance')->assertDontSee('recorded payment differs');
        $this->actingAs($client)->get(route('member.payments.receipt', $first->payments()->first()))->assertOk();
        $this->actingAs(User::factory()->create())->get(route('member.payments.receipt', $first->payments()->first()))->assertNotFound();
    }
}
