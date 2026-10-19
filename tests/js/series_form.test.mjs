import assert from 'node:assert/strict';
import {toTimeValue, fromTimeValue, weekdayNames, ruleFromForm, formFromRule, checkRule, defaultRule} from 'local_entities/unavailability/series_form';

const local = (s) => Math.floor(new Date(s).getTime() / 1000);
let ok = 0;
const t = (name, fn) => { fn(); ok++; console.log('ok -', name); };

t('time values', () => {
    assert.equal(toTimeValue(0), '00:00');
    assert.equal(toTimeValue(8 * 3600 + 30 * 60), '08:30');
    assert.equal(toTimeValue(86400), '23:59');
    assert.equal(fromTimeValue('08:30'), 30600);
    assert.equal(fromTimeValue('23:59'), 86340);
    assert.equal(fromTimeValue('24:00'), null);
    assert.equal(fromTimeValue('8:30'), null);
    assert.equal(fromTimeValue(''), null);
});
t('weekday names start on monday', () => {
    assert.equal(weekdayNames('en').length, 7);
    assert.equal(weekdayNames('en')[0], 'Mon');
    assert.equal(weekdayNames('en')[6], 'Sun');
    assert.match(weekdayNames('de')[0], /^Mo/);
});
t('rule from form', () => {
    const rule = ruleFromForm({weekdays: [3, 1], allday: false, start: '08:00', end: '12:00', from: '2026-10-19', until: '2026-11-30'}, 'Europe/Vienna');
    assert.deepEqual(rule, {weekdays: [1, 3], allday: false, daystart: 28800, dayend: 43200,
        validfrom: local('2026-10-19T00:00'), validuntil: local('2026-11-30T00:00'), timezone: 'Europe/Vienna', reason: ''});
    const allday = ruleFromForm({weekdays: [2], allday: true, start: '', end: '', from: '2026-10-19', until: '2026-10-19'}, 'UTC');
    assert.equal(allday.daystart, 0);
    assert.equal(allday.dayend, 0);
    assert.equal(allday.validuntil, allday.validfrom);
});
t('form from rule and back', () => {
    const rule = {weekdays: [1, 3], allday: false, daystart: 28800, dayend: 43200,
        validfrom: local('2026-10-19T00:00'), validuntil: local('2026-11-30T00:00'), timezone: 'x', reason: ''};
    const form = formFromRule(rule);
    assert.deepEqual(form, {weekdays: [1, 3], allday: false, start: '08:00', end: '12:00', from: '2026-10-19', until: '2026-11-30'});
    assert.deepEqual(ruleFromForm(form, 'x'), rule);
    const allday = formFromRule({...rule, allday: true, daystart: 0, dayend: 0});
    assert.equal(allday.start, '08:00');
    assert.equal(allday.end, '16:00');
});
t('rule checks', () => {
    const today = local('2026-10-16T00:00');
    const base = {weekdays: [1], allday: false, daystart: 28800, dayend: 43200,
        validfrom: local('2026-10-19T00:00'), validuntil: local('2026-11-30T00:00'), timezone: 'x', reason: ''};
    assert.equal(checkRule(base, today), null);
    assert.equal(checkRule({...base, weekdays: []}, today), 'noweekday');
    assert.equal(checkRule({...base, dayend: 28800}, today), 'badtime');
    assert.equal(checkRule({...base, daystart: null}, today), 'badtime');
    assert.equal(checkRule({...base, allday: true, daystart: null, dayend: null}, today), null);
    assert.equal(checkRule({...base, validuntil: local('2026-10-18T00:00')}, today), 'badperiod');
    assert.equal(checkRule({...base, validfrom: null}, today), 'badperiod');
    assert.equal(checkRule({...base, validfrom: local('2026-10-15T00:00')}, today), 'badperiod');
    assert.equal(checkRule({...base, validfrom: local('2026-10-15T00:00')}, today, local('2026-10-15T00:00')), null);
    assert.equal(checkRule({...base, validfrom: today, validuntil: local('2027-10-17T00:00')}, today), null);
    assert.equal(checkRule({...base, validfrom: today, validuntil: local('2027-10-18T00:00')}, today), 'badperiod');
    assert.equal(checkRule({...base, validuntil: base.validfrom}, today), null);
});
t('the default rule is a valid starting point', () => {
    const today = local('2026-10-16T00:00');
    const rule = defaultRule(today, 'Europe/Vienna');
    assert.deepEqual(rule.weekdays, []);
    assert.equal(rule.allday, true);
    assert.equal(rule.validfrom, today);
    assert.equal(rule.validuntil, local('2027-01-14T00:00'));
    assert.equal(checkRule({...rule, weekdays: [1]}, today), null);
    assert.equal(checkRule(rule, today), 'noweekday');
    assert.deepEqual(formFromRule(rule).from, '2026-10-16');
    assert.deepEqual(formFromRule(rule).until, '2027-01-14');
});
console.log(`${ok} tests passed (TZ=${process.env.TZ || 'default'})`);
