<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_access_business_and_studio_pages_but_staff_cannot_access_management(): void
    {
        $owner = User::factory()->create(['user_type' => 'owner']);
        $staff = User::factory()->create(['user_type' => 'staff']);
        $this->actingAs($owner)->get('/dashboard')->assertOk()->assertSee('Studio overview');
        $this->get('/admin/reports')->assertOk();
        $this->get('/studio/clients')->assertOk();
        $this->actingAs($staff)->get('/admin/reports')->assertForbidden();
        $this->get('/studio/clients')->assertOk();
    }

    public function test_owners_can_manage_owners_but_only_administrators_can_assign_administrator(): void
    {
        $owner = User::factory()->create(['user_type' => 'owner']);
        $admin = User::factory()->create(['user_type' => 'administrator']);
        $staff = User::factory()->create(['user_type' => 'staff']);
        $this->actingAs($owner)->patch(route('admin.users.role.update', $admin), ['user_type' => 'staff'])->assertForbidden();
        $this->patch(route('admin.users.role.update', $staff), ['user_type' => 'administrator'])->assertSessionHasErrors('user_type');
        $this->assertSame('staff', $staff->fresh()->user_type);
        $this->get(route('admin.users.index'))->assertOk()->assertDontSee('value="administrator"', false)->assertSee('value="owner"', false);
        $this->patch(route('admin.users.role.update', $staff), ['user_type' => 'owner'])->assertSessionHasNoErrors();
        $this->assertSame('owner', $staff->fresh()->user_type);
        $this->patch(route('admin.users.role.update', $staff), ['user_type' => 'staff'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->patch(route('admin.users.role.update', $staff), ['user_type' => 'owner'])->assertSessionHasNoErrors();
        $this->assertSame('owner', $staff->fresh()->user_type);
        $this->get('/admin/reports')->assertOk();
    }
}
