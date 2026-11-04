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
 * The marker stored once the rewrite rules carry the search base: the theme
 * version and the base. The rules are rebuilt once when either changes (an
 * update, a new base, the option imported from the plugin), never on an
 * ordinary load. This replaces the 2.75 check, which read every saved rule on
 * each admin page load.
 *
 * @param string $version Theme version.
 * @param string $base    Search base.
 * @return string
 */
function paulus_search_rewrite_stamp( $version, $base ) {
	return $version . '|' . $base;
}

/**
 * Whether the saved rewrite rules hold a rule for the search base.
 *
 * @param string $base Search base.
 * @return bool
 */
function paulus_search_rules_ready( $base ) {
	$rules = get_option( 'rewrite_rules' );
	if ( ! is_array( $rules ) ) {
		return false;
	}
	foreach ( array_keys( $rules ) as $rule ) {
		if ( 0 === strpos( (string) $rule, $base . '/' ) ) {
			return true;
		}
	}
	return false;
}

add_action( 'init', static function () {
	if ( ! get_option( 'permalink_structure' ) ) {
		return;
	}
	$base  = paulus_search_base();
	$stamp = paulus_search_rewrite_stamp( PAULUS_VERSION, $base );
	if ( get_option( 'paulus_search_rewrite_built' ) === $stamp ) {
		return;
	}
	flush_rewrite_rules( false );
	// The marker is written only once the rules are seen to hold the search
	// rule, so a flush that fails or never finishes is tried again next load.
	if ( paulus_search_rules_ready( $base ) ) {
		update_option( 'paulus_search_rewrite_built', $stamp );
	}
}, 20 );

/**
 * The clean address of a search: /base/term/, then page/N/ from the second
 * page on, then any other query variables. Empty for an empty search, which
 * gets no redirect.
 *
 * @param string $home  Home URL.
 * @param string $base  Search base.
 * @param string $term  Search terms as typed.
 * @param int    $paged Page number.
 * @param array  $args  Other query variables, already sanitised.
 * @return string
 */
function paulus_search_pretty_url( $home, $base, $term, $paged = 1, $args = array() ) {
	$term = trim( (string) $term );
	if ( '' === $term || '' === (string) $base ) {
		return '';
	}
	$url = rtrim( (string) $home, '/' ) . '/' . $base . '/' . rawurlencode( $term ) . '/';
	if ( (int) $paged > 1 ) {
		$url .= 'page/' . (int) $paged . '/';
	}
	if ( $args ) {
		$url .= '?' . http_build_query( $args, '', '&', PHP_QUERY_RFC3986 );
	}
	return $url;
}

/**
 * A search made by the form (/?s=term) goes to its clean address with a 301.
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
	// Every other query variable (an ordering, say) travels with the terms;
	// the page number goes into the path, so ?s=paul&paged=2 lands on
	// /search/paul/page/2/.
	$args = array();
	foreach ( wp_unslash( $_GET ) as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! in_array( $key, array( 's', 'paged' ), true ) && is_scalar( $value ) ) {
			$args[ sanitize_key( $key ) ] = sanitize_text_field( (string) $value );
		}
	}
	$url = paulus_search_pretty_url( home_url( '/' ), paulus_search_base(), get_search_query( false ), (int) get_query_var( 'paged' ), $args );
	if ( '' === $url ) {
		return;
	}
	wp_safe_redirect( $url, 301 );
	exit;
} );

/**
 * The setting, on Settings, Permalinks, beside WordPress's own bases.
 */
add_action( 'load-options-permalink.php', static function () {
	if ( isset( $_POST['wpseosearch_base'] ) && current_user_can( 'manage_options' ) ) {
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
