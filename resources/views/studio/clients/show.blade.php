<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('studio.clients.index') }}" class="text-xs font-bold uppercase tracking-[.2em] text-[var(--brand-moss)]">← Client records</a>
            <h2 class="mt-1 font-serif text-3xl text-[var(--brand-forest)]">{{ $client->name }}</h2>
            <p class="mt-1 text-sm text-[var(--brand-text-muted)]">{{ $client->email }}</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <section class="space-y-7">@if(session('status'))<p role="status" class="rounded-lg bg-emerald-50 p-3 text-emerald-800">{{ session('status') }}</p>@endif @if($errors->any())<div role="alert" class="rounded-lg bg-red-50 p-3 text-red-800">{{ $errors->first() }}</div>@endif
                <div><h3 class="font-serif text-2xl text-[var(--brand-forest)]">Services availed</h3><div class="mt-4 space-y-3">@forelse($appointments as $appointment)<article class="rounded-2xl border border-[var(--brand-moss)]/20 bg-white p-5 shadow-sm"><div class="flex flex-wrap justify-between gap-3"><div><h4 class="font-semibold text-[var(--brand-forest)]">{{ $appointment->service_names }}</h4><p class="mt-1 text-sm text-[var(--brand-text-muted)]">Session {{ $appointment->session_number }} · {{ $appointment->scheduled_at->copy()->timezone('Asia/Manila')->format('M j, Y · g:i A') }}</p>@if($appointment->staff)<p class="mt-1 text-xs text-[var(--brand-text-muted)]">Recorded by {{ $appointment->staff->name }}</p>@endif</div><span class="rounded-full px-3 py-1 text-xs font-bold {{ $appointment->status === 'completed' ? 'bg-emerald-50 text-emerald-700' : 'bg-[var(--brand-cream)] text-[var(--brand-text-muted)]' }}">{{ ucfirst(str_replace('_', ' ', $appointment->status)) }}</span></div>
@if(in_array(auth()->user()->user_type, ['owner', 'administrator'], true))
    <div class="mt-5 border-t border-[var(--brand-moss)]/20 pt-4">
        <h5 class="font-semibold text-[var(--brand-forest)]">Skin analyzer results <span class="font-normal text-sm">(optional)</span></h5>
        @foreach($appointment->booked_services as $service)
            <div class="mt-4"><p class="font-medium text-sm">{{ $service->name }}</p>
                <div class="mt-2 grid gap-4 sm:grid-cols-2">
                    @foreach(['before', 'after'] as $type)
                        @php($photo = $appointment->skinAnalysisPhotos->first(fn ($item) => $item->service_id === $service->id && $item->type === $type))
                        <div class="rounded-xl bg-[var(--brand-cream)] p-3">
                            <p class="font-semibold text-sm">{{ ucfirst($type) }}</p>
                            @if($photo)
                                <a href="{{ route('admin.skin-analysis.show', $photo) }}" target="_blank" rel="noopener"><img src="{{ route('admin.skin-analysis.show', $photo) }}" alt="{{ ucfirst($type) }} skin analyzer result for {{ $service->name }}" class="mt-2 max-h-48 rounded-lg object-contain"></a>
                                <form method="POST" action="{{ route('admin.skin-analysis.destroy', $photo) }}" class="mt-2" onsubmit="return confirm('Remove this photo?')">@csrf @method('DELETE')<button class="text-sm underline text-red-700">Remove photo</button></form>
                            @endif
                            <form method="POST" enctype="multipart/form-data" action="{{ route('admin.skin-analysis.store', [$appointment, $service]) }}" class="mt-3">@csrf
                                <input type="hidden" name="type" value="{{ $type }}">
                                <label class="block text-xs" for="photo-{{ $appointment->id }}-{{ $service->id }}-{{ $type }}">{{ $photo ? 'Replace' : 'Upload' }} {{ $type }} photo (JPG, PNG or WebP; max 10 MB)</label>
                                <input class="mt-1 block w-full text-sm" id="photo-{{ $appointment->id }}-{{ $service->id }}-{{ $type }}" type="file" name="photo" accept="image/jpeg,image/png,image/webp" required>
                                <button class="mt-2 rounded-lg bg-[var(--brand-moss)] px-3 py-1 text-sm text-white">Save photo</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endif
</article>@empty<div class="rounded-2xl border border-dashed border-[var(--brand-moss)]/30 bg-white/60 px-6 py-10 text-center text-sm text-[var(--brand-text-muted)]">No services have been recorded for this client.</div>@endforelse</div></div>
                <div><h3 class="font-serif text-2xl text-[var(--brand-forest)]">Signed consent forms</h3><div class="mt-4 space-y-3">@forelse($consentForms as $consent)<article class="rounded-2xl border border-emerald-200 bg-white p-5 shadow-sm"><h4 class="font-semibold text-[var(--brand-forest)]">{{ $consent->service_name ?? $consent->service->name }}</h4><a href="{{ route('consents.show', $consent) }}" class="consent-link">View signed record &rarr;</a><p class="mt-1 text-sm text-[var(--brand-text-muted)]">Signed {{ $consent->signed_at->format('M j, Y · g:i A') }}</p><p class="mt-1 text-xs text-[var(--brand-text-muted)]">Recorded by {{ $consent->signed_name ? 'Client electronically' : ($consent->recorder?->name ?? 'Unknown staff member') }}</p>@if($consent->notes)<p class="mt-3 rounded-lg bg-[var(--brand-cream)] p-3 text-sm text-[var(--brand-text-muted)]">{{ $consent->notes }}</p>@endif</article>@empty<div class="rounded-2xl border border-dashed border-[var(--brand-moss)]/30 bg-white/60 px-6 py-10 text-center text-sm text-[var(--brand-text-muted)]">No signed consent forms have been recorded.</div>@endforelse</div></div>
            </section>
        </div>
    </div>
</x-app-layout>

