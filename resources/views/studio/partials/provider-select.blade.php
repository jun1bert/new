<label class="block text-sm font-semibold text-[var(--brand-forest)]">Treatment provider
    <select name="provider_id" class="mt-1 w-full rounded-lg border-gray-300">
        <option value="">{{ $defaultProvider ?? (auth()->user()->user_type === 'staff' ? 'Me ('.auth()->user()->name.')' : 'Choose a staff member') }}</option>
        @foreach($providers as $provider)<option value="{{ $provider->id }}" @selected((string) old('provider_id') === (string) $provider->id)>{{ $provider->name }} — {{ $provider->currently_timed_in ? 'currently timed in' : 'not currently timed in' }}</option>@endforeach
    </select>
    <span class="mt-1 block text-xs font-normal text-[var(--brand-text-muted)]">Attendance is current status only and does not restrict future bookings.</span>
</label>
