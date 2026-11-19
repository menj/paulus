<?php
/**
 * Tags: every article and Journal entry carries at most six tags, all in
 * lower case, tied to the glossary and linked both ways.
 *
 * The tags are listed in content/tags.json (`tags`: slug to list; `glossary`:
 * tag to the glossary term it explains). They are assigned once, and again
 * only while an item still holds the tags the theme last gave it, so an
 * editor's own tags are never replaced. A tag that matches a glossary term
 * shows that definition on its archive with a link to the term, and the
 * term in the glossary links back to the archive.
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'PAULUS_MAX_TAGS' ) ) {
	define( 'PAULUS_MAX_TAGS', 6 );
}

/**
 * The tag data shipped with the theme.
 *
 * @return array{tags: array<string, string[]>, glossary: array<string, string>}
 */
function paulus_tags_data() {
	static $data = null;
	if ( null === $data ) {
		$file = PAULUS_DIR . '/content/tags.json';
		$json = is_readable( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : null; // phpcs:ignore WordPress.WP.AlternativeFunctions
		$data = array(
			'tags'     => is_array( $json['tags'] ?? null ) ? $json['tags'] : array(),
			'glossary' => is_array( $json['glossary'] ?? null ) ? $json['glossary'] : array(),
		);
	}
	return $data;
}

/**
 * A tag name in lower case.
 *
 * @param string $name Name.
 * @return string
 */
function paulus_tag_lower( $name ) {
	return function_exists( 'mb_strtolower' ) ? mb_strtolower( trim( (string) $name ), 'UTF-8' ) : strtolower( trim( (string) $name ) );
}

/** New and renamed tags are lower case. */
function paulus_tags_lower_new( $term, $taxonomy ) {
	return ( 'post_tag' === $taxonomy && is_string( $term ) ) ? paulus_tag_lower( $term ) : $term;
}
add_filter( 'pre_insert_term', 'paulus_tags_lower_new', 10, 2 );

/** A renamed tag is lower case too. */
function paulus_tags_lower_update( $data, $term_id, $taxonomy ) {
	if ( 'post_tag' === $taxonomy && isset( $data['name'] ) ) {
		$data['name'] = paulus_tag_lower( $data['name'] );
	}
	return $data;
}
add_filter( 'wp_update_term_data', 'paulus_tags_lower_update', 10, 3 );

/** Tags may be added to Journal entries. */
function paulus_tags_journal() {
	if ( post_type_exists( 'paulus_journal' ) ) {
		register_taxonomy_for_object_type( 'post_tag', 'paulus_journal' );
	}
}
add_action( 'init', 'paulus_tags_journal', 20 );

/** No more than six tags on one item: the first six stay. */
function paulus_tags_limit( $object_id, $terms, $tt_ids, $taxonomy ) {
	if ( 'post_tag' !== $taxonomy ) {
		return;
	}
	$current = wp_get_object_terms( $object_id, 'post_tag', array( 'fields' => 'ids' ) );
	if ( is_wp_error( $current ) || count( $current ) <= PAULUS_MAX_TAGS ) {
		return;
	}
	remove_action( 'set_object_terms', 'paulus_tags_limit', 10 );
	wp_set_object_terms( $object_id, array_map( 'intval', array_slice( $current, 0, PAULUS_MAX_TAGS ) ), 'post_tag', false );
	add_action( 'set_object_terms', 'paulus_tags_limit', 10, 4 );
}
add_action( 'set_object_terms', 'paulus_tags_limit', 10, 4 );

/** Journal entries appear on a tag's archive beside the articles. */
function paulus_tags_archive_query( $q ) {
	if ( ! is_admin() && $q->is_main_query() && $q->is_tag() ) {
		$q->set( 'post_type', array( 'post', 'paulus_journal' ) );
	}
}
add_action( 'pre_get_posts', 'paulus_tags_archive_query' );

/**
 * Give each shipped item its tags, once per version of the data, keeping any
 * tags an editor chose.
 */
function paulus_tags_sync() {
	$file = PAULUS_DIR . '/content/tags.json';
	if ( ! is_readable( $file ) ) {
		return;
	}
	$hash = md5_file( $file );
	if ( get_option( 'paulus_tags_hash' ) === $hash ) {
		return;
	}
	$data = paulus_tags_data();
	foreach ( $data['tags'] as $slug => $list ) {
		$list = array_slice( array_values( array_unique( array_map( 'paulus_tag_lower', (array) $list ) ) ), 0, PAULUS_MAX_TAGS );
		foreach ( array( 'post', 'paulus_journal' ) as $type ) {
			$found = get_posts( array( 'post_type' => $type, 'name' => $slug, 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ) );
			if ( ! $found ) {
				continue;
			}
			$id       = (int) $found[0];
			$current  = wp_get_object_terms( $id, 'post_tag', array( 'fields' => 'names' ) );
			$current  = is_wp_error( $current ) ? array() : array_map( 'paulus_tag_lower', $current );
			$assigned = get_post_meta( $id, '_paulus_tags', true );
			$assigned = is_array( $assigned ) ? $assigned : array();
			sort( $current );
			$base = $assigned;
			sort( $base );
			if ( $current === $base ) {
				wp_set_object_terms( $id, $list, 'post_tag', false );
				update_post_meta( $id, '_paulus_tags', $list );
			}
		}
	}
	update_option( 'paulus_tags_hash', $hash, false );
}
add_action( 'admin_init', 'paulus_tags_sync', 20 );

/* ---------------------------------------------------------------------
 * The glossary
 * ------------------------------------------------------------------ */

/**
 * The glossary's terms, read from its page: key (lower case, accents off),
 * name, definition (text) and anchor.
 *
 * @return array<string, array{name: string, def: string, id: string}>
 */
function paulus_glossary_terms() {
	static $terms = null;
	if ( null !== $terms ) {
		return $terms;
	}
	$terms = array();
	$page  = get_page_by_path( 'glossary' );
	if ( $page && preg_match_all( '#<dt[^>]*>(.*?)</dt>\s*<dd[^>]*>(.*?)</dd>#s', (string) $page->post_content, $m, PREG_SET_ORDER ) ) {
		foreach ( $m as $row ) {
			$name = trim( wp_strip_all_tags( $row[1] ) );
			$terms[ paulus_tag_lower( remove_accents( $name ) ) ] = array(
				'name' => $name,
				'def'  => trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $row[2] ) ) ),
				'id'   => 'term-' . sanitize_title( remove_accents( $name ) ),
			);
		}
	}
	return $terms;
}

