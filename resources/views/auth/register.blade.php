<x-guest-layout>
    <div class="auth-heading mb-6">
        <p class="auth-eyebrow">A LITTLE CARE STARTS HERE</p>
        <h1>Your next chapter<br>of <em>self-care.</em></h1>
        <p class="auth-intro">Create your account to keep your appointments in one place.</p>
    </div>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Full name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4" x-data="{ visible: false }">
            <x-input-label for="password" :value="__('Password')" />

            <div class="auth-password">
            <x-text-input id="password" class="block mt-1 w-full" x-bind:type="visible ? 'text' : 'password'"
                            type="password"
                            name="password"
                            required autocomplete="new-password" aria-describedby="password-hint" />
            <button type="button" class="auth-show" x-on:click="visible = !visible" x-bind:aria-pressed="visible" aria-controls="password" x-text="visible ? 'Hide' : 'Show'">Show</button>
            </div>
            <p id="password-hint" class="auth-hint">Use at least 8 characters.</p>

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button class="auth-submit">
                {{ __('Create account') }}
            </x-primary-button>
        </div>
    </form>
    <p class="auth-switch">Already have an account? <a href="{{ route('login') }}">Log in &#8594;</a></p>
</x-guest-layout>
