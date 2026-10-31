import test from 'node:test';
import assert from 'node:assert/strict';
import { philippineDateTime, publicRequestTimes } from '../../resources/js/public-booking-times.js';

const hours = Array.from({ length: 7 }, () => ({ open: '09:00', close: '18:00' }));

test('uses Philippine calendar dates regardless of the browser timezone', () => {
    assert.equal(philippineDateTime(new Date('2026-10-31T16:30:00Z')), '2026-11-01T00:30');
});

test('offers only future starts that leave enough time before closing', () => {
    const result = publicRequestTimes({ date: '2026-11-03', hours, duration: 30, now: '2026-11-03T17:10' });
    assert.deepEqual(result.slots.map(slot => slot.value), ['17:15', '17:30']);
    assert.equal(result.slots[0].label, '5:15 PM');
});

test('longer treatments remove late start times', () => {
    const result = publicRequestTimes({ date: '2026-11-03', hours, duration: 90, now: '2026-11-03T08:00' });
    assert.equal(result.slots.at(-1).value, '16:30');
    assert.equal(result.slots[0].value, '09:00');
});

test('closed days and fully elapsed days have clear explanations', () => {
    const closedHours = [...hours];
    closedHours[2] = null;
    const closed = publicRequestTimes({ date: '2026-11-03', hours: closedHours, duration: 30, now: '2026-11-02T10:00' });
    assert.equal(closed.slots.length, 0);
    assert.match(closed.message, /closed/);
    const late = publicRequestTimes({ date: '2026-11-03', hours, duration: 30, now: '2026-11-03T17:45' });
    assert.equal(late.slots.length, 0);
    assert.match(late.message, /another day/);
});

test('requires a date before suggesting times', () => {
    assert.deepEqual(publicRequestTimes({ date: '', hours }), { slots: [], message: 'Choose an appointment date first.' });
});
