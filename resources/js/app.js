import './bootstrap';
import { setupAttendanceQr } from './attendance-qr';

import Alpine from 'alpinejs';
import { setupPortalUX } from './portal-ux';
import { setupSignaturePads } from './signature-pad';
import { registerAppointmentDatePicker } from './appointment-date-picker';
import { philippineDateTime, publicRequestTimes } from './public-booking-times';

window.Alpine = Alpine;
window.LunelleBookingTimes = { philippineDateTime, publicRequestTimes };
registerAppointmentDatePicker(Alpine);

Alpine.start();
Alpine.nextTick(setupPortalUX);
Alpine.nextTick(setupAttendanceQr);
Alpine.nextTick(setupSignaturePads);
window.addEventListener('pageshow', event => {
    if (event.persisted && document.body.classList.contains('portal-page')) window.location.reload();
});

