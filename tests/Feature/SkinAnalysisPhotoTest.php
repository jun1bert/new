<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\SkinAnalysisPhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class SkinAnalysisPhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_manage_private_photos_for_a_booked_service(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create(['user_type' => 'administrator']);
        $client = User::factory()->create();
        $service = Service::create(['name' => 'Facial', 'category' => 'Skin', 'price' => 500, 'session_count' => 1, 'is_active' => true]);
        $other = Service::create(['name' => 'Other', 'category' => 'Skin', 'price' => 500, 'session_count' => 1, 'is_active' => true]);
        $appointment = Appointment::create(['treatment_course_id' => (string) Str::uuid(), 'user_id' => $client->id, 'service_id' => $service->id, 'session_number' => 1, 'scheduled_at' => now(), 'status' => 'completed']);
        $url = route('admin.skin-analysis.store', [$appointment, $service]);
        $this->actingAs($owner)->post($url, ['type' => 'before', 'photo' => new UploadedFile(public_path('images/favicon.png'), 'result.png', 'image/png', null, true)])->assertRedirect();
        $photo = SkinAnalysisPhoto::firstOrFail();
        Storage::disk('local')->assertExists($photo->path);
        $this->get(route('studio.clients.show', $client))->assertOk()->assertSee('Skin analyzer results');
        $this->get(route('admin.skin-analysis.show', $photo))->assertOk();
        $this->post(route('admin.skin-analysis.store', [$appointment, $other]), ['type' => 'after', 'photo' => new UploadedFile(public_path('images/favicon.png'), 'result.png', 'image/png', null, true)])->assertNotFound();
        $oldPath = $photo->path;
        $this->post($url, ['type' => 'before', 'photo' => new UploadedFile(public_path('images/favicon.png'), 'result.png', 'image/png', null, true)])->assertRedirect();
        Storage::disk('local')->assertMissing($oldPath);
        $photo->refresh();
        $this->actingAs(User::factory()->create(['user_type' => 'staff']))->get(route('admin.skin-analysis.show', $photo))->assertForbidden();
        $this->actingAs($client)->get(route('admin.skin-analysis.show', $photo))->assertForbidden();
        $this->actingAs($owner)->delete(route('admin.skin-analysis.destroy', $photo))->assertRedirect();
        Storage::disk('local')->assertMissing($photo->path);
        $this->assertDatabaseCount('skin_analysis_photos', 0);
    }
}

