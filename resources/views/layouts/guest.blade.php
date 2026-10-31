<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ request()->routeIs('register') ? 'Create your account' : (request()->routeIs('login') ? 'Welcome back' : 'Account access') }} | Lunelle Aesthetics</title>
        @include('layouts.partials.favicon')

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=montserrat:400,500,600,700|cormorant-garamond:500,500i,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/css/auth.css', 'resources/js/app.js'])
    </head>
    <body class="auth-page font-sans text-gray-900 antialiased">
        <header class="auth-header"><a href="{{ url('/') }}" class="auth-brand">LUNELLE <small>AESTHETICS</small></a><a href="{{ url('/') }}">&#8592; Back to home</a></header>
        <main class="auth-layout">
        <aside class="auth-story">
            <img src="{{ asset('images/spa-ritual.jpg') }}" alt="Skincare essentials, soft towels, and pink tulips" width="1200" height="800">
            <div><p class="auth-eyebrow">YOUR MOMENT OF CARE</p><h2>A little time.<br>A little <em>you.</em></h2><p>Make room for your next feel-good ritual.</p><small>BEAUTY &nbsp; / &nbsp; WELLNESS &nbsp; / &nbsp; SELF-CARE</small></div>
        </aside>
        <section class="auth-form-area" aria-label="Account access">
        <div class="auth-form-content">
            <div class="auth-card">
                {{ $slot }}
            </div>
        </div>
        <p class="auth-footer">A little care. A lovely glow. &#10022; Lunelle Aesthetics</p>
        </section>
        </main>
    </body>
</html>
