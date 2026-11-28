<x-app-layout>
    <x-slot name="header">
        <div><p class="text-xs font-bold uppercase tracking-[.2em] text-[var(--brand-moss)]">YOUR ACCOUNT</p><h2 class="mt-1 font-serif text-3xl text-[var(--brand-forest)]">Profile &amp; security</h2></div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <section class="max-w-xl">
                    <h2 class="text-lg font-medium text-gray-900">Profile photo</h2>
                    <p class="mt-1 text-sm text-gray-600">Add a photo for your account. JPG, PNG, or WebP up to 2 MB.</p>
                    <div class="mt-4 flex items-center gap-4">
                        @if ($user->profile_photo_path)
                            <img src="{{ route('profile.photo') }}" alt="Your profile photo" class="h-20 w-20 rounded-full object-cover">
                        @else
                            <span aria-hidden="true" class="grid h-20 w-20 place-items-center rounded-full bg-[var(--brand-neutral)] text-2xl font-semibold text-[var(--brand-forest)]">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                        @endif
                        <form method="post" action="{{ route('profile.photo.update') }}" enctype="multipart/form-data" class="min-w-0">@csrf
                            <label for="profile_photo" class="sr-only">Choose a profile photo</label>
                            <input id="profile_photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp" required class="block w-full text-sm text-gray-600">
                            <x-input-error class="mt-2" :messages="$errors->get('photo')" />
                            <x-primary-button class="mt-3">Save photo</x-primary-button>
                        </form>
                    </div>
                    @if ($user->profile_photo_path)<form method="post" action="{{ route('profile.photo.destroy') }}" class="mt-3">@csrf @method('delete')<button type="submit" class="text-sm font-semibold text-[var(--brand-moss)] underline">Remove photo</button></form>@endif
                    @if (in_array(session('status'), ['profile-photo-updated', 'profile-photo-removed']))<p role="status" class="mt-3 text-sm text-gray-600">{{ session('status') === 'profile-photo-updated' ? 'Photo saved.' : 'Photo removed.' }}</p>@endif
                </section>
            </div>
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <section class="max-w-xl">
                    <h2 class="text-lg font-medium text-gray-900">Health profile</h2>
                    <p class="mt-1 text-sm text-gray-600">Save information you may want to share for future treatments. All fields are optional; leaving one blank does not mean there is nothing to declare.</p>
                    <p class="mt-2 text-sm text-gray-600">This profile does not replace a treatment consent form. Please update it when your information changes.</p>
                    @php $healthDetails = $user->healthProfile?->details ?? []; @endphp
                    <form method="post" action="{{ route('profile.health.update') }}" class="mt-6 space-y-5">
                        @csrf
                        @method('patch')
                        @foreach ([
                            'allergies' => ['Allergies and sensitivities', 'Include any known reactions to products, medicines, or materials.'],
                            'medical_conditions' => ['Health conditions', 'Include conditions you think the studio should know about.'],
                            'medications' => ['Medicines and supplements', 'Include current medicines or supplements relevant to your care.'],
                            'skin_concerns' => ['Skin concerns and recent treatments', 'Share concerns or treatments relevant to your visit.'],
                            'other_information' => ['Other information', 'Share anything else you would like to discuss before treatment.'],
                        ] as $field => [$label, $hint])
                            <div>
                                <x-input-label :for="$field" :value="$label" />
                                <p id="{{ $field }}-hint" class="mt-1 text-xs text-gray-600">{{ $hint }}</p>
                                <textarea id="{{ $field }}" name="{{ $field }}" rows="2" maxlength="2000" aria-describedby="{{ $field }}-hint" class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old($field, $healthDetails[$field] ?? '') }}</textarea>
                                <x-input-error class="mt-2" :messages="$errors->get($field)" />
                            </div>
                        @endforeach
                        <div class="flex items-center gap-4"><x-primary-button>Save health profile</x-primary-button>@if (session('status') === 'health-profile-updated')<p role="status" class="text-sm text-gray-600">Saved.</p>@endif</div>
                    </form>
                </section>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
