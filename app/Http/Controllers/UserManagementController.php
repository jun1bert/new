<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        return view('admin.users.index', [
            'users' => User::query()->where('user_type', '!=', 'administrator')->when($search, fn($q) => $q->where(fn($q) => $q->where('name','like',"%{$search}%")->orWhere('email','like',"%{$search}%")))->latest()->paginate(12)->withQueryString(),
            'search' => $search,
            'roles' => ['member', 'staff', 'administrator'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'user_type' => ['required', Rule::in(['member', 'staff', 'administrator'])],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'user_type' => $data['user_type'],
            'password' => Hash::make($data['password']),
        ]);

        return back()->with('status', 'User account created.');
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'user_type' => ['required', Rule::in(['member', 'staff', 'administrator'])],
        ]);

        if ($user->is($request->user()) && $data['user_type'] !== 'administrator') {
            return back()->withErrors(['user_type' => 'You cannot remove your own administrator access.']);
        }

        $user->update(['user_type' => $data['user_type']]);

        return back()->with('status', "{$user->name}'s role was updated.");
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $request->merge(['_editing_user_id' => (string) $user->id]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'user_type' => ['required', Rule::in(['member', 'staff', 'administrator'])],
            'is_active' => ['required', 'boolean'],
        ]);

        if ($user->is($request->user()) && (! $data['is_active'] || $data['user_type'] !== 'administrator')) {
            return back()->withErrors(['user_type' => 'You cannot deactivate yourself or remove your own administrator access.']);
        }

        $user->update($data);
        return back()->with('status', "{$user->name}'s account was updated.");
    }
}
