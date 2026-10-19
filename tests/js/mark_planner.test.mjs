import assert from 'node:assert/strict';
import {planMark} from 'local_entities/unavailability/mark_planner';

let ok = 0;
const t = (name, fn) => { fn(); ok++; console.log('ok -', name); };
const r = (a, b) => ({starttime: a, endtime: b});
const b = (id, a, e, kind = 'pending-block') => ({kind, id, starttime: a, endtime: e});

t('nothing there: the range is added', () => {
    assert.deepEqual(planMark(r(10, 20), [], []), {remove: [], add: [r(10, 20)]});
});
t('a block inside the range is joined into one', () => {
    // the case of the user: a slot, then the whole day
    assert.deepEqual(planMark(r(0, 100), [b('new-1', 40, 60)], []),
        {remove: [{kind: 'pending-block', id: 'new-1'}], add: [r(0, 100)]});
});
t('a saved block inside the range is replaced', () => {
    assert.deepEqual(planMark(r(0, 100), [b(5, 40, 60, 'block')], []),
        {remove: [{kind: 'block', id: 5}], add: [r(0, 100)]});
});
t('the range lies inside a block: nothing changes', () => {
    assert.deepEqual(planMark(r(45, 55), [b(5, 40, 60, 'block')], []), {remove: [], add: []});
    assert.deepEqual(planMark(r(40, 60), [b(5, 40, 60, 'block')], []), {remove: [], add: []});
});
t('overlapping and touching blocks are joined, others stay', () => {
    const blocks = [b(1, 0, 10, 'block'), b(2, 30, 40, 'block'), b(3, 90, 100, 'block')];
    assert.deepEqual(planMark(r(10, 35), blocks, []),
        {remove: [{kind: 'block', id: 1}, {kind: 'block', id: 2}], add: [r(0, 40)]});
});
t('a range that reaches over several blocks joins them all', () => {
    const blocks = [b(1, 10, 20), b(2, 30, 40), b(3, 50, 60)];
    const plan = planMark(r(15, 55), blocks, []);
    assert.equal(plan.remove.length, 3);
    assert.deepEqual(plan.add, [r(10, 60)]);
});
t('days of a series are not touched, the range fills around them', () => {
    const plan = planMark(r(0, 100), [], [r(40, 60)]);
    assert.deepEqual(plan, {remove: [], add: [r(0, 40), r(60, 100)]});
});
t('a block next to a series day is joined only up to it', () => {
    const plan = planMark(r(0, 100), [b(1, 70, 80)], [r(40, 60)]);
    assert.deepEqual(plan, {remove: [{kind: 'pending-block', id: 1}], add: [r(0, 40), r(60, 100)]});
});
t('a block that reaches under a fixed range is not extended through it', () => {
    const plan = planMark(r(0, 30), [b(1, 25, 50)], [r(40, 60)]);
    assert.deepEqual(plan, {remove: [{kind: 'pending-block', id: 1}], add: [r(0, 50)]});
});
t('everything already taken: nothing is added', () => {
    assert.deepEqual(planMark(r(45, 55), [], [r(40, 60)]), {remove: [], add: []});
});
console.log(`${ok} tests passed`);
