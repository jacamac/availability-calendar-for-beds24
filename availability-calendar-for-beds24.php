<?php
/**
 * Plugin Name:       Availability Calendar for Beds24
 * Plugin URI:        https://github.com/jacamac/availability-calendar-for-beds24
 * Description:       Displays a Beds24 room or property availability calendar via the [avail_calendar] shortcode and an Elementor widget.
 * Version:           1.10.1
 * Requires at least: 5.9
 * Requires PHP:      7.4
 * Author:            Jacques Leisy
 * Author URI:        https://github.com/jacamac
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       availability-calendar-for-beds24
 * Domain Path:       /languages
 * GitHub Plugin URI: jacamac/availability-calendar-for-beds24
 * Primary Branch:    main
 *
 * @package Availability_Calendar_For_Beds24
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, see https://www.gnu.org/licenses/gpl-2.0.html
 */

defined( 'ABSPATH' ) || exit;

define( 'BAC_VERSION', '1.10.1' );
define( 'BAC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BAC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BAC_DEFAULT_MONTHS_DESKTOP', 3 );
define( 'BAC_DEFAULT_MONTHS_TABLET', 2 );
define( 'BAC_DEFAULT_MONTHS_MOBILE', 1 );

/*
═══════════════════════════════════════════════════════════
	PLUGIN UPDATE CHECKER
	Uses YahnisElsts/plugin-update-checker to surface update
	notifications in the WordPress admin when a new GitHub
	Release is published.

	Default: checks tagged releases on the 'main' branch.
	To track a development branch instead, add to wp-config.php:
		define( 'BAC_UPDATE_BRANCH', 'development' );
	═══════════════════════════════════════════════════════════
*/

$bac_puc = BAC_PLUGIN_DIR . 'vendor/yahnis-elsts/plugin-update-checker/plugin-update-checker.php';
if ( file_exists( $bac_puc ) ) {
	require_once $bac_puc;
	$bac_checker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		'https://github.com/jacamac/availability-calendar-for-beds24/',
		__FILE__,
		'availability-calendar-for-beds24'
	);
	if ( defined( 'BAC_UPDATE_BRANCH' ) && BAC_UPDATE_BRANCH ) {
		$bac_checker->setBranch( BAC_UPDATE_BRANCH );
	} else {
		$bac_checker->getVcsApi()->enableReleaseAssets(); // @phpstan-ignore method.notFound (GitHubApi uses ReleaseAssetSupport trait; base Api class return type hides it)
	}

	add_filter(
		'puc_request_info_result-availability-calendar-for-beds24',
		function ( $info ) {
			if ( null === $info ) {
				return $info;
			}
			if ( defined( 'BAC_UPDATE_BRANCH' ) && BAC_UPDATE_BRANCH ) {
				// Branch mode: PUC's branch zipball is named {repo}-{branch}/ by GitHub,
				// so WordPress would install it as a separate plugin instead of updating.
				// Point to the correctly-named zip built by CI and attached to the
				// rolling dev-latest pre-release on every push to the development branch.
				$info->download_url = 'https://github.com/jacamac/availability-calendar-for-beds24/releases/download/dev-latest/availability-calendar-for-beds24.zip';
			} elseif ( ! empty( $info->download_url ) ) {
				// Release mode: PUC reads the Version header from the source file, which
				// contains the dev placeholder. Re-derive the real version from the
				// release download URL (.../releases/download/v1.2.3/... or .../zipball/v1.2.3).
				if ( preg_match( '~(?:releases/download|zipball)/v?([\d.]+)~', $info->download_url, $m ) ) {
					$info->version = $m[1]; // @phpstan-ignore property.notFound (PUC info object has dynamic properties; $version coexists with $download_url)
				}
			}
			return $info;
		}
	);

	unset( $bac_checker );
}
unset( $bac_puc );

