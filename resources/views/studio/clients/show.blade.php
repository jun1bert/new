<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('studio.clients.index') }}" class="text-xs font-bold uppercase tracking-[.2em] text-[#a48d78]">← Client records</a>
            <h2 class="mt-1 font-serif text-3xl text-[#4d4037]">{{ $client->name }}</h2>
            <p class="mt-1 text-sm text-[#66584e]">{{ $client->email }}</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <section class="space-y-7">
                <div><h3 class="font-serif text-2xl text-[#4d4037]">Services availed</h3><div class="mt-4 space-y-3">@forelse($appointments as $appointment)<article class="rounded-2xl border border-[#a48d78]/20 bg-white p-5 shadow-sm"><div class="flex flex-wrap justify-between gap-3"><div><h4 class="font-semibold text-[#4d4037]">{{ $appointment->service->name }}</h4><p class="mt-1 text-sm text-[#66584e]">Session {{ $appointment->session_number }} · {{ $appointment->scheduled_at->format('M j, Y · g:i A') }}</p>@if($appointment->staff)<p class="mt-1 text-xs text-[#66584e]">Recorded by {{ $appointment->staff->name }}</p>@endif</div><span class="rounded-full px-3 py-1 text-xs font-bold {{ $appointment->status === 'completed' ? 'bg-emerald-50 text-emerald-700' : 'bg-[#f5f1eb] text-[#66584e]' }}">{{ ucfirst($appointment->status) }}</span></div></article>@empty<div class="rounded-2xl border border-dashed border-[#a48d78]/30 bg-white/60 px-6 py-10 text-center text-sm text-[#66584e]">No services have been recorded for this client.</div>@endforelse</div></div>
                <div><h3 class="font-serif text-2xl text-[#4d4037]">Signed consent forms</h3><div class="mt-4 space-y-3">@forelse($consentForms as $consent)<article class="rounded-2xl border border-emerald-200 bg-white p-5 shadow-sm"><h4 class="font-semibold text-[#4d4037]">{{ $consent->service->name }}</h4><p class="mt-1 text-sm text-[#66584e]">Signed {{ $consent->signed_at->format('M j, Y · g:i A') }}</p><p class="mt-1 text-xs text-[#66584e]">Recorded by {{ $consent->recorder?->name ?? 'Unknown staff member' }}</p>@if($consent->notes)<p class="mt-3 rounded-lg bg-[#f5f1eb] p-3 text-sm text-[#66584e]">{{ $consent->notes }}</p>@endif</article>@empty<div class="rounded-2xl border border-dashed border-[#a48d78]/30 bg-white/60 px-6 py-10 text-center text-sm text-[#66584e]">No signed consent forms have been recorded.</div>@endforelse</div></div>
            </section>
        </div>
    </div>
</x-app-layout>
