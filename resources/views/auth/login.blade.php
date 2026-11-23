<x-guest-layout>
    <div class="auth-heading mb-6">
        <p class="auth-eyebrow">YOUR ACCOUNT</p>
        <h1>Lovely to see<br><em>you again.</em></h1>
        <p class="auth-intro">Sign in to view your appointments and plan your next visit.</p>
    </div>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4" x-data="{ visible: false }">
            <x-input-label for="password" :value="__('Password')" />

            <div class="auth-password">
            <x-text-input id="password" class="block mt-1 w-full" x-bind:type="visible ? 'text' : 'password'"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />
            <button type="button" class="auth-show" x-on:click="visible = !visible" x-bind:aria-pressed="visible" aria-controls="password" x-text="visible ? 'Hide' : 'Show'">Show</button>
            </div>

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="auth-submit">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
    <p class="auth-switch">New to Shafer's? <a href="{{ route('register') }}">Create an account &#8594;</a></p>
</x-guest-layout>
