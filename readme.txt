=== Availability Calendar for Beds24 ===
Contributors:       jacquesleisy
Tags:               beds24, availability, calendar, booking, hotel, b&b
Requires at least:  5.9
Tested up to:       6.9
Requires PHP:       7.4
Stable tag:         1.10.0
License:            GPLv2 or later
License URI:        https://www.gnu.org/licenses/gpl-2.0.html

Displays a Beds24 room or property availability calendar via the [avail_calendar] shortcode.

== Description ==

A lightweight, dependency-free availability calendar for properties managed with Beds24.com.

Features:
* Displays availability for a specific room (`roomid`) or whole property (`propid`)
* Colour-coded tiles: green (available), red (unavailable), diagonal split for arrival/departure days
* Locale-aware: month names, weekday labels, and first day of week adapt automatically to any BCP-47 language tag via the browser's built-in Intl API
* Opens on the month of the first possible arrival (startfrom="available", default), decided in the browser from the loaded data
* Click-to-book: an available date opens the site's /booking/ page (in the page's language) with the date and room, or the Beds24 booking page with beds24_booking="true"
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
| startmonth      | see startfrom     | Starting month (1–12)                            |
| startyear       | see startfrom     | Starting year (2020–2100)                        |
| startfrom       | available         | available = month of the first possible arrival; current = current month. Ignored when startmonth or startyear is set |
| lang            | en                | BCP-47 locale tag, e.g. fr, de, es, it, nl       |
| beds24_booking  | false             | true = days link to the Beds24 booking page      |

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

= 1.10.0 =
* Fix: no day shows as available, or is clickable, before the Beds24 data has arrived, in both startfrom modes: availability comes only from Beds24. Until then days from today are drawn like past days (class bac-pending). Before 1.10.0 every day briefly showed as available.
* Fix: in startfrom="available" mode the calendar can no longer stay hidden: on a failed availability call (network error, HTTP error, unexpected response) it shows the current month at once, and after 5 seconds without an answer it shows the current month; late data then fills the days without moving the months.
* New: DOM event bac:book on the calendar wrapper when a bookable day is clicked, dispatched before the link is followed (bubbles, not cancelable), detail { arrival: 'YYYY-MM-DD', roomId }. For the site's analytics; the plugin sends nothing itself.
* Fix: an HTTP error or a non-object response is no longer stored as availability.

= 1.9.0 =
* New: the calendar opens on the month of the first possible arrival (a day the calendar links to booking: available, or free from the afternoon), from today onwards; with several months shown, that month comes first. If the current month has one, or the loaded data has none, it opens on the current month as before. ‹ goes back to the current month and stops there. Availability is unchanged: this only chooses the first month shown.
* The starting month is computed in the browser from the availability already loaded (Beds24 returns one year ahead), with no extra request: a value computed in PHP would be frozen in cached page HTML. Until the data arrives the months stay hidden (space kept), so the current month never flashes first.
* New: shortcode attribute startfrom — "available" (default) or "current" (the behaviour before 1.9.0, ‹ not limited). An explicit startmonth/startyear still opens on that month. Elementor: Display → « Open On »; the Start Month option "Current month" is now "Not set (see Open On)".
* New: a hint line under the months, « Click an arrival date to book », for touch screens where the tooltip never shows. Printed by PHP through gettext so TranslatePress can translate it; class .bac-hint, custom properties --bac-hint-size, --bac-hint-color, --bac-hint-align.
* New: languages/ with the .pot template and a fr_FR translation (« Cliquez sur une date d'arrivée pour réserver », « Calendrier de disponibilité »); the text domain is now loaded from the plugin.
* Dev: tests for the first-arrival computation (npm test, node:test), run in CI; tests/ is not shipped.

= 1.8.0 =
* New: an available day opens the site's own booking page by default: home_url( '/booking/' ) with ?arrivee=<date>&room_id=<roomid> (room_id only for a room calendar), in the same tab. The page language comes from WordPress: TranslatePress adds its language prefix to home_url() (/en/booking/), so no language logic is added here.
* New: shortcode attribute beds24_booking="true" (Elementor: « Book on Beds24 ») keeps the Beds24 booking page link (checkin, roomid, propid, lang; new tab) — the only behaviour before 1.8.0. Default false.

= 1.7.1 =
* Fix: CI warnings — GitHub Actions moved to their Node 24 releases (checkout v7, cache v6, setup-node v7, softprops/action-gh-release v3.0.3 pinned by commit SHA); runners pinned to ubuntu-24.04 instead of ubuntu-latest (which moves to Ubuntu 26 from 19 Oct 2026). No change to the plugin code.

= 1.7.0 =
* Fix: language auto-detection now uses TranslatePress's $TRP_LANGUAGE global. trp_get_current_language()/trp_get_language() do not exist in TranslatePress 3.x, so the calendar always fell back to the site locale on translated pages
* New: Beds24 booking links (beds24.com/booking*.php) anywhere on the page get their `lang` parameter set to the current page language, replacing an existing value or appending it. Front-end HTML only. Disable with add_filter( 'bac_rewrite_booking_links', '__return_false' )

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

= 1.8.0 =
Available days now open the site's /booking/ page (?arrivee=…&room_id=…) instead of Beds24's booking page. Add beds24_booking="true" to a calendar (Elementor: « Book on Beds24 ») to keep the Beds24 link.

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
