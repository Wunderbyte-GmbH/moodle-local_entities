import assert from 'node:assert/strict';
import PendingChanges from 'local_entities/unavailability/pending_changes';
import {buildEvents} from 'local_entities/unavailability/event_builder';

const ts = (s) => Math.floor(new Date(s).getTime() / 1000);
const labels = {absent: 'Absent', notsaved: 'Not saved'};
const now = new Date('2026-10-22T10:00:00');
let ok = 0;
const t = (name, fn) => { fn(); ok++; console.log('ok -', name); };
const real = (events) => events.filter((e) => e.display !== 'background');
const fills = (events) => events.filter((e) => e.display === 'background');
const ids = (events) => real(events).map((e) => e.id);

const loaded = {
    blocks: [
        {id: 1, starttime: ts('2026-10-23T08:00:00'), endtime: ts('2026-10-23T10:00:00'), reason: '', seriesid: 0},
        {id: 2, starttime: ts('2026-10-24T00:00:00'), endtime: ts('2026-10-25T00:00:00'), reason: '', seriesid: 0},
        {id: 3, starttime: ts('2026-10-19T08:00:00'), endtime: ts('2026-10-19T10:00:00'), reason: '', seriesid: 9},
        {id: 4, starttime: ts('2026-10-26T08:00:00'), endtime: ts('2026-10-26T10:00:00'), reason: '', seriesid: 9},
    ],
    series: [{id: 9, weekdays: [1], allday: false, daystart: 8 * 3600, dayend: 10 * 3600,
        validfrom: ts('2026-10-19T00:00:00'), validuntil: ts('2026-11-02T00:00:00'), timezone: 'x', reason: ''}],
};

t('saved blocks are shown, whole days as all-day', () => {
    const events = real(buildEvents(loaded, new PendingChanges(), labels, now));
    assert.deepEqual(ids(events), ['block-1', 'block-2', 'block-3', 'block-4']);
    assert.equal(events[0].allDay, false);
    assert.equal(events[0].start, '2026-10-23T08:00:00');
    assert.equal(events[0].end, '2026-10-23T10:00:00');
    assert.equal(events[1].allDay, true);
    assert.equal(events[1].start, '2026-10-24');
    assert.equal(events[1].end, '2026-10-25');
    assert.deepEqual(events[2].classNames, ['entities-unavail', 'entities-unavail-series']);
    assert.deepEqual(events[0].extendedProps, {
        kind: 'block', id: 1, seriesid: 0,
        starttime: ts('2026-10-23T08:00:00'), endtime: ts('2026-10-23T10:00:00'),
    });
});
t('a block that is going to be removed is hidden', () => {
    const p = new PendingChanges();
    p.removeBlock(1);
    assert.deepEqual(ids(buildEvents(loaded, p, labels, now)), ['block-2', 'block-3', 'block-4']);
});
t('a deleted series is hidden from today on, its past stays', () => {
    const p = new PendingChanges();
    p.deleteSeries(9);
    assert.deepEqual(ids(buildEvents(loaded, p, labels, now)), ['block-1', 'block-2', 'block-3']);
});
t('a changed series shows its new days from today instead of the saved ones', () => {
    const p = new PendingChanges();
    p.updateSeries(9, {weekdays: [2], allday: false, daystart: 14 * 3600, dayend: 16 * 3600,
        validfrom: ts('2026-10-19T00:00:00'), validuntil: ts('2026-10-30T00:00:00')});
    const events = real(buildEvents(loaded, p, labels, now));
    assert.deepEqual(ids(events), ['block-1', 'block-2', 'block-3', 'useries-9-0']);
    assert.equal(events[3].start, '2026-10-27T14:00:00');
    assert.deepEqual(events[3].classNames, ['entities-unavail-pending']);
    assert.equal(events[3].title, 'Not saved');
});
t('new blocks and new series are shown as not saved', () => {
    const p = new PendingChanges();
    const blockid = p.addBlock(ts('2026-10-28T08:00:00'), ts('2026-10-28T09:00:00'));
    const seriesid = p.createSeries({weekdays: [3], allday: true, daystart: 0, dayend: 0,
        validfrom: ts('2026-11-04T00:00:00'), validuntil: ts('2026-11-11T00:00:00')});
    const all = buildEvents({blocks: [], series: []}, p, labels, now);
    const events = real(all);
    assert.equal(events.length, 3);
    assert.equal(all.length, 5);
    assert.equal(fills(all).length, 2);
    assert.equal(events[0].id, `pending-${blockid}`);
    assert.deepEqual(events[0].extendedProps, {
        kind: 'pending-block', id: blockid, seriesid: 0,
        starttime: ts('2026-10-28T08:00:00'), endtime: ts('2026-10-28T09:00:00'),
    });
    assert.equal(events[1].id, `pseries-${seriesid}-0`);
    assert.equal(events[1].allDay, true);
    assert.equal(events[1].start, '2026-11-04');
    assert.equal(events[1].end, '2026-11-05');
    assert.deepEqual(events[1].extendedProps.kind, 'pending-series');
});
t('whole days fill their column, timed blocks do not', () => {
    const events = buildEvents(loaded, new PendingChanges(), labels, now);
    assert.equal(fills(events).length, 1);
    const fill = fills(events)[0];
    assert.equal(fill.id, 'block-2-fill');
    assert.equal(fill.allDay, false);
    assert.equal(fill.start, '2026-10-24T00:00:00');
    assert.equal(fill.end, '2026-10-25T00:00:00');
    assert.deepEqual(fill.classNames, ['entities-unavail-fill']);
    assert.deepEqual(fill.extendedProps, {kind: 'background'});
});
t('the fill of an entry that is not saved is marked as not saved', () => {
    const p = new PendingChanges();
    p.addBlock(ts('2026-10-28T00:00:00'), ts('2026-10-30T00:00:00'));
    p.addBlock(ts('2026-10-28T08:00:00'), ts('2026-10-28T09:00:00'));
    const events = buildEvents({blocks: [], series: []}, p, labels, now);
    assert.equal(fills(events).length, 1);
    assert.deepEqual(fills(events)[0].classNames, ['entities-unavail-fill-pending']);
    assert.equal(fills(events)[0].end, '2026-10-30T00:00:00');
});
t('a removed whole day has no fill any more', () => {
    const p = new PendingChanges();
    p.removeBlock(2);
    assert.equal(fills(buildEvents(loaded, p, labels, now)).length, 0);
});
console.log(`${ok} tests passed (TZ=${process.env.TZ || 'default'})`);
