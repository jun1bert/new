        @if(auth()->user()->user_type === 'administrator')
            <x-calendar-dialog name="calendar-hours" :show="$errors->any() && old('hours') !== null" focusable>
                <section class="p-5" role="dialog" aria-modal="true" aria-labelledby="calendar-hours-title" x-data="{ target: @js((string) old('provider_id', 'studio') ?: 'studio') }">
                    <div class="flex items-center justify-between gap-3"><h3 id="calendar-hours-title" class="font-serif text-2xl text-[var(--brand-forest)]">Working hours</h3><x-modal-close x-on:click="$dispatch('close-modal', 'calendar-hours')" label="Close working hours" /></div>
                    <p class="mt-2 text-sm text-[var(--brand-text-muted)]">Unchecked days are unavailable. Changes apply to new reservations; existing bookings stay unchanged.</p>
                    <label class="mt-4 block text-sm font-semibold" for="hours-target">Edit schedule</label><select id="hours-target" x-model="target" class="mt-2 w-full rounded-lg border-gray-300 text-sm"><option value="studio">Studio business hours</option>@foreach($providers as $provider)<option value="{{ $provider->id }}">{{ $provider->name }}</option>@endforeach</select>
                    <template x-if="target === 'studio'"><div>@include('studio.partials.working-hours', ['scheduleName' => 'Studio business hours', 'scheduleId' => null, 'scheduleHours' => $hours, 'expanded' => true])</div></template>
                    @foreach($providers as $provider)
                        <template x-if="target === '{{ $provider->id }}'"><div>@include('studio.partials.working-hours', ['scheduleName' => $provider->name, 'scheduleId' => $provider->id, 'scheduleHours' => isset($shifts[$provider->id]) ? json_decode($shifts[$provider->id]->hours, true) : null, 'expanded' => true])</div></template>
                    @endforeach
                </section>
            </x-calendar-dialog>
        @endif