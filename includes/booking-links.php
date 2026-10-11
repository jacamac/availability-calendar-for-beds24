<?php
/**
 * Booking links: align the `lang` parameter of every Beds24 booking link
 * (beds24.com/booking*.php) with the current page language.
 *
 * Works on the final HTML, so it covers links from any source
 * (page builder header, content, widgets). Disable with:
 *     add_filter( 'bac_rewrite_booking_links', '__return_false' );
 *
 * @package AvailabilityCalendarForBeds24
 */

defined( 'ABSPATH' ) || exit;

add_action( 'template_redirect', 'bac_booking_links_start_buffer', 1 );

/**
 * Start an output buffer on front-end HTML requests only.
 */
function bac_booking_links_start_buffer(): void {
	if ( is_admin() || wp_doing_ajax() || wp_is_json_request() || is_feed() || is_robots() ) {
		return;
	}
	if ( ! apply_filters( 'bac_rewrite_booking_links', true ) ) {
		return;
	}
	ob_start( 'bac_booking_links_rewrite_html' );
}

/**
 * Rewrite the lang parameter of Beds24 booking URLs in the page HTML.
 *
 * @param string $html Page HTML.
 * @return string
 */
function bac_booking_links_rewrite_html( string $html ): string {
	if ( false === stripos( $html, 'beds24.com/booking' ) ) {
		return $html;
	}

	// Two-letter code for Beds24 (de_DE -> de, en_GB -> en).
	$lang = strtolower( substr( bac_detect_language(), 0, 2 ) );
	if ( ! preg_match( '/^[a-z]{2}$/', $lang ) ) {
		return $html;
	}

	return preg_replace_callback(
		'#https?://(?:www\.)?beds24\.com/booking[^"\'\s<>]*#i',
		static function ( array $m ) use ( $lang ): string {
			$url = $m[0];
			// Query separators may be &, &amp; or &#038; in HTML.
			if ( preg_match( '/([?&]|&amp;|&#038;)lang=[a-z]{2}(?:-[a-z]{2})?/i', $url ) ) {
				return preg_replace( '/(([?&]|&amp;|&#038;)lang=)[a-z]{2}(?:-[a-z]{2})?/i', '${1}' . $lang, $url );
			}
			$sep = ( false === strpos( $url, '?' ) ) ? '?' : '&amp;';
			return $url . $sep . 'lang=' . $lang;
		},
		$html
	);
}