/*
═══════════════════════════════════════════════════════════
	LANGUAGE DETECTION
	Priority:
	1. Explicit lang attribute/setting on the instance
	2. TranslatePress $TRP_LANGUAGE global (current request language)
	3. Legacy TranslatePress functions (absent in TranslatePress 3.x)
	4. WordPress locale
	5. Fallback: 'en'

	TranslatePress stores languages in WordPress locale format
	(e.g. 'fr_FR', 'de_DE') — we normalise to BCP-47 ('fr', 'de').
	═══════════════════════════════════════════════════════════
*/

/**
 * Convert a WordPress locale string to a BCP-47 language tag.
 * Examples: 'fr_FR' → 'fr', 'zh_TW' → 'zh-TW', 'en_US' → 'en'
 *
 * @param string $locale WordPress locale string, e.g. 'fr_FR'.
 * @return string BCP-47 language tag.
 */
function bac_locale_to_bcp47( string $locale ): string {
	// Replace underscore with hyphen.
	$bcp47 = str_replace( '_', '-', $locale );

	// Split into parts: language[-script][-region].
	$parts = explode( '-', $bcp47 );

	// Keep only language if region is redundant (e.g. fr-FR → fr, en-US → en).
	// Keep region when it carries meaning (e.g. zh-TW, zh-CN, pt-BR).
	$meaningful_regions = array( 'zh-TW', 'zh-CN', 'zh-HK', 'pt-BR', 'pt-PT', 'sr-Latn' );
	if ( count( $parts ) > 1 && ! in_array( $bcp47, $meaningful_regions, true ) ) {
		return strtolower( $parts[0] );
	}

	return $bcp47;
}

/**
 * Detect the current locale from TranslatePress, falling back to
 * the WordPress locale and then 'en'.
 *
 * @return string WordPress locale, e.g. 'en_GB'.
 */
function bac_detect_wp_locale(): string {

	// TranslatePress: current language of the request (WP locale format, e.g. de_DE).
	// phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase -- third-party global.
	global $TRP_LANGUAGE;
	if ( ! empty( $TRP_LANGUAGE ) && is_string( $TRP_LANGUAGE ) ) {
		return $TRP_LANGUAGE;
	}
	// phpcs:enable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase

	// Legacy TranslatePress API — returns visitor's actively selected language (WP locale format).
	if ( function_exists( 'trp_get_current_language' ) ) {
		$trp_lang = trp_get_current_language();
		if ( ! empty( $trp_lang ) ) {
			return $trp_lang;
		}
	}

	// Older TranslatePress API.
	if ( function_exists( 'trp_get_language' ) ) {
		$trp_lang = trp_get_language();
		if ( ! empty( $trp_lang ) ) {
			return $trp_lang;
		}
	}

	// WordPress locale — TranslatePress already set this to the visitor's
	// chosen language, so this is a safe fallback even without TranslatePress.
	$wp_locale = get_locale();
	if ( ! empty( $wp_locale ) ) {
		return $wp_locale;
	}

	return 'en';
}

/**
 * Detect the current language from TranslatePress, falling back to
 * the WordPress locale and then 'en'.
 *
 * @return string BCP-47 language tag.
 */
function bac_detect_language(): string {
	return bac_locale_to_bcp47( bac_detect_wp_locale() );
}

/**
 * Validate and sanitise a BCP-47 language tag supplied by the user.
 * Returns the sanitised tag or null if invalid.
 *
 * @param string $lang BCP-47 language tag to validate.
 * @return string|null Sanitised tag or null if invalid.
 */
function bac_sanitize_lang( string $lang ): ?string {
	$lang = trim( sanitize_text_field( $lang ) );
	return preg_match( '/^[a-z]{2,3}(-[A-Za-z]{2,4})*$/i', $lang ) ? $lang : null;
}

/**
 * Resolve the final language for an instance:
 *   - Use explicit value if provided and valid
 *   - Otherwise auto-detect from TranslatePress / WP locale
 *
 * @param string $explicit Explicit language override.
 * @return string Resolved BCP-47 language tag.
 */
