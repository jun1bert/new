// Give the existing Alpine overlays consistent keyboard and focus behavior.
export function setupPortalUX() {
    if (!document.body.classList.contains('portal-page')) return;

    // Keep validation feedback with the form that was reopened by the server.
    const validation = document.getElementById('portal-validation-feedback');
    if (validation) {
        const nativeDialog = [...document.querySelectorAll('main dialog[open]')].find(dialog => !dialog.closest('.modal-feedback-host'));
        const overlay = [...document.querySelectorAll('main .fixed.inset-0[x-show]')]
            .find(element => !element.hasAttribute('x-cloak') && getComputedStyle(element).display !== 'none');
        const panel = nativeDialog || overlay?.firstElementChild;
        if (panel) {
            if (!panel.querySelector('[role="alert"]')) {
                const form = panel.querySelector('form');
                (form || panel).prepend(validation.content.cloneNode(true));
            }
            const previous = JSON.parse(validation.dataset.oldInput || '{}');
            panel.querySelectorAll('input, select, textarea').forEach(control => {
                if (!control.name) return;
                if (['password', 'file', 'hidden', 'submit', 'button'].includes(control.type)) return;
                const keys = control.name.replaceAll(']', '').split('[').filter(Boolean);
                const value = keys.reduce((current, key) => current?.[key], previous);
                if (value === undefined || value === null) return;
                if (['checkbox', 'radio'].includes(control.type)) {
                    control.checked = Array.isArray(value) ? value.map(String).includes(control.value) : String(value) === control.value;
                } else if (!Array.isArray(value) && typeof value !== 'object') control.value = String(value);
                control.dispatchEvent(new Event('input', { bubbles: true }));
                control.dispatchEvent(new Event('change', { bubbles: true }));
            });
        } else window.dispatchEvent(new CustomEvent('open-modal', { detail: 'portal-feedback-error' }));
    }

    let sequence = 0;
    const active = [];
    const drawer = document.getElementById('portal-sidebar');
    const drawerOpen = () => drawer && !window.matchMedia('(min-width: 1024px)').matches && !drawer.inert;
    const syncScroll = () => document.body.classList.toggle('portal-modal-open', active.length > 0 || drawerOpen());
    const focusable = panel => [...panel.querySelectorAll('a[href], button, input:not([type="hidden"]), select, textarea, [tabindex="0"]')]
        .filter(el => !el.disabled && !el.closest('[inert]') && el.getClientRects().length);
    const top = () => active.at(-1) || (drawerOpen() ? { panel: drawer, close: () => drawer.querySelector('button[aria-label="Close navigation"]')?.click() } : null);
    if (drawer) {
        drawer.tabIndex = -1;
        new MutationObserver(syncScroll).observe(drawer, { attributes: true, attributeFilter: ['inert'] });
        window.addEventListener('resize', syncScroll);
    }

    document.querySelectorAll('main label').forEach(label => {
        if (label.htmlFor || label.querySelector('input, select, textarea')) return;
        const input = label.nextElementSibling;
        if (!input?.matches('input, select, textarea')) return;
        input.id ||= `portal-field-${++sequence}`;
        label.htmlFor = input.id;
    });

    document.querySelectorAll('.portal-sidebar nav a').forEach(link => {
        if (link.classList.contains('bg-[#efdee5]')) link.setAttribute('aria-current', 'page');
    });

    document.querySelectorAll('main .fixed.inset-0[x-show]').forEach(overlay => {
        const expression = overlay.getAttribute('x-show');
        if (!/^[a-zA-Z_$][\w$]*$/.test(expression)) return;
        const panel = overlay.firstElementChild;
        if (!panel || !panel.querySelector('form')) return;
        const close = () => window.Alpine.evaluate(overlay, `${expression} = false`);
        panel.setAttribute('role', 'dialog');
        panel.setAttribute('aria-modal', 'true');
        const heading = panel.querySelector('h2, h3');
        if (heading) {
            heading.id ||= `portal-dialog-${++sequence}`;
            panel.setAttribute('aria-labelledby', heading.id);
            heading.tabIndex = -1;
        }
        panel.tabIndex = -1;
        if (!panel.querySelector('button[aria-label*="Close"], button[aria-label*="close"]')) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'portal-modal-close';
            button.setAttribute('aria-label', 'Close dialog');
            button.textContent = '×';
            button.addEventListener('click', close);
            panel.prepend(button);
        }
        const state = { overlay, panel, close, previous: null };
        const refresh = () => {
            const visible = getComputedStyle(overlay).display !== 'none' && !overlay.hasAttribute('x-cloak');
            const index = active.indexOf(state);
            if (visible && index === -1) {
                state.previous = document.activeElement;
                active.push(state);
                syncScroll();
                requestAnimationFrame(() => {
                    if (top() === state) (panel.querySelector('[aria-invalid="true"]') || heading || panel).focus();
                });
            } else if (!visible && index !== -1) {
                active.splice(index, 1);
                syncScroll();
                if (state.previous?.isConnected) state.previous.focus();
            }
        };
        new MutationObserver(refresh).observe(overlay, { attributes: true, attributeFilter: ['style', 'x-cloak'] });
        refresh();
    });

    document.addEventListener('keydown', event => {
        const state = top();
        if (!state || document.querySelector('dialog[open]')) return;
        if (event.key === 'Escape') {
            event.preventDefault();
            event.stopImmediatePropagation();
            state.close();
        } else if (event.key === 'Tab') {
            const items = focusable(state.panel);
            if (!items.length) { event.preventDefault(); state.panel.focus(); return; }
            const index = items.indexOf(document.activeElement);
            if (event.shiftKey && index <= 0) { event.preventDefault(); items.at(-1).focus(); }
            else if (!event.shiftKey && (index === -1 || index === items.length - 1)) { event.preventDefault(); items[0].focus(); }
        }
    }, true);
    document.addEventListener('focusin', event => {
        const state = top();
        if (state && !document.querySelector('dialog[open]') && !state.panel.contains(event.target)) state.panel.focus();
    });
}
