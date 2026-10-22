/**
 * Tests for the first day of the week and the TranslatePress opt-out (1.10.1).
 * Run with `npm test` (node:test, no dependencies).
 */
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const src = readFileSync(new URL('../assets/calendar.js', import.meta.url), 'utf8');

/* A calendar class in its own context; noWeekInfo = a browser without Intl week data (Firefox). */
function load(noWeekInfo = false) {
    const context = vm.createContext({});
    if (noWeekInfo) {
        vm.runInContext('Intl.Locale = function () { return {}; };', context);
    }
    vm.runInContext(src + '\nthis.AvailCalendar = AvailCalendar;', context);
    return context.AvailCalendar;
}

const calendar = (AvailCalendar, cfg) => Object.assign(Object.create(AvailCalendar.prototype), { cfg });

test('first day of the week follows the locale with its region: en-GB Monday, en-US Sunday', () => {
    for (const noWeekInfo of [false, true]) {
        const AvailCalendar = load(noWeekInfo);
        const first = (cfg) => calendar(AvailCalendar, cfg)._firstDayOfWeek();
        const label = noWeekInfo ? ' (no Intl week data)' : '';
        for (const locale of ['en-GB', 'fr-FR', 'de-DE', 'es-ES', 'it-IT']) {
            assert.equal(first({ lang: locale.slice(0, 2), locale }), 1, locale + label);
        }
        assert.equal(first({ lang: 'en', locale: 'en-US' }), 0, 'en-US' + label);
        assert.equal(first({ lang: 'en', locale: null }), 0, 'no locale: lang alone, as before' + label);
    }
});

test('en-GB weekday row starts on Monday, the month title is unchanged', () => {
    const cal = calendar(load(), { lang: 'en', locale: 'en-GB' });
    assert.equal(cal._dowLabels().join(' '), 'Mon Tue Wed Thu Fri Sat Sun');
    assert.equal(cal._monthName(2027, 4), 'May');
});

test('every element the calendar creates carries data-no-dynamic-translation', () => {
    const context = vm.createContext({
        document: { createElement: (tag) => ({ tag, attrs: {}, setAttribute(n, v) { this.attrs[n] = v; } }) },
    });
    vm.runInContext(src + '\nthis.AvailCalendar = AvailCalendar;', context);
    const el = Object.create(context.AvailCalendar.prototype)._el('div', 'bac-month');
    assert.equal(el.attrs['data-no-dynamic-translation'], '');
    assert.equal(el.className, 'bac-month');
    assert.ok(!/document\.createElement\(/.test(src.replace(/const el = document\.createElement\(tag\);/, '')), 'no element created outside _el()');
});
