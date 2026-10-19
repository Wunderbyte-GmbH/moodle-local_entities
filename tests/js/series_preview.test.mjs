import assert from 'node:assert/strict';
import {occurrences} from 'local_entities/unavailability/series_preview';

const ts = (s) => Math.floor(new Date(s).getTime() / 1000);
const fmt = (r) => {
    const f = (t) => {
        const d = new Date(t * 1000);
        const p = (n) => String(n).padStart(2, '0');
        return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())} ${p(d.getHours())}:${p(d.getMinutes())}`;
    };
    return `${f(r.starttime)} - ${f(r.endtime)}`;
};
let ok = 0;
const t = (name, fn) => { fn(); ok++; console.log('ok -', name); };

const rule = (o = {}) => ({
    weekdays: [1, 3], allday: false, daystart: 8 * 3600, dayend: 10 * 3600,
    validfrom: ts('2026-10-19T12:00:00'), validuntil: ts('2026-11-01T12:00:00'), ...o,
});

t('one range per matching day at local time', () => {
    assert.deepEqual(occurrences(rule()).map(fmt), [
        '2026-10-19 08:00 - 2026-10-19 10:00',
        '2026-10-21 08:00 - 2026-10-21 10:00',
        '2026-10-26 08:00 - 2026-10-26 10:00',
        '2026-10-28 08:00 - 2026-10-28 10:00',
    ]);
});
t('Sunday is weekday 7', () => {
    assert.deepEqual(occurrences(rule({weekdays: [7]})).map(fmt), [
        '2026-10-25 08:00 - 2026-10-25 10:00',
        '2026-11-01 08:00 - 2026-11-01 10:00',
    ]);
});
t('all-day covers whole local days', () => {
    const r = occurrences(rule({weekdays: [7], allday: true}));
    assert.deepEqual(r.map(fmt), [
        '2026-10-25 00:00 - 2026-10-26 00:00',
        '2026-11-01 00:00 - 2026-11-02 00:00',
    ]);
    if (process.env.TZ === 'Europe/Vienna') {
        assert.equal(r[0].endtime - r[0].starttime, 25 * 3600, 'the day with the end of daylight saving time has 25 hours');
    }
});
t('notBefore leaves out earlier days', () => {
    assert.deepEqual(occurrences(rule(), ts('2026-10-22T15:30:00')).map(fmt), [
        '2026-10-26 08:00 - 2026-10-26 10:00',
        '2026-10-28 08:00 - 2026-10-28 10:00',
    ]);
    assert.deepEqual(occurrences(rule(), ts('2026-11-05T00:00:00')), []);
});
t('time to midnight ends at midnight of the next day', () => {
    const r = occurrences(rule({weekdays: [1], daystart: 22 * 3600, dayend: 24 * 3600, validuntil: ts('2026-10-19T12:00:00')}));
    assert.deepEqual(r.map(fmt), ['2026-10-19 22:00 - 2026-10-20 00:00']);
});
t('never runs away', () => {
    const r = occurrences(rule({weekdays: [1, 2, 3, 4, 5, 6, 7], validfrom: 1000, validuntil: 4000000000}));
    assert.ok(r.length <= 400);
});
console.log(`${ok} tests passed (TZ=${process.env.TZ || 'default'})`);
