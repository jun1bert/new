@props(['name'])
<div class="signature-pad" data-signature-pad>
    <label class="block text-sm font-medium">Name from booking<input type="text" value="{{ $name }}" readonly class="consent-input mt-2 signature-name" aria-readonly="true"></label>
    <p class="consent-muted mt-4 text-sm">Draw your signature below using your finger, a stylus, or a mouse.</p>
    <div class="signature-canvas-frame mt-3"><canvas width="1600" height="800" aria-label="Draw your signature here" data-signature-canvas></canvas><span aria-hidden="true">Sign here</span></div>
    <input type="hidden" name="signature" value="" data-signature-value>
    <div class="signature-pad-actions"><p class="consent-muted text-sm" data-signature-status aria-live="polite">Your signature pad is empty.</p><button type="button" class="consent-secondary" data-signature-clear>Clear signature</button></div>
    <noscript><p class="consent-errors mt-3">JavaScript is needed for the signature pad. Please ask the studio team for assistance.</p></noscript>
    <p class="consent-muted mt-2 text-sm">By submitting, you are signing this form electronically. Your signature and booking name will be saved with the form.</p>
</div>