/**
 * The glossary term a tag is tied to.
 *
 * @param string $tag Tag name.
 * @return array|null
 */
function paulus_tag_term( $tag ) {
	$tag   = paulus_tag_lower( $tag );
	$data  = paulus_tags_data();
	$terms = paulus_glossary_terms();
	$want  = $data['glossary'][ $tag ] ?? $tag;
	$key   = paulus_tag_lower( remove_accents( $want ) );
	return $terms[ $key ] ?? null;
}

/** The glossary page's address to a term. */
function paulus_glossary_url( $term ) {
	$page = get_page_by_path( 'glossary' );
	return $page ? get_permalink( $page ) . '#' . $term['id'] : '';
}

/** Every term gets an anchor, and a link to the archives of the tags tied to it. */
function paulus_glossary_tag_links( $content ) {
	if ( ! is_page() || 'glossary' !== get_post_field( 'post_name' ) || false === strpos( $content, 'paulus-glossary' ) ) {
		return $content;
	}
	// Terms by their tags.
	$by_term = array();
	foreach ( paulus_tags_data()['glossary'] as $tag => $term_name ) {
		$by_term[ paulus_tag_lower( remove_accents( $term_name ) ) ][] = $tag;
	}
	return preg_replace_callback(
		'#<dt([^>]*)>(.*?)</dt>(\s*)<dd([^>]*)>(.*?)</dd>#s',
		function ( $m ) use ( $by_term ) {
			$name = trim( wp_strip_all_tags( $m[2] ) );
			$key  = paulus_tag_lower( remove_accents( $name ) );
			$tags = $by_term[ $key ] ?? array();
			if ( ! in_array( $key, array_map( 'paulus_tag_lower', $tags ), true ) ) {
				$tags[] = $key; // A tag with the term's own name.
			}
			$links = array();
			foreach ( array_unique( $tags ) as $tag ) {
				$t = get_term_by( 'name', $tag, 'post_tag' );
				if ( $t && $t->count > 0 && ! is_wp_error( get_term_link( $t ) ) ) {
					$links[] = '<a href="' . esc_url( get_term_link( $t ) ) . '" rel="tag">' . esc_html( $t->name ) . '</a> (' . (int) $t->count . ')';
				}
			}
			$anchor = '<span id="term-' . esc_attr( sanitize_title( remove_accents( $name ) ) ) . '"></span>';
			$out    = '<dt' . $m[1] . '>' . $anchor . $m[2] . '</dt>' . $m[3] . '<dd' . $m[4] . '>' . $m[5];
			if ( $links ) {
				$out .= ' <span class="paulus-glossary__tags">' . esc_html__( 'Read more:', 'paulus' ) . ' ' . implode( ', ', $links ) . '.</span>';
			}
			return $out . '</dd>';
		},
		$content
	);
}
add_filter( 'the_content', 'paulus_glossary_tag_links', 10 );

