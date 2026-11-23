<details class="calendar-hours-editor mt-4 rounded-xl border border-[var(--brand-moss)]/20 p-4" @if(($expanded ?? false) || ($errors->any() && (string) old('provider_id') === (string) $scheduleId)) open @endif>
    <summary class="cursor-pointer font-semibold text-[var(--brand-forest)]">{{ $scheduleName }} <span class="text-xs font-normal text-[var(--brand-text-muted)]">{{ $scheduleHours === null ? ($scheduleId ? '(uses studio hours)' : '(not configured)') : '(configured)' }}</span></summary>
    <form method="POST" action="{{ route('admin.scheduling.hours') }}" class="mt-4 space-y-3">@csrf @method('PUT')
        <input type="hidden" name="provider_id" value="{{ $scheduleId }}">
        @foreach(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $number => $name)
            @php($retry = $errors->any() && (string) old('provider_id') === (string) $scheduleId)
            @php($row = $retry ? old('hours.'.$number, []) : ($scheduleHours[$number] ?? null))
            <div class="flex flex-wrap items-center gap-3">
                <label class="w-32 text-sm"><input type="checkbox" name="hours[{{ $number }}][enabled]" value="1" @checked($retry ? isset($row['enabled']) : ($scheduleHours === null ? $number >= 1 && $number <= 6 : $row !== null)) class="mr-2 rounded border-gray-300 text-[var(--brand-moss)]">{{ $name }}</label>
                <input aria-label="{{ $name }} opening time" type="time" name="hours[{{ $number }}][open]" value="{{ $row['open'] ?? '09:00' }}" required class="rounded-lg border-gray-300 text-sm">
                <span class="text-sm">to</span><input aria-label="{{ $name }} closing time" type="time" name="hours[{{ $number }}][close]" value="{{ $row['close'] ?? '18:00' }}" required class="rounded-lg border-gray-300 text-sm">
            </div>
        @endforeach
        <button class="rounded-lg bg-[var(--brand-moss)] px-4 py-2.5 text-sm font-semibold text-white">Save {{ $scheduleName }}</button>
    </form>
</details>
