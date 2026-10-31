<x-app-layout>
    <x-slot name="header"><div><p class="consent-eyebrow">Administration</p><h2 class="mt-2 font-serif text-3xl text-[#35232d]">Consent forms</h2></div></x-slot>
    <div class="consent-workspace mx-auto max-w-5xl space-y-5 px-4 py-8 sm:px-6">
        <section class="consent-panel">
            <p class="consent-eyebrow">Care begins with understanding</p>
            <h3 class="mt-2 font-serif text-3xl">Your treatment forms, in one place.</h3>
            <p class="consent-muted mt-3">Paste the wording from an existing form below, or write a new version. Clients can read and sign it from their appointment details. Each signed copy keeps the wording as it was when signed.</p>
        </section>
        @if(session('status'))<div class="consent-notice" role="status">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="consent-errors" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @forelse($services as $service)
            <details class="consent-panel" @if((string) old('_consent_service_id') === (string) $service->id) open @endif>
                <summary class="consent-summary"><div><h3 class="font-serif text-2xl">{{ $service->name }}</h3><p class="consent-muted mt-1 text-sm">{{ $service->category }}</p></div><span class="consent-badge">{{ $service->requires_consent ? 'Consent required' : 'Not required' }}</span></summary>
                <form method="POST" action="{{ route('admin.consents.update', $service) }}" class="mt-6 space-y-5">
                    @csrf @method('PATCH')
                    <input type="hidden" name="_consent_service_id" value="{{ $service->id }}">
                    <input type="hidden" name="requires_consent" value="0">
                    <label class="consent-check"><input type="checkbox" name="requires_consent" value="1" @checked((string) old('_consent_service_id') === (string) $service->id ? old('requires_consent') : $service->requires_consent)><span>Require a signed consent form for this treatment</span></label>
                    <div><label for="consent-content-{{ $service->id }}" class="block text-sm font-medium">Form wording</label><p id="consent-help-{{ $service->id }}" class="consent-muted mt-2 text-sm">Copy the full text of your existing form here. Use blank lines between sections. This is the exact text your clients will sign.</p><textarea id="consent-content-{{ $service->id }}" aria-describedby="consent-help-{{ $service->id }}" name="consent_content" rows="12" maxlength="50000" class="consent-input mt-3">{{ (string) old('_consent_service_id') === (string) $service->id ? old('consent_content') : $service->consent_content }}</textarea></div>
                    <div class="consent-actions"><span class="consent-muted text-sm">Changes apply to future signatures.</span><button class="consent-button">Save form</button></div>
                </form>
            </details>
        @empty
            <section class="consent-panel"><p class="consent-muted">Add a service first, then set up its consent form.</p><a class="consent-link" href="{{ route('admin.services.index') }}">Manage services &rarr;</a></section>
        @endforelse
    </div>
</x-app-layout>
