import assert from 'node:assert/strict';
import {clipFrom, subtractRanges} from 'local_entities/unavailability/range_math';
const r = (a, b) => ({starttime: a, endtime: b});

assert.deepEqual(clipFrom(r(10, 20), 5), r(10, 20));
assert.deepEqual(clipFrom(r(10, 20), 15), r(15, 20));
assert.equal(clipFrom(r(10, 20), 20), null);
assert.equal(clipFrom(r(10, 20), 30), null);

assert.deepEqual(subtractRanges(r(10, 20), []), [r(10, 20)]);
assert.deepEqual(subtractRanges(r(10, 20), [r(0, 5), r(25, 30)]), [r(10, 20)]);
assert.deepEqual(subtractRanges(r(10, 20), [r(0, 10), r(20, 30)]), [r(10, 20)]);
assert.deepEqual(subtractRanges(r(10, 20), [r(0, 30)]), []);
assert.deepEqual(subtractRanges(r(10, 20), [r(10, 20)]), []);
assert.deepEqual(subtractRanges(r(10, 20), [r(0, 12)]), [r(12, 20)]);
assert.deepEqual(subtractRanges(r(10, 20), [r(18, 30)]), [r(10, 18)]);
assert.deepEqual(subtractRanges(r(10, 20), [r(12, 14)]), [r(10, 12), r(14, 20)]);
assert.deepEqual(subtractRanges(r(10, 20), [r(16, 18), r(12, 14)]), [r(10, 12), r(14, 16), r(18, 20)]);
assert.deepEqual(subtractRanges(r(10, 20), [r(11, 15), r(13, 17)]), [r(10, 11), r(17, 20)]);
assert.deepEqual(subtractRanges(r(10, 20), [r(11, 19), r(12, 13)]), [r(10, 11), r(19, 20)]);
console.log('ok');

import {toInputValue, fromInputValue} from 'local_entities/unavailability/range_math';
const local = (s) => Math.floor(new Date(s).getTime() / 1000);
assert.equal(toInputValue(local('2026-10-19T08:05:00')), '2026-10-19T08:05');
assert.equal(toInputValue(local('2026-03-29T03:30:00')), '2026-03-29T03:30');
assert.equal(fromInputValue('2026-10-19T08:05'), local('2026-10-19T08:05:00'));
assert.equal(fromInputValue(''), null);
assert.equal(fromInputValue('nonsense'), null);
for (const t of [local('2026-10-25T01:30:00'), local('2026-12-31T23:59:00'), local('2026-01-01T00:00:00')]) {
    assert.equal(fromInputValue(toInputValue(t)), t);
}
console.log('ok time');

import {isWholeDays, toDateValue, toLastDayValue, fromDateValue, fromLastDayValue} from 'local_entities/unavailability/range_math';
assert.equal(isWholeDays(local('2026-10-16T00:00:00'), local('2026-10-23T00:00:00')), true);
assert.equal(isWholeDays(local('2026-10-16T00:00:00'), local('2026-10-23T08:00:00')), false);
assert.equal(isWholeDays(local('2026-10-16T01:00:00'), local('2026-10-23T00:00:00')), false);
assert.equal(toDateValue(local('2026-10-16T23:59:00')), '2026-10-16');
assert.equal(toLastDayValue(local('2026-10-23T00:00:00')), '2026-10-22');
assert.equal(toLastDayValue(local('2026-10-22T14:00:00')), '2026-10-22');
assert.equal(fromDateValue('2026-10-19'), local('2026-10-19T00:00:00'));
assert.equal(fromDateValue('19.10.2026'), null);
assert.equal(fromDateValue(''), null);
assert.equal(fromLastDayValue('2026-10-22'), local('2026-10-23T00:00:00'));
assert.equal(fromLastDayValue('2026-10-31'), local('2026-11-01T00:00:00'));
assert.equal(fromLastDayValue('2026-12-31'), local('2027-01-01T00:00:00'));
assert.equal(fromLastDayValue('x'), null);
// Around the change of the clock the next day still starts at midnight.
assert.equal(fromLastDayValue('2026-03-28') === local('2026-03-29T00:00:00'), true);
assert.equal(fromLastDayValue('2026-03-29') === local('2026-03-30T00:00:00'), true);
assert.equal(fromLastDayValue('2026-10-24') === local('2026-10-25T00:00:00'), true);
assert.equal(fromLastDayValue('2026-10-25') === local('2026-10-26T00:00:00'), true);
// Round trip for whole-day ranges.
const s = local('2026-10-16T00:00:00'), e = local('2026-10-23T00:00:00');
assert.equal(fromDateValue(toDateValue(s)), s);
assert.equal(fromLastDayValue(toLastDayValue(e)), e);
console.log('ok days');