/* ---------------------------------------------------------------------
 * Tag archives
 * ------------------------------------------------------------------ */

/**
 * A tag's description: its own, else the glossary definition with a link to
 * the term, else a plain sentence. Always something, so every archive has a
 * description for search results.
 */
function paulus_tag_description( $description, $term_id = 0 ) {
	$term = $term_id ? get_term( (int) $term_id, 'post_tag' ) : get_queried_object();
	if ( ! $term instanceof WP_Term || 'post_tag' !== $term->taxonomy || '' !== trim( (string) $description ) ) {
		return $description;
	}
	$gloss = paulus_tag_term( $term->name );
	if ( $gloss ) {
		return '<p>' . esc_html( $gloss['def'] ) . ' <a href="' . esc_url( paulus_glossary_url( $gloss ) ) . '">' . esc_html__( 'Defined in the glossary.', 'paulus' ) . '</a></p>';
	}
	/* translators: %s: tag name. */
	return '<p>' . esc_html( sprintf( __( 'Articles and Journal entries on %s in the case against Paul of Tarsus.', 'paulus' ), $term->name ) ) . '</p>';
}
add_filter( 'term_description', 'paulus_tag_description', 10, 2 );

/** A tag archive with fewer than two items is thin: out of the index, links followed. */
function paulus_tags_robots( $robots ) {
	if ( is_tag() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term && (int) $term->count < 2 ) {
			$robots['noindex'] = true;
			unset( $robots['max-image-preview'] );
			$robots['follow'] = true;
		}
	}
	return $robots;
}
add_filter( 'wp_robots', 'paulus_tags_robots' );

/** The tags of an item, as lower-case names. */
function paulus_item_tags( $id ) {
	$tags = get_the_terms( $id, 'post_tag' );
	return ( $tags && ! is_wp_error( $tags ) ) ? $tags : array();
}

/**
 * The tag row under an article's byline: each tag links to its archive, and
 * a tag tied to the glossary links to its term too.
 *
 * @param int $id Post ID.
 * @return string
 */
function paulus_tags_html( $id ) {
	$tags = paulus_item_tags( $id );
	if ( ! $tags ) {
		return '';
	}
	$out = '<ul class="paulus-tags" aria-label="' . esc_attr__( 'Tags', 'paulus' ) . '">';
	foreach ( array_slice( $tags, 0, PAULUS_MAX_TAGS ) as $t ) {
		$link = get_term_link( $t );
		if ( is_wp_error( $link ) ) {
			continue;
		}
		$out .= '<li><a href="' . esc_url( $link ) . '" rel="tag">' . esc_html( $t->name ) . '</a>';
		$term = paulus_tag_term( $t->name );
		if ( $term ) {
			$out .= '<a class="paulus-tags__gloss" href="' . esc_url( paulus_glossary_url( $term ) ) . '" title="' . esc_attr( sprintf( /* translators: %s: glossary term. */ __( 'Glossary: %s', 'paulus' ), $term['name'] ) ) . '"><span class="screen-reader-text">' . esc_html( sprintf( __( 'Glossary: %s', 'paulus' ), $term['name'] ) ) . '</span><span aria-hidden="true">g</span></a>';
		}
		$out .= '</li>';
	}
	return $out . '</ul>';
}

/** `article:tag` for each tag of a single item. */
function paulus_tags_head() {
	if ( ! is_singular( array( 'post', 'paulus_journal' ) ) ) {
		return;
	}
	foreach ( array_slice( paulus_item_tags( get_queried_object_id() ), 0, PAULUS_MAX_TAGS ) as $t ) {
		printf( '<meta property="article:tag" content="%s">' . "\n", esc_attr( $t->name ) );
	}
}
add_action( 'wp_head', 'paulus_tags_head', 6 );

/** The tags as the Article's keywords. */
function paulus_tags_keywords( $id ) {
	$names = wp_list_pluck( array_slice( paulus_item_tags( $id ), 0, PAULUS_MAX_TAGS ), 'name' );
	return $names ? implode( ', ', $names ) : '';
}
