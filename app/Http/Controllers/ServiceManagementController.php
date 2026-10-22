<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceManagementController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        return view('admin.services.index', [
            'services' => Service::query()->when($search, fn($q) => $q->where(fn($q) => $q->where('name','like',"%{$search}%")->orWhere('category','like',"%{$search}%")))->orderBy('category')->orderBy('name')->paginate(12)->withQueryString(),
            'search' => $search,
            'categories' => Service::query()->distinct()->orderBy('category')->pluck('category'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Service::create($this->validated($request));

        return back()->with('status', 'Service created.');
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $service->update($this->validated($request));

        return back()->with('status', 'Service updated.');
    }

    public function toggle(Service $service): RedirectResponse
    {
        $service->update(['is_active' => ! $service->is_active]);

        return back()->with('status', $service->is_active ? 'Service is now active.' : 'Service has been hidden.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'category' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:1000'],
            'duration' => ['nullable', 'integer', 'min:5', 'max:600'],
            'session_count' => ['required', 'integer', 'min:1', 'max:24'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
        ]);
    }
}
