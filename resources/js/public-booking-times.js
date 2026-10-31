export function philippineDateTime(now = new Date()) {
    const parts = new Intl.DateTimeFormat('en-GB', { timeZone: 'Asia/Manila', year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hourCycle: 'h23' }).formatToParts(now);
    const value = key => parts.find(part => part.type === key).value;
    return `${value('year')}-${value('month')}-${value('day')}T${value('hour')}:${value('minute')}`;
}

export function publicRequestTimes({ date, hours, duration = 0, now = philippineDateTime() }) {
    if (!date) return { slots: [], message: 'Choose an appointment date first.' };
    const weekday = new Date(`${date}T12:00:00Z`).getUTCDay();
    const schedule = hours == null ? { open: '09:00', close: '19:00' } : hours[weekday];
    if (!schedule) return { slots: [], message: 'The studio is closed on this date. Please choose another day.' };
    const minutes = value => Number(value.slice(0, 2)) * 60 + Number(value.slice(3, 5));
    const slots = [];
    for (let start = Math.ceil(minutes(schedule.open) / 15) * 15; start < minutes(schedule.close) && start + duration <= minutes(schedule.close); start += 15) {
        const time = `${String(Math.floor(start / 60)).padStart(2, '0')}:${String(start % 60).padStart(2, '0')}`;
        if (`${date}T${time}` <= now) continue;
        slots.push({ value: time, label: `${Math.floor(start / 60) % 12 || 12}:${String(start % 60).padStart(2, '0')} ${start < 720 ? 'AM' : 'PM'}` });
    }
    return { slots, message: slots.length ? `Studio hours: ${schedule.open}–${schedule.close}. Philippine time; requests require staff approval.` : 'No suitable times remain on this date. Please choose another day.' };
}
