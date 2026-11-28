<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function updateHealth(Request $request): RedirectResponse
    {
        $details = $request->validate([
            'allergies' => ['nullable', 'string', 'max:2000'],
            'medical_conditions' => ['nullable', 'string', 'max:2000'],
            'medications' => ['nullable', 'string', 'max:2000'],
            'skin_concerns' => ['nullable', 'string', 'max:2000'],
            'other_information' => ['nullable', 'string', 'max:2000'],
        ]);
        $request->user()->healthProfile()->updateOrCreate([], ['details' => $details]);

        return Redirect::route('profile.edit')->with('status', 'health-profile-updated');
    }

    public function updatePhoto(Request $request): RedirectResponse
    {
        $request->validate(['photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048']]);
        $user = $request->user();
        $oldPath = $user->profile_photo_path;
        $path = $request->file('photo')->store('profile-photos', 'local');
        if (!$path) throw new \RuntimeException('Unable to save profile photo.');
        $user->forceFill(['profile_photo_path' => $path])->save();
        if ($oldPath) Storage::disk('local')->delete($oldPath);
        return Redirect::route('profile.edit')->with('status', 'profile-photo-updated');
    }

    public function photo(Request $request)
    {
        $path = $request->user()->profile_photo_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);
        return Storage::disk('local')->response($path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function deletePhoto(Request $request): RedirectResponse
    {
        $user = $request->user();
        $path = $user->profile_photo_path;
        $user->forceFill(['profile_photo_path' => null])->save();
        if ($path) Storage::disk('local')->delete($path);
        return Redirect::route('profile.edit')->with('status', 'profile-photo-removed');
    }

}
