@php($retryApproval = $errors->any() && (string) old('_approving_appointment_id') === (string) $appointment->id)
<div class="studio-calendar" style="padding:0;margin:0;max-width:none">
    <x-calendar-dialog name="approve-request-{{ $appointment->id }}" :show="$retryApproval" maxWidth="md">
        <section class="p-6" role="dialog" aria-modal="true" aria-labelledby="approve-request-title-{{ $appointment->id }}" x-data="{ changeTime: @js($retryApproval && ($errors->has('scheduled_at') || old('_change_requested_time'))) }">
            <div class="flex items-start justify-between gap-4"><h3 id="approve-request-title-{{ $appointment->id }}" class="font-serif text-2xl">Review appointment request</h3><x-modal-close x-on:click="$dispatch('close-modal', 'approve-request-{{ $appointment->id }}')" label="Close request approval" /></div>
            <p class="mt-4 text-sm font-semibold">{{ $appointment->client->name }} &middot; {{ $appointment->service_names }}</p>
            <p class="mt-2 text-sm text-[var(--brand-text-muted)]">Requested: {{ $appointment->scheduled_at->copy()->timezone('Asia/Manila')->format('M j, Y g:i A') }} (Philippine time)</p>
            @if($retryApproval)<div role="alert" class="modal-feedback-errors mt-3"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <form method="POST" action="{{ route('studio.appointments.accept', $appointment) }}" class="mt-5 space-y-4">@csrf @method('PATCH')
                <input type="hidden" name="_approving_appointment_id" value="{{ $appointment->id }}"><input type="hidden" name="_change_requested_time" :value="changeTime ? '1' : ''">
                <label for="approve-provider-{{ $appointment->id }}" class="block text-sm font-semibold">Treatment provider<select id="approve-provider-{{ $appointment->id }}" name="provider_id" required class="mt-1 block w-full rounded-lg border-gray-300"><option value="">Choose a staff member</option>@foreach($providers as $provider)<option value="{{ $provider->id }}" @selected((string) ($retryApproval ? old('provider_id') : (auth()->user()->user_type === 'staff' ? auth()->id() : null)) === (string) $provider->id)>{{ $provider->name }} — {{ $provider->currently_timed_in ? 'currently timed in' : 'not currently timed in' }}</option>@endforeach</select></label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" x-model="changeTime" data-refresh-appointment-times class="rounded border-gray-300 text-[var(--brand-moss)]">Choose a different date or time</label>
                <div x-show="changeTime" class="space-y-3">
                    <x-appointment-date-picker :id="'approve-request-'.$appointment->id" :value="$retryApproval ? (string) old('scheduled_at', $appointment->scheduled_at->copy()->timezone('Asia/Manila')->format('Y-m-d\TH:i')) : $appointment->scheduled_at->copy()->timezone('Asia/Manila')->format('Y-m-d\TH:i')" :appointment-id="$appointment->id" disabled-when="!changeTime" />
                    <label class="block text-sm font-semibold" for="approval-reason-{{ $appointment->id }}">Change note (optional)<textarea id="approval-reason-{{ $appointment->id }}" name="reason" maxlength="1000" rows="2" :disabled="!changeTime" class="mt-1 block w-full rounded-lg border-gray-300">{{ $retryApproval ? old('reason') : '' }}</textarea></label>
                    <p class="text-xs text-[var(--brand-text-muted)]">Agree on the revised time with the client before confirming.</p>
                </div>
                <p class="text-xs text-[var(--brand-text-muted)]">Availability is checked again when you accept. If the time is taken, choose another time or staff provider here.</p>
                <button class="calendar-button calendar-button-primary">Confirm &amp; accept</button>
            </form>
        </section>
    </x-calendar-dialog>
</div>
