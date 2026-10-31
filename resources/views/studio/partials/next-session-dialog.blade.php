@if($nextSessionSource)
    @php
        $nextNumber = $nextSessionSource->session_number + 1;
        $remainingServices = $nextSessionSource->booked_services->filter(fn ($service) => ($service->pivot?->session_count ?? $service->session_count) >= $nextNumber);
        $defaultProvider = $nextSessionSource->provider?->user_type === 'staff' && $nextSessionSource->provider->is_active
            ? $nextSessionSource->provider_id : (auth()->user()->user_type === 'staff' ? auth()->id() : null);
    @endphp
    <div class="studio-calendar" style="padding:0;margin:0;max-width:none">
        <x-calendar-dialog name="schedule-next-session" :show="true" maxWidth="md">
            <section class="p-6" role="dialog" aria-modal="true" aria-labelledby="schedule-next-session-title">
                <div class="flex items-start justify-between gap-4"><div><p class="text-xs font-semibold uppercase tracking-wider text-[#713b51]">Continue treatment</p><h3 id="schedule-next-session-title" class="mt-1 font-serif text-2xl">Schedule session {{ $nextNumber }}</h3></div><x-modal-close x-on:click="$dispatch('close-modal', 'schedule-next-session')" label="Close next session scheduling" /></div>
                @if(session('status'))<p role="status" class="modal-feedback-success mt-4">{{ session('status') }}</p>@endif
                <p class="mt-4 text-sm font-semibold">{{ $nextSessionSource->client->name }}</p><p class="mt-1 text-sm text-[#786570]">{{ $remainingServices->pluck('name')->implode(', ') }}</p>
                <form method="POST" action="{{ route('studio.appointments.next-session', $nextSessionSource) }}" class="mt-5 space-y-4">@csrf
                    <input type="hidden" name="_next_session_appointment_id" value="{{ $nextSessionSource->id }}">
                    <label for="next-session-provider" class="block text-sm font-semibold">Treatment provider<select id="next-session-provider" name="provider_id" required class="mt-1 block w-full rounded-lg border-gray-300"><option value="">Choose a staff member</option>@foreach($providers as $provider)<option value="{{ $provider->id }}" @selected((string) old('provider_id', $defaultProvider) === (string) $provider->id)>{{ $provider->name }}</option>@endforeach</select></label>
                    <x-appointment-date-picker id="next-session" :value="(string) old('scheduled_at', '')" :appointment-id="$nextSessionSource->id" mode="next" :default-provider="$defaultProvider" />
                    <p class="text-xs text-[#786570]">Availability and working hours will be checked. This session is already completed; scheduling the next one will not record another payment.</p>
                    <div class="flex flex-wrap gap-2"><button class="calendar-button calendar-button-primary">Schedule session {{ $nextNumber }}</button><button type="button" class="calendar-button" x-on:click="$dispatch('close-modal', 'schedule-next-session')">Schedule later</button></div>
                </form>
            </section>
        </x-calendar-dialog>
    </div>
@endif
