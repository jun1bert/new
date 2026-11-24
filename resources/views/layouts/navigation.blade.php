<div x-data="{ sidebarOpen: false, desktop: window.matchMedia('(min-width: 1024px)').matches }" @resize.window="desktop = window.matchMedia('(min-width: 1024px)').matches; if (desktop) sidebarOpen = false" @keydown.escape.window="if (sidebarOpen) { sidebarOpen = false; $refs.menuButton.focus() }">
    {{-- Mobile top bar --}}
    <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-[var(--brand-moss)]/15 bg-white/95 px-4 backdrop-blur lg:hidden">
        <a href="{{ route('dashboard') }}" class="brand-logo brand-logo-mobile" aria-label="Shafer's Skin Lovers Lounge dashboard"><img src="{{ asset('images/branding/shafers-logo.png') }}" alt="Shafer's Skin Lovers Lounge"></a>
        <button type="button" x-ref="menuButton" @click="sidebarOpen = true; $nextTick(() => $refs.closeMenu.focus())" :aria-expanded="sidebarOpen" aria-controls="portal-sidebar" class="grid size-10 place-items-center rounded-xl border border-[var(--brand-moss)]/25 text-[var(--brand-forest)]" aria-label="Open navigation">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" /></svg>
        </button>
    </header>

    {{-- Mobile overlay --}}
    <div x-cloak x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-[var(--brand-forest)]/35 lg:hidden"></div>

    {{-- Desktop sidebar / mobile drawer --}}
    <aside id="portal-sidebar" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" :inert="!desktop && !sidebarOpen" class="portal-sidebar fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-[var(--brand-moss)]/15 bg-[var(--brand-white)] px-4 py-6 shadow-2xl shadow-[var(--brand-forest)]/10 transition-transform duration-300 lg:w-64 lg:translate-x-0 lg:shadow-none">
        <div class="flex items-start justify-between px-3">
            <a href="{{ route('dashboard') }}" class="brand-logo brand-logo-sidebar" @click="sidebarOpen = false" aria-label="Shafer's Skin Lovers Lounge dashboard"><img src="{{ asset('images/branding/shafers-logo.png') }}" alt="Shafer's Skin Lovers Lounge"></a>
            <button type="button" x-ref="closeMenu" @click="sidebarOpen = false; $refs.menuButton.focus()" class="grid size-9 place-items-center rounded-lg text-[var(--brand-text-muted)] lg:hidden" aria-label="Close navigation">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18" /></svg>
            </button>
        </div>

        <div class="sidebar-navigation mt-5 min-h-0 flex-1 overflow-y-auto px-2">
            <p class="mb-3 px-3 text-[10px] font-bold uppercase tracking-[.18em] text-[var(--brand-moss)]">Workspace</p>
            <nav class="space-y-1" aria-label="Workspace navigation">
                <a href="{{ route('dashboard') }}" @click="sidebarOpen = false" @if(request()->routeIs('dashboard')) aria-current="page" @endif class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition {{ request()->routeIs('dashboard') ? 'is-active bg-[var(--brand-neutral)] text-[var(--brand-forest)]' : 'text-[var(--brand-text-muted)] hover:bg-[var(--brand-neutral)] hover:text-[var(--brand-forest)]' }}">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10.5 12 3l9 7.5v9a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 19.5v-9Z"/><path stroke-linecap="round" d="M9 21v-6h6v6"/></svg>
                    Dashboard
                </a>
                @if(in_array(Auth::user()->user_type, ['staff', 'owner', 'administrator'], true))
                    <a href="{{ route('studio.appointments.index') }}" @click="sidebarOpen = false" @if(request()->routeIs('studio.appointments.*')) aria-current="page" @endif class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition {{ request()->routeIs('studio.appointments.*') ? 'is-active bg-[var(--brand-neutral)] text-[var(--brand-forest)]' : 'text-[var(--brand-text-muted)] hover:bg-[var(--brand-neutral)] hover:text-[var(--brand-forest)]' }}">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="16" rx="2"/><path stroke-linecap="round" d="M7 3v4M17 3v4M3 10h18"/></svg>
                        Sessions
                    </a>
                    <a href="{{ route('studio.clients.index') }}" @click="sidebarOpen = false" @if(request()->routeIs('studio.clients.*')) aria-current="page" @endif class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition {{ request()->routeIs('studio.clients.*') ? 'is-active bg-[var(--brand-neutral)] text-[var(--brand-forest)]' : 'text-[var(--brand-text-muted)] hover:bg-[var(--brand-neutral)] hover:text-[var(--brand-forest)]' }}">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="7" r="3"/><path stroke-linecap="round" d="M3.5 21v-1.5A4.5 4.5 0 0 1 8 15h2a4.5 4.5 0 0 1 4.5 4.5V21M16 8h5M18.5 5.5v5"/></svg>
                        Client records
                    </a>
                    <a href="{{ route('studio.gallery.index') }}" @click="sidebarOpen = false" @if(request()->routeIs('studio.gallery.*')) aria-current="page" @endif class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition {{ request()->routeIs('studio.gallery.*') ? 'is-active bg-[var(--brand-neutral)] text-[var(--brand-forest)]' : 'text-[var(--brand-text-muted)] hover:bg-[var(--brand-neutral)] hover:text-[var(--brand-forest)]' }}">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9" r="1.5"/><path stroke-linecap="round" stroke-linejoin="round" d="m21 15-4.5-4.5L7 20"/></svg>
                        Gallery
                    </a>
                @endif
                @if(in_array(Auth::user()->user_type, ['owner', 'administrator'], true))
                    <a href="{{ route('admin.claims.index') }}" @click="sidebarOpen = false" @if(request()->routeIs('admin.claims.*')) aria-current="page" @endif class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition {{ request()->routeIs('admin.claims.*') ? 'is-active bg-[var(--brand-neutral)] text-[var(--brand-forest)]' : 'text-[var(--brand-text-muted)] hover:bg-[var(--brand-neutral)] hover:text-[var(--brand-forest)]' }}">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z"/><path stroke-linecap="round" stroke-linejoin="round" d="m8.5 12 2.2 2.2 4.8-5"/></svg>
                        Claim requests
                    </a>
                    <p class="sidebar-group-label">Studio management</p>                    <a href="{{ route('admin.consents.index') }}" @click="sidebarOpen = false" @if(request()->routeIs('admin.consents.*')) aria-current="page" @endif class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition {{ request()->routeIs('admin.consents.*') ? 'is-active bg-[var(--brand-neutral)] text-[var(--brand-forest)]' : 'text-[var(--brand-text-muted)] hover:bg-[var(--brand-neutral)] hover:text-[var(--brand-forest)]' }}"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 3h9l3 3v15H6V3Z"/><path d="M9 10h6M9 14h6M9 18h4"/></svg>Consent forms</a>

                    
                    <a href="{{ route('admin.reports.index') }}" @click="sidebarOpen = false" @if(request()->routeIs('admin.reports.*')) aria-current="page" @endif class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition {{ request()->routeIs('admin.reports.*') ? 'is-active bg-[var(--brand-neutral)] text-[var(--brand-forest)]' : 'text-[var(--brand-text-muted)] hover:bg-[var(--brand-neutral)] hover:text-[var(--brand-forest)]' }}"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 3v18h17M8 16v-4m5 4V8m5 8V5"/></svg>Reports</a>
                    <a href="{{ route('admin.services.index') }}" @click="sidebarOpen = false" @if(request()->routeIs('admin.services.*')) aria-current="page" @endif class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition {{ request()->routeIs('admin.services.*') ? 'is-active bg-[var(--brand-neutral)] text-[var(--brand-forest)]' : 'text-[var(--brand-text-muted)] hover:bg-[var(--brand-neutral)] hover:text-[var(--brand-forest)]' }}">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7.5h16M4 12h16M4 16.5h10"/><circle cx="18" cy="16.5" r="2"/></svg>
                        Services
                    </a>
                    <a href="{{ route('admin.products.index') }}" @click="sidebarOpen = false" @if(request()->routeIs('admin.products.*')) aria-current="page" @endif class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition {{ request()->routeIs('admin.products.*') ? 'is-active bg-[var(--brand-neutral)] text-[var(--brand-forest)]' : 'text-[var(--brand-text-muted)] hover:bg-[var(--brand-neutral)] hover:text-[var(--brand-forest)]' }}"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 7.5 12 12l8-4.5M12 12v9"/></svg>Inventory</a>
                    <p class="sidebar-group-label">Team &amp; settings</p>
                    <a href="{{ route('admin.users.index') }}" @click="sidebarOpen = false" @if(request()->routeIs('admin.users.*')) aria-current="page" @endif class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition {{ request()->routeIs('admin.users.*') ? 'is-active bg-[var(--brand-neutral)] text-[var(--brand-forest)]' : 'text-[var(--brand-text-muted)] hover:bg-[var(--brand-neutral)] hover:text-[var(--brand-forest)]' }}">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16 20v-1.5A3.5 3.5 0 0 0 12.5 15h-5A3.5 3.5 0 0 0 4 18.5V20"/><circle cx="10" cy="7" r="3"/><path stroke-linecap="round" d="M17 8h4M19 6v4"/></svg>
                        Users
                    </a>
                    <a href="{{ route('admin.attendance.index') }}" @click="sidebarOpen = false" @if(request()->routeIs('admin.attendance.*')) aria-current="page" @endif class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition {{ request()->routeIs('admin.attendance.*') ? 'is-active' : '' }}"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v5l3 2"/></svg>Staff attendance</a>
                    <a href="{{ route('admin.settings.index') }}" @click="sidebarOpen = false" @if(request()->routeIs('admin.settings.*')) aria-current="page" @endif class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition {{ request()->routeIs('admin.settings.*') ? 'is-active' : '' }}">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.4 15a1.6 1.6 0 0 0 .3 1.8l.1.1-1.9 3.2-.2-.1a1.6 1.6 0 0 0-1.7.7l-.1.2h-3.8l-.1-.2a1.6 1.6 0 0 0-1.7-.7l-.2.1-1.9-3.2.1-.1a1.6 1.6 0 0 0 .3-1.8l-.1-.2L5 13v-2l1.5-1.8.1-.2a1.6 1.6 0 0 0-.3-1.8l-.1-.1L8.1 4l.2.1a1.6 1.6 0 0 0 1.7-.7l.1-.2h3.8l.1.2a1.6 1.6 0 0 0 1.7.7l.2-.1 1.9 3.2-.1.1a1.6 1.6 0 0 0-.3 1.8l.1.2L19 11v2l.5 1.8-.1.2Z"/></svg>
                        Settings
                    </a>
                @endif
            </nav>
            @if(Auth::user()->user_type === 'member')
                <div class="portal-care-note"><span aria-hidden="true">&#10022;</span><p>Your next moment of care.</p><a href="{{ url('/#services') }}">Explore treatments &#8594;</a></div>
            @endif
        </div>

        <div class="sidebar-account shrink-0 border-t border-[var(--brand-moss)]/15 px-2 pt-3">
            <a href="{{ route('profile.edit') }}" @click="sidebarOpen = false" class="flex items-center gap-3 rounded-xl px-3 py-3 transition hover:bg-[var(--brand-neutral)]">
                <span class="grid size-9 place-items-center rounded-full bg-[var(--brand-neutral)] text-sm font-bold text-[var(--brand-forest)]">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                <span class="min-w-0">
                    <span class="block truncate text-sm font-semibold text-[var(--brand-forest)]">{{ Auth::user()->name }}</span>
                    <span class="block text-[10px] font-bold uppercase tracking-wider text-[var(--brand-moss)]">{{ Auth::user()->user_type }}</span>
                </span>
            </a>
            <form method="POST" action="{{ route('logout') }}" class="mt-2">
                @csrf
                <button class="flex w-full items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold text-[var(--brand-text-muted)] transition hover:bg-[var(--brand-neutral)] hover:text-[var(--brand-forest)]">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M14 8V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2-2v-2"/><path stroke-linecap="round" stroke-linejoin="round" d="m17 16 4-4-4-4M21 12H9"/></svg>
                    Log out
                </button>
            </form>
        </div>
    </aside>
</div>
