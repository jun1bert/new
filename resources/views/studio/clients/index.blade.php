<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.2em] text-[var(--brand-moss)]">Studio operations</p>
            <h2 class="mt-1 font-serif text-3xl text-[var(--brand-forest)]">Client records</h2>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <p class="mb-4 text-sm text-[var(--brand-text-muted)]">Review a client’s treatments, session history, and recorded consent forms.</p><form method="GET" class="mb-6" role="search"><x-search-field :value="$search" placeholder="Search client name or email" :clear-url="route('studio.clients.index')" /></form>
            <div class="overflow-hidden rounded-2xl border border-[var(--brand-moss)]/20 bg-white shadow-sm">
                <div class="divide-y divide-[var(--brand-moss)]/15">
                    @forelse($clients as $client)
                        <a href="{{ route('studio.clients.show', $client) }}" class="flex flex-wrap items-center justify-between gap-4 p-5 transition hover:bg-[var(--brand-white)]">
                            <div>
                                <h3 class="font-serif text-xl text-[var(--brand-forest)]">{{ $client->name }}</h3>
                                <p class="mt-1 text-sm text-[var(--brand-text-muted)]">{{ $client->email }}</p>
                            </div>
                            <div class="flex gap-2 text-xs font-bold">
                                <span class="rounded-full bg-[var(--brand-cream)] px-3 py-1.5 text-[var(--brand-text-muted)]">{{ $client->appointments_count }} session{{ $client->appointments_count === 1 ? '' : 's' }}</span>
                                <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-emerald-700">{{ $client->consent_forms_count }} consent{{ $client->consent_forms_count === 1 ? '' : 's' }}</span>
                            </div>
                        </a>
                    @empty
                        <div class="px-6 py-12 text-center text-sm text-[var(--brand-text-muted)]">No client accounts have been created yet.</div>
                    @endforelse
                </div>
            </div>
            @if($clients->hasPages())<div class="mt-6">{{ $clients->links() }}</div>@endif
        </div>
    </div>
</x-app-layout>
