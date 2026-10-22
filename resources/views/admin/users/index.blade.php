<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.2em] text-[#a48d78]">Administration</p>
            <h2 class="mt-1 font-serif text-3xl text-[#4d4037]">User management</h2>
        </div>
    </x-slot>

    <div class="py-10">
        <div x-data="{ userOpen: {{ $errors->any() ? 'true' : 'false' }} }" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <section x-cloak x-show="userOpen" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-[#4d4037]/50 p-4 backdrop-blur-sm" @keydown.escape.window="userOpen = false">
                <div @click.outside="userOpen = false" class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
                <h3 class="font-serif text-2xl text-[#4d4037]">Create an account</h3>
                <p class="mt-2 text-sm leading-6 text-[#66584e]">Create staff, member, or administrator accounts. Only administrators can manage roles.</p>

                <form method="POST" action="{{ route('admin.users.store') }}" class="mt-6 space-y-4">
                    @csrf
                    <div>
                        <label for="name" class="text-sm font-semibold text-[#4d4037]">Full name</label>
                        <input id="name" name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#a48d78] focus:ring-[#a48d78]" />
                    </div>
                    <div>
                        <label for="email" class="text-sm font-semibold text-[#4d4037]">Email address</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#a48d78] focus:ring-[#a48d78]" />
                    </div>
                    <div>
                        <label for="user_type" class="text-sm font-semibold text-[#4d4037]">User type</label>
                        <select id="user_type" name="user_type" required class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#a48d78] focus:ring-[#a48d78]">
                            @foreach($roles as $role)
                                <option value="{{ $role }}" @selected(old('user_type', 'staff') === $role)>{{ ucfirst($role) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="password" class="text-sm font-semibold text-[#4d4037]">Temporary password</label>
                        <input id="password" type="password" name="password" required class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#a48d78] focus:ring-[#a48d78]" />
                    </div>
                    <div>
                        <label for="password_confirmation" class="text-sm font-semibold text-[#4d4037]">Confirm password</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#a48d78] focus:ring-[#a48d78]" />
                    </div>
                    <button class="w-full rounded-lg bg-[#a48d78] px-4 py-3 text-sm font-bold text-white transition hover:bg-[#8f7663]">Create user</button>
                </form>
                </div>
            </section>

            <section>
                @if(session('status'))
                    <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
                @endif
                @if($errors->any())
                    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif
                <div class="overflow-hidden rounded-2xl border border-[#a48d78]/20 bg-white shadow-sm">
                    <div class="flex flex-wrap items-end justify-between gap-4 border-b border-[#a48d78]/15 px-6 py-5">
                        <div><h3 class="font-serif text-3xl text-[#4d4037]">Users</h3><p class="mt-1 text-sm text-[#66584e]">{{ $users->total() }} registered user{{ $users->total() === 1 ? '' : 's' }}</p></div>
                        <button type="button" @click="userOpen = true" class="rounded-lg bg-[#a48d78] px-5 py-3 text-sm font-bold text-white transition hover:bg-[#8f7663]">+ Create user</button>
                    </div>
                    <form method="GET" class="border-b border-[#a48d78]/15 px-6 py-4"><input name="search" value="{{ $search }}" placeholder="Search users by name or email" class="w-full max-w-md rounded-lg border-gray-300" /></form>
                    <div class="divide-y divide-[#a48d78]/10">
                        @forelse($users as $managedUser)
                            <div x-data="{ editOpen: false }" class="px-6 py-5">
                                <button type="button" @click="editOpen = true" class="flex w-full flex-col gap-3 text-left sm:flex-row sm:items-center sm:justify-between">
                                    <div><p class="font-semibold text-[#4d4037]">{{ $managedUser->name }} @if($managedUser->is(auth()->user()))<span class="text-xs font-normal text-[#66584e]">(you)</span>@endif</p><p class="mt-1 text-sm text-[#66584e]">{{ $managedUser->email }}</p></div>
                                    <div class="flex items-center gap-3"><span class="rounded-full px-3 py-1 text-xs font-bold {{ $managedUser->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-stone-100 text-stone-500' }}">{{ $managedUser->is_active ? 'Active' : 'Deactivated' }}</span><span class="rounded-full bg-[#f5f1eb] px-3 py-1 text-xs font-bold text-[#66584e]">{{ ucfirst($managedUser->user_type) }}</span></div>
                                </button>
                                <div x-cloak x-show="editOpen" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-[#4d4037]/50 p-4 backdrop-blur-sm" @keydown.escape.window="editOpen = false">
                                    <div @click.outside="editOpen = false" class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
                                        <div class="flex items-start justify-between gap-4"><div><h3 class="font-serif text-2xl text-[#4d4037]">Edit user</h3><p class="mt-1 text-sm text-[#66584e]">Update account access and details.</p></div><button type="button" @click="editOpen = false" class="p-2 text-[#66584e]" aria-label="Close">✕</button></div>
                                        <form method="POST" action="{{ route('admin.users.update', $managedUser) }}" class="mt-6 space-y-4">@csrf @method('PATCH')
                                            <div><label class="text-sm font-semibold text-[#4d4037]">Full name</label><input name="name" value="{{ $managedUser->name }}" required class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#a48d78] focus:ring-[#a48d78]" /></div>
                                            <div><label class="text-sm font-semibold text-[#4d4037]">Email address</label><input name="email" type="email" value="{{ $managedUser->email }}" required class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#a48d78] focus:ring-[#a48d78]" /></div>
                                            <div><label class="text-sm font-semibold text-[#4d4037]">Role</label><select name="user_type" class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#a48d78] focus:ring-[#a48d78]">@foreach($roles as $role)<option value="{{ $role }}" @selected($managedUser->user_type === $role)>{{ ucfirst($role) }}</option>@endforeach</select></div>
                                            <div><label class="text-sm font-semibold text-[#4d4037]">Account status</label><select name="is_active" class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#a48d78] focus:ring-[#a48d78]"><option value="1" @selected($managedUser->is_active)>Active</option><option value="0" @selected(! $managedUser->is_active)>Deactivated</option></select></div>
                                            <button class="w-full rounded-lg bg-[#4d4037] px-4 py-3 text-sm font-bold text-white">Save changes</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="px-6 py-8 text-sm text-[#66584e]">No user accounts yet.</p>
                        @endforelse
                    </div>
                </div>
                @if($users->hasPages())
                    <div class="mt-5">{{ $users->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
