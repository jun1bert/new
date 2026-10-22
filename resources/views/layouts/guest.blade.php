<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Lunelle Aesthetics') }} | Member Login</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center px-4 py-10 bg-[#eee8de]" style="background: linear-gradient(135deg, #eee8de, #d6c3aa);">
            <div class="text-center">
                <a href="/" class="text-[#4d4037]">
                    <span class="block font-serif text-4xl tracking-[.16em]">LUNELLE</span>
                    <span class="block mt-1 text-[10px] font-bold tracking-[.34em] text-[#a48d78]">AESTHETICS</span>
                </a>
                <p class="mt-5 text-sm text-[#66584e]">Member care portal</p>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-7 bg-white/90 shadow-xl shadow-[#4d4037]/10 overflow-hidden rounded-2xl border border-[#a48d78]/20">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
