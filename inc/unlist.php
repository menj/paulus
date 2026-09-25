<?php
/**
 * Unlisted posts and pages: reachable by their address, hidden everywhere
 * else. A native form of the plugin Unlist Posts & Pages by Nikschavan
 * (GPL v2 or later), reading and writing the plugin's own option,
 * `unlist_posts`, so a site that used the plugin keeps its unlisted items.
 *
 * An unlisted item is left out of every listing on the site: archives, the
 * front page, search, feeds, the theme's own lists (section cards, the
 * reading order, the rail, the Sitemap page, the footer columns, the
 * Journal), previous and next links, page lists, WordPress's and Rank
 * Math's XML sitemaps; and its own page tells search engines not to index
 * it. Opened by its address, it displays as usual. Editors see everything.
 *
 * Stands down while the plugin itself is active, so the two never run
 * together.
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'Unlist_Posts' ) ) {
	return;
}

/**
 * The unlisted items' IDs.
 *
 * @return int[]
 */
function paulus_unlisted_ids() {
	$ids = get_option( 'unlist_posts', array() );
	return array_values( array_filter( array_map( 'absint', is_array( $ids ) ? $ids : array() ) ) );
}

/**
 * Whether an item is unlisted.
 *
 * @param int $id Post ID.
 * @return bool
 */
function paulus_is_unlisted( $id ) {
	return in_array( (int) $id, paulus_unlisted_ids(), true );
}

/**
 * Whether this request is one where unlisted items are hidden: the public
 * site, and never an editor at work (admin screens, the block editor's
 * requests, or an admin-side AJAX call).
 *
 * @return bool
 */
function paulus_unlist_applies() {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return false;
	}
	if ( ( defined( 'REST_REQUEST' ) && REST_REQUEST ) && current_user_can( 'edit_posts' ) ) {
		return false;
	}
	if ( wp_doing_ajax() && current_user_can( 'edit_posts' ) ) {
		$referer = (string) wp_get_referer();
		if ( '' !== $referer && 0 === strpos( $referer, admin_url() ) ) {
			return false;
		}
	}
	return (bool) apply_filters( 'unlist_posts_enabled', true );
}

/**
 * Every query for a list leaves unlisted items out. A query for one item
 * (a post or page opened by its address) is left alone. This runs for
 * get_posts() too, which the plugin's SQL filter did not reach, so the
 * theme's own lists are covered.
 *
 * @param WP_Query $q Query.
 */
function paulus_unlist_query( $q ) {
	if ( ! paulus_unlist_applies() || $q->is_singular() || $q->get( 'p' ) || $q->get( 'page_id' ) || $q->get( 'name' ) || $q->get( 'pagename' ) ) {
		return;
	}
	$ids = paulus_unlisted_ids();
	if ( $ids ) {
		$q->set( 'post__not_in', array_values( array_unique( array_merge( (array) $q->get( 'post__not_in' ), $ids ) ) ) );
	}
}
add_action( 'pre_get_posts', 'paulus_unlist_query' );

/**
 * Pages fetched with get_pages() (the Reference and Appendices lists, the
 * Sitemap, the footer) and wp_list_pages().
 *
 * @param WP_Post[] $pages Pages.
 * @return WP_Post[]
 */
function paulus_unlist_pages( $pages ) {
	if ( ! paulus_unlist_applies() || ! $pages ) {
		return $pages;
	}
	$ids = paulus_unlisted_ids();
	return $ids ? array_values( array_filter( $pages, static function ( $p ) use ( $ids ) {
		return ! in_array( (int) $p->ID, $ids, true );
	} ) ) : $pages;
}
add_filter( 'get_pages', 'paulus_unlist_pages' );
add_filter( 'wp_list_pages_excludes', static function ( $exclude ) {
	return paulus_unlist_applies() ? array_merge( (array) $exclude, paulus_unlisted_ids() ) : $exclude;
} );

/**
 * Previous and next links.
 *
 * @param string $where SQL.
 * @return string
 */
function paulus_unlist_adjacent( $where ) {
	$ids = paulus_unlisted_ids();
	return paulus_unlist_applies() && $ids ? $where . ' AND p.ID NOT IN (' . implode( ',', $ids ) . ')' : $where;
}
add_filter( 'get_next_post_where', 'paulus_unlist_adjacent', 20 );
add_filter( 'get_previous_post_where', 'paulus_unlist_adjacent', 20 );

/**
 * The item's own page asks search engines not to index it, through
 * WordPress's robots tag and Rank Math's.
 */
