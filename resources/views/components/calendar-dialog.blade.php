@props(['name', 'show' => false, 'maxWidth' => '2xl'])
<dialog
    class="calendar-dialog {{ $maxWidth === 'md' ? 'calendar-dialog-small' : '' }}"
    x-data="{ visible: @js((bool) $show) }"
    x-init="if (visible) $el.showModal(); $watch('visible', value => { if (value && !$el.open) $el.showModal(); if (!value && $el.open) $el.close() })"
    x-on:open-modal.window="if ($event.detail === @js($name)) visible = true"
    x-on:close-modal.window="if ($event.detail === @js($name)) visible = false"
    x-on:cancel="visible = false"
    x-on:close="visible = false"
    x-on:click="if ($event.target === $el) visible = false"
>
    {{ $slot }}
</dialog>
