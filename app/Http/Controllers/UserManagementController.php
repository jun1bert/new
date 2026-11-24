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
            'users' => User::query()->when($request->user()->user_type === 'owner', fn ($q) => $q->where('user_type', '!=', 'administrator'))->when($search, fn($q) => $q->where(fn($q) => $q->where('name','like',"%{$search}%")->orWhere('email','like',"%{$search}%")))->latest()->paginate(12)->withQueryString(),
            'search' => $search,
            'roles' => $this->manageableRoles($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'user_type' => ['required', Rule::in($this->manageableRoles($request))],
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
        $this->authorizeTarget($request, $user);
        $data = $request->validate([
            'user_type' => ['required', Rule::in($this->manageableRoles($request))],
        ]);

        if ($user->is($request->user()) && $data['user_type'] !== $user->user_type) {
            return back()->withErrors(['user_type' => 'You cannot change your own role.']);
        }

        $user->update(['user_type' => $data['user_type']]);

        return back()->with('status', "{$user->name}'s role was updated.");
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeTarget($request, $user);
        $request->merge(['_editing_user_id' => (string) $user->id]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'user_type' => ['required', Rule::in($this->manageableRoles($request))],
            'is_active' => ['required', 'boolean'],
        ]);

        if ($user->is($request->user()) && (! $data['is_active'] || $data['user_type'] !== $user->user_type)) {
            return back()->withErrors(['user_type' => 'You cannot deactivate yourself or change your own role.']);
        }

        $user->update($data);
        return back()->with('status', "{$user->name}'s account was updated.");
    }

    private function manageableRoles(Request $request): array
    {
        return $request->user()->user_type === 'administrator'
            ? ['member', 'staff', 'owner', 'administrator'] : ['member', 'staff', 'owner'];
    }

    private function authorizeTarget(Request $request, User $user): void
    {
        abort_if($request->user()->user_type === 'owner' && $user->user_type === 'administrator', 403);
    }
}
