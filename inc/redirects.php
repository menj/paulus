<?php
/**
 * Permanent redirects, answered by Rank Math's Redirections module when it
 * is on, and by the theme itself when it is not.
 *
 * Every 301 the theme sends goes through paulus_redirect(). Where the
 * address is a fixed one (a renamed article, a superseded text, an old
 * image, an old part folder in the address) the theme writes it into Rank
 * Math as a rule, once, so the owner sees it under Rank Math, Redirections,
 * and Rank Math answers every later request before WordPress builds the
 * page. Where the address depends on the query (a search from the form,
 * ?author=N) no rule can name it, so Rank Math is asked through its
 * `rank_math/redirection/pre_search` filter instead. Without Rank Math's
 * Redirections module, or without its table, the theme sends the 301 itself,
 * as it always did.
 *
 * Gone addresses (410) are not redirects and never go to Rank Math.
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether Rank Math's Redirections module can take a rule: the module is on,
 * its classes are loaded and its table exists. PAULUS_NO_RM_REDIRECTS in
 * wp-config.php turns the hand-over off.
 *
 * @return bool
 */
function paulus_rm_redirections_ready() {
	static $ready = null;
	if ( null !== $ready ) {
		return $ready;
	}
	$ready = false;
	if ( ( defined( 'PAULUS_NO_RM_REDIRECTS' ) && PAULUS_NO_RM_REDIRECTS )
		|| ! class_exists( '\RankMath\Helper' )
		|| ! class_exists( '\RankMath\Redirections\Redirection' )
		|| ! class_exists( '\RankMath\Redirections\DB' )
		|| ! \RankMath\Helper::is_module_active( 'redirections' ) ) {
		return $ready;
	}
	global $wpdb;
	$table = $wpdb->prefix . 'rank_math_redirections';
	$ready = $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	return $ready;
}

/**
 * Write a fixed redirect into Rank Math, unless it holds a rule for the
 * address already.
 *
 * @param string $source Path of the old address, relative to the home URL, no query.
 * @param string $url    Where it goes.
 * @param int    $code   301 or 308.
 * @return bool Whether Rank Math now holds a rule for it.
 */
function paulus_rm_store_redirect( $source, $url, $code = 301 ) {
	$source = trim( rawurldecode( (string) $source ), '/' );
	if ( '' === $source || ! paulus_rm_redirections_ready() ) {
		return false;
	}
	try {
		if ( \RankMath\Redirections\DB::match_redirections( $source ) ) {
			return true;
		}
		$rule = \RankMath\Redirections\Redirection::from(
			array(
				'sources'     => array( array( 'pattern' => $source, 'comparison' => 'exact' ) ),
				'url_to'      => $url,
				'header_code' => (int) $code,
			)
		);
		if ( $rule->is_infinite_loop() ) {
			return false;
		}
		return (bool) $rule->save();
	} catch ( Throwable $e ) {
		return false;
	}
}

/**
 * Send a permanent redirect and stop.
 *
 * @param string $url    Where to.
 * @param int    $code   Status, 301 by default.
 * @param string $source Path of a fixed old address (no query) to hand to Rank Math as a rule; empty for an address that depends on the query.
 * @return void
 */
function paulus_redirect( $url, $code = 301, $source = '' ) {
	if ( '' !== $source && '' === (string) ( $_SERVER['QUERY_STRING'] ?? '' ) ) {
		paulus_rm_store_redirect( $source, $url, $code );
	}
	wp_safe_redirect( $url, $code );
	exit;
}

/**
 * The 301 that depends on the query, for Rank Math's own pass.
 *
 * @return string The address, or '' when this request has no such redirect.
 */
function paulus_dynamic_redirect_url() {
	if ( is_admin() || wp_doing_ajax() ) {
		return '';
	}
	if ( function_exists( 'paulus_author_redirect_url' ) ) {
		$url = paulus_author_redirect_url();
		if ( '' !== $url ) {
			return $url;
		}
	}
	return function_exists( 'paulus_search_redirect_url' ) ? paulus_search_redirect_url() : '';
}

/**
 * Let Rank Math answer the query-dependent redirects: it runs at "wp",
 * after WordPress has worked out what the request is, and the theme's own
 * template_redirect copies of these redirects then never run.
 *
 * @param array|null $pre      An earlier answer.
 * @param string     $uri      The path asked for.
 * @param string     $full_uri The path and query asked for.
 * @return array|null
 */
function paulus_rm_pre_search( $pre, $uri = '', $full_uri = '' ) {
	if ( null !== $pre || ! paulus_rm_redirections_ready() ) {
		return $pre;
	}
	$url = paulus_dynamic_redirect_url();
	if ( '' === $url ) {
		return $pre;
	}
	return array(
		'url_to'      => $url,
		'header_code' => 301,
		'paulus'      => true,
	);
}
add_filter( 'rank_math/redirection/pre_search', 'paulus_rm_pre_search', 10, 3 );

/**
 * Rank Math adds the visitor's query string to a destination; the theme's
 * own destinations already carry what they need, so it adds none.
 *
 * @param bool  $add     Whether to add it.
 * @param array $matched The rule that matched.
 * @return bool
 */
function paulus_rm_no_query_string( $add, $matched = array() ) {
	return is_array( $matched ) && ! empty( $matched['paulus'] ) ? false : $add;
}
add_filter( 'rank_math/redirection/add_query_string', 'paulus_rm_no_query_string', 10, 2 );

/**
 * The old part folder in an address: /the-charges/justification-by-faith/
 * from before the addresses lost their part. A request that finds nothing
 * and names a part and a published article goes to the article.
 *
 * @return void
 */
function paulus_redirect_old_part_address() {
	if ( ! is_404() || empty( $_SERVER['REQUEST_URI'] ) || ! function_exists( 'paulus_manifest' ) ) {
		return;
	}
	$uri  = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
	$home = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	if ( '/' !== $home && 0 === strpos( $path, $home ) ) {
		$path = '/' . substr( $path, strlen( $home ) );
	}
	$parts = array_values( array_filter( explode( '/', strtolower( trim( $path, '/' ) ) ), 'strlen' ) );
	if ( 2 !== count( $parts ) ) {
		return;
	}
	$slugs = array();
	foreach ( (array) ( paulus_manifest()['parts'] ?? array() ) as $part ) {
		$slugs[] = (string) ( $part['slug'] ?? '' );
	}
	if ( ! in_array( $parts[0], $slugs, true ) ) {
		return;
	}
	$post = get_page_by_path( $parts[1], OBJECT, 'post' );
	if ( ! $post || 'publish' !== $post->post_status ) {
		return;
	}
	$url = (string) get_permalink( $post );
	if ( untrailingslashit( $url ) === untrailingslashit( home_url( $path ) ) ) {
		return;
	}
	$query = (string) wp_parse_url( $uri, PHP_URL_QUERY );
	if ( '' !== $query ) {
		$url .= ( false === strpos( $url, '?' ) ? '?' : '&' ) . $query;
	}
	paulus_redirect( $url, 301, implode( '/', $parts ) );
}
add_action( 'template_redirect', 'paulus_redirect_old_part_address', 5 );

/**
 * The path asked for, relative to the home URL, without the query.
 *
 * @return string
 */
function paulus_request_path() {
	$path = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$home = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	if ( '/' !== $home && 0 === strpos( $path, $home ) ) {
		$path = substr( $path, strlen( $home ) );
	}
	return trim( $path, '/' );
}
