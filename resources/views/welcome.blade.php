@php
    $managedServices = \Illuminate\Support\Facades\Schema::hasTable('services')
        ? \App\Models\Service::query()
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get()
        : collect();

    // The public catalog must mirror only the active services managed in the portal.
    $services = $managedServices;

    // Use the images published in Gallery Management. The built-in photos remain
    // visible until the studio uploads its first gallery image.
    $galleryImages = $galleryImages->isNotEmpty() ? $galleryImages : collect([
        ['url' => asset('images/facial-ritual.jpg'), 'title' => 'The art of care', 'caption' => 'Facial-care inspiration. Stock photograph by Gustavo Fring / Pexels.', 'path' => 'images/facial-ritual.jpg', 'exists_on_disk' => true],
        ['url' => asset('images/spa-ritual.jpg'), 'title' => 'A softer kind of everyday', 'caption' => 'Self-care inspiration. Stock photograph by Camille Brodard / Unsplash.', 'path' => 'images/spa-ritual.jpg', 'exists_on_disk' => true],
    ])->map(fn ($image) => (object) $image);

    $bookedSlots = [];
    $publicBookingHours = \Illuminate\Support\Facades\Schema::hasTable('scheduling_settings')
        ? json_decode(\Illuminate\Support\Facades\DB::table('scheduling_settings')->where('id', 1)->value('hours') ?? 'null', true)
        : null;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shafer's Skin Lovers Lounge</title>

    @include('layouts.partials.favicon')

    <meta name="description" content="Discover Shafer's Skin Lovers Lounge. Explore skin, body, and beauty services and request your next appointment online.">
    @vite(['resources/css/app.css', 'resources/css/landing.css', 'resources/js/app.js'])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Allura&family=Playfair+Display:wght@500;600;700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        @font-face {
            font-family: 'Priestacy';
            src: url('{{ asset('Priestacy.otf') }}') format('opentype');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }

        :root {
            --desert-rock: var(--brand-moss);
            --soft-sandstone: var(--brand-sage);
            --creamed-oat: var(--brand-neutral);
            --porcelain-mist: var(--brand-neutral);
            --feather-white: var(--brand-white);
            --ink: var(--brand-forest);
            --muted: var(--brand-text-muted);
        }

        body {
            font-family: 'DM Sans', Arial, sans-serif;
            font-weight: 600;
            isolation: isolate;
            background: #f7f3f0;
            color: var(--ink);
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            z-index: -1;
            pointer-events: none;
            background:
                radial-gradient(circle at 8% 4%, rgba(196,169,149,.18), transparent 27%),
                radial-gradient(circle at 92% 19%, rgba(141,108,90,.12), transparent 24%);
        }

        h1, h2, h3 {
            font-family: 'Playfair Display', Georgia, serif;
            font-weight: 600;
            letter-spacing: .015em;
        }

        .luxury-title {
            font-size: clamp(2.2rem, 5vw, 5rem);
            line-height: 1.05;
        }

        .glass {
            background: rgba(255,253,251,.88);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(141,108,90,0.16);
            box-shadow: 0 18px 48px rgba(48,40,37,.08);
        }

        .warm-panel {
            background: var(--brand-white);
            border: 1px solid rgba(141,108,90,.18);
            box-shadow: 0 20px 56px rgba(48,40,37,.08);
        }

        .contact-panel {
            font-family: 'DM Sans', Arial, sans-serif;
            font-weight: 400;
        }

        .contact-panel h2 {
            font-family: 'Playfair Display', Georgia, serif;
        }

        .cursive-accent { font-family: 'Priestacy', "Segoe Script", cursive; font-weight: 400; letter-spacing: .01em; }
        .hero-cursive-title {
            font-family: 'Allura', 'Priestacy', "Segoe Script", cursive;
            font-size: clamp(3rem, 7.2vw, 6.25rem);
            font-weight: 400;
            letter-spacing: .005em;
            line-height: 1.08;
            text-wrap: balance;
            text-shadow: 0 3px 2px rgba(24,18,14,.72), 0 12px 34px rgba(24,18,14,.68);
            -webkit-text-stroke: .25px rgba(255,255,255,.20);
        }
        .hero-cursive-tail { color: #f0d99e; }
        .service-luxe-card { position: relative; overflow: hidden; transition: transform .25s ease, box-shadow .25s ease; }
        .service-luxe-card:hover { transform: translateY(-4px); border-color: rgba(164,141,120,.48); box-shadow: 0 24px 62px rgba(77,64,55,.15); }
        .service-description {
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 6;
            overflow: hidden;
        }
        .service-gold-text { color: #765823; }
        .service-gold-link { color: #765823; text-decoration-color: rgba(118,88,35,.35); }
        .service-gold-link:hover { color: #4f3916; }
        .reveal-section { opacity: 0; transform: translateY(22px); transition: opacity .7s ease, transform .7s ease; }
        .reveal-section.is-visible { opacity: 1; transform: translateY(0); }

        @media (prefers-reduced-motion: reduce) {
            .reveal-section { opacity: 1; transform: none; transition: none; }
            .service-luxe-card { transition: none; }
        }

        .section-band {
            background: rgba(185,163,137,.30);
            border-block: 1px solid rgba(110,83,63,.22);
        }

        .booking-form-panel {
            background: rgba(255,253,251,.94);
            border: 1px solid rgba(141,108,90,.22);
            box-shadow: 0 26px 68px rgba(48,40,37,.12);
            backdrop-filter: blur(18px);
        }

        .booking-form-panel .form-field {
            background: rgba(255,253,249,.96);
            border-color: rgba(78,56,41,.54);
            box-shadow: inset 0 1px 0 rgba(255,255,255,.8);
        }

        .booking-form-panel .form-field::placeholder { color: rgba(77,64,55,.62); }
        .booking-form-panel [class*="text-[var(--muted)]"] { color: #625349; }
        .booking-form-panel [class*="bg-white/"] { background-color: rgba(239,229,214,.82) !important; }
        .booking-form-panel [class*="border-[var(--soft-sandstone)]"] { border-color: rgba(91,66,49,.38) !important; }

        .hero-media {
            background-image:
                linear-gradient(90deg, rgba(255,253,251,.18), rgba(255,253,251,.76) 45%, rgba(255,253,251,.40)),
                var(--hero-image);
            background-size: cover;
            background-position: center 38%;
        }

        .hero-photo {
            border: 1px solid rgba(250,249,246,.42);
            box-shadow: 0 22px 60px rgba(44,36,31,.26);
            background: var(--creamed-oat);
        }

        /* Editorial public-facing refresh */
        header { border-bottom-color: rgba(141,108,90,.13) !important; background: rgba(255,253,251,.80) !important; }
        .hero-media { min-height: 86svh !important; background: #f8f2ec; }
        .hero-media::after { content: ""; position: absolute; inset: 0; pointer-events: none; background: radial-gradient(circle at 15% 18%, rgba(255,255,255,.9), transparent 33%), linear-gradient(115deg, rgba(255,253,251,.8), rgba(239,225,214,.45)); }
        .hero-media > div { position: relative; z-index: 1; }
        .hero-layout { min-height: calc(86svh - 7rem); display: grid; align-items: center; gap: 3rem; }
        .hero-visual { min-height: 0; }
        @media (min-width: 1024px) { .hero-layout { grid-template-columns: minmax(0, 1fr) minmax(320px, .72fr); } .hero-visual { min-height: 500px; } }
        .hero-cursive-title { font-family: 'Playfair Display', Georgia, serif; font-size: clamp(3.5rem, 7vw, 6.5rem); font-weight: 500; line-height: .9; letter-spacing: -.035em; }
        .hero-cursive-tail { color: var(--desert-rock); font-style: italic; }
        .section-band { background: #f0e7e1; border-block-color: rgba(141,108,90,.12); }
        #services, #gallery { position: relative; }
        #services::before, #gallery::before { content:""; position:absolute; width:14rem; height:14rem; border-radius:50%; background:rgba(196,169,149,.12); filter:blur(8px); right:4%; top:2rem; pointer-events:none; }
        .service-luxe-card { border-radius: 1.25rem; border-color: rgba(141,108,90,.15); box-shadow: 0 12px 35px rgba(48,40,37,.06); }
        .service-luxe-card .service-image { transition: transform .5s ease; }
        .service-luxe-card:hover .service-image { transform: scale(1.04); }
        .booking-form-panel { border-radius: 1.5rem; }
        .booking-help-step { background: rgba(255,253,251,.62); border: 1px solid rgba(141,108,90,.12); }

        .btn-gold {
            background: var(--desert-rock);
            color: var(--feather-white);
            font-weight: 700;
            letter-spacing: .06em;
        }

        .btn-gold:hover {
            background: #8f7663;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @keyframes modalIn {
            from { opacity: 0; transform: scale(.96) translateY(10px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }

        html { scroll-behavior: smooth; }

        .animate-fade-up { animation: fadeUp 0.7s ease both; }

        .hero-gold {
            color: var(--desert-rock);
            text-shadow: 0 10px 30px rgba(164,141,120,.16);
        }

        .form-field {
            min-width: 0;
            max-width: 100%;
            background: rgba(250,249,246,.92);
            border: 1px solid rgba(164,141,120,.28);
            color: var(--ink);
        }

        .form-field::placeholder { color: rgba(77,64,55,.48); }

        .form-field:focus {
            outline: 2px solid rgba(164,141,120,.36);
            border-color: var(--desert-rock);
        }

        input.form-field[type="date"],
        input.form-field[type="time"],
        select.form-field {
            color: var(--ink);
            color-scheme: light;
            min-height: 56px;
            -webkit-text-fill-color: var(--ink);
            appearance: auto;
            -webkit-appearance: auto;
        }

        input.form-field[type="date"]::-webkit-date-and-time-value,
        input.form-field[type="time"]::-webkit-date-and-time-value {
            color: var(--ink);
            text-align: left;
        }

        #field_date { touch-action: manipulation; cursor: pointer; }

        /* Field-level validation states */
        .form-field.field-error {
            border-color: #e05252;
            outline: 2px solid rgba(224,82,82,.22);
        }

        .field-hint {
            font-size: .72rem;
            font-weight: 600;
            margin-top: 4px;
            padding-left: 2px;
        }

        .field-hint.error  { color: #c0392b; }
        .field-hint.ok     { color: #5a7a5a; }

        .service-choice:has(input:checked) {
            border-color: var(--desert-rock);
            background: rgba(230,218,200,.52);
            box-shadow: 0 0 0 3px rgba(164,141,120,.14);
        }

        .booking-type-button[aria-pressed="true"] {
            background: var(--desert-rock);
            color: white;
            border-color: var(--desert-rock);
        }

        .booking-help-step {
            border: 1px solid rgba(164,141,120,.28);
            background: rgba(250,249,246,.56);
        }

        .booking-help-step[data-active="true"] {
            border-color: var(--desert-rock);
            background: rgba(250,249,246,.92);
            box-shadow: 0 10px 24px rgba(77,64,55,.12);
        }

        .booking-help-step span:first-child {
            background: rgba(164,141,120,.18);
            color: var(--desert-rock);
        }

        .booking-help-step[data-active="true"] span:first-child {
            background: var(--desert-rock);
            color: white;
        }

        .participant-desktop-actions {
            display: none;
        }

        .participant-desktop-actions[data-active="true"] {
            display: grid;
        }

        .participant-tabs {
            scrollbar-width: thin;
        }

        .participant-tab[aria-current="true"] {
            border-color: var(--desert-rock);
            background: var(--desert-rock);
            color: white;
            box-shadow: 0 10px 24px rgba(77,64,55,.14);
        }

        .service-category-card[aria-pressed="true"] {
            border-color: var(--desert-rock);
            background: var(--desert-rock);
            color: white;
            box-shadow: 0 12px 28px rgba(77,64,55,.14);
        }

        @media (max-width: 639px) {
            #book {
                padding-top: 2.5rem;
                padding-bottom: 2.5rem;
            }

            #bookingLayout,
            .booking-form-panel,
            #bookingForm,
            [data-booking-step="1"] {
                align-items: stretch;
                align-self: start;
                height: auto;
                min-height: 0;
            }

            #bookingLayout {
                align-items: start;
            }

            #bookingForm {
                overflow: visible;
            }

            [data-booking-step="1"]:not(.hidden) {
                display: flex;
                flex: 0 0 auto;
            }

            [data-booking-step="1"] > .order-3:last-child {
                margin-bottom: 0;
            }

            .booking-form-panel {
                height: fit-content;
            }

            #serviceFilterBar {
                position: static;
                top: .5rem;
            }

            #serviceChoiceGrid {
                max-height: min(28rem, 58vh);
                overflow-y: auto;
            }

            .current-client-panel {
                position: static;
                box-shadow: 0 14px 32px rgba(77,64,55,.16);
            }

            .service-category-card {
                min-height: 76px;
            }
        }

        .gallery-card { cursor: zoom-in; }

        .modal-panel { animation: modalIn .22s ease both; }

        .site-logo {
            height: clamp(2.6rem, 8vw, 4rem);
            width: auto;
            max-width: min(12rem, 52vw);
            object-fit: contain;
        }

        .mini-stat {
            background: rgba(77,64,55,.64);
            border: 1px solid rgba(250,249,246,.22);
            color: var(--porcelain-mist);
            box-shadow: 0 14px 34px rgba(44,36,31,.22);
        }

        .establishment-thumb {
            background: var(--creamed-oat);
            border: 1px solid rgba(164,141,120,.22);
            box-shadow: 0 16px 42px rgba(77,64,55,.10);
        }

        .map-frame {
            filter: saturate(.82) sepia(.08);
        }

        @media (max-width: 640px) {
            h1, h2, h3 { letter-spacing: .045em; }
            .site-logo { max-width: 9rem; }
        }
    </style>
</head>

<body id="top" class="relative overflow-x-hidden">
<a href="#services" class="skip-link">Skip to services</a>

{{-- Header --}}
<header class="fixed top-0 z-50 w-full border-b border-[var(--desert-rock)]/25 bg-[var(--porcelain-mist)]/92 shadow-[0_8px_30px_rgba(77,64,55,.08)] backdrop-blur-xl">
    <div class="landing-header-row max-w-7xl mx-auto flex items-center justify-between gap-3 px-4 sm:px-6 lg:px-10 py-3">
        <a href="#top" class="landing-brand-logo" aria-label="Shafer's Skin Lovers Lounge home"><img src="{{ asset('images/branding/shafers-logo.png') }}" alt="Shafer's Skin Lovers Lounge"></a>

        <nav class="landing-header-actions flex items-center justify-end gap-3 sm:gap-6 text-[11px] sm:text-sm" aria-label="Account and booking">
            <a href="#about" class="hidden whitespace-nowrap text-[var(--ink)] transition hover:text-[var(--desert-rock)] sm:inline">About</a>
            <a href="#services" class="hidden whitespace-nowrap text-[var(--ink)] transition hover:text-[var(--desert-rock)] sm:inline">Services</a>
            <a href="#gallery"  class="hidden whitespace-nowrap text-[var(--ink)] transition hover:text-[var(--desert-rock)] sm:inline">Gallery</a>
            <a href="#location" class="hidden whitespace-nowrap text-[var(--ink)] transition hover:text-[var(--desert-rock)] sm:inline">Location</a>
            @auth
                <a href="{{ route('dashboard') }}" class="shrink-0 whitespace-nowrap rounded-full border border-[var(--desert-rock)]/35 px-3.5 py-2 font-bold text-[var(--ink)] transition hover:bg-[var(--creamed-oat)] sm:px-4"><span class="sm:hidden">Account</span><span class="hidden sm:inline">My bookings</span></a>
            @else
                <a href="{{ route('login') }}" class="shrink-0 whitespace-nowrap rounded-full border border-[var(--desert-rock)]/35 px-3.5 py-2 font-bold text-[var(--ink)] transition hover:bg-[var(--creamed-oat)] sm:px-4"><span class="sm:hidden">Login</span><span class="hidden sm:inline">Client login</span></a>
            @endauth
            <a href="#book"
               class="shrink-0 whitespace-nowrap rounded-full bg-[var(--desert-rock)] px-3.5 py-2 font-bold text-white shadow-md shadow-[#4d4037]/10 transition hover:bg-[#806752] sm:px-5">
                <span class="sm:hidden">Book now</span><span class="hidden sm:inline">Book appointment</span>
            </a>
        </nav>
    </div>
    <nav class="landing-mobile-nav" aria-label="Explore Shafer's">
        <a href="#about">About</a><a href="#services">Services</a><a href="#gallery">Gallery</a><a href="#location">Location</a>
    </nav>
</header>

{{-- Hero --}}
@php
    $heroImage = asset('images/facial-ritual.jpg');
@endphp
<section class="hero-media relative min-h-[86vh] px-4 pt-28 sm:pt-32"
         style="--hero-image: url('{{ $heroImage }}');">
    <div class="hero-shape hero-shape-forest" aria-hidden="true"></div><div class="hero-shape hero-shape-sage" aria-hidden="true"></div><div class="hero-shape hero-shape-clay" aria-hidden="true"></div>
    <div class="hero-layout mx-auto max-w-7xl">
        <div class="hero-copy max-w-2xl py-12 text-[var(--ink)] sm:py-16">
            <p class="text-[10px] sm:text-xs tracking-[0.28em] uppercase text-[var(--desert-rock)]">
                SKIN LOVERS LOUNGE · YOUR MOMENT OF CARE
            </p>
            <h1 class="hero-cursive-title mt-5 text-[var(--ink)]" style="text-shadow:none;-webkit-text-stroke:0">
                <span class="block">Feel good</span>
                <span class="hero-cursive-tail mt-1 block">in your skin.</span>
            </h1>
            <p class="mt-6 max-w-xl text-sm leading-7 text-[var(--muted)] sm:text-base">
                Make space for yourself. Explore thoughtful skin, body, and beauty treatments, then find a time that works for you.
            </p>

            <div class="hero-actions mt-8 flex flex-wrap gap-3"><a href="#book" class="rounded-full bg-[var(--ink)] px-6 py-3 text-sm font-bold text-white transition hover:bg-[var(--desert-rock)]"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path stroke-linecap="round" d="M7 3v4M17 3v4M3 10h18M8 14h1m3 0h1m-5 3h1m3 0h1"/></svg>Book an appointment</a><a href="#services" class="rounded-full border border-[var(--desert-rock)]/35 bg-white/60 px-6 py-3 text-sm font-bold text-[var(--ink)] backdrop-blur transition hover:bg-white">Explore services</a></div>

        </div>

        <div class="hero-visual">
            <svg class="hero-leaves" viewBox="0 0 180 210" fill="none" aria-hidden="true"><path d="M90 203C102 142 91 75 68 26M91 143c-23-7-41-19-52-38m55 5c21-14 35-33 40-57" stroke="currentColor" stroke-width="3" stroke-linecap="round"/><path d="M69 72C38 68 22 46 25 13c28 4 46 23 44 59ZM77 117C43 129 18 116 7 88c31-9 55 2 70 29Zm25-25c-2-32 15-57 45-64 4 28-12 51-45 64Zm-8 52c6-29 24-45 55-44-2 27-22 43-55 44Z" fill="currentColor" fill-opacity=".72"/><path d="M30 23 65 70M15 96l58 20m63-77-31 50m35 19-45 35" stroke="#c7d1b7" stroke-width="1.3" stroke-linecap="round"/></svg>
            <img src="{{ $heroImage }}" alt="An aesthetician gently applying a facial mask during a skincare treatment"
                 fetchpriority="high" width="1400" height="933" class="hero-photo">
            <div class="hero-image-note"><img class="hero-note-logo" src="{{ asset('images/branding/shafers-logo.png') }}" alt="" aria-hidden="true"><div><span class="eyebrow">A MOMENT FOR YOU</span><p>Care for the skin you're in.</p></div></div>
        </div>
    </div>
</section>

{{-- Establishment preview --}}
<div class="ritual-strip" aria-label="The Shafer's experience"><span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><path d="M12 20C6 20 3 16 3 11c5 0 8 3 9 9Zm0 0c6 0 9-4 9-9-5 0-8 3-9 9Zm0-2C8 14 8 9 12 4c4 5 4 10 0 14ZM5 21h14"/></svg>Beauty, at your pace</span><span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><path d="M4 20C5 11 10 6 20 4c1 10-4 16-13 15M5 21c3-5 7-9 12-12"/></svg>Care that feels personal</span><span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><path d="M12 21 4 13C-1 8 5 2 10 6l2 2 2-2c5-4 11 2 6 7l-8 8Z"/></svg>A moment, just for you</span></div>
<section id="about" class="scroll-mt-20 px-4 py-8 sm:px-6 sm:py-10">
    <div class="about-story mx-auto max-w-7xl">
        <div class="about-story-visual"><img src="{{ asset('images/spa-ritual.jpg') }}" alt="Skincare essentials, towels and flowers" loading="lazy"><span class="about-story-tag">A LITTLE TIME FOR YOU</span></div>
        <div class="about-story-copy"><p class="eyebrow">OUR PHILOSOPHY</p><h2>Your moment to <em>relax, refresh &amp; renew.</em></h2><p>Shafer's brings together thoughtful beauty rituals and a tranquil, welcoming setting—so every visit feels personal, unhurried, and restorative.</p><div class="about-values"><span>Thoughtful care</span><span>Time to unwind</span><span>Made for you</span></div><a href="#services" class="about-story-link">Explore our treatments</a></div>
    </div>
</section>

{{-- Services --}}
<section id="services" class="section-band scroll-mt-20 px-4 py-16 sm:px-6 sm:py-20">
    <div class="mb-10 text-center sm:mb-12">
        <p class="eyebrow mb-3">CARE, CHOSEN BY YOU</p>
        <h2 class="section-heading">Care that fits <em>your moment.</em></h2>
        <p class="text-[var(--muted)] mt-3 text-sm">Explore the treatments available for your next visit.</p>
    </div>

    @php
        $servicesByCategory = $services->groupBy('category');
    @endphp

    @if($servicesByCategory->isNotEmpty())
        <div class="mx-auto max-w-7xl">
            <div class="flex gap-2 overflow-x-auto pb-3" role="tablist" aria-label="Service categories">
                @foreach($servicesByCategory as $category => $categoryServices)
                    <button type="button"
                            class="service-category-tab shrink-0 rounded-full border px-4 py-2.5 text-sm font-semibold transition {{ $loop->first ? 'border-[var(--desert-rock)] bg-[var(--desert-rock)] text-white' : 'service-gold-text border-[var(--soft-sandstone)] bg-[var(--feather-white)] hover:bg-[var(--creamed-oat)]' }}"
                            role="tab"
                            aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                            tabindex="{{ $loop->first ? '0' : '-1' }}"
                            aria-controls="service-category-{{ $loop->index }}"
                            data-category-target="service-category-{{ $loop->index }}">
                        {{ $category }}
                        <span class="ml-1 opacity-70">{{ $categoryServices->count() }}</span>
                    </button>
                @endforeach
            </div>

            @foreach($servicesByCategory as $category => $categoryServices)
                <section id="service-category-{{ $loop->index }}"
                         class="service-category-panel mt-8 {{ $loop->first ? '' : 'hidden' }}"
                         role="tabpanel">
                    <div class="mb-5 flex items-end justify-between gap-4">
                        <div>
                            <h3 class="text-2xl text-[var(--ink)]">{{ $category }}</h3>
                            <p class="mt-1 text-sm text-[var(--muted)]">{{ $categoryServices->count() }} service{{ $categoryServices->count() === 1 ? '' : 's' }}</p>
                        </div>
                    </div>

                    <div class="service-card-grid grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                        @foreach($categoryServices as $service)
                            <article class="service-luxe-card warm-panel flex min-h-[230px] flex-col rounded-2xl p-6">
                                <div class="service-card-heading" aria-hidden="true">
                                    <span class="service-card-category">{{ $category }}</span>
                                    <span class="service-card-logo"><img src="{{ asset('images/branding/shafers-logo.png') }}" alt="" loading="lazy"></span>
                                </div>
                                <div class="service-card-body flex flex-1 flex-col">
                                <svg class="service-art" viewBox="0 0 160 170" fill="none" aria-hidden="true" focusable="false">
                                    <path class="service-art-wash" d="M124 89c16 2 24 14 23 30-1 21-14 34-33 35-15 1-25-2-37 4-12 6-23 4-26-4-4-9 4-22 13-30 13-11 22-13 30-23 8-9 17-14 30-12Z"/>
                                    <path class="service-art-profile" d="M112 21c-3 17-16 29-34 42-24 18-38 40-31 70 3-24 13-44 30-58 19-15 30-29 35-54Zm9 16c6 13 7 24 5 33-1 6 2 10 5 15 2 3 0 6-5 8-2 1-3 2-2 5 1 3-1 5-4 6 1 3-1 5-4 6-3 12-10 18-20 17-9-1-17-7-25-13m25-43c4 4 8 5 13 5m-15-15c4 0 8 1 11 4m-34 53c-7 8-10 16-11 25"/>
                                    <path class="service-art-sparkle" d="M38 40c2 10 4 12 14 14-10 2-12 4-14 14-2-10-4-12-14-14 10-2 12-4 14-14ZM22 76c1 7 3 9 10 10-7 2-9 3-10 10-2-7-3-8-10-10 7-1 8-3 10-10Z"/>
                                </svg>
                                <svg class="service-card-sparkles" viewBox="0 0 75 56" aria-hidden="true" focusable="false"><path d="M17 4c1.5 7.5 3.5 9.5 11 11-7.5 1.5-9.5 3.5-11 11-1.5-7.5-3.5-9.5-11-11 7.5-1.5 9.5-3.5 11-11Zm38 22c1 5.5 2.5 7 8 8-5.5 1-7 2.5-8 8-1-5.5-2.5-7-8-8 5.5-1 7-2.5 8-8Z"/><circle cx="38" cy="43" r="2"/><circle cx="42" cy="8" r="1.5"/></svg>
                                <div class="flex items-start justify-between gap-4">
                                    <h4 class="service-gold-text text-xl">{{ $service->name }}</h4>
                                    @if($service->requires_consent)
                                        <span class="shrink-0 rounded-full bg-[rgba(164,141,120,.14)] px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-[var(--desert-rock)]">Consent</span>
                                    @endif
                                </div>
                                <p class="service-price mt-4">&#8369;{{ number_format($service->price, 2) }}</p>
                                <div class="service-card-details mt-2 flex flex-wrap items-center gap-2 text-sm">
                                    <span class="text-[var(--muted)]"><svg class="service-time-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2"/></svg>{{ $service->duration ? $service->duration.' mins' : 'No fixed duration' }}</span>
                                    @if($service->session_count > 1)
                                        <span class="text-[var(--soft-sandstone)]">&bull;</span>
                                        <span class="font-semibold text-[var(--brand-moss)]">{{ $service->session_count }} sessions</span>
                                    @endif
                                </div>
                                @if($service->description)
                                    <p class="service-description mt-3 text-sm leading-relaxed text-[var(--muted)]">{{ $service->description }}</p>
                                @endif
                                <a href="#book"
                                   data-book-service="{{ $service->id }}"
                                   data-book-category="{{ $service->category }}"
                                   class="service-gold-link mt-auto pt-5 text-sm font-bold underline decoration-1 underline-offset-4">
                                    <svg class="service-book-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path stroke-linecap="round" d="M7 3v4M17 3v4M3 10h18M7 14h2m3 0h2m-7 3h2m3 0h2"/></svg><span>Book this service</span>
                                </a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    @else
        <div class="mx-auto max-w-7xl py-10 text-center text-sm text-[var(--muted)]">No services available yet.</div>
    @endif
</section>

{{-- Gallery --}}
<section id="gallery" class="scroll-mt-20 px-4 py-16 sm:px-6 sm:py-20">
    <div class="mb-10 text-center sm:mb-12">
        <p class="eyebrow mb-3">A CLOSER LOOK</p>
        <h2 class="section-heading">A little <em>inspiration.</em></h2>
        <p class="text-[var(--muted)] mt-3 text-sm">Beauty rituals and moments of calm</p>
    </div>

    @if($galleryImages->isEmpty())
        <div class="text-center py-10 text-[var(--muted)] text-sm">No gallery images yet.</div>
    @else
        @php
            $visibleGalleryCount = 6;
        @endphp
        <div class="gallery-grid mx-auto grid max-w-7xl grid-cols-1 gap-5 {{ $galleryImages->count() === 2 ? 'md:grid-cols-2' : 'lg:grid-cols-3' }}">
            @foreach($galleryImages as $image)
                @php
                    $galleryUrl = $image->url;
                    $isHiddenGalleryItem = $loop->index >= $visibleGalleryCount;
                @endphp
                <button type="button"
                    class="gallery-card relative overflow-hidden rounded-2xl bg-[var(--creamed-oat)] group animate-fade-up w-full text-left shadow-xl shadow-[#4d4037]/10
                           {{ $loop->first && $galleryImages->count() > 2 ? 'lg:col-span-2 lg:row-span-2' : '' }} {{ $isHiddenGalleryItem ? 'hidden' : '' }}"
                    @if($image->exists_on_disk) onclick="openGalleryModal(this)" @endif
                    data-gallery-src="{{ $galleryUrl }}"
                    data-gallery-title="{{ $image->title ?? 'Gallery image' }}"
                    data-gallery-caption="{{ $image->caption ?? '' }}"
                    @if($isHiddenGalleryItem) data-gallery-extra="1" @endif>

                    <div class="absolute inset-0 bg-[var(--soft-sandstone)]/35 animate-pulse"></div>

                    @if($image->exists_on_disk)
                        <img src="{{ $galleryUrl }}"
                             alt="{{ $image->title ?? 'Gallery image' }}"
                             loading="lazy"
                             onload="this.previousElementSibling.style.display='none'; this.classList.remove('opacity-0')"
                             class="relative z-10 w-full object-cover opacity-0
                                    transition-all duration-700 ease-out
                                    group-hover:scale-105 group-hover:brightness-90
                                    {{ $loop->first ? 'h-[420px] sm:h-[520px] lg:h-[650px]' : 'h-[260px] sm:h-[310px]' }}">
                    @else
                        <div class="relative z-10 grid w-full place-items-center bg-[var(--creamed-oat)] p-6 text-center {{ $loop->first ? 'h-[420px] sm:h-[520px] lg:h-[650px]' : 'h-[260px] sm:h-[310px]' }}">
                            <div>
                                <p class="text-sm font-bold text-[var(--ink)]">Gallery image missing</p>
                                <p class="mt-2 break-all text-xs leading-5 text-[var(--muted)]">{{ $image->path }}</p>
                            </div>
                        </div>
                    @endif

                    <div class="absolute inset-0 z-10 bg-gradient-to-t from-[#4d4037]/75 via-[#4d4037]/8 to-transparent
                                opacity-0 group-hover:opacity-100 transition duration-300"></div>

                    @if($image->caption || $image->title)
                        <div class="absolute bottom-0 left-0 right-0 z-20 p-6
                                    translate-y-6 opacity-0
                                    group-hover:translate-y-0 group-hover:opacity-100
                                    transition-all duration-300">
                            @if($image->title)
                                <h3 class="text-[var(--feather-white)] text-xl font-medium">{{ $image->title }}</h3>
                            @endif
                            @if($image->caption)
                                <p class="text-[var(--porcelain-mist)] text-sm mt-2">{{ $image->caption }}</p>
                            @endif
                        </div>
                    @endif
                </button>
            @endforeach
        </div>

        @if($galleryImages->count() > $visibleGalleryCount)
            <div class="mt-8 text-center">
                <button type="button"
                    id="galleryToggle"
                    data-expanded="false"
                    class="rounded-full border border-[var(--desert-rock)]/30 bg-[var(--feather-white)] px-5 py-2.5 text-sm font-semibold text-[var(--ink)] transition hover:bg-[var(--creamed-oat)]">
                    Show all gallery
                </button>
            </div>
        @endif
    @endif
</section>

{{-- Why Shafer's --}}
<section id="why-shafers" class="px-4 py-20 sm:px-6 sm:py-28">
    <div class="mx-auto max-w-6xl">
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-xs font-bold uppercase tracking-[.22em] text-[var(--desert-rock)]">Why Shafer's</p>
            <h2 class="mt-4 text-4xl leading-tight text-[var(--ink)] sm:text-5xl">The little details make the difference.</h2>
        </div>
        <div class="mt-12 grid gap-8 border-y border-[var(--desert-rock)]/20 py-8 md:grid-cols-3">
            <div><p class="font-serif text-2xl text-[var(--ink)]">Personal</p><p class="mt-3 text-sm leading-7 text-[var(--muted)]">Every appointment begins with listening, then care is tailored around what you need.</p></div>
            <div class="md:border-x md:border-[var(--desert-rock)]/20 md:px-8"><p class="font-serif text-2xl text-[var(--ink)]">Considered</p><p class="mt-3 text-sm leading-7 text-[var(--muted)]">A calm setting, thoughtful timing, and an experience designed to let you slow down.</p></div>
            <div><p class="font-serif text-2xl text-[var(--ink)]">Refined</p><p class="mt-3 text-sm leading-7 text-[var(--muted)]">From first welcome to final touch, we value beautiful results and genuine comfort.</p></div>
        </div>
    </div>
</section>

{{-- Contact and location --}}
<section id="location" class="visit-section scroll-mt-20 px-4 py-16 sm:px-6 sm:py-20">
    <div class="visit-layout mx-auto max-w-7xl">
        <div class="visit-copy"><p class="eyebrow">YOUR NEXT VISIT</p><h2>Take a little time <em>for yourself.</em></h2><p>Ready to plan your visit? Browse our treatments or send an appointment request. Our team will review your preferred schedule.</p><div class="visit-actions"><a href="#book" class="visit-primary">Request an appointment</a><a href="#services" class="visit-secondary">Explore treatments</a></div></div>
        <div class="visit-details"><p class="visit-details-label">VISITING SHAFER'S</p><div><h3>Location &amp; contact</h3><p>Location and contact details will be shared here when available.</p></div><div><h3>Appointments</h3><p>Requests are reviewed by our team before they are confirmed.</p></div></div>
    </div>
</section>

{{-- Booking --}}
<section id="book" class="section-band scroll-mt-20 px-4 py-16 pb-20 sm:px-6 sm:py-20 sm:pb-24">
    <div id="bookingLayout" class="mx-auto grid min-w-0 gap-6 lg:items-start {{ session('success') ? 'max-w-3xl lg:grid-cols-1' : 'max-w-6xl lg:grid-cols-[0.8fr_1.2fr]' }}">
        <aside id="reservationInstructions" class="warm-panel min-w-0 rounded-2xl p-5 sm:p-8 {{ session('success') ? 'hidden' : '' }}">
            <p class="text-xs font-bold uppercase tracking-[.22em] text-[var(--desert-rock)]">Reservations</p>
            <h2 class="mt-4 text-3xl">Let’s plan your visit.</h2>
            <p class="mt-4 text-sm leading-7 text-[var(--muted)]">
                Enter your details, pick a schedule, choose services, then review before sending. Reception will confirm your appointment.
            </p>
            <div class="mt-6 space-y-3 text-sm text-[var(--muted)]">
                <p class="booking-help-step rounded-xl p-3" data-guide-step="1"><span class="mr-2 inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold">1</span><strong class="text-[var(--ink)]">Your details</strong><br><span class="ml-8 block text-xs">Add the name and number we can contact.</span></p>
                <p class="booking-help-step rounded-xl p-3" data-guide-step="2"><span class="mr-2 inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold">2</span><strong class="text-[var(--ink)]">Services</strong><br><span class="ml-8 block text-xs">Choose treatments for yourself or each client.</span></p>
                <p class="booking-help-step rounded-xl p-3" data-guide-step="3"><span class="mr-2 inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold">3</span><strong class="text-[var(--ink)]">Date and time</strong><br><span class="ml-8 block text-xs">Choose a time that fits your treatments.</span></p>
                <p class="booking-help-step rounded-xl p-3" data-guide-step="4"><span class="mr-2 inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold">4</span><strong class="text-[var(--ink)]">Review and send</strong><br><span class="ml-8 block text-xs">Check details before submitting.</span></p>
            </div>
            <p class="mt-5 rounded-xl bg-[var(--feather-white)]/70 p-3 text-xs leading-6 text-[var(--muted)]">Some services need consent. We will help you review and sign it at the store before treatment.</p>
        </aside>

        <div class="booking-form-panel min-w-0 overflow-hidden rounded-2xl p-4 sm:p-8">

        <h2 id="bookingFormHeading" class="mb-2 text-center text-2xl {{ session('success') ? 'hidden' : '' }}">Request an appointment</h2>
        <p id="bookingFormIntro" class="mb-8 text-center text-sm text-[var(--muted)] {{ session('success') ? 'hidden' : '' }}">Send your appointment request online. Reception will review and confirm the schedule.</p>

        {{-- Flash messages --}}
        @if(session('success'))
            <div id="bookingSuccess" class="rounded-2xl border border-emerald-300 bg-emerald-50 p-6 text-center shadow-lg sm:p-10">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-600 text-2xl font-bold text-white">✓</div>
                <p class="mt-5 text-xs font-bold uppercase tracking-[.2em] text-emerald-700">Request received</p>
                <h2 class="mt-2 text-3xl text-[var(--ink)]">Thank you for booking</h2>
                <p class="mx-auto mt-4 max-w-lg text-sm leading-7 text-[var(--muted)]">{{ session('success') }} Your request is pending until reception confirms the schedule.</p>
                <div class="mx-auto mt-6 grid max-w-lg gap-3 text-left text-sm sm:grid-cols-3">
                    <div class="rounded-xl bg-white p-3"><strong class="block text-[var(--ink)]">1. Review</strong><span class="mt-1 block text-xs text-[var(--muted)]">Reception checks availability.</span></div>
                    <div class="rounded-xl bg-white p-3"><strong class="block text-[var(--ink)]">2. Confirm</strong><span class="mt-1 block text-xs text-[var(--muted)]">Your booking is confirmed.</span></div>
                    <div class="rounded-xl bg-white p-3"><strong class="block text-[var(--ink)]">3. Visit</strong><span class="mt-1 block text-xs text-[var(--muted)]">Arrive at your scheduled time.</span></div>
                </div>
                <div class="mt-7 flex flex-col justify-center gap-2 sm:flex-row"><a href="#top" class="rounded-xl border border-emerald-300 bg-white px-5 py-3 text-sm font-semibold text-emerald-800">Back to home</a><a href="#book" class="rounded-xl bg-[var(--desert-rock)] px-5 py-3 text-sm font-semibold text-white">Book another appointment</a></div>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-5 p-4 rounded-xl bg-red-500/20 text-red-700 text-sm">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>&bull; {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="bookingForm" method="POST" action="{{ route('appointments.request') }}" class="min-w-0 space-y-5 {{ session('success') ? 'hidden' : '' }}" autocomplete="off" novalidate>
            @csrf

            <div class="mb-6">
                <div class="grid grid-cols-4 gap-2 text-center text-[10px] font-semibold text-[var(--muted)] sm:text-[11px]"><span>Details</span><span>Schedule</span><span>Services</span><span>Review</span></div>
                <div class="mt-2 h-2 overflow-hidden rounded-full bg-[var(--creamed-oat)]"><div id="bookingStepProgress" class="h-full w-1/4 rounded-full bg-[var(--desert-rock)] transition-all duration-300"></div></div>
                <p id="bookingStepTitle" aria-live="polite" aria-atomic="true" class="mt-4 text-center text-sm font-semibold text-[var(--ink)]">Step 1 of 4 - Your details</p>
            </div>

            {{-- Service --}}
            @php
                $loggedInClient = auth()->user();
                $oldParticipants = collect(old('participants', []));
                $oldServiceIds = $oldParticipants->isNotEmpty()
                    ? $oldParticipants->pluck('service_ids')->flatten()->map(fn ($id) => (int) $id)
                    : collect(old('service_ids', old('service_id') ? [old('service_id')] : []))->map(fn ($id) => (int) $id);
                $firstServiceIds = collect($oldParticipants->first()['service_ids'] ?? $oldServiceIds)->map(fn ($id) => (int) $id);
                $oldService = $services->firstWhere('id', $oldServiceIds->first());
                $bookingServiceCatalog = $services->map(fn ($service) => [
                    'id' => $service->id,
                    'name' => $service->name,
                    'category' => $service->category,
                    'price' => (float) $service->price,
                    'session_count' => (int) $service->session_count,
                    'duration' => (int) ($service->duration ?: 60),
                ])->values();
            @endphp
            <div data-booking-step="1" class="flex flex-col gap-5">
            <div class="order-1 rounded-2xl border border-[var(--soft-sandstone)]/45 bg-white/45 p-4">
                <p class="mb-3 text-sm font-semibold text-[var(--ink)]">1. Your details</p>
                @if($loggedInClient)
                    <p class="mb-4 rounded-xl bg-[var(--creamed-oat)]/60 px-3 py-2 text-xs font-semibold text-[var(--ink)]">Booking as {{ $loggedInClient->name }}. This appointment will be saved to your account.</p>
                @endif
                <label for="field_name" class="mb-2 block text-sm font-semibold text-[var(--ink)]">Full name</label>
                <input type="text" id="field_name" name="full_name"
                       placeholder="Full Name"
                       value="{{ old('full_name', $loggedInClient?->name) }}"
                       class="form-field w-full p-4 rounded-xl"
                       autocomplete="name">
                <p id="hint_name" class="field-hint hidden"></p>
                <p class="mt-2 text-xs text-[var(--muted)]">We will use these details to confirm your appointment request.</p>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="field_contact" class="mb-2 block text-sm font-semibold text-[var(--ink)]">Contact number</label>
                        <input type="text" id="field_contact" name="contact_number"
                               placeholder="e.g. 09171234567"
                               value="{{ old('contact_number') }}"
                               class="form-field w-full p-4 rounded-xl"
                               inputmode="tel" autocomplete="tel">
                        <p id="hint_contact" class="field-hint hidden"></p>
                    </div>
                    <div>
                        <label for="field_email" class="mb-2 block text-sm font-semibold text-[var(--ink)]">Email <span class="font-normal text-[var(--muted)]">(optional)</span></label>
                        <input type="email" id="field_email" name="email"
                               placeholder="email@example.com"
                               value="{{ old('email', $loggedInClient?->email) }}"
                               class="form-field w-full p-4 rounded-xl"
                               autocomplete="email">
                        <p id="hint_email" class="field-hint hidden"></p>
                    </div>
                </div>
            </div>
            </div>

            <div data-booking-step="2" class="hidden flex flex-col gap-5">
            <div class="order-2 rounded-2xl border border-[var(--soft-sandstone)]/45 bg-white/45 p-4">
                <p class="mb-3 text-sm font-semibold text-[var(--ink)]">Who is coming?</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    <button type="button" data-booking-mode="solo" aria-pressed="{{ old('party_size', 1) > 1 ? 'false' : 'true' }}" class="booking-type-button rounded-xl border border-[var(--soft-sandstone)] px-4 py-4 text-left text-sm font-semibold">
                        <span class="block text-base">Just me</span>
                        <span class="mt-1 block text-xs font-medium opacity-80">One client, one appointment request.</span>
                    </button>
                    <button type="button" data-booking-mode="group" aria-pressed="{{ old('party_size', 1) > 1 ? 'true' : 'false' }}" class="booking-type-button rounded-xl border border-[var(--soft-sandstone)] px-4 py-4 text-left text-sm font-semibold">
                        <span class="block text-base">Me and others</span>
                        <span class="mt-1 block text-xs font-medium opacity-80">Add clients and choose services per person.</span>
                    </button>
                </div>
                <div id="groupSizeControls" class="mt-4 hidden rounded-xl border border-[var(--soft-sandstone)]/45 bg-white/55 p-3">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-[var(--ink)]">How many clients?</p>
                            <p class="mt-1 text-xs text-[var(--muted)]">You can customize each person's services.</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <button type="button" id="decreaseGroupSize" class="h-10 w-10 rounded-xl border border-[var(--soft-sandstone)] bg-white text-lg font-bold text-[var(--ink)]">-</button>
                            <strong id="groupSizeDisplay" class="w-8 text-center text-[var(--ink)]">1</strong>
                            <button type="button" id="increaseGroupSize" class="h-10 w-10 rounded-xl border border-[var(--soft-sandstone)] bg-white text-lg font-bold text-[var(--ink)]">+</button>
                        </div>
                    </div>
                </div>
                <input type="hidden" id="field_party_size" name="party_size" value="{{ old('party_size', 1) }}">
            </div>

            <div class="order-3 min-w-0">
                <div id="serviceFilterBar" class="sticky top-20 z-20 mb-4 rounded-2xl border border-[var(--soft-sandstone)]/45 bg-[rgba(246,241,232,.96)] p-3 shadow-lg shadow-[#4d4037]/10 backdrop-blur">
                <label for="serviceSearch" class="mb-2 block text-sm font-semibold text-[var(--ink)]">2. Choose service</label>
                <div class="relative mb-3">
                    <input id="serviceSearch" type="search" class="form-field w-full rounded-xl py-3 pl-4 pr-10" placeholder="Search by service" autocomplete="off">
                    <span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-[var(--muted)]" aria-hidden="true">⌕</span>
                </div>
                <input id="field_service_category" type="hidden" value="all">
                </div>
                <select id="field_service" name="service_ids[]" multiple class="hidden" aria-hidden="true">
                    @foreach($services as $service)
                        <option value="{{ $service->id }}"
                                data-category="{{ $service->category }}"
                                data-requires-consent="{{ $service->requires_consent ? '1' : '0' }}"
                                data-price="{{ $service->price }}"
                                data-session-count="{{ $service->session_count }}"
                                {{ $oldServiceIds->contains($service->id) ? 'selected' : '' }}>
                            {{ $service->name }} — PHP {{ number_format($service->price, 2) }}
                        </option>
                    @endforeach
                </select>
                <p id="serviceCatalogPrompt" class="hidden rounded-xl border border-dashed border-[var(--soft-sandstone)] bg-white/40 px-4 py-5 text-center text-sm text-[var(--muted)]">Search or scroll to view available services.</p>
                <div id="serviceCatalogContent">
                <p class="mb-2 text-sm font-semibold text-[var(--ink)]">Tap a service to add it. Tap again to remove it.</p>
                <div class="rounded-2xl border border-[var(--soft-sandstone)]/45 bg-white/35 p-3 sm:p-4">
                    <div id="currentClientPanel" class="current-client-panel mb-3 hidden rounded-xl border border-[var(--soft-sandstone)]/45 bg-white/90 p-3 backdrop-blur">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <strong id="currentParticipantTitle" class="text-sm text-[var(--ink)]">Client 1 of 1</strong>
                                <p id="currentParticipantBadge" class="mt-1 text-xs text-[var(--muted)]">Primary client</p>
                            </div>
                        </div>
                        <div id="participantTabs" class="participant-tabs mt-3 hidden gap-2 overflow-x-auto pb-1"></div>
                    </div>
                    <div id="currentParticipantNameField" class="mb-3 hidden">
                        <label for="currentParticipantName" class="mb-1 block text-xs font-semibold text-[var(--muted)]">Client name <span class="text-red-600">*</span></label>
                        <input id="currentParticipantName" class="form-field w-full rounded-xl p-3" placeholder="Client name for reception">
                    </div>
                <div id="currentClientCart" class="mb-4 rounded-xl border border-[var(--desert-rock)]/25 bg-[var(--creamed-oat)]/55 p-3">
                    <div class="flex items-center justify-between gap-3">
                        <strong class="text-sm text-[var(--ink)]">Your selections</strong>
                        <span id="currentClientCartTotal" class="text-xs font-bold text-[var(--desert-rock)]">PHP 0.00</span>
                    </div>
                    <p id="currentClientCartEmpty" class="mt-2 text-xs text-[var(--muted)]">Your cart is empty. Tap a service below to add it.</p>
                    <div id="currentClientCartItems" class="mt-2 space-y-2"></div>
                    <button id="applyServicesToAll" type="button" class="mt-3 hidden w-full rounded-xl border border-[var(--desert-rock)]/35 bg-white/80 px-3 py-2 text-xs font-semibold text-[var(--ink)]">
                        Apply these services to all clients
                    </button>
                    <div id="participantDesktopActions" data-active="false" class="participant-desktop-actions mt-3 gap-2">
                        <button id="saveAndAddParticipant" type="button" class="hidden w-full rounded-xl border border-[var(--desert-rock)]/35 bg-white px-4 py-3 text-sm font-semibold text-[var(--ink)]">Save &amp; next client</button>
                    </div>
                </div>
                <div id="serviceChoiceGrid" class="grid max-h-[28rem] gap-3 overflow-y-auto pr-1 sm:grid-cols-2">
                    @foreach($services as $service)
                    <label class="service-choice min-w-0 cursor-pointer overflow-hidden rounded-xl border border-[var(--soft-sandstone)]/55 bg-white/65 p-3 transition hover:border-[var(--desert-rock)] hover:bg-white sm:p-4" data-service-category="{{ $service->category }}" data-service-name="{{ Str::lower($service->name) }}">
                        <input type="checkbox" value="{{ $service->id }}" data-price="{{ $service->price }}" data-session-count="{{ $service->session_count }}" class="service-choice-input sr-only">
                        <span class="flex items-start justify-between gap-3">
                            <span class="min-w-0"><span class="block break-words text-sm font-semibold text-[var(--ink)]">{{ $service->name }}</span>@if($service->session_count > 1)<span class="mt-1 block text-xs text-[var(--muted)]">{{ $service->session_count }} sessions · One client only</span>@endif</span>
                            <span class="shrink-0 text-right"><span class="block text-xs font-bold text-[var(--desert-rock)] sm:text-sm">PHP {{ number_format($service->price, 2) }}</span><span class="service-choice-state mt-2 inline-block rounded-full bg-[var(--desert-rock)] px-2 py-1 text-[10px] font-bold text-white"><span class="service-state-add">+ Add</span><span class="service-state-selected">Selected ✓</span></span></span>
                        </span>
                    </label>
                    @endforeach
                </div>
                <p id="serviceCatalogEmpty" class="hidden py-6 text-center text-sm text-[var(--muted)]">No available services match your search.</p>
                </div>
                </div>
                <p id="hint_participant" class="field-hint hidden"></p>
                <div id="participantSummaryWrap" class="mt-4 hidden rounded-2xl border border-[var(--soft-sandstone)]/45 bg-white/35 p-3">
                    <button id="participantSummaryToggle" type="button" class="flex w-full items-center justify-between gap-3 text-left text-sm font-semibold text-[var(--ink)]">
                        <span>Review group so far</span>
                        <span id="participantSummaryToggleText" class="text-xs text-[var(--muted)]">Show</span>
                    </button>
                    <div id="participantSummary" class="mt-3 hidden space-y-2"></div>
                </div>
                <div id="participantInputs"></div>
                <p id="packageBookingNotice" class="mt-3 hidden rounded-xl bg-violet-50 p-3 text-xs font-semibold text-violet-700">Multi-session packages are available for one client only.</p>
                <p id="hint_service" class="field-hint hidden"></p>
            </div>

            <div class="order-3 rounded-2xl bg-[var(--creamed-oat)]/45 p-4">
                <div class="flex justify-between gap-4 text-sm"><span class="text-[var(--muted)]">Selected services</span><strong id="estimateServiceCount">0</strong></div>
                <div class="mt-2 flex justify-between gap-4 text-sm"><span class="text-[var(--muted)]">Clients</span><strong id="estimateClientCount">1</strong></div>
                <div class="mt-3 flex justify-between gap-4 border-t border-[var(--soft-sandstone)]/50 pt-3"><span class="font-semibold text-[var(--ink)]">Estimated total</span><strong id="estimateTotal" class="text-lg text-[var(--desert-rock)]">PHP 0.00</strong></div>
            </div>
            </div>

            <div data-booking-step="3" class="hidden space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="field_date" class="mb-2 block text-sm font-semibold text-[var(--ink)]">Appointment date</label>
                    <div class="flex gap-2">
                        <input type="date" id="field_date" name="date"
                               value="{{ old('date') }}"
                               min="{{ now('Asia/Manila')->toDateString() }}"
                               class="form-field min-w-0 flex-1 p-4 rounded-xl">
                        <button type="button" id="openDatePicker" class="btn-gold min-h-14 shrink-0 rounded-xl px-4 text-sm font-semibold" aria-label="Open appointment calendar">Calendar</button>
                    </div>
                    <p id="hint_date" class="field-hint hidden"></p>
                </div>

                <div>
                    <label for="field_time" class="mb-2 block text-sm font-semibold text-[var(--ink)]">Appointment time</label>
                    <select id="field_time" name="time" data-previous-time="{{ old('time') }}" class="form-field w-full p-4 rounded-xl" aria-describedby="bookingTimeGuidance hint_time">
                        <option value="">Choose a date first</option>
                    </select>
                    <p id="bookingTimeGuidance" class="mt-2 text-xs text-[var(--muted)]" aria-live="polite">Times are shown in Philippine time.</p>
                    <p id="hint_time" class="field-hint hidden"></p>
                </div>
            </div>

            {{-- Notes --}}
            <div>
                <textarea id="field_notes" name="notes"
                          placeholder="Special Requests (optional)"
                          rows="3"
                          class="form-field w-full p-4 rounded-xl resize-none">{{ old('notes') }}</textarea>
            </div>

            <div id="consentAtStoreNotice" class="hidden rounded-xl bg-[var(--creamed-oat)]/45 p-4 text-sm text-[var(--ink)]">
                Consent for the selected service will be reviewed and signed at the store before treatment.
            </div>
            </div>

            <div data-booking-step="4" class="hidden space-y-4">
                <div class="rounded-2xl border border-[var(--soft-sandstone)]/45 bg-white/55 p-5">
                    <h3 class="text-lg font-semibold text-[var(--ink)]">Review your booking</h3>
                    <div class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between gap-4"><span class="text-[var(--muted)]">Contact</span><strong id="wizardReviewContact" class="text-right"></strong></div>
                        <div class="flex justify-between gap-4"><span class="text-[var(--muted)]">Clients</span><strong id="wizardReviewClients"></strong></div>
                        <div class="flex justify-between gap-4"><span class="text-[var(--muted)]">Services</span><strong id="wizardReviewServices"></strong></div>
                        <button type="button" onclick="goToScheduleStep(true)" class="flex w-full justify-between gap-4 rounded-xl px-0 py-1 text-left transition hover:text-[var(--desert-rock)]">
                            <span class="text-[var(--muted)]">Schedule</span>
                            <strong id="wizardReviewSchedule" class="text-right"></strong>
                        </button>
                        <div class="flex justify-between gap-4 border-t border-[var(--soft-sandstone)]/45 pt-3"><span class="font-semibold">Estimated total</span><strong id="wizardReviewTotal" class="text-[var(--desert-rock)]"></strong></div>
                    </div>
                    <div id="wizardReviewParticipantList" class="mt-4 space-y-2 border-t border-[var(--soft-sandstone)]/45 pt-4"></div>
                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <button type="button" onclick="showBookingStep(1)" class="rounded-xl border border-[var(--soft-sandstone)] px-3 py-2 text-xs font-semibold text-[var(--ink)]">Edit details</button>
                        <button type="button" onclick="goToScheduleStep(true)" class="rounded-xl border border-[var(--soft-sandstone)] px-3 py-2 text-xs font-semibold text-[var(--ink)]">Edit schedule</button>
                    </div>
                </div>
            <button type="submit"
                    class="w-full btn-gold py-3.5 rounded-xl tracking-wide transition-all duration-200 active:scale-[.98]">
                Send Appointment Request
            </button>
            </div>

            <div class="grid grid-cols-2 gap-3 pt-2">
                <button id="bookingStepBack" type="button" class="invisible rounded-xl border border-[var(--soft-sandstone)] px-4 py-3 text-sm font-semibold text-[var(--ink)]">Back</button>
                <button id="bookingStepNext" type="button" class="btn-gold rounded-xl px-4 py-3 text-sm font-semibold">Continue</button>
            </div>
        </form>
        </div>
    </div>
</section>

{{-- Footer --}}
<footer class="border-t border-[var(--desert-rock)]/20 bg-[var(--ink)] text-[var(--porcelain-mist)]">
    <div class="landing-footer-top mx-auto max-w-7xl px-4 sm:px-6"><div><a href="#top" class="landing-footer-logo" aria-label="Shafer's Skin Lovers Lounge home"><img src="{{ asset('images/branding/shafers-logo.png') }}" alt="Shafer's Skin Lovers Lounge"></a><p>SKIN LOVERS LOUNGE</p></div><nav aria-label="Footer navigation"><a href="#about">Our approach</a><a href="#services">Treatments</a><a href="#gallery">Gallery</a><a href="#book">Book a visit</a></nav></div>
    <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 sm:py-6">
        {{-- Bottom bar --}}
        <div class="text-center text-xs leading-5 text-[var(--soft-sandstone)]">
            <p>&copy; {{ now()->year }} Shafer's Skin Lovers Lounge. All rights reserved.</p>
            
        </div>
    </div>
</footer>

{{-- Gallery modal --}}
<div id="galleryModal"
     class="hidden fixed inset-0 z-[60] items-center justify-center bg-[#2c241f]/85 p-4 backdrop-blur-sm">
    <div class="modal-panel relative w-full max-w-5xl">
        <button type="button" onclick="closeGalleryModal()"
                class="absolute -top-12 right-0 rounded-full bg-[var(--feather-white)]
                       px-4 py-2 text-sm font-bold text-[var(--ink)] shadow-lg hover:bg-[var(--creamed-oat)] transition">
            ✕ Close
        </button>
        <img id="galleryModalImage" src="" alt=""
             class="max-h-[78vh] w-full rounded-2xl object-contain bg-[var(--feather-white)] shadow-2xl">
        <div id="galleryModalText" class="mt-3 hidden rounded-2xl bg-[var(--feather-white)]/90 p-4 text-[var(--ink)]">
            <h3 id="galleryModalTitle"   class="text-lg font-semibold"></h3>
            <p  id="galleryModalCaption" class="mt-1 text-sm text-[var(--muted)]"></p>
        </div>
    </div>
</div>

{{-- Confirm modal --}}
<div id="confirmModal"
     class="hidden fixed inset-0 z-50 flex items-center justify-center bg-[#2c241f]/60 p-4 backdrop-blur-sm">
    <div class="modal-panel glass rounded-2xl w-[90%] max-w-lg p-6 sm:p-8">
        <h2 class="text-2xl mb-5">Confirm Appointment</h2>
        <div class="space-y-2.5 text-sm text-[var(--muted)]">
            <p><strong class="text-[var(--ink)]">Name:</strong>    <span id="previewName"></span></p>
            <p><strong class="text-[var(--ink)]">Contact:</strong> <span id="previewContact"></span></p>
            <p><strong class="text-[var(--ink)]">Email:</strong>   <span id="previewEmail"></span></p>
            <p><strong class="text-[var(--ink)]">Service:</strong> <span id="previewService"></span></p>
            <p><strong class="text-[var(--ink)]">Clients:</strong> <span id="previewPartySize"></span></p>
            <p><strong class="text-[var(--ink)]">Date:</strong>    <span id="previewDate"></span></p>
            <p><strong class="text-[var(--ink)]">Time:</strong>    <span id="previewTime"></span></p>
            <p><strong class="text-[var(--ink)]">Notes:</strong>   <span id="previewNotes"></span></p>
        </div>
        <div class="flex justify-end gap-3 mt-7">
            <button type="button" onclick="closeConfirmModal()"
                    class="px-5 py-2.5 border border-[var(--desert-rock)]/40 rounded-xl
                           text-[var(--ink)] text-sm font-semibold hover:bg-[var(--creamed-oat)] transition">
                Edit booking
            </button>
            <button type="button" onclick="confirmSubmit()"
                    class="btn-gold px-5 py-2.5 rounded-xl text-sm transition-all active:scale-[.97]">
                Confirm &amp; Book
            </button>
        </div>
    </div>
</div>

{{-- Fully booked modal --}}
<div id="fullModal"
     class="hidden fixed inset-0 z-50 flex items-center justify-center bg-[#2c241f]/60 p-4 backdrop-blur-sm">
    <div class="modal-panel glass rounded-2xl w-[90%] max-w-md p-7 sm:p-10 text-center">
        {{-- Icon --}}
        <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-[var(--creamed-oat)] text-sm font-bold text-[var(--desert-rock)]">
            FULL
        </div>
        <h2 class="text-2xl mb-3">We're Fully Booked</h2>
        <p class="text-[var(--muted)] text-sm leading-relaxed">
            Sorry, we've reached our limit of <strong>10 confirmed appointments</strong>
            for <strong id="fullModalDate"></strong>. <br><br>
            Please try again tomorrow, choose a different date, or feel free to
            <span class="text-[var(--desert-rock)] font-semibold">walk in</span> and
            we'll do our best to accommodate you.
        </p>
        <div class="mt-7 flex flex-col sm:flex-row gap-3 justify-center">
            <button onclick="closeFullModal(); scrollToDateField();"
                    class="btn-gold px-6 py-2.5 rounded-xl text-sm transition-all active:scale-[.97]">
                Try Another Date
            </button>
            <button onclick="closeFullModal()"
                    class="px-6 py-2.5 border border-[var(--desert-rock)]/40 rounded-xl
                           text-sm font-semibold hover:bg-[var(--creamed-oat)] transition">
                Dismiss
            </button>
        </div>
    </div>
</div>

<script>
function scrollToBookingSection(force = false) {
    const target = document.getElementById('book');
    if (!target) return;
    const top = target.getBoundingClientRect().top + window.pageYOffset - 96;
    window.scrollTo({ top, behavior: force ? 'auto' : 'smooth' });
}

window.addEventListener('load', function () {
    const params = new URLSearchParams(window.location.search);
    const shouldShowBookingSuccess = window.location.pathname === '/' && params.get('booking') === 'success';

    if (shouldShowBookingSuccess) {
        const nextUrl = new URL(window.location.href);
        nextUrl.searchParams.delete('booking');
        nextUrl.hash = 'book';
        window.history.replaceState({}, '', `${nextUrl.pathname}${nextUrl.search}${nextUrl.hash}`);
    }

    if (window.location.pathname === '/' && window.location.hash === '#book') {
        setTimeout(() => scrollToBookingSection(true), 60);
        setTimeout(() => scrollToBookingSection(true), 220);
    }
});

/* Subtle section entrance motion */
const revealSections = document.querySelectorAll('body > section:not(.hero-media)');
if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    const revealObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
        });
    }, { threshold: 0.08, rootMargin: '0px 0px -40px' });
    revealSections.forEach(section => {
        section.classList.add('reveal-section');
        revealObserver.observe(section);
    });
}

/* Service category browser */
const serviceCategoryTabs = document.querySelectorAll('.service-category-tab');
const serviceCategoryPanels = document.querySelectorAll('.service-category-panel');

function showServiceCategory(tab) {
    const targetId = tab.dataset.categoryTarget;

    serviceCategoryTabs.forEach((item) => {
        const selected = item === tab;
        item.setAttribute('aria-selected', selected ? 'true' : 'false');
        item.tabIndex = selected ? 0 : -1;
        item.classList.toggle('border-[var(--desert-rock)]', selected);
        item.classList.toggle('bg-[var(--desert-rock)]', selected);
        item.classList.toggle('text-white', selected);
        item.classList.toggle('service-gold-text', !selected);
        item.classList.toggle('border-[var(--soft-sandstone)]', !selected);
        item.classList.toggle('bg-[var(--feather-white)]', !selected);
        item.classList.toggle('text-[var(--ink)]', !selected);
    });

    serviceCategoryPanels.forEach((panel) => {
        panel.classList.toggle('hidden', panel.id !== targetId);
    });
}

serviceCategoryTabs.forEach((tab) => {
    tab.addEventListener('click', () => showServiceCategory(tab));
    tab.addEventListener('keydown', (event) => {
        const tabs = Array.from(serviceCategoryTabs);
        const index = tabs.indexOf(tab);
        const target = event.key === 'ArrowRight' ? tabs[(index + 1) % tabs.length]
            : event.key === 'ArrowLeft' ? tabs[(index - 1 + tabs.length) % tabs.length]
            : event.key === 'Home' ? tabs[0] : event.key === 'End' ? tabs.at(-1) : null;
        if (!target) return;
        event.preventDefault();
        showServiceCategory(target);
        target.focus({ preventScroll: true });
        target.scrollIntoView({ block: 'nearest', inline: 'nearest' });
    });
});

document.querySelectorAll('[data-book-service]').forEach((link) => {
    link.addEventListener('click', () => {
        const categoryField = document.getElementById('field_service_category');
        const serviceField = document.getElementById('field_service');
        if (!categoryField || !serviceField) return;

        categoryField.value = 'all';
        openServiceCatalog();
        filterClientServices('all');
        const option = Array.from(serviceField.options).find(item => item.value === link.dataset.bookService);
        if (option) option.selected = true;
        const firstClientChoice = document.querySelector(`#serviceChoiceGrid .service-choice-input[value="${link.dataset.bookService}"]`);
        if (firstClientChoice) {
            firstClientChoice.checked = true;
            firstClientChoice.dispatchEvent(new Event('change', { bubbles: true }));
        } else {
            syncParticipantServices();
            serviceField.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });
});

function filterClientServices(category, preserveSelection = false) {
    const serviceField = document.getElementById('field_service');
    if (!serviceField) return;
    const query = normalizeServiceSearch(document.getElementById('serviceSearch')?.value || '');
    let visibleCount = 0;
    Array.from(serviceField.options).forEach(option => option.disabled = false);
    document.querySelectorAll('[data-service-category]').forEach(card => {
        const selected = card.querySelector('.service-choice-input')?.checked;
        const searchableText = normalizeServiceSearch(`${card.dataset.serviceName || ''} ${card.textContent || ''}`);
        const searchTerms = query.split(' ').filter(Boolean);
        const searchMismatch = searchTerms.length && !searchTerms.every(term => searchableText.includes(term));
        const hidden = searchMismatch;
        card.classList.toggle('hidden', hidden);
        if (!hidden) visibleCount++;
    });
    document.getElementById('serviceCatalogEmpty')?.classList.toggle('hidden', visibleCount > 0);
}

function normalizeServiceSearch(value) {
    return String(value || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^\w\s]/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
}

function openServiceCatalog() {
    document.getElementById('serviceCatalogContent')?.classList.remove('hidden');
    document.getElementById('serviceCatalogPrompt')?.classList.add('hidden');
}

function renderCurrentClientCart() {
    const participant = participantState[currentParticipantIndex] || { service_ids: [] };
    const selectedIds = new Set(participant.service_ids.map(String));
    const selectedServices = bookingServiceCatalog.filter(service => selectedIds.has(String(service.id)));
    const items = document.getElementById('currentClientCartItems');
    const empty = document.getElementById('currentClientCartEmpty');
    if (!items || !empty) return;
    empty.classList.toggle('hidden', selectedServices.length > 0);
    items.innerHTML = selectedServices.map(service => `<div class="flex items-center justify-between gap-3 rounded-lg bg-white/80 px-3 py-2">
        <div class="min-w-0"><p class="truncate text-xs font-semibold text-[var(--ink)]">${bookingEscape(service.name)}</p><p class="text-[11px] text-[var(--muted)]">PHP ${Number(service.price).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</p></div>
        <button type="button" data-remove-cart-service="${bookingEscape(service.id)}" class="shrink-0 rounded-lg border border-red-200 px-2 py-1 text-xs font-semibold text-red-600">Remove</button>
    </div>`).join('');
    document.getElementById('currentClientCartTotal').textContent = `${selectedServices.length} item${selectedServices.length === 1 ? '' : 's'}`;
    document.getElementById('applyServicesToAll')?.classList.toggle('hidden', !isGroupBooking || selectedServices.length === 0);
    renderParticipantTabs();
    items.querySelectorAll('[data-remove-cart-service]').forEach(button => button.addEventListener('click', () => {
        const input = document.querySelector(`#serviceChoiceGrid .service-choice-input[value="${CSS.escape(button.dataset.removeCartService)}"]`);
        if (input) {
            input.checked = false;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }));
}

function syncParticipantServices() {
    const selected = new Set(participantState.flatMap(participant => participant.service_ids.map(String)));
    Array.from(document.getElementById('field_service').options).forEach(option => option.selected = selected.has(option.value));
    document.getElementById('participantInputs').innerHTML = participantState.map((participant, index) => `
        <input type="hidden" name="participants[${index}][name]" value="${bookingEscape(participant.name)}">
        ${participant.service_ids.map(id => `<input type="hidden" name="participants[${index}][service_ids][]" value="${bookingEscape(id)}">`).join('')}
    `).join('');
    document.getElementById('field_party_size').value = participantState.length;
}

function updateBookingEstimate() {
    const prices = new Map(bookingServiceCatalog.map(service => [String(service.id), Number(service.price)]));
    const selectedIds = participantState.flatMap(participant => participant.service_ids.map(String));
    const total = selectedIds.reduce((sum, id) => sum + (prices.get(id) || 0), 0);
    document.getElementById('estimateServiceCount').textContent = selectedIds.length;
    document.getElementById('estimateClientCount').textContent = participantState.length;
    document.getElementById('estimateTotal').textContent = `PHP ${total.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    if (window.ShafersBookingTimes) updateAvailableTimes();
}

const bookingServiceCatalog = @json($bookingServiceCatalog);
const publicBookingHours = @json($publicBookingHours);
const previousParticipants = @json(array_values(old('participants', [])));
const initialPrimaryServiceIds = @json($firstServiceIds->values());
let participantState = previousParticipants.length
    ? previousParticipants.map((participant, index) => ({ name: participant.name || (index === 0 ? @json(old('full_name', '')) : ''), service_ids: (participant.service_ids || []).map(String) }))
    : [{ name: @json(old('full_name', '')), service_ids: initialPrimaryServiceIds.map(String) }];
let currentParticipantIndex = 0;
let isGroupBooking = participantState.length > 1 || Number(@json(old('party_size', 1))) > 1;

function bookingEscape(value) {
    return String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
}

function serviceNamesFor(ids) {
    const selected = new Set(ids.map(String));
    return bookingServiceCatalog.filter(service => selected.has(String(service.id))).map(service => service.name);
}

function setPackageBookingNotice(message = '') {
    const notice = document.getElementById('packageBookingNotice');
    if (!notice) return;
    notice.textContent = message || 'Multi-session packages are available for one client only.';
    notice.classList.toggle('hidden', !message);
}

function commitCurrentParticipant(showErrors = true) {
    const name = currentParticipantIndex === 0 ? document.getElementById('field_name').value.trim() : document.getElementById('currentParticipantName').value.trim();
    const serviceIds = Array.from(document.querySelectorAll('#serviceChoiceGrid .service-choice-input:checked')).map(input => input.value);
    if (currentParticipantIndex > 0 && !name) {
        if (showErrors) setHint('hint_participant', 'Please enter this client name so reception can prepare the booking.', 'error');
        return false;
    }
    if (!serviceIds.length) {
        if (showErrors) setHint('hint_participant', 'Choose at least one service for this client.', 'error');
        return false;
    }
    setHint('hint_participant', '', '');
    participantState[currentParticipantIndex] = { name, service_ids: serviceIds };
    syncParticipantServices();
    toggleConsentSection();
    renderParticipantSummary();
    updateBookingEstimate();
    return true;
}

function showCurrentClientServiceRequired() {
    setHint('hint_participant', 'Please choose at least one service for this client before continuing.', 'error');
    const serviceGrid = document.getElementById('serviceChoiceGrid');
    serviceGrid?.classList.add('rounded-xl', 'ring-2', 'ring-red-400');
    serviceGrid?.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function renderParticipantEditor(index) {
    currentParticipantIndex = Math.max(0, Math.min(index, participantState.length - 1));
    const participant = participantState[currentParticipantIndex];
    document.getElementById('currentParticipantTitle').textContent = `Client ${currentParticipantIndex + 1} of ${participantState.length}`;
    document.getElementById('currentParticipantBadge').textContent = currentParticipantIndex === 0 ? 'Main client' : (participant.name || 'Client name is required');
    document.getElementById('currentParticipantNameField').classList.toggle('hidden', currentParticipantIndex === 0);
    document.getElementById('currentParticipantName').value = currentParticipantIndex === 0 ? '' : participant.name;
    const selected = new Set(participant.service_ids.map(String));
    document.querySelectorAll('#serviceChoiceGrid .service-choice-input').forEach(input => input.checked = selected.has(input.value));
    filterClientServices(document.getElementById('field_service_category')?.value || 'all');
    renderCurrentClientCart();
    renderParticipantTabs();
    renderParticipantSummary();
    updateGroupNextButtonLabel();
}

function renderParticipantTabs() {
    const container = document.getElementById('participantTabs');
    if (!container) return;
    container.classList.toggle('hidden', !isGroupBooking);
    container.classList.toggle('flex', isGroupBooking);
    if (!isGroupBooking) {
        container.innerHTML = '';
        return;
    }

    container.innerHTML = participantState.map((participant, index) => {
        const selectedCount = participant.service_ids.length;
        const label = index === 0 ? 'You' : (participant.name || `Client ${index + 1}`);
        return `<button type="button" data-participant-tab="${index}" aria-current="${index === currentParticipantIndex ? 'true' : 'false'}" class="participant-tab shrink-0 rounded-full border border-[var(--soft-sandstone)] bg-white/80 px-3 py-2 text-xs font-semibold text-[var(--ink)]">
            ${bookingEscape(label)}
            <span class="ml-1 opacity-75">${selectedCount}</span>
        </button>`;
    }).join('');

    container.querySelectorAll('[data-participant-tab]').forEach(button => button.addEventListener('click', () => {
        const targetIndex = Number(button.dataset.participantTab);
        if (targetIndex === currentParticipantIndex) return;
        const currentDraftName = currentParticipantIndex === 0 ? document.getElementById('field_name').value.trim() : document.getElementById('currentParticipantName').value.trim();
        const currentDraftServices = Array.from(document.querySelectorAll('#serviceChoiceGrid .service-choice-input:checked')).map(input => input.value);
        if (currentParticipantIndex > 0 && currentDraftServices.length > 0 && !currentDraftName) {
            commitCurrentParticipant(true);
            return;
        }
        if (currentDraftServices.length > 0) {
            commitCurrentParticipant(false);
        }
        renderParticipantEditor(targetIndex);
        document.getElementById('currentParticipantTitle')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }));
}

function renderParticipantSummary() {
    const container = document.getElementById('participantSummary');
    document.getElementById('participantSummaryWrap')?.classList.toggle('hidden', !isGroupBooking);
    container.innerHTML = participantState.map((participant, index) => `<div class="flex items-start justify-between gap-3 rounded-xl border ${index === currentParticipantIndex ? 'border-[var(--desert-rock)] bg-[rgba(230,218,200,.3)]' : 'border-[var(--soft-sandstone)]/45 bg-white/55'} p-3">
        <div class="min-w-0"><p class="text-sm font-semibold text-[var(--ink)]">${bookingEscape(participant.name || `Client ${index + 1}`)}</p><p class="mt-1 break-words text-xs text-[var(--muted)]">${bookingEscape(serviceNamesFor(participant.service_ids).join(', ') || 'No service selected')}</p></div>
        <button type="button" data-edit-participant="${index}" class="shrink-0 text-xs font-semibold text-[var(--desert-rock)]">Edit</button>
    </div>`).join('');
    container.querySelectorAll('[data-edit-participant]').forEach(button => button.addEventListener('click', () => renderParticipantEditor(Number(button.dataset.editParticipant))));
}

function updateGroupNextButtonLabel() {
    const nextButton = document.getElementById('saveAndAddParticipant');
    if (!nextButton) return;
    nextButton.textContent = currentParticipantIndex < participantState.length - 1 ? 'Save & next client' : 'Choose date and time';
}

function refreshBookingModeControls() {
    document.getElementById('currentClientPanel')?.classList.toggle('hidden', !isGroupBooking);
    document.querySelectorAll('[data-booking-mode]').forEach(button => {
        const active = button.dataset.bookingMode === (isGroupBooking ? 'group' : 'solo');
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
    document.getElementById('groupSizeControls')?.classList.toggle('hidden', !isGroupBooking);
    document.getElementById('participantDesktopActions')?.setAttribute('data-active', isGroupBooking ? 'true' : 'false');
    document.getElementById('saveAndAddParticipant')?.classList.toggle('hidden', !isGroupBooking);
    const sizeDisplay = document.getElementById('groupSizeDisplay');
    if (sizeDisplay) sizeDisplay.textContent = participantState.length;
    updateGroupNextButtonLabel();
    document.getElementById('bookingStepNext')?.classList.toggle('hidden', currentBookingStep === 4 || (currentBookingStep === 2 && isGroupBooking));
    renderCurrentClientCart();
}

function resizeParticipantState(size) {
    const targetSize = Math.max(1, Math.min(10, Number(size) || 1));
    while (participantState.length < targetSize) {
        participantState.push({ name: '', service_ids: [] });
    }
    if (participantState.length > targetSize) {
        participantState = participantState.slice(0, targetSize);
    }
    currentParticipantIndex = Math.min(currentParticipantIndex, participantState.length - 1);
    isGroupBooking = participantState.length > 1;
    refreshBookingModeControls();
    renderParticipantEditor(currentParticipantIndex);
    syncParticipantServices();
    updateBookingEstimate();
}

function setBookingMode(mode) {
    const group = mode === 'group';
    if (group) {
        const packageIds = new Set(bookingServiceCatalog.filter(service => Number(service.session_count || 1) > 1).map(service => String(service.id)));
        const hadPackage = participantState.some(participant => participant.service_ids.some(id => packageIds.has(String(id))));
        participantState = participantState.map(participant => ({ ...participant, service_ids: participant.service_ids.filter(id => !packageIds.has(String(id))) }));
        document.querySelectorAll('#serviceChoiceGrid .service-choice-input').forEach(input => {
            if (Number(input.dataset.sessionCount || 1) > 1) input.checked = false;
        });
        setPackageBookingNotice(hadPackage ? 'Multi-session services are available for one client only. They were removed so you can continue with a group booking.' : '');
        resetServiceListScroll();
        resizeParticipantState(Math.max(2, participantState.length));
        return;
    }

    participantState = [participantState[0] || { name: document.getElementById('field_name').value.trim(), service_ids: [] }];
    currentParticipantIndex = 0;
    isGroupBooking = false;
    refreshBookingModeControls();
    renderParticipantEditor(0);
    syncParticipantServices();
    updateBookingEstimate();
    setPackageBookingNotice('');
}

function applyCurrentServicesToAllParticipants() {
    if (!commitCurrentParticipant()) return;
    const services = [...participantState[currentParticipantIndex].service_ids];
    participantState = participantState.map((participant, index) => ({
        ...participant,
        service_ids: index === currentParticipantIndex ? participant.service_ids : [...services],
    }));
    syncParticipantServices();
    renderParticipantEditor(currentParticipantIndex);
    renderParticipantSummary();
    updateBookingEstimate();
    setHint('hint_participant', 'These services were applied to every client. You can still edit anyone individually.', 'ok');
}

/* Capacity check */
// Slot data injected from the controller (confirmed counts per date).
// Shape: { "2025-07-10": 8, "2025-07-11": 10 }
const bookedSlots = @json($bookedSlots ?? []);
const MAX_CAPACITY = 10;

function isFullyBooked(dateStr) {
    return Number(bookedSlots[dateStr] ?? 0) + participantState.length > MAX_CAPACITY;
}

function updateAvailableTimes() {
    const timeField = document.getElementById('field_time');
    if (!timeField || !timeField.options) return;
    const previous = timeField.value || timeField.dataset.previousTime || '';
    const date = document.getElementById('field_date').value;
    const durations = new Map(bookingServiceCatalog.map(service => [String(service.id), service.duration]));
    const duration = Math.max(0, ...participantState.map(participant => participant.service_ids.reduce((total, id) => total + (durations.get(String(id)) || 60), 0)));
    const result = window.ShafersBookingTimes.publicRequestTimes({ date, hours: publicBookingHours, duration });
    timeField.replaceChildren(new Option(!date ? 'Choose a date first' : (result.slots.length ? 'Select time' : 'No times available'), ''));
    result.slots.forEach(slot => timeField.add(new Option(slot.label, slot.value)));
    timeField.disabled = !result.slots.length;
    timeField.dataset.unavailableReason = result.slots.length ? '' : result.message;
    if (result.slots.some(slot => slot.value === previous)) timeField.value = previous;
    timeField.dataset.previousTime = '';
    document.getElementById('bookingTimeGuidance').textContent = result.message;
    setHint('hint_time', previous && !timeField.value && result.slots.length ? 'Please choose a new time that fits your treatments.' : '', '');
    timeField.classList.remove('field-error');
    timeField.setAttribute('aria-invalid', 'false');
}

function resetServiceListScroll() {
    const list = document.getElementById('serviceChoiceGrid');
    if (list) list.scrollTop = 0;
}

function keepServicePickerInView() {
    if (!window.matchMedia('(max-width: 639px)').matches) return;
    requestAnimationFrame(() => {
        document.getElementById('currentParticipantTitle')?.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
        });
    });
}

/* Date helpers */
function todayString() {
    return window.ShafersBookingTimes.philippineDateTime().slice(0, 10);
}

function formatDateFriendly(dateStr) {
    if (!dateStr) return '';
    const [y, m, d] = dateStr.split('-');
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return `${months[parseInt(m,10)-1]} ${parseInt(d,10)}, ${y}`;
}

function formatTime(time) {
    if (!time) return '';
    let [h, m] = time.split(':');
    h = parseInt(h);
    const ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    return `${h}:${m} ${ampm}`;
}

/* Set date min on load */
document.addEventListener('DOMContentLoaded', function () {
    const navigationEntry = performance.getEntriesByType('navigation')[0];
    if (navigationEntry?.type === 'reload') {
        ['field_name', 'field_contact', 'field_email', 'field_date', 'field_notes'].forEach(id => {
            const field = document.getElementById(id); if (field) field.value = '';
        });
        document.getElementById('field_time').value = '';
        document.querySelectorAll('.service-choice-input').forEach(input => input.checked = false);
        Array.from(document.getElementById('field_service').options).forEach(option => option.selected = false);
        participantState = [{ name: '', service_ids: [] }];
        isGroupBooking = false;
    }
    const categoryField = document.getElementById('field_service_category');
    if (categoryField) categoryField.value = 'all';
    filterClientServices('all', true);
    document.getElementById('serviceSearch')?.addEventListener('input', () => {
        openServiceCatalog();
        filterClientServices('all');
        resetServiceListScroll();
    });

    document.getElementById('bookingForm')?.addEventListener('change', event => {
        if (!event.target.classList.contains('service-choice-input')) return;
        const changed = event.target;
        if (changed.checked && Number(changed.dataset.sessionCount || 1) > 1) {
            if (isGroupBooking) {
                changed.checked = false;
                setPackageBookingNotice('This service is only available for a solo booking. Please choose "Just me" to book it.');
                setHint('hint_participant', 'This service cannot be added to a group booking.', 'error');
                filterClientServices('all');
                resetServiceListScroll();
                renderCurrentClientCart();
                updateBookingEstimate();
                return;
            }
            participantState = [participantState[0]];
            currentParticipantIndex = 0;
            isGroupBooking = false;
            refreshBookingModeControls();
            setPackageBookingNotice('This multi-session service will be booked for one client. You can add other services for the same client.');
            resetServiceListScroll();
        } else if (changed.checked) {
            setHint('hint_participant', '', '');
            document.getElementById('serviceChoiceGrid')?.classList.remove('ring-2', 'ring-red-400');
            setPackageBookingNotice('');
        } else if (!document.querySelector('#serviceChoiceGrid .service-choice-input[data-session-count]:checked')) {
            setPackageBookingNotice('');
        }
        participantState[currentParticipantIndex] = {
            name: currentParticipantIndex === 0 ? document.getElementById('field_name').value.trim() : document.getElementById('currentParticipantName').value.trim(),
            service_ids: Array.from(document.querySelectorAll('#serviceChoiceGrid .service-choice-input:checked')).map(input => input.value),
        };
        filterClientServices('all');
        syncParticipantServices();
        renderCurrentClientCart();
        renderParticipantSummary();
        updateBookingEstimate();
        keepServicePickerInView();
    });

    document.querySelectorAll('[data-booking-mode]').forEach(button => button.addEventListener('click', () => {
        setBookingMode(button.dataset.bookingMode);
    }));

    document.getElementById('decreaseGroupSize')?.addEventListener('click', () => {
        resizeParticipantState(participantState.length - 1);
    });

    document.getElementById('increaseGroupSize')?.addEventListener('click', () => {
        resizeParticipantState(participantState.length + 1);
    });

    document.getElementById('applyServicesToAll')?.addEventListener('click', applyCurrentServicesToAllParticipants);
    document.getElementById('participantSummaryToggle')?.addEventListener('click', () => {
        const summary = document.getElementById('participantSummary');
        const expanded = summary && !summary.classList.contains('hidden');
        summary?.classList.toggle('hidden', expanded);
        const label = document.getElementById('participantSummaryToggleText');
        if (label) label.textContent = expanded ? 'Show' : 'Hide';
    });

    document.getElementById('saveAndAddParticipant')?.addEventListener('click', () => {
        if (!commitCurrentParticipant()) {
            if (!document.querySelector('#serviceChoiceGrid .service-choice-input:checked')) showCurrentClientServiceRequired();
            return;
        }
        if (currentParticipantIndex < participantState.length - 1) {
            renderParticipantEditor(currentParticipantIndex + 1);
            document.getElementById('currentParticipantTitle')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
            if (isGroupBooking && participantState.length < 2) {
                setHint('hint_participant', 'Add at least one more client for a group booking.', 'error');
                return;
            }
            if (!validateBookingStep(2)) return;
            goToScheduleStep();
        }
    });

    document.getElementById('field_name')?.addEventListener('input', event => {
        participantState[0].name = event.target.value.trim();
        syncParticipantServices();
        renderParticipantTabs();
        renderParticipantSummary();
        if (currentBookingStep === 4) updateWizardReviewV2();
    });
    ['field_contact', 'field_email'].forEach(id => document.getElementById(id)?.addEventListener('input', () => {
        if (currentBookingStep === 4) updateWizardReviewV2();
    }));
    document.getElementById('currentParticipantName')?.addEventListener('input', event => {
        if (currentParticipantIndex > 0) participantState[currentParticipantIndex].name = event.target.value;
        syncParticipantServices();
        renderParticipantTabs();
        renderParticipantSummary();
    });
    if (isGroupBooking && participantState.length < 2) {
        resizeParticipantState(2);
    }
    refreshBookingModeControls();
    renderParticipantEditor(0);
    syncParticipantServices();
    updateBookingEstimate();

    const dateField = document.getElementById('field_date');
    if (dateField) {
        const today = todayString();
        dateField.min = today;

        // If old() value is in the past, clear it
        if (dateField.value && dateField.value < today) {
            dateField.value = '';
        }

        dateField.addEventListener('change', () => {
            updateAvailableTimes();
            validateDate();
            if (document.getElementById('field_time').value) validateTime();
        });

        document.getElementById('openDatePicker')?.addEventListener('click', () => {
            dateField.focus({ preventScroll: true });
            try {
                if (typeof dateField.showPicker === 'function') dateField.showPicker();
                else dateField.click();
            } catch (_) {
                dateField.click();
            }
        });
    }

    updateAvailableTimes();
});

/* Field validation helpers */
function setHint(id, msg, type) {
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent = msg;
    el.className = `field-hint ${type}`;
    el.classList.toggle('hidden', !msg);
}

function setFieldState(fieldId, ok) {
    const el = document.getElementById(fieldId);
    if (!el) return;
    el.classList.toggle('field-error', !ok);
    el.setAttribute('aria-invalid', ok ? 'false' : 'true');
}

function validateName() {
    const val = document.getElementById('field_name').value.trim();
    if (!val) {
        setHint('hint_name', 'Full name is required.', 'error');
        setFieldState('field_name', false);
        return false;
    }
    if (val.length < 2) {
        setHint('hint_name', 'Please enter your full name.', 'error');
        setFieldState('field_name', false);
        return false;
    }
    setHint('hint_name', '', '');
    setFieldState('field_name', true);
    return true;
}

function validateContact() {
    const val = document.getElementById('field_contact').value.trim();
    const ph  = /^(\+63|0)9\d{9}$/.test(val.replace(/\s/g, ''));
    if (!val) {
        setHint('hint_contact', 'Contact number is required.', 'error');
        setFieldState('field_contact', false);
        return false;
    }
    if (!ph) {
        setHint('hint_contact', 'Enter a valid PH number (e.g. 09171234567).', 'error');
        setFieldState('field_contact', false);
        return false;
    }
    setHint('hint_contact', 'Looks good.', 'ok');
    setFieldState('field_contact', true);
    return true;
}

function validateEmail() {
    const val = document.getElementById('field_email').value.trim();
    if (!val) {
        setHint('hint_email', '', '');
        setFieldState('field_email', true);
        return true;
    }

    const valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
    if (!valid) {
        setHint('hint_email', 'Enter a valid email address or leave it blank.', 'error');
        setFieldState('field_email', false);
        return false;
    }

    setHint('hint_email', 'Email will be saved with this booking.', 'ok');
    setFieldState('field_email', true);
    return true;
}

function validateService() {
    const selected = document.getElementById('field_service').selectedOptions;
    const everyClientHasService = participantState.every(participant => participant.service_ids.length > 0);
    const everyClientHasName = participantState.every((participant, index) => index === 0 || String(participant.name || '').trim().length > 0);
    if (!everyClientHasName) {
        setHint('hint_participant', 'Please enter every client name so reception can prepare the booking.', 'error');
        return false;
    }
    if (!selected.length || !everyClientHasService) {
        setHint('hint_service', 'Please select at least one service for every client.', 'error');
        setFieldState('field_service', false);
        document.getElementById('serviceChoiceGrid')?.classList.add('rounded-xl', 'ring-2', 'ring-red-400');
        return false;
    }
    setHint('hint_participant', '', '');
    setHint('hint_service', '', '');
    setFieldState('field_service', true);
    document.getElementById('serviceChoiceGrid')?.classList.remove('ring-2', 'ring-red-400');
    return true;
}

function validatePartySize() {
    const field = document.getElementById('field_party_size');
    const value = Number(field.value);
    const valid = Number.isInteger(value) && value >= 1 && value <= 10;
    setFieldState('field_party_size', valid);
    return valid;
}

function selectedServiceRequiresConsent() {
    const select = document.getElementById('field_service');
    return Array.from(select?.selectedOptions || []).some(option => option.dataset.requiresConsent === '1');
}

function toggleConsentSection() {
    document.getElementById('consentAtStoreNotice')?.classList.toggle('hidden', !selectedServiceRequiresConsent());
}

function validateConsent() {
    return true;
}

function validateDate() {
    const dateField = document.getElementById('field_date');
    const val       = dateField.value;
    const today     = todayString();

    if (!val) {
        setHint('hint_date', 'Please choose a date.', 'error');
        setFieldState('field_date', false);
        return false;
    }
    if (val < today) {
        setHint('hint_date', 'Date cannot be in the past.', 'error');
        setFieldState('field_date', false);
        return false;
    }
    // Capacity check
    if (isFullyBooked(val)) {
        setHint('hint_date', `Fully booked on ${formatDateFriendly(val)}. Pick another date.`, 'error');
        setFieldState('field_date', false);
        return false;
    }
    setHint('hint_date', `${formatDateFriendly(val)} is available.`, 'ok');
    setFieldState('field_date', true);
    updateAvailableTimes();
    return true;
}

function validateTime() {
    updateAvailableTimes();
    const val = document.getElementById('field_time').value;
    const dateVal = document.getElementById('field_date').value;
    if (!val) {
        setHint('hint_time', document.getElementById('field_time').dataset.unavailableReason || 'Please select a time.', 'error');
        setFieldState('field_time', false);
        return false;
    }

    setHint('hint_time', '', '');
    setFieldState('field_time', true);
    return true;
}

/* Live validation on blur */
document.getElementById('field_name')?.addEventListener('blur', validateName);
document.getElementById('field_contact')?.addEventListener('blur', validateContact);
document.getElementById('field_email')?.addEventListener('blur', validateEmail);
document.getElementById('field_service')?.addEventListener('change', () => {
    updateBookingEstimate();
    validateService();
    toggleConsentSection();
    updateAvailableTimes();
    if (document.getElementById('field_time').value) validateTime();
});
document.getElementById('field_time')?.addEventListener('change', validateTime);

let currentBookingStep = 1;
const bookingStepTitles = ['Your details', 'Services', 'Date and time', 'Review and send'];

function showBookingStep(step) {
    currentBookingStep = Math.max(1, Math.min(4, step));
    document.querySelectorAll('[data-booking-step]').forEach(panel => panel.classList.toggle('hidden', Number(panel.dataset.bookingStep) !== currentBookingStep));
    document.getElementById('bookingStepProgress').style.width = `${currentBookingStep * (100 / 4)}%`;
    document.getElementById('bookingStepTitle').textContent = `Step ${currentBookingStep} of 4 - ${bookingStepTitles[currentBookingStep - 1]}`;
    document.getElementById('bookingStepBack').classList.toggle('invisible', currentBookingStep === 1);
    document.getElementById('bookingStepNext').classList.toggle('hidden', currentBookingStep === 4 || (currentBookingStep === 2 && isGroupBooking));
    document.getElementById('bookingStepNext').textContent = currentBookingStep === 1 ? 'Choose services' : (currentBookingStep === 2 ? 'Choose date and time' : 'Review request');
    document.querySelectorAll('[data-guide-step]').forEach(item => item.dataset.active = String(currentBookingStep) === item.dataset.guideStep ? 'true' : 'false');
    if (currentBookingStep === 3) updateAvailableTimes();
    if (currentBookingStep === 4) updateWizardReviewV2();
}

function goToScheduleStep(openPicker = false) {
    showBookingStep(3);
    const dateField = document.getElementById('field_date');
    if (!dateField) return;
    dateField.focus({ preventScroll: true });
    if (!openPicker) return;
    try {
        if (typeof dateField.showPicker === 'function') dateField.showPicker();
    } catch (_) {
        dateField.click();
    }
}

function validateBookingStep(step) {
    if (step === 1) return validateName() && validateContact() && validateEmail();
    if (step === 3) return validateDate() && validateTime();
    if (step === 2) return validatePartySize() && validateService();
    if (step === 4) return true;
    return true;
}

function updateWizardReviewV2() {
    document.getElementById('wizardReviewContact').textContent = `${document.getElementById('field_name').value} · ${document.getElementById('field_contact').value}`;
    document.getElementById('wizardReviewClients').textContent = participantState.length;
    document.getElementById('wizardReviewServices').textContent = participantState.reduce((count, participant) => count + participant.service_ids.length, 0);
    document.getElementById('wizardReviewSchedule').textContent = `${formatDateFriendly(document.getElementById('field_date').value)} · ${formatTime(document.getElementById('field_time').value)}`;
    document.getElementById('wizardReviewTotal').textContent = document.getElementById('estimateTotal').textContent;
    const reviewList = document.getElementById('wizardReviewParticipantList');
    reviewList.innerHTML = participantState.map((participant, index) => `<div class="flex items-start justify-between gap-3 rounded-xl bg-white/65 p-3">
        <div><p class="text-sm font-semibold text-[var(--ink)]">${bookingEscape(participant.name || `Client ${index + 1}`)}</p><p class="mt-1 text-xs text-[var(--muted)]">${bookingEscape(serviceNamesFor(participant.service_ids).join(', '))}</p></div>
        <button type="button" data-review-edit="${index}" class="text-xs font-semibold text-[var(--desert-rock)]">Edit</button>
    </div>`).join('');
    reviewList.querySelectorAll('[data-review-edit]').forEach(button => button.addEventListener('click', () => {
        showBookingStep(2);
        renderParticipantEditor(Number(button.dataset.reviewEdit));
    }));
}

document.getElementById('bookingStepNext')?.addEventListener('click', () => {
    if (!validateBookingStep(currentBookingStep)) return;
    showBookingStep(currentBookingStep + 1);
});
document.getElementById('bookingStepBack')?.addEventListener('click', () => showBookingStep(currentBookingStep - 1));
showBookingStep(1);

/* Open confirm modal */
function openConfirmModal() {
    const okName    = validateName();
    const okContact = validateContact();
    const okEmail   = validateEmail();
    const okService = validateService();
    const okPartySize = validatePartySize();
    const okDate    = validateDate();
    const okTime    = validateTime();
    const okConsent = validateConsent();

    if (!okName || !okContact || !okEmail || !okService || !okPartySize || !okDate || !okTime || !okConsent) {
        if (!okName || !okContact || !okEmail) {
            showBookingStep(1);
        } else if (!okService || !okPartySize) {
            showBookingStep(2);
        } else if (!okDate || !okTime) {
            showBookingStep(3);
        }
        const firstErr = document.querySelector('.field-error');
        firstErr?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }

    const dateVal = document.getElementById('field_date').value;

    // Final capacity check before opening confirm modal
    if (isFullyBooked(dateVal)) {
        openFullModal(dateVal);
        return;
    }

    // Populate preview
    const serviceSelect = document.getElementById('field_service');
    document.getElementById('previewName').textContent    = document.getElementById('field_name').value.trim();
    document.getElementById('previewContact').textContent = document.getElementById('field_contact').value.trim();
    document.getElementById('previewEmail').textContent   = document.getElementById('field_email').value.trim() || 'Not provided';
    document.getElementById('previewService').textContent = Array.from(serviceSelect.selectedOptions).map(option => option.text).join(', ');
    document.getElementById('previewPartySize').textContent = document.getElementById('field_party_size').value;
    document.getElementById('previewDate').textContent    = formatDateFriendly(dateVal);
    document.getElementById('previewTime').textContent    = formatTime(document.getElementById('field_time').value);
    document.getElementById('previewNotes').textContent   = document.getElementById('field_notes').value.trim() || 'None';

    document.getElementById('confirmModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

const signatureCanvas = document.getElementById('signatureCanvas');
const signatureInput = document.getElementById('consent_signature');
const signatureCtx = signatureCanvas?.getContext('2d');
let signatureDrawing = false;
let signatureHasInk = false;

function resizeSignatureCanvas() {
    if (!signatureCanvas || !signatureCtx) return;
    const data = signatureHasInk ? signatureCanvas.toDataURL('image/png') : null;
    const rect = signatureCanvas.getBoundingClientRect();
    signatureCanvas.width = Math.max(rect.width, 320);
    signatureCanvas.height = 160;
    signatureCtx.lineWidth = 2;
    signatureCtx.lineCap = 'round';
    signatureCtx.strokeStyle = '#4d4037';

    if (data) {
        const img = new Image();
        img.onload = () => signatureCtx.drawImage(img, 0, 0, signatureCanvas.width, signatureCanvas.height);
        img.src = data;
    }
}

function signaturePoint(event) {
    const rect = signatureCanvas.getBoundingClientRect();
    const source = event.touches?.[0] ?? event;
    return {
        x: source.clientX - rect.left,
        y: source.clientY - rect.top,
    };
}

function startSignature(event) {
    if (!signatureCanvas || !selectedServiceRequiresConsent()) return;
    event.preventDefault();
    signatureDrawing = true;
    const point = signaturePoint(event);
    signatureCtx.beginPath();
    signatureCtx.moveTo(point.x, point.y);
}

function drawSignature(event) {
    if (!signatureDrawing || !signatureCanvas) return;
    event.preventDefault();
    const point = signaturePoint(event);
    signatureCtx.lineTo(point.x, point.y);
    signatureCtx.stroke();
    signatureHasInk = true;
    signatureInput.value = signatureCanvas.toDataURL('image/png');
    setHint('hint_consent', 'Consent signed.', 'ok');
}

function stopSignature() {
    signatureDrawing = false;
}

function clearSignature() {
    if (!signatureCanvas || !signatureCtx) return;
    signatureCtx.clearRect(0, 0, signatureCanvas.width, signatureCanvas.height);
    signatureHasInk = false;
    signatureInput.value = '';
}

signatureCanvas?.addEventListener('mousedown', startSignature);
signatureCanvas?.addEventListener('mousemove', drawSignature);
window.addEventListener('mouseup', stopSignature);
signatureCanvas?.addEventListener('touchstart', startSignature, { passive: false });
signatureCanvas?.addEventListener('touchmove', drawSignature, { passive: false });
window.addEventListener('touchend', stopSignature);
window.addEventListener('resize', resizeSignatureCanvas);
toggleConsentSection();

function closeConfirmModal() {
    document.getElementById('confirmModal').classList.add('hidden');
    document.body.style.overflow = '';
}

function confirmSubmit() {
    closeConfirmModal();
    setTimeout(() => {
        document.getElementById('bookingForm').submit();
    }, 150);
}

/* Fully booked modal */
function openFullModal(dateStr) {
    document.getElementById('fullModalDate').textContent = formatDateFriendly(dateStr);
    document.getElementById('fullModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeFullModal() {
    document.getElementById('fullModal').classList.add('hidden');
    document.body.style.overflow = '';
}

function scrollToDateField() {
    const f = document.getElementById('field_date');
    if (f) {
        f.scrollIntoView({ behavior: 'smooth', block: 'center' });
        setTimeout(() => f.focus(), 400);
    }
}

/* Gallery modal */
function openGalleryModal(trigger) {
    const modal   = document.getElementById('galleryModal');
    const image   = document.getElementById('galleryModalImage');
    const title   = document.getElementById('galleryModalTitle');
    const caption = document.getElementById('galleryModalCaption');
    const text    = document.getElementById('galleryModalText');

    image.src           = trigger.dataset.gallerySrc;
    image.alt           = trigger.dataset.galleryTitle || 'Gallery image';
    title.textContent   = trigger.dataset.galleryTitle || '';
    caption.textContent = trigger.dataset.galleryCaption || '';
    text.classList.toggle('hidden', !title.textContent && !caption.textContent);

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function closeGalleryModal() {
    const modal = document.getElementById('galleryModal');
    const image = document.getElementById('galleryModalImage');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    image.src = '';
    document.body.style.overflow = '';
}

document.getElementById('galleryModal')?.addEventListener('click', function (e) {
    if (e.target === this) closeGalleryModal();
});

const galleryToggle = document.getElementById('galleryToggle');
const galleryExtraItems = Array.from(document.querySelectorAll('[data-gallery-extra="1"]'));

if (galleryToggle && galleryExtraItems.length) {
    galleryToggle.addEventListener('click', function () {
        const expanded = this.dataset.expanded === 'true';

        galleryExtraItems.forEach((item) => item.classList.toggle('hidden', expanded));

        this.dataset.expanded = String(!expanded);
        this.textContent = expanded ? 'Show all gallery' : 'Show fewer photos';
    });
}

/* Keyboard escape */
document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    if (!document.getElementById('galleryModal')?.classList.contains('hidden')) closeGalleryModal();
    if (!document.getElementById('confirmModal')?.classList.contains('hidden')) closeConfirmModal();
    if (!document.getElementById('fullModal')?.classList.contains('hidden'))    closeFullModal();
});

/* Click outside to close confirm / full-booked modals */
['confirmModal','fullModal'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.add('hidden');
            document.body.style.overflow = '';
        }
    });
});
</script>

</body>
</html>

