<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Treatment consent | Shafer's Skin Lovers Lounge</title>
    @include('layouts.partials.favicon')
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|playfair-display:500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/css/portal.css', 'resources/js/app.js'])
</head>
<body class="consent-tablet">
    <header class="tablet-header"><img src="{{ asset('images/branding/shafers-logo.png') }}" alt="Shafer's Skin Lovers Lounge" style="width: 150px; max-width: 50%; height: auto;"><span class="consent-badge">In-studio consent</span></header>
    <main class="consent-workspace mx-auto max-w-3xl space-y-5 px-4 py-6 sm:px-6 sm:py-10">
        @if($appointment && $appointment->user_id === session('consent_tablet.client_id') && $appointment->consentForm)
            <section class="consent-panel tablet-complete" role="status">
                <span class="consent-eyebrow">Your form has been saved</span><h1 class="mt-3 font-serif text-4xl">Thank you.</h1>
                <p class="consent-muted mt-4">Your signed consent is now part of your client record. Please hand the tablet back to the studio team.</p>
            </section>
        @elseif($available)
            <section class="consent-panel">
                <p class="consent-eyebrow">Read before you sign</p><h1 class="mt-3 font-serif text-3xl">{{ $appointment->consent_service?->name }}</h1>
                <div class="tablet-client mt-5"><p class="text-sm">This form is for</p><p class="mt-1 font-serif text-2xl">{{ $appointment->client->name }}</p><p class="consent-muted mt-2">{{ $appointment->scheduled_at->timezone('Asia/Manila')->format('F j, Y · g:i A') }} (Philippine time)</p></div>
                <p class="consent-muted mt-5">Please take your time to read. If this is not your name, or you have any questions, ask the studio team before signing.</p>
                <div class="consent-document">{{ $appointment->consent_service?->consent_content }}</div>
            </section>
            <section class="consent-panel">
                <h2 class="font-serif text-2xl">Your signature</h2>
                @if($errors->getBag('default')->any())<div class="consent-errors mt-4" role="alert"><ul>@foreach($errors->getBag('default')->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                <form method="POST" action="{{ route('studio.consents.sign') }}" class="mt-5 space-y-5" autocomplete="off">
                    @csrf
                    <input type="hidden" name="content_hash" value="{{ hash('sha256', $appointment->consent_service?->name."\n".$appointment->consent_service?->consent_content) }}">
                    <label class="consent-check"><input type="checkbox" name="acknowledged" value="1" required @checked(old('acknowledged') && !$errors->has('content_hash'))><span>I am the client named above. I have read this form, had the opportunity to ask questions, and agree to the consent described above.</span></label>
                    <x-signature-pad :name="$appointment->client->name" />
                    <button class="consent-button w-full">Sign and save consent</button>
                </form>
            </section>
        @else
            <section class="consent-panel"><h1 class="font-serif text-3xl">Please ask the studio team.</h1><p class="consent-muted mt-4">This form is no longer available to sign. Please hand the tablet back to a staff member.</p></section>
        @endif
        <details class="tablet-staff" @if($errors->getBag('tabletUnlock')->any()) open @endif>
            <summary>Staff: return to management</summary>
            <form method="POST" action="{{ route('studio.consents.unlock') }}" class="mt-4 space-y-4" autocomplete="off">
                @csrf
                <p class="consent-muted">Enter the password for the staff account that opened this form.</p>
                @if($errors->getBag('tabletUnlock')->any())<p class="consent-errors" role="alert">{{ $errors->getBag('tabletUnlock')->first('password') }}</p>@endif
                <label for="staff-unlock-password" class="block text-sm">Staff password<input id="staff-unlock-password" name="password" type="password" autocomplete="off" required class="consent-input mt-2"></label>
                <button class="consent-secondary w-full">Unlock management</button>
            </form>
        </details>
        <p class="consent-muted text-center text-sm">Your moment of care, at Shafer's.</p>
    </main>
</body>
</html>

