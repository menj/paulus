<?php
/**
 * Clean search addresses: /search/paul/ in place of /?s=paul. A native form
 * of the plugin Pretty Search Permalinks by Angel Costa (GPL v2 or later),
 * reading the plugin's own option, `wpseosearch_base`, so a site that used
 * the plugin keeps its search base. The base is set on Settings, Permalinks.
 *
 * Stands down while the plugin itself is active.
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

if ( function_exists( 'wpseosearch_base' ) ) {
	return;
}

/**
 * The search base: the word in /search/term/.
 *
 * @return string
 */
function paulus_search_base() {
	$base = sanitize_key( (string) get_option( 'wpseosearch_base', 'search' ) );
	return '' !== $base ? $base : 'search';
}

// WordPress builds its search rules and links from this base.
add_action( 'init', static function () {
	global $wp_rewrite;
	if ( $wp_rewrite instanceof WP_Rewrite ) {
		$wp_rewrite->search_base = paulus_search_base();
	}
}, 1 );

/**
 * A search made by the form (/?s=term) goes to its clean address.
 */
add_action( 'template_redirect', static function () {
	global $wp_rewrite;
	if ( ! is_search() || is_admin() || ! $wp_rewrite->using_permalinks() || isset( $_GET['post_type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	$path = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( false !== strpos( $path, '/' . paulus_search_base() . '/' ) ) {
		return;
	}
	$query = get_search_query( false );
	if ( '' === trim( $query ) ) {
		return;
	}
	wp_safe_redirect( get_search_link( $query ), 301 );
	exit;
} );

/**
 * The setting, on Settings, Permalinks, beside WordPress's own bases.
 */
add_action( 'load-options-permalink.php', static function () {
	if ( isset( $_POST['wpseosearch_base'] ) ) {
		check_admin_referer( 'update-permalink' );
		$base = sanitize_key( wp_unslash( $_POST['wpseosearch_base'] ) );
		update_option( 'wpseosearch_base', '' !== $base ? $base : 'search' );
		global $wp_rewrite;
		$wp_rewrite->search_base = paulus_search_base();
	}
	add_settings_field(
		'wpseosearch_base',
		__( 'Search base', 'paulus' ),
		static function () {
			printf( '<input type="text" name="wpseosearch_base" id="wpseosearch_base" value="%s" class="regular-text code"><p class="description">%s</p>', esc_attr( paulus_search_base() ), esc_html( sprintf( /* translators: %s: example address. */ __( 'Searches use clean addresses, such as %s.', 'paulus' ), home_url( '/' . paulus_search_base() . '/paul/' ) ) ) );
		},
		'permalink',
		'optional'
	);
} );
