@props(['name', 'title', 'show' => false])
<dialog
    class="editor-modal"
    aria-labelledby="{{ $name }}-title"
    x-data="{ visible: @js((bool) $show) }"
    x-init="if (visible) $el.showModal(); $watch('visible', value => { if (value && !$el.open) $el.showModal(); if (!value && $el.open) $el.close() })"
    x-on:open-editor.window="if ($event.detail === @js($name)) visible = true"
    x-on:close-editor.stop="visible = false"
    x-on:close="visible = false"
    x-on:click="if ($event.target === $el) visible = false"
>
    <div class="editor-modal-panel">
        <header class="editor-modal-heading"><div><p class="portal-eyebrow">SERVICE DETAILS</p><h2 id="{{ $name }}-title">{{ $title }}</h2></div><x-modal-close x-on:click="visible = false" label="Close editor" /></header>
        {{ $slot }}
    </div>
</dialog>
