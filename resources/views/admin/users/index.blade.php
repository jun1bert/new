<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.2em] text-[var(--brand-moss)]">Administration</p>
            <h2 class="mt-1 font-serif text-3xl text-[var(--brand-forest)]">User management</h2>
        </div>
    </x-slot>

    <div class="py-10">
        <div x-data="{ userOpen: {{ $errors->any() && !old('_editing_user_id') ? 'true' : 'false' }} }" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <section x-cloak x-show="userOpen" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-[var(--brand-forest)]/50 p-4 backdrop-blur-sm" @keydown.escape.window="userOpen = false">
                <div @click.outside="userOpen = false" class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-4"><h3 class="font-serif text-2xl text-[var(--brand-forest)]">Create an account</h3><x-modal-close @click="userOpen = false" label="Close create an account" /></div>
                <p class="mt-2 text-sm leading-6 text-[var(--brand-text-muted)]">Create staff, member, or administrator accounts. Only administrators can manage roles.</p>

                <form method="POST" action="{{ route('admin.users.store') }}" class="mt-6 space-y-4">
                    @csrf
                    <div>
                        <label for="name" class="text-sm font-semibold text-[var(--brand-forest)]">Full name</label>
                        <input id="name" name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-lg border-gray-300 focus:border-[var(--brand-moss)] focus:ring-[var(--brand-moss)]" />
                    </div>
                    <div>
                        <label for="email" class="text-sm font-semibold text-[var(--brand-forest)]">Email address</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-lg border-gray-300 focus:border-[var(--brand-moss)] focus:ring-[var(--brand-moss)]" />
                    </div>
                    <div>
                        <label for="user_type" class="text-sm font-semibold text-[var(--brand-forest)]">User type</label>
                        <select id="user_type" name="user_type" required class="mt-1 w-full rounded-lg border-gray-300 focus:border-[var(--brand-moss)] focus:ring-[var(--brand-moss)]">
                            @foreach($roles as $role)
                                <option value="{{ $role }}" @selected(old('user_type', 'staff') === $role)>{{ ucfirst($role) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="password" class="text-sm font-semibold text-[var(--brand-forest)]">Temporary password</label>
                        <input id="password" type="password" name="password" required class="mt-1 w-full rounded-lg border-gray-300 focus:border-[var(--brand-moss)] focus:ring-[var(--brand-moss)]" />
                    </div>
                    <div>
                        <label for="password_confirmation" class="text-sm font-semibold text-[var(--brand-forest)]">Confirm password</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required class="mt-1 w-full rounded-lg border-gray-300 focus:border-[var(--brand-moss)] focus:ring-[var(--brand-moss)]" />
                    </div>
                    <button class="w-full rounded-lg bg-[var(--brand-moss)] px-4 py-3 text-sm font-bold text-white transition hover:bg-[var(--brand-forest)]">Create user</button>
                </form>
                </div>
            </section>

            <section>


                <div class="overflow-hidden rounded-2xl border border-[var(--brand-moss)]/20 bg-white shadow-sm">
                    <div class="flex flex-wrap items-end justify-between gap-4 border-b border-[var(--brand-moss)]/15 px-6 py-5">
                        <div><h3 class="font-serif text-3xl text-[var(--brand-forest)]">Users</h3><p class="mt-1 text-sm text-[var(--brand-text-muted)]">{{ $users->total() }} registered user{{ $users->total() === 1 ? '' : 's' }}</p></div>
                        <button type="button" @click="userOpen = true" class="rounded-lg bg-[var(--brand-moss)] px-5 py-3 text-sm font-bold text-white transition hover:bg-[var(--brand-forest)]">+ Create user</button>
                    </div>
                    <form method="GET" class="border-b border-[var(--brand-moss)]/15 px-6 py-4" role="search"><x-search-field :value="$search" placeholder="Search users by name or email" :clear-url="route('admin.users.index')" /></form>
                    <div class="divide-y divide-[var(--brand-moss)]/10">
                        @forelse($users as $managedUser)
                            @php($editingThisUser = $errors->any() && (string) old('_editing_user_id') === (string) $managedUser->id)
                            <div x-data="{ editOpen: {{ $errors->any() && (string) old('_editing_user_id') === (string) $managedUser->id ? 'true' : 'false' }} }" class="px-6 py-5">
                                <button type="button" @click="editOpen = true" class="flex w-full flex-col gap-3 text-left sm:flex-row sm:items-center sm:justify-between">
                                    <div><p class="font-semibold text-[var(--brand-forest)]">{{ $managedUser->name }} @if($managedUser->is(auth()->user()))<span class="text-xs font-normal text-[var(--brand-text-muted)]">(you)</span>@endif</p><p class="mt-1 text-sm text-[var(--brand-text-muted)]">{{ $managedUser->email }}</p></div>
                                    <div class="flex items-center gap-3"><span class="rounded-full px-3 py-1 text-xs font-bold {{ $managedUser->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-stone-100 text-stone-500' }}">{{ $managedUser->is_active ? 'Active' : 'Deactivated' }}</span><span class="rounded-full bg-[var(--brand-cream)] px-3 py-1 text-xs font-bold text-[var(--brand-text-muted)]">{{ ucfirst($managedUser->user_type) }}</span></div>
                                </button>
                                <div x-cloak x-show="editOpen" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-[var(--brand-forest)]/50 p-4 backdrop-blur-sm" @keydown.escape.window="editOpen = false">
                                    <div @click.outside="editOpen = false" class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
                                        <div class="flex items-start justify-between gap-4"><div><h3 class="font-serif text-2xl text-[var(--brand-forest)]">Edit user</h3><p class="mt-1 text-sm text-[var(--brand-text-muted)]">Update account access and details.</p></div><x-modal-close @click="editOpen = false" label="Close" /></div>
                                        <form method="POST" action="{{ route('admin.users.update', $managedUser) }}" class="mt-6 space-y-4">@csrf @method('PATCH')
                                            <div><label class="text-sm font-semibold text-[var(--brand-forest)]">Full name</label><input name="name" value="{{ $editingThisUser ? old('name', $managedUser->name) : $managedUser->name }}" required class="mt-1 w-full rounded-lg border-gray-300 focus:border-[var(--brand-moss)] focus:ring-[var(--brand-moss)]" /></div>
                                            <div><label class="text-sm font-semibold text-[var(--brand-forest)]">Email address</label><input name="email" type="email" value="{{ $editingThisUser ? old('email', $managedUser->email) : $managedUser->email }}" required class="mt-1 w-full rounded-lg border-gray-300 focus:border-[var(--brand-moss)] focus:ring-[var(--brand-moss)]" /></div>
                                            <div><label class="text-sm font-semibold text-[var(--brand-forest)]">Role</label><select name="user_type" class="mt-1 w-full rounded-lg border-gray-300 focus:border-[var(--brand-moss)] focus:ring-[var(--brand-moss)]">@foreach($roles as $role)<option value="{{ $role }}" @selected(($editingThisUser ? old('user_type') : $managedUser->user_type) === $role)>{{ ucfirst($role) }}</option>@endforeach</select></div>
                                            <div><label class="text-sm font-semibold text-[var(--brand-forest)]">Account status</label><select name="is_active" class="mt-1 w-full rounded-lg border-gray-300 focus:border-[var(--brand-moss)] focus:ring-[var(--brand-moss)]"><option value="1" @selected((string) ($editingThisUser ? old('is_active') : (int) $managedUser->is_active) === '1')>Active</option><option value="0" @selected((string) ($editingThisUser ? old('is_active') : (int) $managedUser->is_active) === '0')>Deactivated</option></select></div>
                                            <button class="w-full rounded-lg bg-[var(--brand-forest)] px-4 py-3 text-sm font-bold text-white">Save changes</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="px-6 py-8 text-sm text-[var(--brand-text-muted)]">No user accounts yet.</p>
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
