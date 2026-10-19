import assert from 'node:assert/strict';
import PendingChanges from 'local_entities/unavailability/pending_changes';

let ok = 0;
const t = (name, fn) => { fn(); ok++; console.log('ok -', name); };

t('starts clean', () => {
    const p = new PendingChanges();
    assert.equal(p.isDirty(), false);
    assert.deepEqual(p.toPayload(), {addblocks: [], removeblockids: [], createseries: [], updateseries: [], deleteseries: []});
});
t('adds a block and reports it in the payload', () => {
    const p = new PendingChanges();
    const id = p.addBlock(1000, 2000);
    assert.match(id, /^new-/);
    assert.equal(p.isDirty(), true);
    assert.deepEqual(p.toPayload().addblocks, [{starttime: 1000, endtime: 2000, reason: ''}]);
});
t('ignores a block without length', () => {
    const p = new PendingChanges();
    assert.equal(p.addBlock(2000, 2000), null);
    assert.equal(p.addBlock(3000, 2000), null);
    assert.equal(p.isDirty(), false);
});
t('removing a block added here just forgets it', () => {
    const p = new PendingChanges();
    const id = p.addBlock(1000, 2000);
    p.removeBlock(id);
    assert.equal(p.isDirty(), false);
    assert.deepEqual(p.toPayload().removeblockids, []);
});
t('removing a stored block lists its id once', () => {
    const p = new PendingChanges();
    p.removeBlock(7);
    p.removeBlock('7');
    assert.deepEqual(p.toPayload().removeblockids, [7]);
    assert.equal(p.isBlockRemoved(7), true);
    assert.equal(p.isBlockRemoved(8), false);
});
t('creating a series and deleting it again leaves nothing', () => {
    const p = new PendingChanges();
    const id = p.createSeries({weekdays: [1]});
    assert.equal(p.isDirty(), true);
    p.deleteSeries(id);
    assert.equal(p.isDirty(), false);
});
t('changing a series created here replaces its rule, a stored one is listed as update', () => {
    const p = new PendingChanges();
    const id = p.createSeries({weekdays: [1]});
    p.updateSeries(id, {weekdays: [2]});
    assert.deepEqual(p.toPayload().createseries, [{weekdays: [2]}]);
    assert.deepEqual(p.toPayload().updateseries, []);
    p.updateSeries(5, {weekdays: [3]});
    assert.deepEqual(p.toPayload().updateseries, [{id: 5, rule: {weekdays: [3]}}]);
});
t('deleting a stored series drops a pending update of it', () => {
    const p = new PendingChanges();
    p.updateSeries(5, {weekdays: [3]});
    p.deleteSeries(5);
    assert.deepEqual(p.toPayload().updateseries, []);
    assert.deepEqual(p.toPayload().deleteseries, [5]);
    assert.equal(p.isSeriesDeleted(5), true);
});
t('reset forgets everything', () => {
    const p = new PendingChanges();
    p.addBlock(1, 2);
    p.removeBlock(3);
    p.createSeries({});
    p.deleteSeries(4);
    p.reset();
    assert.equal(p.isDirty(), false);
});
console.log(`${ok} tests passed`);
