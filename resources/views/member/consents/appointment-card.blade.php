@if($appointment->consentForm || $appointment->consent_service)
    <section class="consent-panel">
        <p class="consent-eyebrow">Before your treatment</p><h2 class="mt-2 font-serif text-2xl">Your consent form</h2>
        @if($appointment->consentForm)
            <p class="consent-muted mt-3">Signed {{ $appointment->consentForm->signed_at->timezone('Asia/Manila')->format('F j, Y') }}. Your copy is ready to view.</p><a href="{{ route('consents.show', $appointment->consentForm) }}" class="consent-link mt-3">View signed consent &rarr;</a>
        @elseif(filled($appointment->consent_service?->consent_content) && in_array($appointment->status, ['pending', 'scheduled'], true))
            <p class="consent-muted mt-3">Please read the treatment form and sign when you are ready.</p><a href="{{ route('member.consents.create', $appointment) }}" class="consent-button mt-4">Read and sign</a>
        @else
            <p class="consent-muted mt-3">Please speak with the studio about your treatment consent.</p>
        @endif
    </section>
@endif
