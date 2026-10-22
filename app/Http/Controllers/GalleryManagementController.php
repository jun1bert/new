<?php

namespace App\Http\Controllers;

use App\Models\GalleryImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class GalleryManagementController extends Controller
{
    public function index(): View { return view('studio.gallery.index', ['images' => GalleryImage::latest()->paginate(18)]); }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['images' => ['required', 'array', 'max:10'], 'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'], 'title' => ['nullable', 'string', 'max:120'], 'caption' => ['nullable', 'string', 'max:500']]);
        $directory = public_path('uploads/gallery');
        File::ensureDirectoryExists($directory);
        foreach ($request->file('images') as $upload) {
            $name = $upload->hashName();
            $upload->move($directory, $name);
            GalleryImage::create(['path' => "uploads/gallery/{$name}", 'title' => $data['title'] ?? null, 'caption' => $data['caption'] ?? null]);
        }
        return back()->with('status', 'Gallery image(s) uploaded.');
    }

    public function toggle(GalleryImage $image): RedirectResponse
    {
        $image->update(['is_published' => ! $image->is_published]);
        return back()->with('status', $image->is_published ? 'Image is now visible.' : 'Image is now hidden.');
    }

    public function destroy(GalleryImage $image): RedirectResponse
    {
        File::delete(public_path($image->path));
        $image->delete();
        return back()->with('status', 'Gallery image deleted.');
    }
}
