<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModalFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_edit_errors_reopen_the_edit_dialog_with_the_entered_values(): void
    {
        $this->actingAs(User::factory()->create(['user_type' => 'administrator']));
        $staff = User::factory()->create(['user_type' => 'staff']);
        $this->from('/admin/users')->patch(route('admin.users.update', $staff), [
            'name' => 'Updated staff name', 'email' => 'invalid', 'user_type' => 'staff', 'is_active' => '1',
        ])->assertSessionHasErrors('email')->assertSessionHasInput('_editing_user_id', (string) $staff->id);
        $this->get('/admin/users')->assertOk()->assertSee('userOpen: false', false)->assertSee('editOpen: true', false)
            ->assertSee('value="Updated staff name"', false)->assertSee('value="invalid"', false)
            ->assertSee('id="portal-validation-feedback"', false)->assertSee('portal-feedback-error');
    }

    public function test_user_edit_success_is_rendered_in_a_confirmation_dialog(): void
    {
        $this->actingAs(User::factory()->create(['user_type' => 'administrator']));
        $staff = User::factory()->create(['user_type' => 'staff']);
        $this->from('/admin/users')->patch(route('admin.users.update', $staff), [
            'name' => 'Updated staff name', 'email' => $staff->email, 'user_type' => 'staff', 'is_active' => '1',
        ])->assertSessionHasNoErrors()->assertRedirect('/admin/users');
        $this->get('/admin/users')->assertOk()->assertSee('portal-feedback-success')->assertSee('feedback-success-title')
            ->assertSee('Updated staff name&#039;s account was updated.', false)->assertDontSee('border-emerald-200', false);
    }

    public function test_inventory_errors_reopen_the_correct_operation(): void
    {
        $this->actingAs(User::factory()->create(['user_type' => 'administrator']));
        $product = Product::create(['name' => 'Serum', 'type' => 'retail', 'stock_quantity' => 1]);
        $this->from('/admin/products')->patch(route('admin.products.stock.adjust', $product), ['quantity' => -2])
            ->assertSessionHasErrors('quantity')->assertSessionHasInput('_adjusting_product_id', (string) $product->id);
        $this->get('/admin/products')->assertOk()->assertSee('addOpen: false', false)->assertSee('adjustOpen: true', false)->assertSee('portal-validation-feedback');
        $this->from('/admin/products')->post(route('admin.products.store'), ['name' => 'New item'])
            ->assertSessionHasErrors()->assertSessionHasInput('_creating_product', '1');
        $this->get('/admin/products')->assertOk()->assertSee('addOpen: true', false)->assertSee('adjustOpen: false', false);
    }
}
