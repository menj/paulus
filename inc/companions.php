<?php
/**
 * Companion sites: the author's other sites on the same ground, Best of
 * Islam (bestofislam.org) and The Rightly-Guided Injil (injil.help), linked
 * from the places a reader looks for more: a "Further reading" band after
 * an article, an entry or a page, pointing at the pages there that take
 * the same question further; a line in the footer brand block; a line in
 * the author card; a group on the Sitemap page; and the structured data
 * (the author's sameAs, a page's relatedLink). Set on Theme Options,
 * Companions: the sites, one per line, and the page-by-page links.
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

/**
 * The companion sites: name, address and a line about each, from the
 * option, one site per line as "Name | URL | description".
 *
 * @return array<int,array{name:string,url:string,desc:string,host:string}>
 */
function paulus_companion_sites() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$out = array();
	foreach ( preg_split( '/\R/u', (string) paulus_option( 'companion_sites' ) ) as $line ) {
		$parts = array_map( 'trim', explode( '|', $line, 3 ) );
		if ( count( $parts ) < 2 || '' === $parts[0] || ! preg_match( '#^https?://#i', $parts[1] ) ) {
			continue;
		}
		$host  = preg_replace( '#^www\.#', '', strtolower( (string) wp_parse_url( $parts[1], PHP_URL_HOST ) ) );
		$out[] = array( 'name' => $parts[0], 'url' => $parts[1], 'desc' => $parts[2] ?? '', 'host' => $host );
	}
	return $cache = $out;
}

/**
 * The name of the companion site an address belongs to, or its host.
 *
 * @param string $url Address.
 * @return string
 */
function paulus_companion_site_name( $url ) {
	$host = preg_replace( '#^www\.#', '', strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) ) );
	foreach ( paulus_companion_sites() as $site ) {
		if ( $site['host'] === $host ) {
			return $site['name'];
		}
	}
	return $host;
}

/**
 * The page-by-page links, from the option: "key | Label | URL" per line,
 * where the key is an article, entry or page slug, "section:<slug>" for
 * every article of a section, or "*" for any page without links of its
 * own. Grouped by key, in the order given.
 *
 * @return array<string,array<int,array{label:string,url:string}>>
 */
function paulus_companion_links() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$out = array();
	foreach ( preg_split( '/\R/u', (string) paulus_option( 'companion_links' ) ) as $line ) {
		$parts = array_map( 'trim', explode( '|', $line, 3 ) );
		if ( count( $parts ) < 3 || '' === $parts[0] || '' === $parts[1] || ! preg_match( '#^https?://#i', $parts[2] ) ) {
			continue;
		}
		$out[ $parts[0] ][] = array( 'label' => $parts[1], 'url' => $parts[2] );
	}
	return $cache = $out;
}

/**
 * The companion links for a post: its own, else its section's, else the
 * general ones. At most three, each address once.
 *
 * @param int|WP_Post|null $post Post.
 * @return array<int,array{label:string,url:string}>
 */
function paulus_companion_links_for( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return array();
	}
	$all  = paulus_companion_links();
	$keys = array( $post->post_name );
	if ( 'post' === $post->post_type && function_exists( 'paulus_part_of' ) ) {
		$part = paulus_part_of( $post->ID );
		if ( $part ) {
			$keys[] = 'section:' . $part->slug;
		}
	}
	// The general links stand in for an article or an entry without links of
	// its own; a page (the legal pages, the sitemap) shows only its own.
	if ( in_array( $post->post_type, array( 'post', 'paulus_journal' ), true ) ) {
		$keys[] = '*';
	}
	$links = array();
	$seen  = array();
	foreach ( $keys as $key ) {
		foreach ( $all[ $key ] ?? array() as $link ) {
			if ( isset( $seen[ $link['url'] ] ) ) {
				continue;
			}
			$seen[ $link['url'] ] = true;
			$links[]              = $link;
		}
		if ( $links ) {
			break;
		}
	}
	return array_slice( $links, 0, 3 );
}

/**
 * The "Further reading" band after an article, an entry or a page.
 *
 * @return string
 */
