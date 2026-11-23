@if(in_array($appointment->status, ['pending', 'scheduled', 'cancelled', 'no_show'], true))
    @php($retryChange = (string) old('_changing_appointment_id') === (string) $appointment->id)
    @php($changeAction = $retryChange ? old('_appointment_action', 'reschedule') : ($appointment->status === 'pending' ? 'cancel' : 'reschedule'))
    <div class="studio-calendar" style="padding:0;margin:0;max-width:none" x-data="{ action: @js($changeAction), paths: @js(['reschedule' => route('studio.appointments.reschedule', $appointment), 'cancel' => route('studio.appointments.cancel', $appointment), 'no-show' => route('studio.appointments.no-show', $appointment)]) }">
        <div class="mt-3 flex flex-wrap gap-2">
            @if($appointment->status !== 'pending')<button type="button" class="calendar-button" x-on:click="action = 'reschedule'; $dispatch('open-modal', 'session-change-{{ $appointment->id }}')">Reschedule</button>@endif
            @if(in_array($appointment->status, ['pending', 'scheduled'], true))<button type="button" class="calendar-button" x-on:click="action = 'cancel'; $dispatch('open-modal', 'session-change-{{ $appointment->id }}')">Cancel session</button>@endif
            @if($appointment->status === 'scheduled')
                @php($noShowAvailable = $appointment->scheduled_at->lte(now()))
                <div x-data="{ ready: @js($noShowAvailable), starts: @js($appointment->scheduled_at->getTimestampMs()), serverTime: @js(now()->getTimestampMs()), opened: performance.now(), timer: null, init() { if (!this.ready) this.timer = setInterval(() => { this.ready = this.serverTime + performance.now() - this.opened >= this.starts; if (this.ready) clearInterval(this.timer) }, 1000) }, destroy() { clearInterval(this.timer) } }">
                    <button type="button" class="calendar-button" @disabled(!$noShowAvailable) :disabled="!ready" x-on:click="action = 'no-show'; $dispatch('open-modal', 'session-change-{{ $appointment->id }}')" aria-describedby="no-show-availability-{{ $appointment->id }}">Mark no-show</button>
                    <p id="no-show-availability-{{ $appointment->id }}" x-show="!ready" @if($noShowAvailable) style="display:none" @endif class="mt-1 text-xs text-[var(--brand-text-muted)]">Available from {{ $appointment->scheduled_at->copy()->timezone('Asia/Manila')->format('M j, g:i A') }} (Philippine time).</p>
                </div>
            @endif
        </div>
        <x-calendar-dialog name="session-change-{{ $appointment->id }}" :show="$retryChange" maxWidth="md">
            <section class="p-5" role="dialog" aria-modal="true" aria-labelledby="session-change-title-{{ $appointment->id }}">
                <div class="flex items-start justify-between gap-3"><h3 id="session-change-title-{{ $appointment->id }}" class="font-serif text-2xl" x-text="action === 'reschedule' ? 'Reschedule session' : (action === 'cancel' ? 'Cancel session' : 'Mark no-show')"></h3><x-modal-close x-on:click="$dispatch('close-modal', 'session-change-{{ $appointment->id }}')" label="Close session change" /></div>
                <p class="mt-2 text-sm text-[var(--brand-text-muted)]">{{ $appointment->client->name }} &middot; {{ $appointment->service_names }}</p>
                @if($retryChange && $errors->any())<ul role="alert" class="mt-3 text-sm text-red-700">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
                <form method="POST" :action="paths[action]" class="mt-4 space-y-4">@csrf @method('PATCH')
                    <input type="hidden" name="_changing_appointment_id" value="{{ $appointment->id }}"><input type="hidden" name="_appointment_action" :value="action">
                    <div x-show="action === 'reschedule'" class="space-y-3">
                        <x-appointment-date-picker :id="'reschedule-'.$appointment->id" :value="$retryChange ? (string) old('scheduled_at', '') : $appointment->scheduled_at->copy()->timezone('Asia/Manila')->format('Y-m-d\TH:i')" :appointment-id="$appointment->id" :default-provider="$appointment->provider_id" disabled-when="action !== 'reschedule'" />
                        <label class="block text-sm font-semibold" for="reschedule-provider-{{ $appointment->id }}">Treatment provider<select id="reschedule-provider-{{ $appointment->id }}" name="provider_id" required :disabled="action !== 'reschedule'" class="mt-1 block w-full rounded-lg border-gray-300"><option value="">Choose a staff member</option>@foreach($providers as $provider)<option value="{{ $provider->id }}" @selected((string) ($retryChange ? old('provider_id') : $appointment->provider_id) === (string) $provider->id)>{{ $provider->name }} — {{ $provider->currently_timed_in ? 'currently timed in' : 'not currently timed in' }}</option>@endforeach</select></label>
                    </div>
                    <label class="block text-sm font-semibold" for="change-reason-{{ $appointment->id }}">Reason<textarea id="change-reason-{{ $appointment->id }}" name="reason" required maxlength="1000" rows="3" class="mt-1 block w-full rounded-lg border-gray-300">{{ $retryChange ? old('reason') : '' }}</textarea></label>
                    <p class="text-xs text-[var(--brand-text-muted)]" x-text="action === 'reschedule' ? 'Availability will be checked before saving. Cancelled or missed sessions become scheduled again.' : 'This updates the session status and releases its reserved time. No payment is recorded.'"></p>
                    <button class="calendar-button calendar-button-primary" x-text="action === 'reschedule' ? 'Save new schedule' : (action === 'cancel' ? 'Confirm cancellation' : 'Confirm no-show')"></button>
                </form>
            </section>
        </x-calendar-dialog>
    </div>
