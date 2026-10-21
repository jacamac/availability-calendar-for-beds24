/**
 * Availability Calendar for Beds24 — calendar.js
 *
 * Copyright (C) 2026 Jacques Leisy
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * This plugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * You should have received a copy of the GNU General Public License
 * along with this plugin. If not, see <https://www.gnu.org/licenses/old-licenses/gpl-2.0.txt>.
 *
 * Usage (emitted by WordPress shortcode handler):
 *   new AvailCalendar({
 *     containerId: 'bac-1',
 *     roomid:      12345,    // OR propid, not both
 *     numMonths:   5,
 *     startMonth:  null,     // 1-based, null = current
 *     startYear:   null,     // null = current
 *     lang:        'fr',
 *   });
 */

/* exported AvailCalendar */
class AvailCalendar {

    /* ─── Constructor ─────────────────────────────────────── */
    constructor(config) {
        // Merge caller config with safe defaults
        this.cfg = Object.assign({
            containerId:    null,
            propid:         null,
            roomid:         null,
            numMonths:      5,
            numMonthsTablet: null,
            numMonthsMobile: null,
            startMonth:     null,
            startYear:      null,
            lang:           'en',
            bookingUrl:     null,   // the site's /booking/ page in the page language (PHP); null = Beds24's page
            scheme:        'solid',
            schemeShape:   null,
            schemeAvail:   null,
            schemeUnavail: null,
            schemePast:    null,
            schemeHover:   null,
            schemeToday:   null,
            schemeNav:     null,
        }, config);

        this.container = document.getElementById(this.cfg.containerId);
        if (!this.container) {
            console.error('AvailCalendar: container not found —', this.cfg.containerId);
            return;
        }

        const now       = new Date();
        this.TODAY      = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        this.viewYear   = this.cfg.startYear  || now.getFullYear();
        this.viewMonth  = this.cfg.startMonth || (now.getMonth() + 1);  // 1-based
        this.avail      = {};
        this.CACHE_TTL  = 5 * 60 * 1000;   // 5 minutes
        this.AVAIL_BASE = 'https://media.xmlcal.com/widget/1.00/scripts/availability.php';
        this.BOOK_BASE  = 'https://beds24.com/booking.php';

        this._buildDOM();
        this._render();
        this._fetchAvailability().then(() => {
            this._render();
            // Refresh every 5 minutes for long-lived tabs
            this._refreshTimer = setInterval(() => {
                this._fetchAvailability().then(() => this._render());
            }, this.CACHE_TTL);
        });

        // Re-render only when the viewport crosses a breakpoint tier
        if (this.cfg.numMonthsTablet || this.cfg.numMonthsMobile) {
            this._currentTier = this._breakpointTier();
            window.addEventListener('resize', () => {
                const tier = this._breakpointTier();
                if (tier !== this._currentTier) {
                    this._currentTier = tier;
                    this._render();
                }
            });
        }
    }

