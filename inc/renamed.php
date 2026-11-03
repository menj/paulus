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
 * @return array<string,array{type:string,slug:string,parent:string,base:string}>
 */
function paulus_renamed_map( $manifest ) {
	$map = array();
	foreach ( array( 'posts' => 'post', 'journal' => 'paulus_journal', 'pages' => 'page' ) as $list => $type ) {
		foreach ( (array) ( $manifest[ $list ] ?? array() ) as $item ) {
			if ( empty( $item['was_slug'] ) || empty( $item['slug'] ) ) {
				continue;
			}
			$base = '';
			if ( 'page' === $type ) {
				$base = (string) ( $item['parent'] ?? '' );
			} elseif ( 'post' === $type ) {
				$base = (string) ( $item['part'] ?? '' );
			} elseif ( preg_match( '/^(\d{4})-(\d{2})/', (string) ( $item['date'] ?? '' ), $m ) ) {
				$base = 'journal/' . $m[1] . '/' . $m[2];
			}
			$map[ (string) $item['was_slug'] ] = array(
				'type'   => $type,
				'slug'   => (string) $item['slug'],
				'parent' => 'page' === $type ? $base : '',
				'base'   => $base,
			);
		}
	}
	return $map;
}

/**
 * The renamed post a request path names, or null.
 *
 * Every type matches on its whole old path, never on the last segment alone,
 * so an unrelated 404 that happens to end in an old slug stays a 404. A page's
 * old path is its parent and old slug. An article's is the post permalink
 * structure with its part as %category%; a structure carrying any other tag
 * matches nothing. A Journal entry's is journal/<year>/<month>/<old slug>.
 *
 * @param string $path      Request path.
 * @param array  $manifest  The content manifest.
 * @param string $structure Post permalink structure.
 * @return array{type:string,slug:string,parent:string,base:string,path:string}|null
 */
function paulus_renamed_target( $path, $manifest, $structure = '/%category%/%postname%/' ) {
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
	if ( 'post' === $hit['type'] ) {
		$want = str_replace( array( '%category%', '%postname%' ), array( $hit['base'], $old ), trim( (string) $structure, '/' ) );
		if ( false !== strpos( $want, '%' ) || '' === $hit['base'] && false !== strpos( (string) $structure, '%category%' ) ) {
			return null;
		}
	} else {
		$want = '' !== $hit['base'] ? $hit['base'] . '/' . $old : $old;
	}
	if ( implode( '/', $parts ) !== trim( $want, '/' ) ) {
		return null;
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
	$hit = paulus_renamed_target( $path, (array) paulus_manifest(), (string) get_option( 'permalink_structure' ) );
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
