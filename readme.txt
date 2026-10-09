=== Availability Calendar for Beds24 ===
Contributors:       jacquesleisy
Tags:               beds24, availability, calendar, booking, hotel, b&b
Requires at least:  5.9
Tested up to:       6.9
Requires PHP:       7.4
Stable tag:         1.6.1
License:            GPLv2 or later
License URI:        https://www.gnu.org/licenses/gpl-2.0.html

Displays a Beds24 room or property availability calendar via the [avail_calendar] shortcode.

== Description ==

A lightweight, dependency-free availability calendar for properties managed with Beds24.com.

Features:
* Displays availability for a specific room (`roomid`) or whole property (`propid`)
* Colour-coded tiles: green (available), red (unavailable), diagonal split for arrival/departure days
* Locale-aware: month names, weekday labels, and first day of week adapt automatically to any BCP-47 language tag via the browser's built-in Intl API
* Click-to-book: available dates link directly to the Beds24 booking page
* SessionStorage cache with 5-minute TTL — avoids repeated API calls on navigation
* Dark mode support via `prefers-color-scheme`
* Multiple instances on the same page are fully supported
* No jQuery, no build step, no external dependencies

== Installation ==

1. Upload the `availability-calendar-for-beds24` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu in WordPress
3. Add the shortcode to any page or post

== Shortcode Usage ==

Display a calendar for a specific room:
  [avail_calendar roomid="12345" nummonths="5" lang="fr"]

Display a calendar for a whole property:
  [avail_calendar propid="67890" nummonths="3" lang="en"]

Show 3 months on desktop, 2 on tablet and 1 on mobile:
  [avail_calendar roomid="12345" nummonths="3" nummonthstablet="2" nummonthsmobile="1" lang="fr"]

= Shortcode Attributes =

| Attribute       | Default           | Description                                      |
|-----------------|-------------------|--------------------------------------------------|
| roomid          | —                 | Beds24 room ID (use roomid OR propid, not both)  |
| propid          | —                 | Beds24 property ID                               |
| nummonths       | 5                 | Months shown on desktop (1–12)                   |
| nummonthstablet | same as nummonths | Months shown on tablet, 768–1024 px (1–12)       |
| nummonthsmobile | same as nummonths | Months shown on mobile, < 768 px (1–12)          |
| startmonth      | current           | Starting month (1–12)                            |
| startyear       | current           | Starting year (2020–2100)                        |
| lang            | en                | BCP-47 locale tag, e.g. fr, de, es, it, nl       |

The device tier is decided in the visitor's browser from the viewport width, and the calendar re-renders if the window is resized across a tier.

== Privacy Notes ==

* The calendar fetches availability data directly from `media.xmlcal.com` in the visitor's browser.
  No data passes through your WordPress server.
* The Beds24 `roomid` or `propid` is visible in the browser's network requests and in the
  booking URL when a guest clicks a date. This is inherent to how the Beds24 API works
  and is consistent with the behaviour of the official Beds24 booking widget.
* No personal data is collected or stored. SessionStorage is used only to cache
  availability data for 5 minutes and is cleared when the browser tab is closed.

== Changelog ==

= 1.6.1 =
* Fix: the [avail_calendar] shortcode now accepts nummonthstablet and nummonthsmobile (previously dropped by shortcode_atts), matching the Elementor widget's per-device month counts
* Docs: document the per-device attributes; correct the month range to 1–12

= 1.6.0 =
* Add display schemes: five named presets (Solid, Minimal, Outline, Soft, Dot) selectable from the Elementor Style tab or via the `scheme` shortcode attribute
* Add seven display axes — day shape (rounded/square/circle), available style (fill/outline/ghost/dot/plain), unavailable style (fill/outline/ghost/dot/strikethrough/dim/hidden), past days (muted/same-as-unavailable/hidden), hover effect (brighten/fill/outline/scale/lift), today indicator (ring-rect/ring-circle/bold/none), nav arrows (split/top-left/top-right) — configurable individually via "Custom" scheme
* Fix: today ring outline clipping in Chrome — replaced `outline` with `box-shadow: inset` throughout
* Fix: Elementor color and border-radius controls now set CSS custom properties on the wrapper instead of direct element styles, so they work correctly with all display schemes

= 1.5.2 =
* Upgrade PHPCS ruleset from WordPress to WordPress-Extra for stricter escape, nonce, and SQL auditing
* Add composer audit step to CI pipeline to detect known CVEs in vendored dependencies
* Pin GitHub Actions to full commit SHAs to prevent supply-chain tag-hijacking attacks
* Replace phpcs:ignore escape suppression in Elementor widget render() with wp_kses() and an explicit HTML allowlist

