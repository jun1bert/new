export function registerAppointmentDatePicker(Alpine) {
    Alpine.data('appointmentDatePicker', (options) => ({
        day: options.value?.slice(0, 10) || '', time: options.value?.slice(11, 16) || '',
        slots: [], loading: false, note: 'Choose a date to see available times.', request: 0, aborter: null, listener: null, root: null, observer: null,
        init() {
            this.root = this.$el;
            this.listener = (event) => {
                if (event.target.matches('[name="provider_id"], [name="user_id"], [name="service_ids[]"], [name="service_id"], [data-refresh-appointment-times]')) this.$nextTick(() => this.refresh());
            };
            this.root.closest('form').addEventListener('change', this.listener);
            const modal = this.root.closest('dialog, .fixed.inset-0[x-show]');
            if (modal) {
                this.observer = new MutationObserver(() => this.$nextTick(() => this.refresh()));
                this.observer.observe(modal, { attributes: true, attributeFilter: ['open', 'style', 'x-cloak'] });
            }
            this.$nextTick(() => this.refresh());
        },
        destroy() { this.aborter?.abort(); this.observer?.disconnect(); this.root.closest('form')?.removeEventListener('change', this.listener); },
        shortcut(day) { this.day = day; this.time = ''; this.refresh(); },
        async refresh() {
            const sequence = ++this.request;
            this.aborter?.abort();
            this.slots = [];
            if (!this.root.getClientRects().length) { this.loading = false; return; }
            if (!this.day) { this.loading = false; this.note = 'Choose a date to see available times.'; return; }
            const form = this.root.closest('form');
            const provider = form.querySelector('[name="provider_id"]')?.value || options.defaultProvider;
            if (!provider) { this.loading = false; this.note = 'Choose a staff provider first.'; return; }
            const params = new URLSearchParams({ date: this.day, provider_id: provider });
            if (options.appointmentId) { params.set('appointment_id', options.appointmentId); params.set('mode', options.mode); }
            else {
                const ids = [...form.querySelectorAll('[name="service_ids[]"]:checked')].map(input => input.value);
                const single = form.querySelector('[name="service_id"]')?.value;
                if (!ids.length && single) ids.push(single);
                if (!ids.length) { this.loading = false; this.note = 'Choose services first.'; return; }
                ids.forEach(id => params.append('service_ids[]', id));
                const client = form.querySelector('[name="user_id"]')?.value;
                if (client) params.set('client_id', client);
            }
            this.loading = true;
            this.note = 'Checking available times…';
            this.aborter = new AbortController();
            try {
                const response = await fetch(`${options.url}?${params}`, { headers: { Accept: 'application/json' }, signal: this.aborter.signal });
                let data;
                try { data = await response.json(); } catch { throw new Error('Unable to load times. Please refresh and try again.'); }
                if (sequence !== this.request) return;
                if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || 'Unable to load times. Try another date.');
                this.slots = data.slots;
                if (!this.slots.some(slot => slot.value === this.time)) this.time = '';
                this.$nextTick(() => { this.root.querySelector('select').value = this.time; });
                this.note = data.message || (this.slots.length ? `${data.duration} minutes reserved. Times show start and finish.${data.hoursConfigured ? '' : ' Studio hours are not configured.'}` : 'No available times. Try another date or provider.');
            } catch (error) {
                if (sequence !== this.request || error.name === 'AbortError') return;
                this.time = '';
                this.note = error.message || 'Unable to load times. Please try again.';
            } finally { if (sequence === this.request) this.loading = false; }
        },
    }));
}
