<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('studio.clients.index') }}" class="text-xs font-bold uppercase tracking-[.2em] text-[#713b51]">← Client records</a>
            <h2 class="mt-1 font-serif text-3xl text-[#35232d]">{{ $client->name }}</h2>
            <p class="mt-1 text-sm text-[#786570]">{{ $client->email }}</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <section class="space-y-7">
                <div><h3 class="font-serif text-2xl text-[#35232d]">Services availed</h3><div class="mt-4 space-y-3">@forelse($appointments as $appointment)<article class="rounded-2xl border border-[#713b51]/20 bg-white p-5 shadow-sm"><div class="flex flex-wrap justify-between gap-3"><div><h4 class="font-semibold text-[#35232d]">{{ $appointment->service_names }}</h4><p class="mt-1 text-sm text-[#786570]">Session {{ $appointment->session_number }} · {{ $appointment->scheduled_at->copy()->timezone('Asia/Manila')->format('M j, Y · g:i A') }}</p>@if($appointment->staff)<p class="mt-1 text-xs text-[#786570]">Recorded by {{ $appointment->staff->name }}</p>@endif</div><span class="rounded-full px-3 py-1 text-xs font-bold {{ $appointment->status === 'completed' ? 'bg-emerald-50 text-emerald-700' : 'bg-[#faf5f7] text-[#786570]' }}">{{ ucfirst(str_replace('_', ' ', $appointment->status)) }}</span></div></article>@empty<div class="rounded-2xl border border-dashed border-[#713b51]/30 bg-white/60 px-6 py-10 text-center text-sm text-[#786570]">No services have been recorded for this client.</div>@endforelse</div></div>
                <div><h3 class="font-serif text-2xl text-[#35232d]">Signed consent forms</h3><div class="mt-4 space-y-3">@forelse($consentForms as $consent)<article class="rounded-2xl border border-emerald-200 bg-white p-5 shadow-sm"><h4 class="font-semibold text-[#35232d]">{{ $consent->service_name ?? $consent->service->name }}</h4><a href="{{ route('consents.show', $consent) }}" class="consent-link">View signed record &rarr;</a><p class="mt-1 text-sm text-[#786570]">Signed {{ $consent->signed_at->format('M j, Y · g:i A') }}</p><p class="mt-1 text-xs text-[#786570]">Recorded by {{ $consent->signed_name ? 'Client electronically' : ($consent->recorder?->name ?? 'Unknown staff member') }}</p>@if($consent->notes)<p class="mt-3 rounded-lg bg-[#faf5f7] p-3 text-sm text-[#786570]">{{ $consent->notes }}</p>@endif</article>@empty<div class="rounded-2xl border border-dashed border-[#713b51]/30 bg-white/60 px-6 py-10 text-center text-sm text-[#786570]">No signed consent forms have been recorded.</div>@endforelse</div></div>
            </section>
        </div>
    </div>
</x-app-layout>
