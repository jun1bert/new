<?php

namespace Tests\Feature;

use App\Models\StaffAttendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    private function scanCurrentCode(): void
    {
        $slot = (string) intdiv(now()->timestamp, 30);
        $this->get(route('attendance.qr.scan', ['slot' => $slot, 'code' => hash_hmac('sha256', $slot, config('app.key'))]))->assertRedirect(route('attendance.qr.confirm'));
    }

    public function test_qr_code_expires_and_staff_must_scan_before_clocking(): void
    {
        $staff = User::factory()->create(['user_type' => 'staff']);
        $slot = (string) intdiv(now()->timestamp, 30);
        $code = hash_hmac('sha256', $slot, config('app.key'));
        $this->get(route('attendance.qr.scan', ['slot' => $slot, 'code' => 'invalid']))->assertStatus(410);
        $this->get(route('attendance.qr.scan', ['slot' => (string) ((int) $slot - 1), 'code' => $code]))->assertStatus(410);
        $this->actingAs($staff)->post(route('studio.attendance.in'))->assertRedirect(route('attendance.qr.confirm'));
        $this->assertDatabaseCount('staff_attendances', 0);
        $this->get(route('attendance.qr.scan', ['slot' => $slot, 'code' => $code]))->assertRedirect(route('attendance.qr.confirm'));
        $this->get(route('attendance.qr.confirm'))->assertOk()->assertSee('Confirm time in');
        $this->post(route('studio.attendance.in'))->assertSessionHasNoErrors();
        $this->get(route('attendance.qr.confirm'))->assertStatus(410);
        $this->post(route('studio.attendance.out'))->assertRedirect(route('attendance.qr.confirm'));
        $this->assertNull(StaffAttendance::first()->clocked_out_at);
    }

    public function test_only_owner_can_display_and_issue_codes(): void
    {
        $staff = User::factory()->create(['user_type' => 'staff']);
        $owner = User::factory()->create(['user_type' => 'administrator']);
        $this->actingAs($staff)->get(route('admin.attendance.qr.current'))->assertForbidden();
        $this->actingAs($owner)->get(route('admin.attendance.qr.display'))->assertOk()->assertSee('Scan to record attendance');
        $this->getJson(route('admin.attendance.qr.current'))->assertOk()->assertJsonStructure(['url', 'refresh_in']);
    }

    public function test_scan_survives_staff_login_but_the_confirmation_window_expires(): void
    {
        $staff = User::factory()->create(['user_type' => 'staff']);
        $slot = (string) intdiv(now()->timestamp, 30);
        $this->get(route('attendance.qr.scan', ['slot' => $slot, 'code' => hash_hmac('sha256', $slot, config('app.key'))]))->assertRedirect(route('attendance.qr.confirm'));
        $this->get(route('attendance.qr.confirm'))->assertRedirect(route('login'));
        $this->post(route('login'), ['email' => $staff->email, 'password' => 'password'])->assertRedirect(route('attendance.qr.confirm'));
        $this->get(route('attendance.qr.confirm'))->assertOk();
        $this->travel(6)->minutes();
        $this->get(route('attendance.qr.confirm'))->assertStatus(410);
        $this->post(route('studio.attendance.in'))->assertRedirect(route('attendance.qr.confirm'));
        $this->assertDatabaseCount('staff_attendances', 0);
    }

    public function test_staff_clock_in_and_out_once_and_members_cannot_clock_in(): void
    {
        $staff = User::factory()->create(['user_type' => 'staff']);
        $this->actingAs($staff);
        $this->scanCurrentCode();
        $this->post(route('studio.attendance.in'))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('staff_attendances', 1);
        $this->scanCurrentCode();
        $this->post(route('studio.attendance.in'))->assertSessionHasErrors('attendance');
        $this->get(route('dashboard'))->assertOk()->assertSee('time out');
        $this->scanCurrentCode();
        $this->post(route('studio.attendance.out'))->assertSessionHasNoErrors();
        $this->assertNotNull(StaffAttendance::first()->clocked_out_at);
        $this->scanCurrentCode();
        $this->post(route('studio.attendance.out'))->assertSessionHasErrors('attendance');
        $this->actingAs(User::factory()->create())->post(route('studio.attendance.in'))->assertForbidden();
    }

    public function test_owner_can_add_and_correct_records_with_an_audit_and_no_overlap(): void
    {
        $owner = User::factory()->create(['user_type' => 'administrator']);
        $staff = User::factory()->create(['user_type' => 'staff']);
        $in = now('Asia/Manila')->subHours(3)->format('Y-m-d\TH:i');
        $out = now('Asia/Manila')->subHours(2)->format('Y-m-d\TH:i');
        $this->actingAs($staff)->get(route('admin.attendance.index'))->assertForbidden();
        $this->actingAs($owner)->post(route('admin.attendance.corrections.store'), ['user_id' => $staff->id, 'clocked_in_at' => $in, 'clocked_out_at' => $out, 'reason' => 'Forgot to clock in'])->assertSessionHasNoErrors();
        $record = StaffAttendance::firstOrFail();
        $this->assertSame('Forgot to clock in', $record->changes()->first()->reason);
        $this->post(route('admin.attendance.corrections.store'), ['user_id' => $staff->id, 'clocked_in_at' => $in, 'clocked_out_at' => $out, 'reason' => 'Duplicate'])->assertSessionHasErrors('clocked_in_at');
        $updatedOut = now('Asia/Manila')->subHour()->format('Y-m-d\TH:i');
        $this->patch(route('admin.attendance.corrections.update', $record), ['clocked_in_at' => $in, 'clocked_out_at' => $updatedOut, 'reason' => 'Correct departure'])->assertSessionHasNoErrors();
        $this->assertSame(2, $record->changes()->count());
        $this->assertNotNull($record->changes()->first()->before);
        $this->get(route('admin.attendance.index', ['date' => now('Asia/Manila')->toDateString()]))->assertOk()->assertSee('Correct departure')->assertSee($staff->name);
    }

    public function test_provider_list_shows_current_attendance_without_blocking_booking(): void
    {
        $staff = User::factory()->create(['user_type' => 'staff']);
        $owner = User::factory()->create(['user_type' => 'administrator']);
        \App\Models\Service::create(['name' => 'Facial', 'category' => 'Skin', 'price' => 100, 'session_count' => 1, 'is_active' => true]);
        $this->actingAs($owner)->get(route('studio.appointments.index'))->assertOk()->assertSee('not currently timed in');
        $this->actingAs($staff)->post(route('studio.attendance.in'))->assertSessionHasNoErrors();
        $this->actingAs($owner)->get(route('studio.appointments.index'))->assertOk()->assertSee('currently timed in');
    }
}
