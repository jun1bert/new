@props(['id', 'value' => '', 'appointmentId' => null, 'mode' => 'reschedule', 'defaultProvider' => null, 'disabledWhen' => 'false'])
<fieldset class="appointment-date-picker" x-data="appointmentDatePicker(@js(['value' => $value, 'appointmentId' => $appointmentId, 'mode' => $mode, 'defaultProvider' => $defaultProvider ?? (auth()->user()->user_type === 'staff' ? auth()->id() : null), 'url' => route('studio.appointments.availability')]))">
    <legend class="text-sm font-semibold">Date and time <span class="text-xs font-normal">(Philippine time)</span></legend>
    <input type="hidden" name="scheduled_at" value="{{ $value }}" :value="day && time ? day + 'T' + time : ''" :disabled="{{ $disabledWhen }}">
    <div class="date-picker-shortcuts"><button type="button" class="calendar-button" :disabled="{{ $disabledWhen }}" x-on:click="shortcut(@js(now('Asia/Manila')->toDateString()))">Today</button><button type="button" class="calendar-button" :disabled="{{ $disabledWhen }}" x-on:click="shortcut(@js(now('Asia/Manila')->addDay()->toDateString()))">Tomorrow</button></div>
    <div class="date-picker-fields">
        <label for="{{ $id }}-date">Date<input id="{{ $id }}-date" type="date" x-model="day" x-on:change="time = ''; refresh()" min="{{ now('Asia/Manila')->toDateString() }}" required :disabled="{{ $disabledWhen }}"></label>
        <label for="{{ $id }}-time">Available time<select id="{{ $id }}-time" x-model="time" required :disabled="({{ $disabledWhen }}) || loading || !slots.length"><option value="" x-text="loading ? 'Checking times…' : 'Choose a time'"></option><template x-for="slot in slots" :key="slot.value"><option :value="slot.value" x-text="slot.label"></option></template></select></label>
    </div>
    <p class="date-picker-note" role="status" aria-live="polite" x-text="note"></p>
    <button type="button" class="date-picker-retry" x-show="day" x-on:click="refresh()" :disabled="loading || ({{ $disabledWhen }})">Refresh available times</button>
</fieldset>
