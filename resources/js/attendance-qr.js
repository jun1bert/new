import QRCode from 'qrcode';

export function setupAttendanceQr() {
    const container = document.querySelector('[data-attendance-qr]');
    if (!container) return;
    const canvas = container.querySelector('[data-qr-canvas]');
    const status = container.querySelector('[data-qr-status]');
    let timer;
    async function refresh() {
        clearTimeout(timer);
        canvas.hidden = true;
        status.textContent = 'Refreshing code…';
        try {
            const response = await fetch(container.dataset.url, { cache: 'no-store', headers: { Accept: 'application/json' } });
            if (!response.ok || !response.headers.get('content-type')?.includes('application/json')) throw new Error('Unable to refresh');
            const data = await response.json();
            await QRCode.toCanvas(canvas, data.url, { width: 320, margin: 3, errorCorrectionLevel: 'M', color: { dark: '#263C32', light: '#FFFFFF' } });
            canvas.hidden = false;
            status.textContent = 'Ready to scan';
            timer = setTimeout(refresh, Math.max(250, data.refresh_in * 1000));
        } catch {
            status.textContent = 'Could not refresh the code. Check the connection or sign in again.';
            timer = setTimeout(refresh, 5000);
        }
    }
    refresh();
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
}