function paulus_sc_companions() {
	if ( ! paulus_option( 'companion_reading' ) || ! is_singular() || is_front_page() ) {
		return '';
	}
	$links = paulus_companion_links_for( get_queried_object_id() );
	if ( ! $links ) {
		return '';
	}
	$items = '';
	foreach ( $links as $link ) {
		/* translators: %s: name of the companion site. */
		$where  = sprintf( __( 'on %s', 'paulus' ), paulus_companion_site_name( $link['url'] ) );
		$items .= '<li><a href="' . esc_url( $link['url'] ) . '" rel="noopener">' . esc_html( $link['label'] ) . '</a> <span>' . esc_html( $where ) . '</span></li>';
	}
	return '<section class="paulus-related paulus-companions" aria-label="' . esc_attr__( 'Further reading on the companion sites', 'paulus' ) . '"><h2 class="paulus-related__label">' . esc_html__( 'Further reading elsewhere', 'paulus' ) . '</h2><ul>' . $items . '</ul></section>';
}
add_shortcode( 'paulus_companions', 'paulus_sc_companions' );

/**
 * The sites as a sentence of links: "Best of Islam and The Rightly-Guided
 * Injil", for the footer and the author card.
 *
 * @return string HTML, or '' with no sites.
 */
function paulus_companion_list_html() {
	$links = array();
	foreach ( paulus_companion_sites() as $site ) {
		$links[] = '<a href="' . esc_url( $site['url'] ) . '" rel="noopener"' . ( '' !== $site['desc'] ? ' title="' . esc_attr( $site['desc'] ) . '"' : '' ) . '>' . esc_html( $site['name'] ) . '</a>';
	}
	if ( ! $links ) {
		return '';
	}
	$last = array_pop( $links );
	/* translators: 1: the sites but the last, separated by commas; 2: the last site. */
	return $links ? sprintf( __( '%1$s and %2$s', 'paulus' ), implode( ', ', $links ), $last ) : $last;
}

/**
 * The line in the footer brand block.
 *
 * @return string
 */
function paulus_companion_footer_html() {
	if ( ! paulus_option( 'companion_footer' ) ) {
		return '';
	}
	$list = paulus_companion_list_html();
	if ( '' === $list ) {
		return '';
	}
	/* translators: %s: the companion sites, linked. */
	return '<p class="paulus-footer-brand__companions">' . sprintf( esc_html__( 'Companion sites: %s.', 'paulus' ), $list ) . '</p>';
}

/**
 * The line in the author card.
 *
 * @return string
 */
function paulus_companion_author_html() {
	if ( ! paulus_option( 'companion_author' ) ) {
		return '';
	}
	$list = paulus_companion_list_html();
	if ( '' === $list ) {
		return '';
	}
	/* translators: %s: the companion sites, linked. */
	return '<p class="paulus-author__sites">' . sprintf( esc_html__( 'Also by the author: %s.', 'paulus' ), $list ) . '</p>';
}

/**
 * The group on the Sitemap page: each site, with its line.
 *
 * @return string HTML of the list items, or ''.
 */
function paulus_companion_sitemap_items() {
	$items = array();
	foreach ( paulus_companion_sites() as $site ) {
		$items[] = '<a href="' . esc_url( $site['url'] ) . '" rel="noopener">' . esc_html( $site['name'] ) . '</a>' . ( '' !== $site['desc'] ? ' <span class="paulus-sm__desc">' . esc_html( $site['desc'] ) . '</span>' : '' );
	}
	return $items;
}

/**
 * The author's sameAs takes the companion sites: they are the author's own.
 *
 * @param array $person Person node.
 * @return array
 */
function paulus_companion_person_schema( $person ) {
	$same = isset( $person['sameAs'] ) ? (array) $person['sameAs'] : array();
	foreach ( paulus_companion_sites() as $site ) {
		$same[] = $site['url'];
	}
	if ( $same ) {
		$person['sameAs'] = array_values( array_unique( $same ) );
	}
	return $person;
}
add_filter( 'paulus_person_schema', 'paulus_companion_person_schema' );

/**
 * A page node names the companion pages it points to as relatedLink.
 *
 * @param array $node WebPage node.
 * @return array
 */
function paulus_companion_webpage_schema( $node ) {
	if ( ! is_singular() || is_front_page() || ! paulus_option( 'companion_reading' ) ) {
		return $node;
	}
	$urls = wp_list_pluck( paulus_companion_links_for( get_queried_object_id() ), 'url' );
	if ( $urls && empty( $node['relatedLink'] ) ) {
		$node['relatedLink'] = array_values( $urls );
	}
	return $node;
}
add_filter( 'paulus_webpage_schema', 'paulus_companion_webpage_schema' );
