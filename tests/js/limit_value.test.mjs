import assert from 'node:assert/strict';
import {parseLimit} from 'local_entities/unavailability/limit_value';

assert.equal(parseLimit('', 99), 0);
assert.equal(parseLimit('  ', 99), 0);
assert.equal(parseLimit('3', 99), 3);
assert.equal(parseLimit(' 99 ', 99), 99);
assert.equal(parseLimit('0', 99), null);
assert.equal(parseLimit('100', 99), null);
assert.equal(parseLimit('2.5', 99), null);
assert.equal(parseLimit('-1', 99), null);
assert.equal(parseLimit('abc', 99), null);
console.log('ok daily limit');
