<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\SkinAnalysisPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SkinAnalysisPhotoController extends Controller
{
    public function store(Request $request, Appointment $appointment, Service $service)
    {
        abort_unless($appointment->booked_services->contains('id', $service->id), 404);
        $data = $request->validate([
            'type' => ['required', 'in:before,after'],
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);
        $path = $request->file('photo')->store('skin-analysis', 'local');
        abort_if($path === false, 500, 'Could not save the photo.');
        $photo = SkinAnalysisPhoto::firstOrNew(['appointment_id' => $appointment->id, 'service_id' => $service->id, 'type' => $data['type']]);
        $oldPath = $photo->path;
        try {
            $photo->path = $path;
            $photo->save();
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }
        if ($oldPath) Storage::disk('local')->delete($oldPath);
        return back()->with('status', 'Skin analyzer photo saved.');
    }

    public function show(SkinAnalysisPhoto $photo): BinaryFileResponse
    {
        abort_unless(Storage::disk('local')->exists($photo->path), 404);
        return response()->file(Storage::disk('local')->path($photo->path), ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function destroy(SkinAnalysisPhoto $photo)
    {
        $path = $photo->path;
        $photo->delete();
        Storage::disk('local')->delete($path);
        return back()->with('status', 'Skin analyzer photo removed.');
    }
}