function bac_resolve_lang( string $explicit = '' ): string {
	if ( ! empty( $explicit ) ) {
		$sanitised = bac_sanitize_lang( $explicit );
		if ( $sanitised ) {
			return $sanitised;
		}
	}
	return bac_detect_language();
}

/**
 * Resolve the locale the calendar formats with (Intl, 1.10.1): the explicit
 * language when valid, else the detected locale with its region (en_GB →
 * en-GB), which decides the first day of the week. Falls back to the language.
 *
 * @param string $explicit Explicit language override.
 * @return string BCP-47 locale tag.
 */
function bac_resolve_locale( string $explicit = '' ): string {
	if ( ! empty( $explicit ) ) {
		$sanitised = bac_sanitize_lang( $explicit );
		if ( $sanitised ) {
			return $sanitised;
		}
	}
	$locale = bac_sanitize_lang( str_replace( '_', '-', bac_detect_wp_locale() ) );
	return $locale ? $locale : bac_detect_language();
}

require_once BAC_PLUGIN_DIR . 'includes/booking-links.php';

add_action( 'init', 'bac_load_textdomain' );

/**
 * Load the plugin's translations from languages/ (fr_FR ships with the plugin).
 * Strings can still be overridden per language in TranslatePress.
 */
function bac_load_textdomain(): void {
	load_plugin_textdomain( 'availability-calendar-for-beds24', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

/*
═══════════════════════════════════════════════════════════
	ASSET REGISTRATION & ENQUEUING
	Registration runs on 'init' so handles exist in all contexts
	(front-end, admin, Elementor editor) before any enqueue call.
	Actual enqueuing is triggered by shortcode/widget or editor hook.
	═══════════════════════════════════════════════════════════
*/

add_action( 'init', 'bac_register_assets' );

/**
 * Register the plugin's CSS and JS handles.
 */
function bac_register_assets(): void {
	wp_register_style(
		'bac-calendar',
		BAC_PLUGIN_URL . 'assets/calendar.css',
		array(),
		BAC_VERSION
	);

	wp_register_script(
		'bac-calendar',
		BAC_PLUGIN_URL . 'assets/calendar.js',
		array(),
		BAC_VERSION,
		true    // load in footer.
	);
}

/*
═══════════════════════════════════════════════════════════
	SHARED INSTANCE REGISTRY
	Both the shortcode handler and the Elementor widget push
	their config here. A single wp_footer script initialises
	all instances at once.
	═══════════════════════════════════════════════════════════
*/

$bac_instances = array();

/**
 * Register a calendar instance and ensure assets are enqueued.
 * Returns the container div HTML.
 *
 * @param array<string, mixed>  $config      Validated config array for AvailCalendar JS class.
 * @param array<string, string> $extra_attrs Optional extra HTML attributes for the wrapper div.
 * @return string Container div HTML.
 */
function bac_register_instance( array $config, array $extra_attrs = array() ): string {
	global $bac_instances;

	$instance_id           = 'bac-' . wp_unique_id();
	$config['containerId'] = $instance_id;
	$bac_instances[]       = $config;

	wp_enqueue_style( 'bac-calendar' );
	wp_enqueue_script( 'bac-calendar' );

	$attrs_html = '';
	foreach ( $extra_attrs as $attr_name => $attr_value ) {
		$attrs_html .= sprintf( ' %s="%s"', esc_attr( $attr_name ), esc_attr( $attr_value ) );
	}

	// 1.9.0: a visible hint, for touch screens where the tiles' title tooltip never shows. Static markup
	// (gettext) rather than JS text, so TranslatePress can translate it; calendar.js moves it below the months.
	// Every bookable day is a link (site or Beds24 booking page), so the hint is always shown.
	// 1.10.1: data-no-dynamic-translation keeps TranslatePress's browser-side lookup away from the months, which
	// calendar.js renders already localised. Server-side translation of the hint and aria-label is unaffected.
	return sprintf(
		'<div id="%s" class="bac-wrapper"%s aria-label="%s" data-no-dynamic-translation><p class="bac-hint">%s</p></div>',
		esc_attr( $instance_id ),
		$attrs_html,
		esc_attr__( 'Availability calendar', 'availability-calendar-for-beds24' ),
		esc_html__( 'Click an arrival date to book', 'availability-calendar-for-beds24' )
	);
}

/*
═══════════════════════════════════════════════════════════
	SHARED ATTRIBUTE SANITISER
	Used by both shortcode and Elementor widget so validation
	logic is never duplicated.
	═══════════════════════════════════════════════════════════
*/

/**
 * Sanitise raw attribute values into a clean config array.
 *
 * @param array<string, mixed> $raw  Associative array with keys:
 *                                   roomid, propid, nummonths, startmonth, startyear, startfrom, lang.
 * @return array<string, mixed> Clean config array for AvailCalendar JS class.
 */
function bac_sanitize_config( array $raw ): array {
	$config = array();

	$roomid = absint( $raw['roomid'] ?? 0 );
	$propid = absint( $raw['propid'] ?? 0 );
	if ( $roomid ) {
		$config['roomid'] = $roomid;
	}
	if ( $propid ) {
		$config['propid'] = $propid;
	}

	$raw_months          = absint( $raw['nummonths'] ?? BAC_DEFAULT_MONTHS_DESKTOP );
	$nummonths           = max( 1, min( 12, $raw_months ? $raw_months : BAC_DEFAULT_MONTHS_DESKTOP ) );
	$config['numMonths'] = $nummonths;

	// Always include tablet/mobile counts so the JS resize listener is always
	// wired up. Fall back to the desktop value when not explicitly set.
	$tablet_months             = absint( $raw['nummonthstablet'] ?? 0 );
	$config['numMonthsTablet'] = $tablet_months >= 1 ? min( 12, $tablet_months ) : $nummonths;
	$mobile_months             = absint( $raw['nummonthsmobile'] ?? 0 );
	$config['numMonthsMobile'] = $mobile_months >= 1 ? min( 12, $mobile_months ) : $nummonths;

	$startmonth = absint( $raw['startmonth'] ?? 0 );
	if ( $startmonth >= 1 && $startmonth <= 12 ) {
		$config['startMonth'] = $startmonth;
	}

	$startyear = absint( $raw['startyear'] ?? 0 );
	if ( $startyear >= 2020 && $startyear <= 2100 ) {
		$config['startYear'] = $startyear;
	}

	// Which month the calendar opens on (1.9.0), decided in the browser from the loaded availability
	// (the page HTML may be cached): 'available' (default) = month of the first possible arrival,
	// 'current' = current month, as before 1.9.0. An explicit startmonth/startyear takes precedence.
	$config['startFrom'] = 'current' === sanitize_key( $raw['startfrom'] ?? '' ) ? 'current' : 'available';

	$config['lang']   = bac_resolve_lang( $raw['lang'] ?? '' );
	$config['locale'] = bac_resolve_locale( $raw['lang'] ?? '' );

	// Where a bookable day leads (1.8.0): the site's booking page /booking/ by default, in the page's language —
	// TranslatePress adds its language prefix to home_url() (/en/booking/) — with ?arrivee=<date>&room_id=<roomid>;
	// beds24_booking="true": Beds24's booking page, as before 1.8.0.
	$config['beds24Booking'] = in_array( strtolower( trim( (string) ( $raw['beds24_booking'] ?? '' ) ) ), array( '1', 'true', 'yes', 'on' ), true );
	if ( ! $config['beds24Booking'] ) {
		$config['bookingUrl'] = esc_url_raw( home_url( '/booking/' ) );
	}

	$allowed_schemes  = array( 'solid', 'minimal', 'outline', 'soft', 'dot', 'custom' );
	$scheme           = sanitize_key( $raw['scheme'] ?? 'solid' );
	$config['scheme'] = in_array( $scheme, $allowed_schemes, true ) ? $scheme : 'solid';

	if ( 'custom' === $config['scheme'] ) {
		$allowed_shapes   = array( 'rounded', 'square', 'circle' );
		$allowed_avails   = array( 'fill', 'outline', 'ghost', 'dot', 'plain' );
		$allowed_unavails = array( 'fill', 'outline', 'ghost', 'dot', 'strikethrough', 'dim', 'hidden' );
		$allowed_pasts    = array( 'muted', 'unavailable', 'hidden' );
		$allowed_hovers   = array( 'brighten', 'fill', 'outline', 'scale', 'lift' );
		$allowed_todays   = array( 'ring-rect', 'ring-circle', 'bold', 'none' );
		$allowed_navs     = array( 'top-left', 'top-right', 'split' );

		$v = sanitize_key( $raw['scheme_shape'] ?? '' );
		if ( in_array( $v, $allowed_shapes, true ) ) {
			$config['schemeShape'] = $v;
		}
		$v = sanitize_key( $raw['scheme_avail'] ?? '' );
		if ( in_array( $v, $allowed_avails, true ) ) {
			$config['schemeAvail'] = $v;
		}
		$v = sanitize_key( $raw['scheme_unavail'] ?? '' );
		if ( in_array( $v, $allowed_unavails, true ) ) {
			$config['schemeUnavail'] = $v;
		}
		$v = sanitize_key( $raw['scheme_past'] ?? '' );
		if ( in_array( $v, $allowed_pasts, true ) ) {
			$config['schemePast'] = $v;
		}
		$v = sanitize_key( $raw['scheme_hover'] ?? '' );
		if ( in_array( $v, $allowed_hovers, true ) ) {
			$config['schemeHover'] = $v;
		}
		$v = sanitize_key( $raw['scheme_today'] ?? '' );
		if ( in_array( $v, $allowed_todays, true ) ) {
			$config['schemeToday'] = $v;
		}
		$v = sanitize_key( $raw['scheme_nav'] ?? '' );
		if ( in_array( $v, $allowed_navs, true ) ) {
			$config['schemeNav'] = $v;
		}
	}

	return $config;
}

/*
═══════════════════════════════════════════════════════════
	SHORTCODE  [avail_calendar]

	Attributes:
	roomid          — Beds24 room ID  (roomid OR propid, not both)
	propid          — Beds24 property ID
	nummonths       — months on desktop (1-12, default 5)
	nummonthstablet — months on tablet, 768-1024 px (1-12, default: nummonths)
	nummonthsmobile — months on mobile, < 768 px (1-12, default: nummonths)
	startmonth      — starting month 1-12 (default: see startfrom)
	startyear       — starting year (default: see startfrom)
	startfrom       — 'available' (default) = month of the first possible arrival;
					'current' = current month. Ignored when startmonth or startyear is set
	lang            — BCP-47 locale override, e.g. 'fr'
					Omit to use TranslatePress / WP locale automatically
	beds24_booking  — 'true' = days link to Beds24's booking page; default: the site's /booking/ page

	Examples:
	[avail_calendar roomid="12345" nummonths="5"]
	[avail_calendar propid="67890" nummonths="3" lang="de"]
	═══════════════════════════════════════════════════════════
*/

add_shortcode( 'avail_calendar', 'bac_shortcode' );

/**
 * Render the [avail_calendar] shortcode.
 *
 * @param array<string, string>|string $atts Shortcode attributes.
 * @return string Container div HTML.
 */
function bac_shortcode( $atts ): string {
	$atts = shortcode_atts(
		array(
			'roomid'          => '',
			'propid'          => '',
			'nummonths'       => '5',
			'nummonthstablet' => '',   // empty = same as nummonths.
			'nummonthsmobile' => '',   // empty = same as nummonths.
			'startmonth'      => '',
			'startyear'       => '',
			'startfrom'       => 'available', // 'current' = open on the current month (before 1.9.0: always).
			'lang'            => '',   // empty = auto-detect.
			'beds24_booking'  => 'false', // 'true' = days link to Beds24's booking page (before 1.8.0: always).
			'scheme'          => 'solid',
			'scheme_shape'    => '',
			'scheme_avail'    => '',
			'scheme_unavail'  => '',
			'scheme_past'     => '',
			'scheme_hover'    => '',
			'scheme_today'    => '',
			'scheme_nav'      => '',
		),
		$atts,
		'avail_calendar'
	);

	$config = bac_sanitize_config( $atts );
	return bac_register_instance( $config );
}

/*
═══════════════════════════════════════════════════════════
	DEFERRED INIT SCRIPT
	wp_footer priority 20 — after all scripts are printed.
	Emits one <script> block that boots every registered instance.
	═══════════════════════════════════════════════════════════
*/

add_action( 'wp_footer', 'bac_footer_init', 20 );

/**
 * Emit the inline script that boots all registered calendar instances.
 */
function bac_footer_init(): void {
	global $bac_instances;
	if ( empty( $bac_instances ) ) {
		return;
	}

	/*
	 * Safety rationale for the phpcs:ignore below:
	 * $bac_instances is built entirely from bac_sanitize_config() output:
	 *   - roomid/propid/nummonths/startmonth/startyear are cast via absint()
	 *   - lang and locale are validated against a strict BCP-47 regex
	 *   - startFrom is one of two literals
	 *   - beds24Booking is a boolean; bookingUrl is home_url( '/booking/' ) through esc_url_raw()
	 * wp_json_encode() then serialises the array to safe JSON with all
	 * special characters escaped. The result cannot contain executable code.
	 */
	$json = wp_json_encode( $bac_instances );

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- see rationale above
	echo "\n<script>\n";
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode output is safe
	echo "(function(){\n  var instances = {$json};\n  instances.forEach(function(cfg){\n    if(typeof AvailCalendar !== 'undefined'){\n      new AvailCalendar(cfg);\n    }\n  });\n})();\n";
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static string literal
	echo "</script>\n";
}

/*
═══════════════════════════════════════════════════════════
	ELEMENTOR WIDGET
	Loaded only when Elementor is active, after it is ready.
	═══════════════════════════════════════════════════════════
*/

add_action( 'elementor/widgets/register', 'bac_register_elementor_widget' );

/**
 * Register the Elementor widget when the Elementor widgets manager is ready.
 *
 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager instance.
 */
function bac_register_elementor_widget( $widgets_manager ): void {
	// Guard: Elementor Widget_Base must exist.
	if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
		return;
	}

	require_once BAC_PLUGIN_DIR . 'widgets/class-avail-calendar-widget.php';
	$widgets_manager->register( new \BAC\Avail_Calendar_Widget() );
}

// Enqueue assets in the Elementor editor so the live preview works.
// Handles are registered on 'init' above, so they exist in all contexts
// including the editor — no need to repeat URL or version.
add_action(
	'elementor/editor/before_enqueue_scripts',
	function () {
		wp_enqueue_style( 'bac-calendar' );
		wp_enqueue_script( 'bac-calendar' );
	}
);

// Load the live-preview JS handler inside the Elementor preview iframe.
// This script initialises AvailCalendar in demo mode and re-initialises it
// whenever widget settings change, giving instant feedback in the editor.
add_action(
	'elementor/preview/enqueue_scripts',
	function () {
		wp_enqueue_style( 'bac-calendar' );
		wp_enqueue_script( 'bac-calendar' );
		wp_enqueue_script(
			'bac-elementor-preview',
			BAC_PLUGIN_URL . 'assets/elementor-preview.js',
			array( 'elementor-frontend', 'bac-calendar' ),
			BAC_VERSION,
			true
		);
	}
);
