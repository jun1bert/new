<x-app-layout>
    <x-slot name="header"><div><p class="consent-eyebrow">Client record</p><h2 class="mt-2 font-serif text-3xl">Signed consent</h2></div></x-slot>
    <div class="consent-workspace mx-auto max-w-3xl space-y-5 px-4 py-8 sm:px-6">
        <div class="consent-actions consent-no-print"><a class="consent-link" href="{{ in_array(auth()->user()->user_type, ['staff', 'owner', 'administrator'], true) ? route('studio.clients.show', $consent->client) : ($consent->appointment ? route('member.appointments.show', $consent->appointment) : route('dashboard')) }}">&larr; Back to {{ in_array(auth()->user()->user_type, ['staff', 'owner', 'administrator'], true) ? 'client record' : 'appointment' }}</a><button type="button" onclick="window.print()" class="consent-secondary">Print / save PDF</button></div>
        @if(session('status'))<p role="status" class="consent-notice consent-no-print">{{ session('status') }}</p>@endif
        <article class="consent-panel">
            <p class="consent-eyebrow">Shafer's Skin Lovers Lounge · Signed record #{{ $consent->id }}</p>
            <h1 class="mt-2 font-serif text-3xl">{{ $consent->service_name ?? $consent->service->name }}</h1>
            <p class="consent-muted mt-3">Client: {{ $consent->client->name }}</p>
            @if($consent->content_snapshot)
                <x-saved-signature :strokes="$consent->signature_strokes" />
                <div class="consent-document">{{ $consent->content_snapshot }}</div>
                <div class="consent-signature"><span class="consent-eyebrow">Electronically signed by</span><p class="mt-2 font-serif text-3xl">{{ $consent->signed_name }}</p><p class="consent-muted mt-2 text-sm">{{ $consent->signed_at->timezone('Asia/Manila')->format('F j, Y · g:i A') }} (Philippine time)</p><p class="consent-muted mt-3 text-sm">The signer acknowledged reading and agreeing to this form.</p></div>
            @else
                <p class="consent-muted mt-6">This is a previously recorded consent. No digital copy of the wording or signature was stored.</p>
                <p class="consent-muted mt-3">Recorded as signed: {{ $consent->signed_at->timezone('Asia/Manila')->format('F j, Y · g:i A') }} (Philippine time)</p>
            @endif
            @if($consent->notes)<p class="consent-muted mt-4">{{ $consent->notes }}</p>@endif
            @if($consent->signed_name && $consent->recorder)<p class="consent-muted mt-4 text-sm">Signed in the studio. Form presented by {{ $consent->recorder->name }}.</p>@endif
        </article>
    </div>
</x-app-layout>
