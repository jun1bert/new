<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_photo_can_be_uploaded_viewed_and_removed(): void
    {
        \Storage::fake('local');
        $user = User::factory()->create();
        $photo = \Illuminate\Http\UploadedFile::fake()->createWithContent('portrait.jpg', file_get_contents(public_path('images/facial-ritual.jpg')));
        $this->actingAs($user)->post(route('profile.photo.update'), ['photo' => $photo])->assertRedirect(route('profile.edit'));
        $path = $user->fresh()->profile_photo_path;
        \Storage::disk('local')->assertExists($path);
        $this->get(route('profile.photo'))->assertOk();
        $this->delete(route('profile.photo.destroy'))->assertRedirect();
        \Storage::disk('local')->assertMissing($path);
        $this->assertNull($user->fresh()->profile_photo_path);
    }

    public function test_health_profile_is_private_encrypted_and_can_be_updated(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($user)->patch(route('profile.health.update'), ['allergies' => 'Latex sensitivity', 'medications' => 'Example medicine'])->assertRedirect(route('profile.edit'));
        $this->assertSame('Latex sensitivity', $user->fresh()->healthProfile->details['allergies']);
        $this->assertStringNotContainsString('Latex sensitivity', \DB::table('health_profiles')->where('user_id', $user->id)->value('details'));
        $this->actingAs($other)->get(route('profile.edit'))->assertDontSee('Latex sensitivity');
        $this->actingAs($user)->patch(route('profile.health.update'), ['allergies' => ''])->assertRedirect();
        $this->assertNull($user->fresh()->healthProfile->details['allergies']);
    }

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_self_service_account_deletion_is_not_exposed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response->assertStatus(405);
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh());
    }

    public function test_deletion_attempt_with_wrong_password_cannot_remove_an_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response->assertStatus(405);

        $this->assertNotNull($user->fresh());
    }
}
