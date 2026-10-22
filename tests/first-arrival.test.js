/**
 * Tests for AvailCalendar._firstArrival() and the ‹ floor (1.9.0), and the state before the data arrives (1.10.0).
 * Run with `npm test` (node:test, no dependencies).
 */
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const src = readFileSync(new URL('../assets/calendar.js', import.meta.url), 'utf8');
const context = vm.createContext({});
vm.runInContext(src + '\nthis.AvailCalendar = AvailCalendar;', context);
const { AvailCalendar } = context;

/* An instance without DOM: today = 22 Oct 2026, availability from `avail`. */
function calendar(avail, today = new Date(2026, 9, 22)) {
    const cal = Object.create(AvailCalendar.prototype);
    cal.TODAY = today;
    cal.avail = avail;
    cal._loaded = true;
    return cal;
}

/* { 'YYYY-MM-DD': v } for every day from..to (inclusive). */
function range(from, to, v, into = {}) {
    const [fy, fm, fd] = from.split('-').map(Number);
    const [ty, tm, td] = to.split('-').map(Number);
    for (const d = new Date(fy, fm - 1, fd); d <= new Date(ty, tm - 1, td); d.setDate(d.getDate() + 1)) {
        into[d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0')] = v;
    }
    return into;
}

const iso = (d) => d && d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');

test('today available → today', () => {
    const avail = range('2026-10-21', '2027-10-22', 1);
    assert.equal(iso(calendar(avail)._firstArrival()), '2026-10-22');
});

test('first arrival months ahead (Domeyne, Oct 2026) → 1 May 2027, free from the afternoon', () => {
    const avail = range('2026-10-21', '2027-04-30', 0);
    range('2027-05-01', '2027-10-22', 1, avail);
    const cal = calendar(avail);
    const first = cal._firstArrival();
    assert.equal(iso(first), '2027-05-01');
    assert.equal(cal._tileClass(first), 'bac-split-avail-pm');
});

test('split day at a month boundary: 1 Dec free after 30 Nov taken → December', () => {
    const avail = range('2026-10-21', '2026-11-30', 0);
    range('2026-12-01', '2027-10-22', 1, avail);
    assert.equal(iso(calendar(avail)._firstArrival()), '2026-12-01');
});

test('split day at a month boundary: 30 Nov free, 1 Dec taken → November (30 Nov)', () => {
    const avail = range('2026-10-21', '2027-10-22', 0);
    avail['2026-11-30'] = 1;
    const cal = calendar(avail);
    assert.equal(iso(cal._firstArrival()), '2026-11-30');
    // 1 Dec is free in the morning only (departure day): not an arrival
    assert.equal(cal._tileClass(new Date(2026, 11, 1)), 'bac-split-avail-am');
});

test('yesterday free, today taken → today is not an arrival', () => {
    const avail = range('2026-10-21', '2027-10-22', 0);
    avail['2026-10-21'] = 1;
    assert.equal(calendar(avail)._firstArrival(), null);
});

test('no arrival in the data → null, not the day after the data ends', () => {
    assert.equal(calendar(range('2026-10-21', '2027-10-22', 0))._firstArrival(), null);
});

test('empty, null or malformed data → null', () => {
    assert.equal(calendar({})._firstArrival(), null);
    assert.equal(calendar(null)._firstArrival(), null);
    assert.equal(calendar([0, 1])._firstArrival(), null);
});

test('a date missing inside the data counts as available, as displayed', () => {
    const avail = range('2026-10-21', '2027-10-22', 0);
    delete avail['2027-01-15'];
    assert.equal(iso(calendar(avail)._firstArrival()), '2027-01-15');
});

test('‹ stops at the current month when opening on the first arrival', () => {
    const cal = calendar({});
    cal._render = () => {};
    cal._startFirstArrival = true;
    cal.viewYear = 2027;
    cal.viewMonth = 1;
    for (let i = 0; i < 10; i++) cal._shift(-1);
    assert.deepEqual([cal.viewYear, cal.viewMonth], [2026, 10]);
    cal._shift(+1);
    assert.deepEqual([cal.viewYear, cal.viewMonth], [2026, 11]);
});

test('‹ is not limited with startFrom "current" (as before 1.9.0)', () => {
    const cal = calendar({});
    cal._render = () => {};
    cal._startFirstArrival = false;
    cal.viewYear = 2026;
    cal.viewMonth = 10;
    cal._shift(-1);
    assert.deepEqual([cal.viewYear, cal.viewMonth], [2026, 9]);
});

test('before the data arrives: no day is available or bookable, past days stay past', () => {
    const cal = calendar({});
    cal._loaded = false;
    assert.equal(cal._tileClass(new Date(2026, 9, 21)), 'bac-past');
    for (const d of [new Date(2026, 9, 22), new Date(2026, 10, 3), new Date(2027, 4, 1)]) {
        assert.equal(cal._tileClass(d), 'bac-pending');
        assert.equal(cal._isBookable(cal._tileClass(d)), false);
    }
});

test('after the data arrives: tiles come from the data', () => {
    const cal = calendar(range('2026-10-21', '2027-10-22', 1));
    assert.equal(cal._tileClass(new Date(2026, 10, 3)), 'bac-available');
});
