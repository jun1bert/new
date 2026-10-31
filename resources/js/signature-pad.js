export function setupSignaturePads() {
    document.querySelectorAll('[data-signature-pad]').forEach(pad => {
        const canvas = pad.querySelector('[data-signature-canvas]');
        const field = pad.querySelector('[data-signature-value]');
        const status = pad.querySelector('[data-signature-status]');
        const ctx = canvas.getContext('2d');
        let strokes = [], current = null, pointer = null;
        ctx.setTransform(2, 0, 0, 2, 0, 0);
        ctx.strokeStyle = '#3f3430';
        ctx.lineWidth = 2.2;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        const point = event => {
            const rect = canvas.getBoundingClientRect();
            return [Math.round(Math.max(0, Math.min(800, (event.clientX - rect.left) * 800 / rect.width)) * 10) / 10,
                Math.round(Math.max(0, Math.min(400, (event.clientY - rect.top) * 400 / rect.height)) * 10) / 10];
        };
        const redraw = () => {
            ctx.clearRect(0, 0, 800, 400);
            strokes.forEach(stroke => {
                ctx.beginPath();
                stroke.forEach(([x, y], i) => i ? ctx.lineTo(x, y) : ctx.moveTo(x, y));
                ctx.stroke();
            });
        };
        const sync = () => {
            const valid = strokes.filter(stroke => stroke.length >= 2);
            field.value = valid.length ? JSON.stringify(valid) : '';
            pad.classList.toggle('signature-has-ink', valid.length > 0);
            canvas.setAttribute('aria-invalid', 'false');
            status.textContent = valid.length ? 'Signature captured. You can clear it and try again.' : 'Your signature pad is empty.';
        };
        const finish = () => {
            if (pointer === null) return;
            pointer = null;
            current = null;
            strokes = strokes.filter(stroke => stroke.length >= 2);
            redraw();
            sync();
        };
        canvas.addEventListener('pointerdown', event => {
            if (pointer !== null || (event.pointerType === 'mouse' && event.button !== 0)) return;
            if (strokes.length >= 80) { status.textContent = 'Please clear the pad and sign again.'; return; }
            event.preventDefault();
            pointer = event.pointerId;
            canvas.setPointerCapture(pointer);
            current = [point(event)];
            strokes.push(current);
        });
        canvas.addEventListener('pointermove', event => {
            if (event.pointerId !== pointer || !current) return;
            if (current.length >= 600 || strokes.reduce((total, stroke) => total + stroke.length, 0) >= 5000) { finish(); return; }
            const next = point(event), previous = current.at(-1);
            if (Math.hypot(next[0] - previous[0], next[1] - previous[1]) < 1) return;
            current.push(next);
            ctx.beginPath(); ctx.moveTo(...previous); ctx.lineTo(...next); ctx.stroke();
        });
        canvas.addEventListener('pointerup', event => { if (event.pointerId === pointer) finish(); });
        canvas.addEventListener('pointercancel', event => { if (event.pointerId === pointer) finish(); });
        canvas.addEventListener('lostpointercapture', finish);
        pad.querySelector('[data-signature-clear]').addEventListener('click', () => { pointer = null; current = null; strokes = []; redraw(); sync(); });
        pad.closest('form').addEventListener('submit', event => {
            finish();
            let length = 0;
            strokes.forEach(stroke => stroke.forEach((p, i) => { if (i) length += Math.hypot(p[0] - stroke[i - 1][0], p[1] - stroke[i - 1][1]); }));
            if (!field.value || length < 12) {
                event.preventDefault();
                canvas.setAttribute('aria-invalid', 'true');
                status.textContent = 'Please draw your signature before submitting.';
                canvas.scrollIntoView({ block: 'center', behavior: 'auto' });
            }
        });
    });
}
