<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><p class="text-xs font-bold uppercase tracking-[.2em] text-[#a48d78]">Lunelle care portal</p><h2 class="mt-1 font-serif text-3xl text-[#4d4037]">Dashboard Overview</h2></div>
            <span class="rounded-full bg-[#ded0bc] px-4 py-2 text-xs font-bold uppercase tracking-wider text-[#4d4037]">{{ $type }}</span>
        </div>
    </x-slot>

    <div class="py-7 sm:py-10" @if($type === 'member') x-data="{ bookingOpen: false }" @endif>
        <div class="mx-auto max-w-7xl space-y-7 px-4 sm:px-6 lg:px-8">
            @if(session('success'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>@endif
            @if($type === 'member')
                <section class="overflow-hidden rounded-2xl bg-white shadow-[0_10px_28px_rgba(77,64,55,.08)] ring-1 ring-[#a48d78]/10">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#a48d78]/15 px-5 py-4 sm:px-6">
                        <div><p class="font-serif text-2xl text-[#4d4037]">Previous bookings</p><p class="mt-1 text-sm text-[#66584e]">Your appointment history in one place.</p></div>
                        <div class="flex flex-wrap items-center gap-3">
                            <a href="{{ route('claims.create') }}" class="text-sm font-bold text-[#8d6c5a] hover:text-[#4d4037]">Claim previous booking</a>
                            <button type="button" @click="bookingOpen = true" class="rounded-xl bg-[#4d4037] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#665044]">Book an appointment</button>
                        </div>
                    </div>
                    @forelse($previousAppointments as $appointment)
                        <a href="{{ route('member.appointments.show', $appointment) }}" class="flex flex-wrap items-center justify-between gap-3 border-b border-[#a48d78]/10 px-5 py-4 last:border-0 transition hover:bg-[#faf9f6] sm:px-6">
                            <div><p class="font-semibold text-[#4d4037]">{{ $appointment->service->name }}</p><p class="mt-1 text-sm text-[#66584e]">{{ $appointment->scheduled_at->format('M j, Y · g:i A') }}</p></div>
                            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $appointment->status === 'completed' ? 'bg-emerald-50 text-emerald-700' : ($appointment->status === 'pending' ? 'bg-amber-50 text-amber-700' : 'bg-[#f5f1eb] text-[#66584e]') }}">{{ ucfirst($appointment->status) }}</span>
                        </a>
                    @empty
                        <div class="px-5 py-8 text-sm text-[#66584e] sm:px-6">No appointments have been linked to this account yet. <a href="{{ route('claims.create') }}" class="font-bold text-[#8d6c5a]">Claim a past appointment</a>.</div>
                    @endforelse
                </section>
                <div x-cloak x-show="bookingOpen" x-transition.opacity class="fixed inset-0 z-[70] grid place-items-center bg-[#302825]/45 p-4 backdrop-blur-sm" @keydown.escape.window="bookingOpen = false">
                    <div @click.outside="bookingOpen = false" class="w-full max-w-lg rounded-2xl bg-[#fffdfb] p-6 shadow-2xl sm:p-8">
                        <div class="flex items-start justify-between gap-4"><div><p class="text-xs font-bold uppercase tracking-[.2em] text-[#a48d78]">New request</p><h3 class="mt-1 font-serif text-3xl text-[#4d4037]">Book an appointment</h3><p class="mt-2 text-sm leading-6 text-[#66584e]">Your request will be sent to the studio for approval.</p></div><button type="button" @click="bookingOpen = false" class="rounded-lg p-2 text-xl text-[#66584e]" aria-label="Close">×</button></div>
                        <form method="POST" action="{{ route('appointments.request') }}" class="mt-6 space-y-4">@csrf
                            <input type="hidden" name="full_name" value="{{ $user->name }}"><input type="hidden" name="email" value="{{ $user->email }}"><input type="hidden" name="participants[0][name]" value="{{ $user->name }}">
                            <div><label class="text-sm font-bold text-[#4d4037]" for="member_contact">Contact number</label><input id="member_contact" required name="contact_number" inputmode="tel" placeholder="e.g. 09171234567" class="mt-2 w-full rounded-xl border-[#a48d78]/30"></div>
                            <div class="grid gap-4 sm:grid-cols-2"><div><label class="text-sm font-bold text-[#4d4037]" for="member_date">Preferred date</label><input id="member_date" required type="date" min="{{ now()->toDateString() }}" name="date" class="mt-2 w-full rounded-xl border-[#a48d78]/30"></div><div><label class="text-sm font-bold text-[#4d4037]" for="member_time">Preferred time</label><input id="member_time" required type="time" name="time" class="mt-2 w-full rounded-xl border-[#a48d78]/30"></div></div>
                            <div><label class="text-sm font-bold text-[#4d4037]" for="member_service">Service</label><select id="member_service" required name="participants[0][service_ids][]" class="mt-2 w-full rounded-xl border-[#a48d78]/30"><option value="">Select a service</option>@foreach($bookableServices as $service)<option value="{{ $service->id }}">{{ $service->name }}{{ $service->duration ? ' · '.$service->duration.' mins' : '' }}</option>@endforeach</select></div>
                            <div><label class="text-sm font-bold text-[#4d4037]" for="member_notes">Note <span class="font-normal text-[#66584e]">(optional)</span></label><textarea id="member_notes" name="notes" rows="2" class="mt-2 w-full rounded-xl border-[#a48d78]/30"></textarea></div>
                            <button class="w-full rounded-xl bg-[#4d4037] px-5 py-3 text-sm font-bold text-white">Send appointment request</button>
                        </form>
                    </div>
                </div>
            @endif
            @if($type !== 'member')
            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([['T', 'Total', $stats['total'], 'bg-[#e7dcc9] text-[#a48d78]'], ['P', 'Pending', $stats['pending'], 'bg-[#fff7e7] text-[#b57927]'], ['C', 'Confirmed', $stats['confirmed'], 'bg-emerald-50 text-emerald-700'], ['C', 'Completed', $stats['completed'], 'bg-stone-100 text-[#66584e]']] as [$letter, $label, $value, $color])
                    <article class="flex min-h-28 items-center gap-4 rounded-2xl bg-white px-5 py-5 shadow-[0_10px_28px_rgba(77,64,55,.08)] ring-1 ring-[#a48d78]/10">
                        <span class="grid size-11 shrink-0 place-items-center rounded-xl text-sm font-bold {{ $color }}">{{ $letter }}</span>
                        <div><p class="text-xs font-bold uppercase tracking-wide text-[#8d765f]">{{ $label }}</p><p class="mt-1 font-serif text-3xl font-bold text-[#4d4037]">{{ $value }}</p></div>
                    </article>
                @endforeach
            </section>
            @endif

            @if($type !== 'member')
            <section class="overflow-hidden rounded-2xl bg-white shadow-[0_10px_28px_rgba(77,64,55,.08)] ring-1 ring-[#a48d78]/10">
                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-[#a48d78]/15 px-5 py-5 sm:px-6">
                    <div><h3 class="font-serif text-xl font-bold text-[#4d4037]">{{ $monthStart->format('F Y') }} Appointment Calendar</h3><p class="mt-1 text-sm text-[#66584e]">Scheduled and completed treatment sessions by date</p></div>
                    @if(in_array($type, ['staff', 'administrator'], true))<a href="{{ route('studio.appointments.index') }}" class="text-sm font-bold text-[#a48d78] hover:text-[#806752]">Manage sessions</a>@endif
                </div>
                <div class="overflow-x-auto">
                    <div class="min-w-[760px]">
                        <div class="grid grid-cols-7 border-b border-[#a48d78]/15 bg-[#faf9f6] text-center text-[10px] font-bold uppercase tracking-wide text-[#8d765f]">
                            @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $weekday)<div class="py-3">{{ $weekday }}</div>@endforeach
                        </div>
                        <div class="grid grid-cols-7">
                            @foreach($calendarDays as $day)
                                @php($items = $monthAppointments->filter(fn ($appointment) => $appointment->scheduled_at->isSameDay($day)))
                                <div class="min-h-36 border-b border-r border-[#a48d78]/10 p-2 {{ $day->month !== $monthStart->month ? 'bg-[#faf9f6]/65 text-[#a48d78]/55' : '' }}">
                                    <div class="flex items-center justify-between"><span class="text-xs font-bold text-[#4d4037]">{{ $day->day }}</span>@if($items->isNotEmpty())<span class="rounded-full bg-[#e7dcc9] px-2 py-0.5 text-[10px] font-bold text-[#8d765f]">{{ $items->count() }}</span>@endif</div>
                                    <div class="mt-2 space-y-1.5">
                                        @foreach($items->take(3) as $appointment)
                                            <div class="rounded-lg border border-[#a48d78]/15 bg-[#faf9f6] px-2 py-1.5 text-[10px] shadow-sm"><div class="flex justify-between gap-1 font-bold text-[#4d4037]"><span class="truncate">{{ $appointment->client->name }}</span><span class="shrink-0 text-[#a48d78]">{{ $appointment->scheduled_at->format('g:i A') }}</span></div><div class="mt-0.5 flex justify-between gap-1"><span class="truncate text-[#66584e]">{{ $appointment->service->name }}</span><span class="rounded px-1 text-[9px] font-bold {{ $appointment->status === 'completed' ? 'bg-emerald-50 text-emerald-700' : 'bg-[#fff7e7] text-[#b57927]' }}">{{ ucfirst($appointment->status) }}</span></div></div>
                                        @endforeach
                                        @if($items->count() > 3)<p class="text-center text-[10px] font-bold text-[#a48d78]">+{{ $items->count() - 3 }} more</p>@endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>
            @endif
        </div>
    </div>
</x-app-layout>
