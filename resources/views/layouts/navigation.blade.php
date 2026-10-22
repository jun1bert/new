<div x-data="{ sidebarOpen: false }">
    {{-- Mobile top bar --}}
    <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-[#a48d78]/15 bg-white/95 px-4 backdrop-blur lg:hidden">
        <a href="{{ route('dashboard') }}" class="leading-none text-[#4d4037]">
            <span class="block font-serif text-xl tracking-[.14em]">LUNELLE</span>
            <span class="block mt-1 text-[7px] font-bold tracking-[.28em] text-[#a48d78]">AESTHETICS</span>
        </a>
        <button type="button" @click="sidebarOpen = true" class="grid size-10 place-items-center rounded-xl border border-[#a48d78]/25 text-[#4d4037]" aria-label="Open navigation">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" /></svg>
        </button>
    </header>

    {{-- Mobile overlay --}}
    <div x-cloak x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-[#4d4037]/35 lg:hidden"></div>

    {{-- Desktop sidebar / mobile drawer --}}
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-[#a48d78]/15 bg-[#faf9f6] px-4 py-6 shadow-2xl shadow-[#4d4037]/10 transition-transform duration-300 lg:w-64 lg:translate-x-0 lg:shadow-none">
        <div class="flex items-start justify-between px-3">
            <a href="{{ route('dashboard') }}" class="leading-none text-[#4d4037]" @click="sidebarOpen = false">
                <span class="block font-serif text-2xl tracking-[.14em]">LUNELLE</span>
                <span class="block mt-1 text-[8px] font-bold tracking-[.30em] text-[#a48d78]">AESTHETICS</span>
            </a>
            <button type="button" @click="sidebarOpen = false" class="grid size-9 place-items-center rounded-lg text-[#66584e] lg:hidden" aria-label="Close navigation">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18" /></svg>
            </button>
        </div>

        <div class="mt-10 px-2">
            <p class="mb-3 px-3 text-[10px] font-bold uppercase tracking-[.18em] text-[#a48d78]">Workspace</p>
            <nav class="space-y-1">
                <a href="{{ route('dashboard') }}" @click="sidebarOpen = false" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition {{ request()->routeIs('dashboard') ? 'bg-[#ded0bc] text-[#4d4037]' : 'text-[#66584e] hover:bg-[#eee8de] hover:text-[#4d4037]' }}">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10.5 12 3l9 7.5v9a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 19.5v-9Z"/><path stroke-linecap="round" d="M9 21v-6h6v6"/></svg>
                    Dashboard
                </a>
                @if(in_array(Auth::user()->user_type, ['staff', 'administrator'], true))
                    <a href="{{ route('studio.appointments.index') }}" @click="sidebarOpen = false" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition {{ request()->routeIs('studio.appointments.*') ? 'bg-[#ded0bc] text-[#4d4037]' : 'text-[#66584e] hover:bg-[#eee8de] hover:text-[#4d4037]' }}">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="16" rx="2"/><path stroke-linecap="round" d="M7 3v4M17 3v4M3 10h18"/></svg>
                        Sessions
                    </a>
                    <a href="{{ route('studio.clients.index') }}" @click="sidebarOpen = false" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition {{ request()->routeIs('studio.clients.*') ? 'bg-[#ded0bc] text-[#4d4037]' : 'text-[#66584e] hover:bg-[#eee8de] hover:text-[#4d4037]' }}">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="7" r="3"/><path stroke-linecap="round" d="M3.5 21v-1.5A4.5 4.5 0 0 1 8 15h2a4.5 4.5 0 0 1 4.5 4.5V21M16 8h5M18.5 5.5v5"/></svg>
                        Client records
                    </a>
                    <a href="{{ route('studio.gallery.index') }}" @click="sidebarOpen = false" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition {{ request()->routeIs('studio.gallery.*') ? 'bg-[#ded0bc] text-[#4d4037]' : 'text-[#66584e] hover:bg-[#eee8de] hover:text-[#4d4037]' }}">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9" r="1.5"/><path stroke-linecap="round" stroke-linejoin="round" d="m21 15-4.5-4.5L7 20"/></svg>
                        Gallery
                    </a>
                @endif
                @if(Auth::user()->user_type === 'administrator')
                    <p class="mb-3 mt-6 px-3 text-[10px] font-bold uppercase tracking-[.18em] text-[#a48d78]">Management</p>
                    <a href="{{ route('admin.claims.index') }}" @click="sidebarOpen = false" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition {{ request()->routeIs('admin.claims.*') ? 'bg-[#ded0bc] text-[#4d4037]' : 'text-[#66584e] hover:bg-[#eee8de] hover:text-[#4d4037]' }}">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z"/><path stroke-linecap="round" stroke-linejoin="round" d="m8.5 12 2.2 2.2 4.8-5"/></svg>
                        Claim requests
                    </a>
                    <a href="{{ route('admin.services.index') }}" @click="sidebarOpen = false" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition {{ request()->routeIs('admin.services.*') ? 'bg-[#ded0bc] text-[#4d4037]' : 'text-[#66584e] hover:bg-[#eee8de] hover:text-[#4d4037]' }}">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7.5h16M4 12h16M4 16.5h10"/><circle cx="18" cy="16.5" r="2"/></svg>
                        Services
                    </a>
                    <a href="{{ route('admin.products.index') }}" @click="sidebarOpen = false" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition {{ request()->routeIs('admin.products.*') ? 'bg-[#ded0bc] text-[#4d4037]' : 'text-[#66584e] hover:bg-[#eee8de] hover:text-[#4d4037]' }}"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 7.5 12 12l8-4.5M12 12v9"/></svg>Inventory</a>
                    <a href="{{ route('admin.users.index') }}" @click="sidebarOpen = false" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition {{ request()->routeIs('admin.users.*') ? 'bg-[#ded0bc] text-[#4d4037]' : 'text-[#66584e] hover:bg-[#eee8de] hover:text-[#4d4037]' }}">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16 20v-1.5A3.5 3.5 0 0 0 12.5 15h-5A3.5 3.5 0 0 0 4 18.5V20"/><circle cx="10" cy="7" r="3"/><path stroke-linecap="round" d="M17 8h4M19 6v4"/></svg>
                        Users
                    </a>
                    <p class="mb-2 mt-6 px-3 text-[10px] font-bold uppercase tracking-[.18em] text-[#a48d78]">Loyalty</p>
                    <p class="px-3 text-xs leading-5 text-[#a48d78]">Coming soon</p>
                @endif
            </nav>
        </div>

        <div class="mt-auto border-t border-[#a48d78]/15 px-2 pt-5">
            <a href="{{ route('profile.edit') }}" @click="sidebarOpen = false" class="flex items-center gap-3 rounded-xl px-3 py-3 transition hover:bg-[#eee8de]">
                <span class="grid size-9 place-items-center rounded-full bg-[#ded0bc] text-sm font-bold text-[#4d4037]">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                <span class="min-w-0">
                    <span class="block truncate text-sm font-semibold text-[#4d4037]">{{ Auth::user()->name }}</span>
                    <span class="block text-[10px] font-bold uppercase tracking-wider text-[#a48d78]">{{ Auth::user()->user_type }}</span>
                </span>
            </a>
            <form method="POST" action="{{ route('logout') }}" class="mt-2">
                @csrf
                <button class="flex w-full items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold text-[#66584e] transition hover:bg-[#eee8de] hover:text-[#4d4037]">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M14 8V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2-2v-2"/><path stroke-linecap="round" stroke-linejoin="round" d="m17 16 4-4-4-4M21 12H9"/></svg>
                    Log out
                </button>
            </form>
        </div>
    </aside>
</div>