    /* ─── DOM scaffold ────────────────────────────────────── */
    _buildDOM() {
        this.container.innerHTML = '';

        // Navigation bar
        const nav = document.createElement('div');
        nav.className = 'bac-nav';

        this._btnPrev = document.createElement('button');
        this._btnPrev.setAttribute('aria-label', 'Previous');
        this._btnPrev.innerHTML = '&#8249;';
        this._btnPrev.addEventListener('click', () => this._shift(-1));

        this._btnNext = document.createElement('button');
        this._btnNext.setAttribute('aria-label', 'Next');
        this._btnNext.innerHTML = '&#8250;';
        this._btnNext.addEventListener('click', () => this._shift(+1));

        this._statusEl = document.createElement('span');
        this._statusEl.className = 'bac-status';

        nav.appendChild(this._btnPrev);
        nav.appendChild(this._btnNext);
        nav.appendChild(this._statusEl);
        this.container.appendChild(nav);

        // Month strip
        this._strip = document.createElement('div');
        this._strip.className = 'bac-strip';
        this.container.appendChild(this._strip);

        // Keyboard navigation — scoped to this instance's container
        this.container.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft')  this._shift(-1);
            if (e.key === 'ArrowRight') this._shift(+1);
        });

        this._applyScheme();
    }

    /* ─── Scheme resolution ───────────────────────────────── */
    _resolveScheme() {
        const PRESETS = {
            solid:   { shape: 'rounded', avail: 'fill',    unavail: 'fill',          past: 'muted',  hover: 'brighten', today: 'ring-rect',   nav: 'split'    },
            minimal: { shape: 'rounded', avail: 'plain',   unavail: 'strikethrough', past: 'hidden', hover: 'fill',     today: 'ring-circle', nav: 'split'    },
            outline: { shape: 'rounded', avail: 'outline', unavail: 'dim',           past: 'muted',  hover: 'fill',     today: 'ring-circle', nav: 'split'    },
            soft:    { shape: 'rounded', avail: 'ghost',   unavail: 'ghost',         past: 'muted',  hover: 'fill',     today: 'ring-circle', nav: 'split'    },
            dot:     { shape: 'circle',  avail: 'dot',     unavail: 'dot',           past: 'hidden', hover: 'scale',    today: 'bold',        nav: 'split'    },
        };
        if (this.cfg.scheme === 'custom') {
            return {
                shape:   this.cfg.schemeShape   || 'rounded',
                avail:   this.cfg.schemeAvail   || 'fill',
                unavail: this.cfg.schemeUnavail || 'fill',
                past:    this.cfg.schemePast    || 'muted',
                hover:   this.cfg.schemeHover   || 'brighten',
                today:   this.cfg.schemeToday   || 'ring-rect',
                nav:     this.cfg.schemeNav     || 'split',
            };
        }
        return PRESETS[this.cfg.scheme] || PRESETS.solid;
    }

    _applyScheme() {
        const s = this._resolveScheme();
        const d = this.container.dataset;
        d.shape   = s.shape;
        d.avail   = s.avail;
        d.unavail = s.unavail;
        d.past    = s.past;
        d.hover   = s.hover;
        d.today   = s.today;
        d.nav     = s.nav;
    }

    /* ─── Responsive numMonths ───────────────────────────────── */
    _breakpointTier() {
        const w = window.innerWidth;
        if (w < 768)  return 'mobile';
        if (w < 1025) return 'tablet';
        return 'desktop';
    }

    _getNumMonths() {
        const tier = this._breakpointTier();
        if (tier === 'mobile' && this.cfg.numMonthsMobile) return this.cfg.numMonthsMobile;
        if (tier === 'tablet' && this.cfg.numMonthsTablet) return this.cfg.numMonthsTablet;
        return this.cfg.numMonths;
    }

    /* ─── Render all visible months ────────────────────────── */
    _render() {
        this._strip.innerHTML = '';
        let y = this.viewYear;
        let m = this.viewMonth;
        for (let i = 0; i < this._getNumMonths(); i++) {
            this._strip.appendChild(this._renderMonth(y, m));
            if (++m > 12) { m = 1; y++; }
        }
    }

    /* ─── Render a single month ─────────────────────────────── */
    _renderMonth(year, m1) {
        const first  = new Date(year, m1 - 1, 1);
        const days   = new Date(year, m1, 0).getDate();
        const fdow   = this._firstDayOfWeek();
        const offset = (first.getDay() - fdow + 7) % 7;

        const wrap = document.createElement('div');
        wrap.className = 'bac-month';

        // Title
        const mn    = this._monthName(year, m1 - 1);
        const title = document.createElement('div');
        title.className   = 'bac-month-title';
        title.textContent = mn.charAt(0).toUpperCase() + mn.slice(1) + ' ' + year;
        wrap.appendChild(title);

        // DOW header
        const dowRow = document.createElement('div');
        dowRow.className = 'bac-dow-row';
        this._dowLabels().forEach(label => {
            const c = document.createElement('div');
            c.className   = 'bac-dow-cell';
            c.textContent = label;
            dowRow.appendChild(c);
        });
        wrap.appendChild(dowRow);

        // Day grid
        const grid = document.createElement('div');
        grid.className = 'bac-grid';

        // Leading blank cells
        for (let i = 0; i < offset; i++) {
            const e = document.createElement('div');
            e.className = 'bac-day bac-empty';
            grid.appendChild(e);
        }

        // Day tiles
        const todayIso = this._iso(this.TODAY);
        for (let d = 1; d <= days; d++) {
            const dt      = new Date(year, m1 - 1, d);
            const cls     = this._tileClass(dt);
            const dateIso = this._iso(dt);
            const isToday = dateIso === todayIso;
            const bookable = this._isBookable(cls);

            const cell = document.createElement('div');
            cell.className = 'bac-day ' + cls + (isToday ? ' bac-today' : '');
            cell.title     = cls === 'bac-past'
                ? dateIso
                : bookable
                    ? this._t('book')
                    : this._t('unavailable');

            const span = document.createElement('span');
            span.textContent = d;
            cell.appendChild(span);

            if (bookable) {
                const a   = document.createElement('a');
                a.href    = this._bookingURL(dateIso);
                if (!this.cfg.bookingUrl) {
                    // Beds24's booking page: a new tab, as before 1.8.0. The site's own page opens in this tab.
                    a.target  = '_blank';
                    a.rel     = 'noopener noreferrer';
                }
                a.style.cssText = 'display:contents;text-decoration:none;color:inherit;';
                a.appendChild(cell);
                grid.appendChild(a);
            } else {
                grid.appendChild(cell);
            }
        }

        wrap.appendChild(grid);
        return wrap;
    }

    /* ─── Navigation ──────────────────────────────────────── */
    _shift(delta) {
        this.viewMonth += delta;
        if      (this.viewMonth > 12) { this.viewMonth -= 12; this.viewYear++; }
        else if (this.viewMonth <  1) { this.viewMonth += 12; this.viewYear--; }
        this._render();
    }

    /* ─── Tile classification ─────────────────────────────── */
    /*
     * Rules:
     *   past                 → 'bac-past'
     *   cur=0, prev=0|past   → 'bac-unavailable'      (rule 2a)
     *   cur=0, prev=1        → 'bac-split-avail-am'   (rule 2b)
     *   cur=1, prev=1|absent → 'bac-available'        (rule 3a)
     *   cur=1, prev=0        → 'bac-split-avail-pm'   (rule 3b)
     */
    _tileClass(dateObj) {
        if (dateObj < this.TODAY) return 'bac-past';

        const cur      = this._val(dateObj);
        const prevDate = new Date(dateObj);
        prevDate.setDate(prevDate.getDate() - 1);
        const prev = (prevDate < this.TODAY) ? 0 : this._val(prevDate);

        if (cur === 0) return prev === 1 ? 'bac-split-avail-am' : 'bac-unavailable';
        else           return prev === 0 ? 'bac-split-avail-pm' : 'bac-available';
    }

    _isBookable(cls) {
        return cls === 'bac-available' || cls === 'bac-split-avail-pm';
    }

    /* ─── Availability data ───────────────────────────────── */
    /* Normalise API value → 0 (unavail) | 1 (avail) */
    _val(dateObj) {
        const v = this.avail[this._iso(dateObj)];
        return (v === undefined || v === 1) ? 1 : 0;
    }

    /* ─── Cache ───────────────────────────────────────────── */
    _cacheKey() {
        if (this.cfg.roomid) return 'beds24_avail_roomid_' + this.cfg.roomid;
        if (this.cfg.propid) return 'beds24_avail_propid_' + this.cfg.propid;
        return null;
    }

    _cacheRead() {
        const key = this._cacheKey();
        if (!key) return null;
        try {
            const raw = sessionStorage.getItem(key);
            if (!raw) return null;
            const parsed = JSON.parse(raw);
            if (typeof parsed.ts   !== 'number'  ||
                typeof parsed.data !== 'object'   ||
                parsed.data === null              ||
                Array.isArray(parsed.data)) {
                sessionStorage.removeItem(key);
                return null;
            }
            if (Date.now() - parsed.ts > this.CACHE_TTL) {
                sessionStorage.removeItem(key);
                return null;
            }
            return parsed.data;
        } catch(_) { return null; }
    }

    _cacheWrite(data) {
        const key = this._cacheKey();
        if (!key) return;
        try {
            sessionStorage.setItem(key, JSON.stringify({ ts: Date.now(), data }));
        } catch(_) {}
    }

    /* ─── Fetch ───────────────────────────────────────────── */
    async _fetchAvailability() {
        if (!this.cfg.propid && !this.cfg.roomid) {
            this.avail = this._buildSampleData();
            this._setStatus('Demo mode — configure a propid or roomid to show live data');
            return;
        }

        const cached = this._cacheRead();
        if (cached) {
            this.avail = cached;
            this._setStatus('');
            return;
        }

        const params = new URLSearchParams();
        if (this.cfg.roomid) params.set('roomid', this.cfg.roomid);
        if (this.cfg.propid) params.set('propid', this.cfg.propid);
        this._setStatus('Loading\u2026');
        try {
            const r    = await fetch(this.AVAIL_BASE + '?' + params);
            this.avail = await r.json();
            this._cacheWrite(this.avail);
            this._setStatus('');
        } catch(e) {
            this._setStatus('\u26a0 Could not load availability data');
            console.error('AvailCalendar fetch error:', e);
        }
    }

    /* Randomised demo data — realistic stay/gap pattern, no real data */
    _buildSampleData() {
        const data   = {};
        const cursor = new Date(this.TODAY);
        cursor.setDate(cursor.getDate() - 30);
        const end = new Date(this.TODAY);
        end.setDate(end.getDate() + 270);

        while (cursor <= end) {
            // gap: 1-5 available nights (absent = available, no entry needed)
            cursor.setDate(cursor.getDate() + 1 + Math.floor(Math.random() * 5));
            // stay: 2-7 unavailable nights
            const stay = 2 + Math.floor(Math.random() * 6);
            for (let i = 0; i < stay && cursor <= end; i++) {
                data[this._iso(cursor)] = 0;
                cursor.setDate(cursor.getDate() + 1);
            }
        }
        return data;
    }

    /* ─── Locale helpers ──────────────────────────────────── */

    /* First day of week for the locale: 0=Sun, 1=Mon */
    _firstDayOfWeek() {
        try {
            const info = new Intl.Locale(this.cfg.lang).getWeekInfo?.() ??
                         new Intl.Locale(this.cfg.lang).weekInfo;
            if (info && info.firstDay !== undefined) return info.firstDay % 7;
        } catch(_) {}
        const sunLocales = ['en','zh','ja','ko','ar','he','fa','hi'];
        return sunLocales.includes(this.cfg.lang.split('-')[0]) ? 0 : 1;
    }

    /* 7 short weekday labels starting on locale's first day */
    _dowLabels() {
        const fmt   = new Intl.DateTimeFormat(this.cfg.lang, { weekday: 'short' });
        const start = this._firstDayOfWeek();
        // Anchor: Mon 1 Jan 2024 (index 0) or Sun 7 Jan 2024 (index 0 for Sunday-first)
        return Array.from({ length: 7 }, (_, i) => {
            const day   = new Date(2024, 0, (start === 0 ? 7 : 1) + i);
            const label = fmt.format(day).replace(/\.$/, '');
            return label.charAt(0).toUpperCase() + label.slice(1);
        });
    }

    /* Full month name */
    _monthName(year, month0) {
        return new Intl.DateTimeFormat(this.cfg.lang, { month: 'long' })
            .format(new Date(year, month0, 1));
    }

    /* ─── i18n tooltip strings ────────────────────────────── */
    _t(key) {
        const strings = {
            en: { unavailable: 'Unavailable',      book: 'Click to book' },
            fr: { unavailable: 'Indisponible',     book: 'Cliquez pour r\u00e9server' },
            de: { unavailable: 'Nicht verf\u00fcgbar', book: 'Zum Buchen klicken' },
            es: { unavailable: 'No disponible',    book: 'Haga clic para reservar' },
            it: { unavailable: 'Non disponibile',  book: 'Clicca per prenotare' },
            nl: { unavailable: 'Niet beschikbaar', book: 'Klik om te boeken' },
            pt: { unavailable: 'Indispon\u00edvel', book: 'Clique para reservar' },
        };
        const base = this.cfg.lang.split('-')[0];
        return (strings[base] || strings.en)[key];
    }

    /* ─── Booking URL ─────────────────────────────────────── */
    _bookingURL(dateIso) {
        if (this.cfg.bookingUrl) {
            // 1.8.0: the site's booking page (/booking/, /en/booking/…) with the arrival and the room.
            const url = new URL(this.cfg.bookingUrl, window.location.href);
            url.searchParams.set('arrivee', dateIso);
            if (this.cfg.roomid) url.searchParams.set('room_id', this.cfg.roomid);
            return url.toString();
        }
        const p = new URLSearchParams({
            checkin: dateIso,
            referer: 'BookingCalendar',
            lang:    this.cfg.lang,
        });
        if (this.cfg.roomid) p.set('roomid', this.cfg.roomid);
        if (this.cfg.propid) p.set('propid', this.cfg.propid);
        return this.BOOK_BASE + '?' + p;
    }

    /* ─── Utilities ───────────────────────────────────────── */
    _iso(d) {
        return d.getFullYear() + '-' +
            String(d.getMonth() + 1).padStart(2, '0') + '-' +
            String(d.getDate()).padStart(2, '0');
    }

    _setStatus(msg) {
        if (this._statusEl) this._statusEl.textContent = msg;
    }

    /* Allow callers to cleanly tear down an instance */
    destroy() {
        clearInterval(this._refreshTimer);
        if (this.container) this.container.innerHTML = '';
    }
}
