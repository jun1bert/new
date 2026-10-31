@props(['label' => 'Close'])
<button type="button" aria-label="{{ $label }}" {{ $attributes->merge(['class' => 'modal-close']) }}>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true" focusable="false"><path d="m6 6 12 12M18 6 6 18" /></svg>
</button>
