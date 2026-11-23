@php
        $calendarBookings = $appointments->mapWithKeys(function ($booking) {
            $minutes = $booking->duration_minutes ?? $booking->booked_services->sum(fn ($service) => max(1, (int) ($service->duration ?: 60)));
            return [$booking->id => [
                'id' => $booking->id, 'name' => $booking->client->name, 'services' => $booking->service_names,
                'time' => $booking->scheduled_at->copy()->timezone('Asia/Manila')->format('D, M j, Y · g:i A').' – '.$booking->scheduled_at->copy()->addMinutes($minutes)->timezone('Asia/Manila')->format('g:i A'),
                'minutes' => $minutes, 'status' => $booking->status, 'statusLabel' => ucfirst(str_replace('_', ' ', $booking->status)),
                'provider' => $booking->provider?->name ?? 'Provider unassigned', 
                'manageUrl' => route('studio.appointments.index', ['appointment_id' => $booking->id]).'#appointment-'.$booking->id,
            ]];
        });
    @endphp
    <div id="calendar" class="studio-calendar studio-calendar-dashboard" x-data="{ bookings: @js($calendarBookings), selectedId: null, selected() { return this.bookings[this.selectedId] || {} } }">


        <section class="calendar-surface">
            <div class="calendar-toolbar">
                <div class="calendar-heading"><h3>{{ $start->format('F j, Y') }}@if($mode === 'week') &ndash; {{ $days->last()->format('M j') }}@endif</h3><span>{{ $appointments->count() }} appointments &middot; Philippine time</span></div>
            </div>
            <div class="calendar-controls">
                <nav aria-label="Calendar dates" class="calendar-date-nav">
                    <a aria-label="Previous {{ $mode }}" href="{{ route('dashboard', ['date' => $date->copy()->subDays($mode === 'week' ? 7 : 1)->toDateString(), 'view' => $mode, 'provider_id' => $providerId]) }}">&larr;</a>
                    <a href="{{ route('dashboard', ['view' => $mode, 'provider_id' => $providerId]) }}">Today</a>
                    <a aria-label="Next {{ $mode }}" href="{{ route('dashboard', ['date' => $date->copy()->addDays($mode === 'week' ? 7 : 1)->toDateString(), 'view' => $mode, 'provider_id' => $providerId]) }}">&rarr;</a>
                </nav>
                <form method="GET" action="{{ route('dashboard') }}#calendar" class="calendar-filter-form">
                    <label><span class="sr-only">Date</span><input aria-label="Calendar date" type="date" name="date" value="{{ $date->toDateString() }}" required></label>
                    <label><span class="sr-only">View</span><select name="view" aria-label="Calendar view"><option value="day" @selected($mode === 'day')>Day</option><option value="week" @selected($mode === 'week')>Week</option></select></label>
                    <label class="calendar-provider-filter"><span class="sr-only">Treatment provider</span><select name="provider_id" aria-label="Treatment provider"><option value="">All providers</option>@foreach($providers as $provider)<option value="{{ $provider->id }}" @selected($providerId == $provider->id)>{{ $provider->name }}</option>@endforeach</select></label>
                    <button class="calendar-button">Apply</button>
                </form>
            </div>
            <div class="calendar-legend"><span><i class="calendar-dot scheduled"></i>Scheduled</span><span><i class="calendar-dot pending"></i>Pending</span><span><i class="calendar-dot completed"></i>Completed</span><span><i class="calendar-dot cancelled"></i>Cancelled / no-show</span><span class="calendar-legend-help">Select an appointment for details</span></div>
            @if($hours === null)<p class="calendar-hours-note">Business hours not set. An administrator can configure them in Settings.</p>@endif
            <div class="calendar-board {{ $mode === 'week' ? 'calendar-board-week' : 'calendar-board-day' }}">
                @foreach($days as $day)
                    @php($dayBookings = $appointments->filter(fn ($booking) => $booking->scheduled_at->copy()->timezone('Asia/Manila')->toDateString() === $day->toDateString()))
                    <section class="calendar-column" aria-label="{{ $day->format('l F j') }}">
                        <div class="calendar-column-header {{ $day->isToday() ? 'is-today' : '' }}">
                            <div><h3>{{ $day->format('D, M j') }}</h3><span class="calendar-day-count">{{ $dayBookings->count() }}</span></div>
                            <p>{{ $hours === null ? 'Hours not set' : (isset($hours[$day->dayOfWeek]) ? $hours[$day->dayOfWeek]['open'].' - '.$hours[$day->dayOfWeek]['close'] : 'Studio closed') }}</p>
                            @if($providerId && isset($shifts[$providerId]))
                                @php($shiftHours = json_decode($shifts[$providerId]->hours, true)[$day->dayOfWeek] ?? null)
                                <p>Provider: {{ $shiftHours ? $shiftHours['open'].' - '.$shiftHours['close'] : 'Off duty' }}</p>
                            @endif
                        </div>
                        <div class="calendar-events">
                            @forelse($dayBookings as $booking)
                                <button type="button" class="calendar-event calendar-event-{{ $booking->status }}" aria-haspopup="dialog" aria-label="{{ $booking->client->name }}, {{ $booking->scheduled_at->copy()->timezone('Asia/Manila')->format('g:i A') }}, {{ $booking->status }}" x-on:click="selectedId = {{ $booking->id }}; $dispatch('open-modal', 'calendar-appointment')">
                                    <time>{{ $booking->scheduled_at->copy()->timezone('Asia/Manila')->format('g:i A') }}</time>
                                    <strong>{{ $booking->client->name }}</strong>
                                    <span title="{{ $booking->service_names }}">{{ $booking->service_names }}</span>
                                    <small>{{ $booking->provider?->name ?? 'Provider unassigned' }}</small>
                                </button>
                            @empty<p class="calendar-empty">No appointments.</p>@endforelse
                        </div>
                    </section>
                @endforeach
            </div>
            <details class="calendar-help"><summary>Scheduling notes</summary><p>Pending requests do not reserve a slot. Services without a duration reserve 60 minutes each. Assign providers to older unassigned sessions to include them in provider availability checks.</p></details>
        </section>
        <x-calendar-dialog name="calendar-appointment" maxWidth="md" focusable>
            <section class="p-6" role="dialog" aria-modal="true" aria-labelledby="calendar-detail-title">
                <div class="flex items-start justify-between gap-4"><div><p class="text-xs font-semibold uppercase tracking-wider text-[var(--brand-moss)]" x-text="selected().statusLabel"></p><h3 id="calendar-detail-title" class="mt-1 font-serif text-2xl text-[var(--brand-forest)]" x-text="selected().name"></h3></div><x-modal-close x-on:click="$dispatch('close-modal', 'calendar-appointment')" label="Close appointment details" /></div>
                <p class="mt-4 text-sm font-semibold" x-text="selected().services"></p><p class="mt-2 text-sm text-[var(--brand-text-muted)]" x-text="selected().time"></p><p class="mt-2 text-sm text-[var(--brand-text-muted)]"><span x-text="selected().minutes"></span> minutes &middot; <span x-text="selected().provider"></span></p>
                <a :href="selected().manageUrl" class="mt-5 inline-block text-sm font-semibold text-[var(--brand-moss)] underline">Manage session &rarr;</a>
            </section>
        </x-calendar-dialog>

    </div>