= 1.5.0 =
* Add Elementor live preview: the calendar now renders live in the editor canvas — changes to room/property ID, month count, start date, and language update instantly without saving
* Add full Style-tab live preview: all colour, typography, and tile settings are visible immediately as you adjust them in the editor
* Add dynamic field support in the Elementor preview: Room ID and Property ID fields now accept Elementor dynamic tags (e.g. ACF fields) — the editor preview fetches and displays live Beds24 availability data using the resolved field value

= 1.3.5 =
* Add Elementor Style tab with Colors, Typography, and Tiles sections — available/unavailable colours, calendar background, text, past days, month title, day headers, today ring, font controls, tile size, border radius, tile gap, and month gap are all now configurable directly in the Elementor editor

= 1.3.4 =
* Fix: Remove dark mode CSS — calendar now always renders in the light theme, preventing a dark background box from appearing on devices with dark mode enabled

= 1.3.3 =
* Fix: Responsive slider values (numMonthsTablet / numMonthsMobile) now read from get_data('settings') with control-default fallbacks — Elementor skips register_controls() on the front-end when applying a Single Post Template, so get_settings() could not resolve tablet/mobile defaults, causing both to fall back to the desktop value

= 1.3.2 =
* Debug: Temporary release adding error_log traces to identify Elementor responsive slider data source — superseded by 1.3.3

= 1.3.1 =
* Fix: Attempted fix for Elementor responsive slider cascade (superseded by 1.3.3)

= 1.3.0 =
* Rename: plugin is now "Availability Calendar for Beds24" — new slug availability-calendar-for-beds24, new main file availability-calendar-for-beds24.php
* Rename: JavaScript class renamed from Beds24Calendar to AvailCalendar
* Note: shortcode [avail_calendar], Elementor widget slug, and CSS classes (.bac-*) are unchanged for backward compatibility

= 1.2.2 =
* Fix: Elementor responsive months-per-device regression introduced in 1.2.1 — desktop nummonths slider now reads from raw settings (get_settings()) to prevent Elementor's server-side responsive cascade from overwriting the desktop value

= 1.2.1 =
* Fix: Elementor widget now resolves ACF dynamic tag values (e.g. roomid/propid) correctly — switching from get_settings() back to get_settings_for_display() for text controls while preserving raw breakpoint slider values via get_settings()
* Fix: Plugin Update Checker now correctly detects available versions — version is derived from the GitHub release download URL rather than the static placeholder in the repo source file

= 1.2.0 =
* Fix: Elementor responsive months-per-device setting now correctly controls desktop, tablet, and mobile display counts
* Fix: viewport resize listener is now always wired up so switching device sizes re-renders the calendar without a page reload

= 1.1.6 =
* Tested and confirmed compatible with WordPress 6.9

= 1.1.5 =
* Add Plugin Update Checker (YahnisElsts/plugin-update-checker v5) — WordPress admin now shows update notifications and release notes when a new version is published on GitHub
* To track a development branch instead of tagged releases, define BAC_UPDATE_BRANCH in wp-config.php

= 1.1.4 =
* Elementor widget: Months to Display slider range reduced to 1–12; defaults 3 / 2 / 1 for desktop / tablet / mobile

= 1.1.3 =
* Release workflow: version number is now injected automatically from the git tag — no manual bump needed

= 1.1.2 =
* Elementor widget: Months to Display is now a responsive control — configure different values for desktop, tablet, and mobile

= 1.1.1 =
* Fix: moved assets (calendar.css, calendar.js) into assets/ subdirectory to resolve 404 errors on deployed sites

= 1.1.0 =
* Add Elementor widget with full settings panel (room/property ID, months, start date, language)
* Add automatic language detection via TranslatePress and WordPress locale (BCP-47)
* Add GitHub Actions CI (PHPCS + PHPStan) and automated release workflow

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.3.0 =
Plugin renamed to "Availability Calendar for Beds24". After updating, you will need to reactivate the plugin manually — the plugin file has been renamed.

= 1.2.2 =
Fixes a regression in 1.2.1 where the desktop months-per-device count could be overridden by Elementor's server-side responsive cascade.

= 1.2.0 =
Fixes responsive month counts in the Elementor widget — desktop, tablet, and mobile values now work as configured.

= 1.1.6 =
Confirms compatibility with WordPress 6.9 — clears the "not tested" admin warning.

= 1.1.5 =
Adds automatic update notifications in the WordPress admin — no manual zip download needed for future releases.

= 1.1.4 =
Elementor widget slider now capped at 12 months with responsive defaults.
