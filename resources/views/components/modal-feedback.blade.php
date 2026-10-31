@props(['nextSessionPrompt' => null])
@php
    $usesModals = request()->routeIs('admin.users.*', 'admin.services.*', 'admin.products.index', 'studio.appointments.*', 'studio.gallery.*', 'dashboard');
    $message = session('success') ?: session('status');
@endphp
@if($usesModals)
    <div class="studio-calendar modal-feedback-host" style="padding:0;margin:0;max-width:none">
        @if($message && !$nextSessionPrompt)
            <x-calendar-dialog name="portal-feedback-success" :show="true" maxWidth="md">
                <section class="p-6" role="dialog" aria-modal="true" aria-labelledby="feedback-success-title">
                    <div class="flex items-center justify-between gap-4"><h3 id="feedback-success-title" class="font-serif text-2xl">Success</h3><x-modal-close x-on:click="$dispatch('close-modal', 'portal-feedback-success')" label="Close success message" /></div>
                    <p role="status" class="modal-feedback-success mt-4">{{ $message }}</p>
                    <button type="button" class="calendar-button calendar-button-primary mt-5" x-on:click="$dispatch('close-modal', 'portal-feedback-success')">Done</button>
                </section>
            </x-calendar-dialog>
        @endif
        @if($errors->any())
            <template id="portal-validation-feedback" data-old-input="{{ json_encode(\Illuminate\Support\Arr::except(session()->getOldInput(), ['password', 'password_confirmation', 'current_password', '_token'])) }}">
                <div role="alert" class="modal-feedback-errors"><p>Please check the following:</p><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            </template>
            <x-calendar-dialog name="portal-feedback-error" maxWidth="md">
                <section class="p-6" role="dialog" aria-modal="true" aria-labelledby="feedback-error-title">
                    <div class="flex items-center justify-between gap-4"><h3 id="feedback-error-title" class="font-serif text-2xl">Unable to save</h3><x-modal-close x-on:click="$dispatch('close-modal', 'portal-feedback-error')" label="Close error message" /></div>
                    <div role="alert" class="modal-feedback-errors mt-4"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                    <button type="button" class="calendar-button mt-5" x-on:click="$dispatch('close-modal', 'portal-feedback-error')">Close</button>
                </section>
            </x-calendar-dialog>
        @endif
    </div>
@endif
