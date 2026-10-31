<x-app-layout>
    <x-slot name="header"><div><p class="consent-eyebrow">Your care, your choice</p><h2 class="mt-2 font-serif text-3xl">Treatment consent</h2></div></x-slot>
    <div class="consent-workspace mx-auto max-w-3xl space-y-5 px-4 py-8 sm:px-6">
        <a href="{{ route('member.appointments.show', $appointment) }}" class="consent-link">&larr; Back to appointment</a>
        <section class="consent-panel">
            <p class="consent-eyebrow">Read before you sign</p>
            <h1 class="mt-2 font-serif text-3xl">{{ $service->name }}</h1>
            <p class="consent-muted mt-3">Appointment: {{ $appointment->scheduled_at->timezone('Asia/Manila')->format('F j, Y · g:i A') }} (Philippine time)</p>
            <p class="consent-muted mt-3">Take your time to read the form. If anything is unclear, speak with the studio before signing.</p>
            <div class="consent-document">{{ $service->consent_content }}</div>
        </section>
        <section class="consent-panel">
            <h2 class="font-serif text-2xl">Your signature</h2>
            @if($errors->any())<div class="consent-errors mt-4" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <form method="POST" action="{{ route('member.consents.store', $appointment) }}" class="mt-5 space-y-5">
                @csrf
                <input type="hidden" name="content_hash" value="{{ hash('sha256', $service->name."\n".$service->consent_content) }}">
                <label class="consent-check"><input type="checkbox" name="acknowledged" value="1" required @checked(old('acknowledged') && !$errors->has('content_hash'))><span>I have read this form, had the opportunity to ask questions, and agree to the consent described above.</span></label>
                <x-signature-pad :name="$appointment->client->name" />
                <div class="consent-actions"><a href="{{ route('member.appointments.show', $appointment) }}" class="consent-link">Return without signing</a><button class="consent-button">Sign and save consent</button></div>
            </form>
        </section>
    </div>
</x-app-layout>