add_filter( 'wp_robots', static function ( $robots ) {
	return is_singular() && paulus_is_unlisted( get_queried_object_id() ) ? wp_robots_no_robots( $robots ) : $robots;
} );
add_filter( 'rank_math/frontend/robots', static function ( $robots ) {
	if ( is_singular() && paulus_is_unlisted( get_queried_object_id() ) ) {
		$robots['index'] = 'noindex';
	}
	return $robots;
} );

/**
 * The XML sitemaps: WordPress's own and Rank Math's.
 */
add_filter( 'wp_sitemaps_posts_query_args', static function ( $args ) {
	$args['post__not_in'] = array_merge( (array) ( $args['post__not_in'] ?? array() ), paulus_unlisted_ids() );
	return $args;
} );
add_filter( 'rank_math/sitemap/posts_to_exclude', static function ( $ids ) {
	return array_values( array_unique( array_merge( (array) $ids, paulus_unlisted_ids() ) ) );
} );

/* ------------------------------------------------------------------ */
/* The editor: a switch in the sidebar, a label in the lists, a filter. */
/* ------------------------------------------------------------------ */

/**
 * Post types that can be unlisted: the public ones.
 *
 * @return string[]
 */
function paulus_unlist_types() {
	return array_values( get_post_types( array( 'public' => true ) ) );
}

add_action( 'add_meta_boxes', static function () {
	foreach ( paulus_unlist_types() as $type ) {
		add_meta_box( 'paulus-unlist', __( 'Unlist', 'paulus' ), 'paulus_unlist_box', $type, 'side', 'low' );
	}
} );

/**
 * The meta box.
 *
 * @param WP_Post $post Post.
 */
function paulus_unlist_box( $post ) {
	wp_nonce_field( 'paulus_unlist', 'paulus_unlist_nonce' );
	printf(
		'<label><input type="checkbox" name="paulus_unlisted" value="1"%s> %s</label><p class="description">%s</p>',
		checked( paulus_is_unlisted( $post->ID ), true, false ),
		esc_html__( 'Unlist this item', 'paulus' ),
		esc_html__( 'Reachable only by its address: left out of every list, search and sitemap on the site, and marked not to be indexed.', 'paulus' )
	);
}

add_action( 'save_post', static function ( $post_id ) {
	if ( ! isset( $_POST['paulus_unlist_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['paulus_unlist_nonce'] ) ), 'paulus_unlist' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$ids = paulus_unlisted_ids();
	$ids = ! empty( $_POST['paulus_unlisted'] ) ? array_merge( $ids, array( (int) $post_id ) ) : array_diff( $ids, array( (int) $post_id ) );
	update_option( 'unlist_posts', array_values( array_unique( array_map( 'intval', $ids ) ) ) );
} );

add_filter( 'display_post_states', static function ( $states, $post ) {
	if ( paulus_is_unlisted( $post->ID ) ) {
		$states['paulus-unlisted'] = __( 'Unlisted', 'paulus' );
	}
	return $states;
}, 10, 2 );

/**
 * An "Unlisted" view in each list of posts and pages.
 */
add_action( 'admin_init', static function () {
	foreach ( paulus_unlist_types() as $type ) {
		add_filter( "views_edit-{$type}", static function ( $views ) use ( $type ) {
			$ids = paulus_unlisted_ids();
			if ( ! $ids ) {
				return $views;
			}
			$count = count( get_posts( array( 'post_type' => $type, 'post__in' => $ids, 'post_status' => 'any', 'fields' => 'ids', 'numberposts' => -1 ) ) );
			if ( $count ) {
				$current                  = ! empty( $_GET['paulus_unlisted'] ); // phpcs:ignore WordPress.Security.NonceVerification
				$views['paulus-unlisted'] = '<a href="' . esc_url( add_query_arg( array( 'post_type' => $type, 'paulus_unlisted' => 1 ), admin_url( 'edit.php' ) ) ) . '"' . ( $current ? ' class="current" aria-current="page"' : '' ) . '>' . esc_html__( 'Unlisted', 'paulus' ) . ' <span class="count">(' . number_format_i18n( $count ) . ')</span></a>';
			}
			return $views;
		} );
	}
} );
add_action( 'pre_get_posts', static function ( $q ) {
	if ( is_admin() && $q->is_main_query() && ! empty( $_GET['paulus_unlisted'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$ids = paulus_unlisted_ids();
		$q->set( 'post__in', $ids ? $ids : array( 0 ) );
	}
} );
