<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3 {{ $type !== 'member' ? 'studio-banner-header' : '' }}">
            <div><p class="text-xs font-bold uppercase tracking-[.2em] text-[var(--brand-moss)]">Shafer's care portal</p><h2 class="mt-1 font-serif text-3xl text-[var(--brand-forest)]">{{ $type === 'member' ? 'My care space' : 'Studio overview' }}</h2></div>
            <span class="rounded-full bg-[var(--brand-neutral)] px-4 py-2 text-xs font-bold uppercase tracking-wider text-[var(--brand-forest)]">{{ $type }}</span>
        </div>
    </x-slot>

    <div class="py-7 sm:py-10 {{ $type !== 'member' ? 'studio-overview-content' : '' }}" @if($type === 'member') x-data="{ bookingOpen: {{ $errors->any() ? 'true' : 'false' }} }" @endif>
        <div class="mx-auto max-w-7xl space-y-7 px-4 sm:px-6 lg:px-8 {{ $type !== 'member' ? 'studio-overview-stack' : '' }}">

            @if($type !== 'member')
                <div class="studio-greeting"><h1>Welcome back, <span>{{ $user->name }}.</span></h1><time datetime="{{ now('Asia/Manila')->toDateString() }}">{{ now('Asia/Manila')->format('D, M j, Y') }}</time></div>
                @if($type === 'staff')
                    <section class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-[var(--brand-border)] bg-white p-5 shadow-sm" aria-label="Attendance">
                        <div><h2 class="font-serif text-xl">Your attendance</h2><p class="mt-1 text-sm text-[var(--brand-text-muted)]">@if($activeAttendance)Timed in since {{ $activeAttendance->clocked_in_at->copy()->timezone('Asia/Manila')->format('M j, Y · g:i A') }}@else You are currently timed out.@endif</p></div>
                        <p class="text-sm font-semibold text-[var(--brand-moss)]">Scan the current shop QR code to {{ $activeAttendance ? 'time out' : 'time in' }}.</p>
                    </section>
                @endif
            @else
            <section class="portal-welcome">
                <div><p class="portal-eyebrow">{{ $type === 'member' ? 'A LITTLE TIME FOR YOU' : 'YOUR STUDIO, AT A GLANCE' }}</p><h1>Welcome back, <em>{{ $user->name }}.</em></h1><p>{{ $type === 'member' ? 'Your appointments, treatments, and moments of care, all in one place.' : 'A clear view of your appointments and the care ahead.' }}</p></div>
                <div class="portal-welcome-detail"><span aria-hidden="true">&#10022;</span><p>{{ now()->format('l') }}</p><strong>{{ now()->format('F j, Y') }}</strong></div>
            </section>
            @endif
            @if($type === 'member')
                <section class="overflow-hidden rounded-2xl bg-white shadow-[0_10px_28px_rgba(77,64,55,.08)] ring-1 ring-[var(--brand-moss)]/10">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[var(--brand-moss)]/15 px-5 py-4 sm:px-6">
                        <div><p class="font-serif text-2xl text-[var(--brand-forest)]">Recent appointments</p><p class="mt-1 text-sm text-[var(--brand-text-muted)]">Your latest requests and treatment sessions.</p></div>
                        <div class="flex flex-wrap items-center gap-3">
                            <a href="{{ route('claims.create') }}" class="text-sm font-bold text-[var(--brand-moss)] hover:text-[var(--brand-forest)]">Claim previous booking</a>
                            <button type="button" @click="bookingOpen = true" class="rounded-xl bg-[var(--brand-forest)] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[var(--brand-forest)]">Book an appointment</button>
                        </div>
                    </div>
                    @forelse($previousAppointments as $appointment)
                        <a href="{{ route('member.appointments.show', $appointment) }}" class="flex flex-wrap items-center justify-between gap-3 border-b border-[var(--brand-moss)]/10 px-5 py-4 last:border-0 transition hover:bg-[var(--brand-white)] sm:px-6">
                            <div><p class="font-semibold text-[var(--brand-forest)]">{{ $appointment->service_names }}</p><p class="mt-1 text-sm text-[var(--brand-text-muted)]">{{ $appointment->scheduled_at->copy()->timezone('Asia/Manila')->format('M j, Y · g:i A') }}</p></div>
                            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $appointment->status === 'completed' ? 'bg-emerald-50 text-emerald-700' : ($appointment->status === 'pending' ? 'bg-amber-50 text-amber-700' : 'bg-[var(--brand-cream)] text-[var(--brand-text-muted)]') }}">{{ ucfirst(str_replace('_', ' ', $appointment->status)) }}</span>
                        </a>
                    @empty
                        <div class="px-5 py-8 text-sm text-[var(--brand-text-muted)] sm:px-6">No appointments have been linked to this account yet. <a href="{{ route('claims.create') }}" class="font-bold text-[var(--brand-moss)]">Claim a past appointment</a>.</div>
                    @endforelse
                </section>
                <div x-cloak x-show="bookingOpen" x-transition.opacity class="fixed inset-0 z-[70] grid place-items-center bg-[var(--brand-forest)]/45 p-4 backdrop-blur-sm" @keydown.escape.window="bookingOpen = false">
                    <div @click.outside="bookingOpen = false" class="w-full max-w-lg rounded-2xl bg-[var(--brand-white)] p-6 shadow-2xl sm:p-8">
                        <div class="flex items-start justify-between gap-4"><div><p class="text-xs font-bold uppercase tracking-[.2em] text-[var(--brand-moss)]">New request</p><h3 class="mt-1 font-serif text-3xl text-[var(--brand-forest)]">Book an appointment</h3><p class="mt-2 text-sm leading-6 text-[var(--brand-text-muted)]">Your request will be sent to the studio for approval.</p></div><x-modal-close @click="bookingOpen = false" label="Close" /></div>
                        <form method="POST" action="{{ route('appointments.request') }}" class="mt-6 space-y-4">@csrf
                            @if($errors->any())<div role="alert" class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800"><p class="font-medium">Please check your request.</p><ul class="mt-2 list-disc pl-4">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                            <input type="hidden" name="full_name" value="{{ $user->name }}"><input type="hidden" name="email" value="{{ $user->email }}"><input type="hidden" name="participants[0][name]" value="{{ $user->name }}">
                            <div><label class="text-sm font-bold text-[var(--brand-forest)]" for="member_contact">Contact number</label><input id="member_contact" required name="contact_number" value="{{ old('contact_number') }}" autocomplete="tel" inputmode="tel" placeholder="e.g. 09171234567" class="mt-2 w-full rounded-xl border-[var(--brand-moss)]/30"></div>
                            <div class="grid gap-4 sm:grid-cols-2"><div><label class="text-sm font-bold text-[var(--brand-forest)]" for="member_date">Preferred date</label><input id="member_date" required type="date" value="{{ old('date') }}" min="{{ now()->toDateString() }}" name="date" class="mt-2 w-full rounded-xl border-[var(--brand-moss)]/30"></div><div><label class="text-sm font-bold text-[var(--brand-forest)]" for="member_time">Preferred time</label><input id="member_time" required type="time" value="{{ old('time') }}" name="time" class="mt-2 w-full rounded-xl border-[var(--brand-moss)]/30"></div></div>
                            <fieldset><legend class="text-sm font-bold text-[var(--brand-forest)]">Services</legend><p class="mt-1 text-xs text-[var(--brand-text-muted)]">Choose one or more services for your appointment.</p><div class="mt-2 max-h-60 space-y-2 overflow-y-auto rounded-xl border border-[var(--brand-moss)]/30 p-3">@foreach($bookableServices as $service)<label class="flex items-start gap-3 rounded-lg p-2"><input type="checkbox" name="participants[0][service_ids][]" value="{{ $service->id }}" @checked(in_array((string) $service->id, array_map('strval', old('participants.0.service_ids', [])), true)) class="mt-1 rounded border-gray-300 text-[var(--brand-moss)] focus:ring-[var(--brand-moss)]" /><span class="text-sm">{{ $service->name }}<span class="block text-xs text-[var(--brand-text-muted)]">PHP {{ number_format($service->price, 2) }}{{ $service->duration ? ' · '.$service->duration.' mins' : '' }} · {{ $service->session_count }} session(s)</span></span></label>@endforeach</div></fieldset>
                            <div><label class="text-sm font-bold text-[var(--brand-forest)]" for="member_notes">Note <span class="font-normal text-[var(--brand-text-muted)]">(optional)</span></label><textarea id="member_notes" name="notes" rows="2" class="mt-2 w-full rounded-xl border-[var(--brand-moss)]/30">{{ old('notes') }}</textarea></div>
                            <button class="w-full rounded-xl bg-[var(--brand-forest)] px-5 py-3 text-sm font-bold text-white">Send appointment request</button>
                        </form>
                    </div>
                </div>
            @endif
            @if($type !== 'member')
            <div><p class="mb-3 text-sm text-[var(--brand-text-muted)]">All appointments &middot; all time</p><section aria-label="All appointments, all time" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([['T', 'Total', $stats['total'], 'bg-[var(--brand-peach)] text-[var(--brand-moss)]'], ['P', 'Pending', $stats['pending'], 'bg-[#fff7e7] text-[var(--brand-orange)]'], ['S', 'Scheduled', $stats['scheduled'], 'bg-emerald-50 text-emerald-700'], ['C', 'Completed', $stats['completed'], 'bg-stone-100 text-[var(--brand-text-muted)]']] as [$letter, $label, $value, $color])
                    <article class="flex min-h-28 items-center gap-4 rounded-2xl bg-white px-5 py-5 shadow-[0_10px_28px_rgba(77,64,55,.08)] ring-1 ring-[var(--brand-moss)]/10">
                        <span class="grid size-11 shrink-0 place-items-center rounded-xl text-sm font-bold {{ $color }}">{{ $letter }}</span>
                        <div><p class="text-xs font-bold uppercase tracking-wide text-[var(--brand-moss)]">{{ $label }}</p><p class="mt-1 font-serif text-3xl font-bold text-[var(--brand-forest)]">{{ $value }}</p></div>
                    </article>
                @endforeach
            </section></div>
            @endif

            @if($type !== 'member')
            @include('studio.partials.calendar')
            @endif
        </div>
    </div>
</x-app-layout>
