<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.2em] text-[#a48d78]">Studio operations</p>
            <h2 class="mt-1 font-serif text-3xl text-[#4d4037]">Client records</h2>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <p class="mb-4 text-sm text-[#66584e]">Review a client’s treatments, session history, and recorded consent forms.</p><form method="GET" class="mb-6"><input name="search" value="{{ $search }}" placeholder="Search client name or email" class="w-full max-w-md rounded-lg border-gray-300" /></form>
            <div class="overflow-hidden rounded-2xl border border-[#a48d78]/20 bg-white shadow-sm">
                <div class="divide-y divide-[#a48d78]/15">
                    @forelse($clients as $client)
                        <a href="{{ route('studio.clients.show', $client) }}" class="flex flex-wrap items-center justify-between gap-4 p-5 transition hover:bg-[#faf9f6]">
                            <div>
                                <h3 class="font-serif text-xl text-[#4d4037]">{{ $client->name }}</h3>
                                <p class="mt-1 text-sm text-[#66584e]">{{ $client->email }}</p>
                            </div>
                            <div class="flex gap-2 text-xs font-bold">
                                <span class="rounded-full bg-[#f5f1eb] px-3 py-1.5 text-[#66584e]">{{ $client->appointments_count }} session{{ $client->appointments_count === 1 ? '' : 's' }}</span>
                                <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-emerald-700">{{ $client->consent_forms_count }} consent{{ $client->consent_forms_count === 1 ? '' : 's' }}</span>
                            </div>
                        </a>
                    @empty
                        <div class="px-6 py-12 text-center text-sm text-[#66584e]">No client accounts have been created yet.</div>
                    @endforelse
                </div>
            </div>
            @if($clients->hasPages())<div class="mt-6">{{ $clients->links() }}</div>@endif
        </div>
    </div>
</x-app-layout>