@endif
<details class="mt-3 rounded-lg border border-[var(--brand-moss)]/15 p-3">
    <summary class="cursor-pointer text-sm font-semibold text-[var(--brand-moss)]">Session history</summary>
    <ol class="mt-3 space-y-3 text-sm">
        @forelse($appointment->changeHistory as $change)
            <li class="border-l-2 border-[var(--brand-moss)]/20 pl-3"><p class="font-semibold">{{ ucfirst(str_replace('_', ' ', $change->action)) }} <span class="font-normal text-[var(--brand-text-muted)]">&middot; {{ $change->actor?->name ?? 'Online booking / system' }} &middot; {{ $change->created_at->copy()->timezone('Asia/Manila')->format('M j, Y g:i A') }}</span></p>
                @if($change->before && ($change->before['scheduled_at'] ?? null) !== ($change->after['scheduled_at'] ?? null))<p class="mt-1 text-xs text-[var(--brand-text-muted)]">Time: {{ \Carbon\Carbon::parse($change->before['scheduled_at'], config('app.timezone'))->timezone('Asia/Manila')->format('M j, Y g:i A') }} &rarr; {{ \Carbon\Carbon::parse($change->after['scheduled_at'], config('app.timezone'))->timezone('Asia/Manila')->format('M j, Y g:i A') }}</p>@endif
                @if($change->before && ($change->before['provider_id'] ?? null) != ($change->after['provider_id'] ?? null))<p class="mt-1 text-xs text-[var(--brand-text-muted)]">Provider: {{ $change->before['provider_name'] ?? 'Unassigned' }} &rarr; {{ $change->after['provider_name'] ?? 'Unassigned' }}</p>@endif
                @if($change->before && ($change->before['status'] ?? null) !== ($change->after['status'] ?? null))<p class="mt-1 text-xs text-[var(--brand-text-muted)]">Status: {{ ucfirst(str_replace('_', ' ', $change->before['status'])) }} &rarr; {{ ucfirst(str_replace('_', ' ', $change->after['status'])) }}</p>@endif
                @if($change->reason)<p class="mt-1 text-sm">{{ $change->reason }}</p>@endif
            </li>
        @empty<li class="text-xs text-[var(--brand-text-muted)]">No change history recorded. Older activity predates history tracking.</li>@endforelse
    </ol>
</details>
