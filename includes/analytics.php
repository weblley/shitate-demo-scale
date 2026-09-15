<?php
/**
 * Google Analytics 4 for demo sites.
 *
 * All shitate demo sites report into the one GA4 property that the official
 * site uses, so a visitor's path official site → demo → pricing stays one
 * journey. Each site tags its hits with a `site_name` parameter (register it
 * as an event-scoped custom dimension in GA4) so reports can be split per
 * demo, and the modal sends `demo_scale_*` events so the popular type scales
 * can be seen.
 *
 * Settings → shitate demo scale → Analytics: measurement ID, site name,
 * whether to track logged-in editors (off by default, keeps the data clean).
 *
 * @package ShitateDemoScale
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitize a GA4 measurement ID (G-XXXXXXX). Empty string when invalid.
 *
 * @param mixed $id Raw value.
 * @return string
 */
function sds_sanitize_ga_id( $id ) {
	$id = strtoupper( trim( (string) $id ) );
	return preg_match( '/^G-[A-Z0-9]{4,16}$/', $id ) ? $id : '';
}

/**
 * Default site name: the last path segment of the home URL (demo-pro1), or
 * the host when the site lives at the domain root.
 *
 * @return string
 */
function sds_default_site_name() {
	$path = trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
	if ( '' !== $path ) {
		$parts = explode( '/', $path );
		return sanitize_title( end( $parts ) );
	}
	return sanitize_title( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
}

/**
 * The site name sent with every hit.
 *
 * @return string
 */
function sds_site_name() {
	$settings = sds_get_settings();
	$name     = isset( $settings['ga_site_name'] ) ? sanitize_title( $settings['ga_site_name'] ) : '';
	return '' !== $name ? $name : sds_default_site_name();
}

/**
 * Whether GA should load on this request.
 *
 * Front end only; skipped for logged-in users who can edit content unless the
 * setting says otherwise. Independent of the active theme (the demo modal is
 * not), so the numbers stay comparable if a demo temporarily runs another
 * theme. The `sds_analytics_enabled` filter can switch it off per host.
 *
 * @return bool
 */
function sds_analytics_enabled() {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || is_customize_preview() ) {
		return false;
	}
	$settings = sds_get_settings();
	if ( '' === sds_sanitize_ga_id( $settings['ga_id'] ) ) {
		return false;
	}
	if ( empty( $settings['ga_track_admins'] ) && is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
		return false;
	}
	return (bool) apply_filters( 'sds_analytics_enabled', true );
}

/**
 * Whether the early gtag('set', { site_name }) snippet should print.
 *
 * Unlike the loader this does not need a measurement ID: another plugin
 * (Rank Math on the demo sites) may be the one loading gtag.js, and the
 * point is to tag ITS events too. Front end only; the editor-tracking switch
 * applies the same way as for the loader. `sds_site_name_snippet_enabled`
 * filter to switch it off.
 *
 * @return bool
 */
function sds_site_name_snippet_enabled() {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || is_customize_preview() ) {
		return false;
	}
	$settings = sds_get_settings();
	if ( empty( $settings['ga_track_admins'] ) && is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
		return false;
	}
	return (bool) apply_filters( 'sds_site_name_snippet_enabled', '' !== sds_site_name() );
}

/**
 * Print gtag('set', { site_name }) at the very top of <head>.
 *
 * gtag('set') applies to every later gtag('config') on the page, so running
 * it at wp_head priority 1 — before Rank Math's tag (and before this plugin's
 * own loader, which prints with the head scripts) — puts site_name on every
 * event, page_view included, whichever plugin loads gtag.js.
 */
function sds_print_site_name_snippet() {
	if ( ! sds_site_name_snippet_enabled() ) {
		return;
	}
	echo '<script id="sds-ga-site-name">'
		. 'window.dataLayer = window.dataLayer || [];'
		. 'function gtag(){dataLayer.push(arguments);}'
		. 'gtag("set", ' . wp_json_encode( array( 'site_name' => sds_site_name() ) ) . ');'
		. "</script>\n";
}
add_action( 'wp_head', 'sds_print_site_name_snippet', 1 );

/**
 * Load gtag.js and configure it with the site name.
 */
function sds_enqueue_analytics() {
	if ( ! sds_analytics_enabled() ) {
		return;
	}
	$settings = sds_get_settings();
	$id       = sds_sanitize_ga_id( $settings['ga_id'] );

	wp_enqueue_script(
		'sds-gtag',
		'https://www.googletagmanager.com/gtag/js?id=' . rawurlencode( $id ),
		array(),
		null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- external, versioned by Google.
		array(
			'in_footer' => false,
			'strategy'  => 'async',
		)
	);
	wp_add_inline_script(
		'sds-gtag',
		'window.dataLayer = window.dataLayer || [];'
		. 'function gtag(){dataLayer.push(arguments);}'
		. 'gtag("js", new Date());'
		. 'gtag("config", ' . wp_json_encode( $id ) . ', ' . wp_json_encode(
			array(
				'anonymize_ip' => true,
				'site_name'    => sds_site_name(),
			)
		) . ');',
		// "before": gtag() only queues into dataLayer, so the config can run
		// ahead of the loader — and WordPress keeps the loader async (an
		// "after" inline script would force it to load synchronously).
		'before'
	);
}
add_action( 'wp_enqueue_scripts', 'sds_enqueue_analytics', 1 );
