<?php
/**
 * Permanent redirects for renamed addresses.
 *
 * An address names its subject, never a verse number, a count or a year
 * (the owner's rule of 2026-11-03). When the manifest renames an article or
 * a page, its entry keeps the old slug as `was_slug`; the importer renames the
 * post in place and removes WordPress's old-slug note. This file answers the
 * old address with a 301 to the post's current permalink. It reads the
 * manifest, never the old-slug note, so the redirect holds through every later
 * structure sync. It runs only on a 404, so a post that later takes an old
 * address keeps it.
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

/**
 * Every renamed address in a manifest: old slug => where the post now is.
 *
 * @param array $manifest The content manifest.
 * @return array<string,array{type:string,slug:string,parent:string}>
 */
function paulus_renamed_map( $manifest ) {
	$map = array();
	foreach ( array( 'posts' => 'post', 'journal' => 'post', 'pages' => 'page' ) as $list => $type ) {
		foreach ( (array) ( $manifest[ $list ] ?? array() ) as $item ) {
			if ( empty( $item['was_slug'] ) || empty( $item['slug'] ) ) {
				continue;
			}
			$map[ (string) $item['was_slug'] ] = array(
				'type'   => $type,
				'slug'   => (string) $item['slug'],
				'parent' => 'page' === $type ? (string) ( $item['parent'] ?? '' ) : '',
			);
		}
	}
	return $map;
}

/**
 * The renamed post a request path names, or null.
 *
 * A page matches on its whole path (parent and old slug); an article matches
 * on its last segment, since its address carries its part as a prefix.
 *
 * @param string $path     Request path.
 * @param array  $manifest The content manifest.
 * @return array{type:string,slug:string,parent:string,path:string}|null
 */
function paulus_renamed_target( $path, $manifest ) {
	$parts = array_values( array_filter( explode( '/', strtolower( trim( (string) $path, '/' ) ) ), 'strlen' ) );
	if ( ! $parts ) {
		return null;
	}
	$old = end( $parts );
	$map = paulus_renamed_map( $manifest );
	if ( ! isset( $map[ $old ] ) ) {
		return null;
	}
	$hit = $map[ $old ];
	if ( 'page' === $hit['type'] ) {
		$want = '' !== $hit['parent'] ? $hit['parent'] . '/' . $old : $old;
		if ( implode( '/', $parts ) !== $want ) {
			return null;
		}
	}
	$hit['path'] = 'page' === $hit['type'] && '' !== $hit['parent'] ? $hit['parent'] . '/' . $hit['slug'] : $hit['slug'];
	return $hit;
}

/**
 * Send the 301 for a renamed address.
 *
 * @return void
 */
function paulus_redirect_renamed() {
	if ( ! is_404() || empty( $_SERVER['REQUEST_URI'] ) || ! function_exists( 'paulus_manifest' ) ) {
		return;
	}
	$uri  = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
	$home = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	if ( '' !== trim( $home, '/' ) && 0 === strpos( $path, $home ) ) {
		$path = substr( $path, strlen( $home ) );
	}
	$hit = paulus_renamed_target( $path, (array) paulus_manifest() );
	if ( ! $hit ) {
		return;
	}
	$post = get_page_by_path( $hit['path'], OBJECT, $hit['type'] );
	if ( ! $post || 'publish' !== $post->post_status ) {
		return; // Target missing: let the 404 stand rather than chain to another.
	}
	$url   = (string) get_permalink( $post );
	$query = (string) wp_parse_url( $uri, PHP_URL_QUERY );
	if ( '' !== $query ) {
		$url .= ( false === strpos( $url, '?' ) ? '?' : '&' ) . $query;
	}
	wp_safe_redirect( $url, 301 );
	exit;
}
add_action( 'template_redirect', 'paulus_redirect_renamed', 5 );
