<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Lunelle Aesthetics') }}</title>
        @include('layouts.partials.favicon')

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=montserrat:400,500,600,700|cormorant-garamond:500,500i,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/css/portal.css', 'resources/js/app.js'])
    </head>
    <body class="portal-page antialiased">
        <a href="#main-content" class="portal-skip">Skip to content</a>
        <div class="min-h-screen bg-[#faf5f7]">
            @include('layouts.navigation')

            <div class="portal-workspace min-h-screen lg:pl-64">
                <!-- Page Heading -->
                @isset($header)
                    <header class="portal-page-header border-b border-[#713b51]/15 bg-white/85 shadow-sm backdrop-blur">
                        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                            <div class="portal-header-content">{{ $header }}</div>
                        </div>
                    </header>
                @endisset

                <!-- Page Content -->
                <main id="main-content" tabindex="-1">
                    {{ $slot }}
                    <x-modal-feedback :next-session-prompt="request()->routeIs('studio.appointments.index') && (session('schedule_next_appointment_id') || old('_next_session_appointment_id'))" />
                </main>
                <footer class="portal-footer"><span>Lunelle Aesthetics</span><a href="{{ url('/') }}">Visit website <span aria-hidden="true">&#8599;</span></a></footer>
            </div>
        </div>
    </body>
</html>
