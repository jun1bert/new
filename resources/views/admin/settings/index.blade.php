<x-app-layout>
    <x-slot name="header">
        <div><p class="portal-eyebrow">Owner's workspace</p><h2 class="mt-1 font-serif text-3xl">Settings</h2><p class="mt-1 text-sm text-[var(--brand-text-muted)]">Manage how your studio operates.</p></div>
    </x-slot>
    <div class="py-8 sm:py-10"><div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <section class="rounded-2xl border border-[var(--brand-border)] bg-white p-5 shadow-sm sm:p-7" x-data="{ target: @js((string) old('provider_id', 'studio') ?: 'studio') }">
            <div><p class="text-xs font-bold uppercase tracking-widest text-[var(--brand-moss)]">Scheduling</p><h3 class="mt-2 font-serif text-2xl text-[var(--brand-forest)]">Working hours</h3><p class="mt-2 max-w-2xl text-sm leading-6 text-[var(--brand-text-muted)]">Set studio business hours and weekly staff shifts. Unchecked days are unavailable. Changes apply to new reservations; existing appointments stay unchanged.</p></div>
            @if(session('status'))<p role="status" class="mt-4 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('status') }}</p>@endif
            @if($errors->any())<div role="alert" class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-800"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <label class="mt-6 block text-sm font-semibold text-[var(--brand-forest)]" for="settings-hours-target">Schedule to edit</label>
            <select id="settings-hours-target" x-model="target" class="mt-2 w-full max-w-md rounded-lg border-[var(--brand-border)] text-sm"><option value="studio">Studio business hours</option>@foreach($providers as $provider)<option value="{{ $provider->id }}">{{ $provider->name }}</option>@endforeach</select>
            <p class="mt-2 text-sm text-[var(--brand-text-muted)]">Staff without a saved shift use the studio's business hours.</p>
            <template x-if="target === 'studio'"><div>@include('studio.partials.working-hours', ['scheduleName' => 'Studio business hours', 'scheduleId' => null, 'scheduleHours' => $hours, 'expanded' => true])</div></template>
            @foreach($providers as $provider)
                <template x-if="target === '{{ $provider->id }}'"><div>@include('studio.partials.working-hours', ['scheduleName' => $provider->name, 'scheduleId' => $provider->id, 'scheduleHours' => isset($shifts[$provider->id]) ? json_decode($shifts[$provider->id]->hours, true) : null, 'expanded' => true])</div></template>
            @endforeach
        </section>
    </div></div>
</x-app-layout>
