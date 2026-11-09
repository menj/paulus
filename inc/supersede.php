<?php
/**
 * Superseded content: a new text that replaces an old one, safely.
 *
 * The manifest may carry a top-level `supersedes` map, old slug => new slug. While the new item is
 * published, the old one:
 *
 *   - answers with a 301 to the new one (template_redirect, priority 1);
 *   - is hidden from lists, search, feeds, previous and next links and sitemaps, through the unlist
 *     module (paulus_unlisted_ids() includes it);
 *   - asks search engines not to index it (the unlist module's robots rule);
 *   - is moved to the Trash by the sync, never deleted, when the owner has not edited it.
 *
 * A superseded item the owner edited is left alone: it is not redirected, hidden or trashed, and it
 * stays on the dashboard's list of kept edits. Nothing the owner created is touched, because only
 * slugs the manifest names are ever considered.
 *
 * Guards. Removals stop when a shipped text fails its checksum, when the item count dropped by more
 * than 10 percent since the last good sync, or when PAULUS_DISABLE_SUPERSEDE is defined as true in
 * wp-config.php (which also switches off the redirect and the hiding).
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

/** The post types a manifest item can have. */
function paulus_supersede_types() {
	return array( 'post', 'page', 'paulus_journal' );
}

/**
 * The kill switch.
 *
 * @return bool
 */
function paulus_supersede_disabled() {
	return defined( 'PAULUS_DISABLE_SUPERSEDE' ) && PAULUS_DISABLE_SUPERSEDE;
}

/**
 * The supersedes map of a manifest, cleaned: slug => slug, never a slug that replaces itself.
 *
 * @param array $manifest The content manifest.
 * @return array<string,string>
 */
function paulus_supersedes_map( $manifest ) {
	$map = array();
	$raw = $manifest['supersedes'] ?? array();
	foreach ( is_array( $raw ) ? $raw : array() as $old => $new ) {
		$old = sanitize_title( (string) $old );
		$new = sanitize_title( (string) $new );
		if ( '' !== $old && '' !== $new && $old !== $new ) {
			$map[ $old ] = $new;
		}
	}
	return $map;
}

/**
 * Whether removals may run. Pure.
 *
 * @param bool $disabled    The kill switch.
 * @param bool $intact      Every shipped text read and matched its checksum.
 * @param int  $count_now   Items in the manifest now.
 * @param int  $count_prior Items at the last good sync, 0 when none.
 * @return string '' when allowed, otherwise the reason.
 */
function paulus_removal_guard( $disabled, $intact, $count_now, $count_prior ) {
	if ( $disabled ) {
		return 'the kill switch PAULUS_DISABLE_SUPERSEDE is on';
	}
	if ( ! $intact ) {
		return 'a shipped text is missing or fails its checksum';
	}
	if ( $count_prior > 0 && $count_now < (int) floor( $count_prior * 0.9 ) ) {
		return 'the manifest lists more than 10 percent fewer items than at the last good sync';
	}
	return '';
}

/**
 * The number of items the manifest ships.
 *
 * @return int
 */
function paulus_manifest_count() {
	$m = paulus_manifest();
	return count( (array) ( $m['posts'] ?? array() ) ) + count( (array) ( $m['journal'] ?? array() ) ) + count( (array) ( $m['pages'] ?? array() ) );
}

/**
 * Whether every shipped text the manifest names reads and matches its checksum.
 *
 * @return bool
 */
function paulus_manifest_intact() {
	$m = paulus_manifest();
	foreach ( array( 'posts', 'journal', 'pages' ) as $list ) {
		foreach ( (array) ( $m[ $list ] ?? array() ) as $item ) {
			// Every item needs a text, and it is read by the exact value the manifest holds.
			if ( empty( $item['file'] ) || ! is_string( $item['file'] ) || '' === paulus_content_file( $item['file'] ) ) {
				return false;
			}
		}
	}
	return true;
}

/**
 * A published post with a slug, of any type the manifest uses.
 *
 * @param string $slug Slug.
 * @return WP_Post|null
 */
function paulus_supersede_find( $slug ) {
	$found = get_posts(
		array(
			'name'           => $slug,
			'post_type'      => paulus_supersede_types(),
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'no_found_rows'  => true,
		)
	);
	return $found ? $found[0] : null;
}

/**
 * Every hash of a text this theme shipped: the baselines this site recorded and every earlier release.
 *
 * @return string[]
 */
function paulus_shipped_hashes() {
	$set = array();
	foreach ( (array) get_option( 'paulus_content_hashes', array() ) as $entry ) {
		$set[] = is_string( $entry ) ? $entry : (string) ( $entry['content'] ?? '' );
	}
	foreach ( (array) ( paulus_manifest()['prior_hashes'] ?? array() ) as $hash ) {
		$set[] = (string) $hash;
	}
	return array_values( array_filter( array_unique( $set ) ) );
}

/**
 * Whether a post still holds text the theme shipped. Fail closed: a body that matches no shipped text
 * counts as the owner's, so it is never redirected, hidden or trashed. The check reads the body itself,
 * so an edit made since the last sync is seen even when the old item is no longer in the manifest.
 *
 * @param WP_Post $post Post.
 * @return bool
 */
function paulus_is_shipped_text( $post ) {
	return in_array( md5( $post->post_content ), paulus_shipped_hashes(), true );
}

/**
 * Old post => new post, for every pair where both are live and the old one is not an owner edit.
 *
 * @return array<int,int> Old post ID => new post ID.
 */
function paulus_superseded_pairs() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$cache = array();
	if ( paulus_supersede_disabled() ) {
		return $cache;
	}
	$kept = (array) get_option( 'paulus_kept_edits', array() );
	foreach ( paulus_supersedes_map( paulus_manifest() ) as $old => $new ) {
		$old_post = paulus_supersede_find( $old );
		$new_post = paulus_supersede_find( $new );
		if ( $old_post && $new_post && $old_post->ID !== $new_post->ID && ! isset( $kept[ $old_post->ID ] ) && paulus_is_shipped_text( $old_post ) ) {
			$cache[ (int) $old_post->ID ] = (int) $new_post->ID;
		}
	}
	return $cache;
}

/**
 * IDs of the superseded posts, for the unlist module.
 *
 * @return int[]
 */
function paulus_superseded_ids() {
	return array_keys( paulus_superseded_pairs() );
}

/**
 * The 301 from a superseded item to its replacement. Runs first, before the theme's other redirects.
 */
function paulus_supersede_redirect() {
	if ( paulus_supersede_disabled() || is_admin() || is_preview() || is_feed() ) {
		return;
	}
	$pairs = paulus_superseded_pairs();
	if ( ! $pairs ) {
		return;
	}
	if ( is_singular() ) {
		$id = (int) get_queried_object_id();
		if ( isset( $pairs[ $id ] ) ) {
			wp_safe_redirect( get_permalink( $pairs[ $id ] ), 301 );
			exit;
		}
	}
}
add_action( 'template_redirect', 'paulus_supersede_redirect', 1 );

/**
 * After the old item is in the Trash it is gone from the pairs, so a request for its address (a 404 now)
 * still reaches the new one.
 */
function paulus_supersede_redirect_trashed() {
	if ( paulus_supersede_disabled() || ! is_404() ) {
		return;
	}
	$slug = (string) get_query_var( 'name' );
	if ( '' === $slug ) {
		$slug = basename( (string) get_query_var( 'pagename' ) );
	}
	$map = paulus_supersedes_map( paulus_manifest() );
	if ( '' !== $slug && isset( $map[ $slug ] ) ) {
		$new = paulus_supersede_find( $map[ $slug ] );
		if ( $new ) {
			wp_safe_redirect( get_permalink( $new ), 301 );
			exit;
		}
	}
}
add_action( 'template_redirect', 'paulus_supersede_redirect_trashed', 2 );

/**
 * The sync step: move to the Trash the superseded items whose replacement is live, unless a guard stops it.
 *
 * @return array{trashed:int[],blocked:string}
 */
function paulus_supersede_retire() {
	$out    = array( 'trashed' => array(), 'blocked' => '' );
	$reason = paulus_removal_guard( paulus_supersede_disabled(), paulus_manifest_intact(), paulus_manifest_count(), (int) get_option( 'paulus_manifest_count', 0 ) );
	if ( '' !== $reason ) {
		$out['blocked'] = $reason;
		update_option( 'paulus_supersede_blocked', $reason, false );
		return $out;
	}
	delete_option( 'paulus_supersede_blocked' );
	foreach ( paulus_superseded_pairs() as $old_id => $new_id ) {
		$post = get_post( $old_id );
		if ( $post && 'publish' === $post->post_status && wp_trash_post( $old_id ) ) {
			$out['trashed'][] = $old_id;
			paulus_supersede_log( $post->post_name, 'moved to the Trash, replaced by ' . get_post_field( 'post_name', $new_id ) );
		}
	}
	return $out;
}

/**
 * The key a retry is judged by: the map and the guard state together. Pure.
 *
 * @param array  $map    The supersedes map.
 * @param string $reason The guard's reason, '' when removals may run.
 * @return string
 */
function paulus_supersede_state_key( $map, $reason ) {
	return md5( wp_json_encode( $map ) . '|' . $reason );
}

/**
 * Retire superseded items again when the map or the guard state changed since the last try. The sync runs only
 * when the content version changes, so a retire that a guard blocked then (a count drop, the kill switch) would
 * never be tried again once the block cleared. This gives it that retry, for an administrator on an admin page.
 */
function paulus_supersede_maybe_retry() {
	if ( wp_doing_ajax() || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$map = paulus_supersedes_map( paulus_manifest() );
	if ( ! $map ) {
		return;
	}
	$reason = paulus_removal_guard( paulus_supersede_disabled(), paulus_manifest_intact(), paulus_manifest_count(), (int) get_option( 'paulus_manifest_count', 0 ) );
	$key    = paulus_supersede_state_key( $map, $reason );
	if ( get_option( 'paulus_supersede_state' ) === $key ) {
		return;
	}
	paulus_supersede_retire();
	update_option( 'paulus_supersede_state', $key, false );
}
add_action( 'admin_init', 'paulus_supersede_maybe_retry', 30 );

/**
 * Record the item count as the baseline for the 10 percent guard. Called when a sync has finished
 * completely, never after a partial run.
 */
function paulus_supersede_record_count() {
	if ( paulus_manifest_intact() ) {
		update_option( 'paulus_manifest_count', paulus_manifest_count(), false );
	}
}

/**
 * Append to the sync log (the last 100 lines).
 *
 * @param string $slug   Item.
 * @param string $action What happened.
 */
function paulus_supersede_log( $slug, $action ) {
	$log   = (array) get_option( 'paulus_sync_log', array() );
	$log[] = array( 't' => time(), 'slug' => $slug, 'action' => $action );
	update_option( 'paulus_sync_log', array_slice( $log, -100 ), false );
}

/**
 * What the next sync would do with each shipped text, without changing anything.
 *
 * create   no post with that slug yet
 * current  the post matches the shipped text
 * refresh  the post is untouched by the owner and the shipped text changed
 * kept     the owner edited it: it stays, and the owner chooses on the dashboard
 *
 * @return array<int,array{title:string,slug:string,action:string}>
 */
function paulus_sync_preview() {
	$m     = paulus_manifest();
	$known = (array) get_option( 'paulus_content_hashes', array() );
	$rows  = array();
	foreach ( array( 'posts' => 'post', 'journal' => 'paulus_journal', 'pages' => 'page' ) as $list => $type ) {
		foreach ( (array) ( $m[ $list ] ?? array() ) as $item ) {
			if ( empty( $item['file'] ) || empty( $item['slug'] ) ) {
				continue;
			}
			$path = ( 'page' === $type && ! empty( $item['parent'] ) ) ? $item['parent'] . '/' . $item['slug'] : $item['slug'];
			$post = 'paulus_journal' === $type ? paulus_supersede_find( $item['slug'] ) : get_page_by_path( $path, OBJECT, $type );
			$new  = paulus_content_file( basename( $item['file'] ) );
			if ( ! $post ) {
				$action = 'create';
			} elseif ( '' === $new || md5( $new ) === md5( $post->post_content ) ) {
				$action = 'current';
			} else {
				$entry  = $known[ basename( $item['file'] ) ] ?? array();
				$entry  = is_string( $entry ) ? array( 'content' => $entry ) : $entry;
				$prior  = array();
				foreach ( (array) ( $m['prior_hashes'] ?? array() ) as $key => $hash ) {
					if ( basename( $item['file'] ) === $key || 0 === strpos( $key, basename( $item['file'] ) . '#' ) ) {
						$prior[] = $hash;
					}
				}
				$current = md5( $post->post_content );
				$action  = ( $current === ( $entry['content'] ?? '' ) || in_array( $current, $prior, true ) ) ? 'refresh' : 'kept';
			}
			$rows[] = array( 'title' => (string) ( $item['title'] ?? $item['slug'] ), 'slug' => (string) $item['slug'], 'action' => $action );
		}
	}
	foreach ( paulus_supersedes_map( $m ) as $old => $new ) {
		$old_post = paulus_supersede_find( $old );
		if ( $old_post ) {
			$rows[] = array(
				'title'  => $old_post->post_title,
				'slug'   => $old,
				'action' => isset( paulus_superseded_pairs()[ $old_post->ID ] ) ? 'supersede' : 'supersede-kept',
			);
		}
	}
	return $rows;
}

/** The dry-run table for the Content tab. */
function paulus_sync_preview_render() {
	$labels = array(
		'create'         => __( 'Would be added', 'paulus' ),
		'current'        => __( 'Matches the theme', 'paulus' ),
		'refresh'        => __( 'Would take the theme\'s new text (you have not edited it)', 'paulus' ),
		'kept'           => __( 'Stays as you edited it; you choose on the dashboard', 'paulus' ),
		'supersede'      => __( 'Would be redirected, hidden and moved to the Trash: its replacement is live', 'paulus' ),
		'supersede-kept' => __( 'Replaced, but you edited it, so it stays as it is', 'paulus' ),
	);
	$rows = array_filter( paulus_sync_preview(), static fn( $r ) => 'current' !== $r['action'] );
	echo '<h2>' . esc_html__( 'Next sync, as a dry run', 'paulus' ) . '</h2>';
	$blocked = paulus_removal_guard( paulus_supersede_disabled(), paulus_manifest_intact(), paulus_manifest_count(), (int) get_option( 'paulus_manifest_count', 0 ) );
	if ( '' !== $blocked ) {
		echo '<div class="notice notice-warning inline"><p>' . esc_html( sprintf( /* translators: %s: reason. */ __( 'Removals are stopped: %s.', 'paulus' ), $blocked ) ) . '</p></div>';
	}
	if ( ! $rows ) {
		echo '<p>' . esc_html__( 'Nothing would change.', 'paulus' ) . '</p>';
	} else {
		echo '<table class="widefat striped"><thead><tr><th scope="col">' . esc_html__( 'Item', 'paulus' ) . '</th><th scope="col">' . esc_html__( 'The next sync', 'paulus' ) . '</th></tr></thead><tbody>';
		foreach ( $rows as $row ) {
			echo '<tr><td>' . esc_html( $row['title'] ) . '</td><td>' . esc_html( $labels[ $row['action'] ] ?? $row['action'] ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}
	$log = array_reverse( (array) get_option( 'paulus_sync_log', array() ) );
	if ( $log ) {
		echo '<h3>' . esc_html__( 'What changed', 'paulus' ) . '</h3><table class="widefat striped"><tbody>';
		foreach ( array_slice( $log, 0, 20 ) as $line ) {
			echo '<tr><td>' . esc_html( wp_date( 'Y-m-d H:i', (int) $line['t'] ) ) . '</td><td>' . esc_html( (string) $line['slug'] ) . '</td><td>' . esc_html( (string) $line['action'] ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}
}

/**
 * Whether superseded items are hidden on this request: the public site, never an editor at work.
 * The same rule the unlist module applies to its own list.
 *
 * @return bool
 */
function paulus_supersede_hides() {
	if ( paulus_supersede_disabled() ) {
		return false;
	}
	if ( is_admin() && ! wp_doing_ajax() ) {
		return false;
	}
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST && current_user_can( 'edit_posts' ) ) {
		return false;
	}
	// An editor's admin-side AJAX call, from a screen under wp-admin, sees everything (the native rule).
	if ( wp_doing_ajax() && current_user_can( 'edit_posts' ) ) {
		$referer = (string) wp_get_referer();
		if ( '' !== $referer && 0 === strpos( $referer, admin_url() ) ) {
			return false;
		}
	}
	return true;
}

/**
 * Take the superseded IDs out of a query's include list, or ask for a post that cannot exist when that
 * empties it. WordPress ignores post__not_in whenever post__in is set, so the list is cut itself.
 *
 * @param int[] $in  The include list.
 * @param int[] $ids Superseded IDs.
 * @return int[]|null The new list, or null when the query has no include list.
 */
function paulus_supersede_cut_includes( $in, $ids ) {
	$in = array_filter( array_map( 'intval', (array) $in ) );
	if ( ! $in ) {
		return null;
	}
	$in = array_values( array_diff( $in, $ids ) );
	return $in ? $in : array( 0 );
}

/**
 * With the Unlist Posts plugin active the unlist module stands down, so the hiding of superseded items is
 * done here: left out of lists, search and feeds (also queries that set post__in), out of the WordPress and
 * Rank Math sitemaps, and noindex on the item's own page, through WordPress's robots tag and Rank Math's.
 */
if ( class_exists( 'Unlist_Posts' ) ) {
	add_action(
		'pre_get_posts',
		static function ( $q ) {
			if ( ! paulus_supersede_hides() || $q->is_singular() || $q->get( 'p' ) || $q->get( 'page_id' ) || $q->get( 'name' ) || $q->get( 'pagename' ) ) {
				return;
			}
			$ids = paulus_superseded_ids();
			if ( ! $ids ) {
				return;
			}
			$in = paulus_supersede_cut_includes( $q->get( 'post__in' ), $ids );
			if ( null !== $in ) {
				$q->set( 'post__in', $in );
				return;
			}
			$q->set( 'post__not_in', array_values( array_unique( array_merge( (array) $q->get( 'post__not_in' ), $ids ) ) ) );
		}
	);
	add_filter(
		'wp_sitemaps_posts_query_args',
		static function ( $args ) {
			$ids = paulus_superseded_ids();
			if ( $ids ) {
				$args['post__not_in'] = array_merge( (array) ( $args['post__not_in'] ?? array() ), $ids );
			}
			return $args;
		}
	);
	add_filter(
		'wp_robots',
		static function ( $robots ) {
			return is_singular() && in_array( (int) get_queried_object_id(), paulus_superseded_ids(), true ) ? wp_robots_no_robots( $robots ) : $robots;
		}
	);
	add_filter(
		'rank_math/frontend/robots',
		static function ( $robots ) {
			if ( is_singular() && in_array( (int) get_queried_object_id(), paulus_superseded_ids(), true ) ) {
				$robots['index'] = 'noindex';
			}
			return $robots;
		}
	);
	add_filter(
		'rank_math/sitemap/posts_to_exclude',
		static function ( $ids ) {
			$ids = is_array( $ids ) ? $ids : preg_split( '/[\s,]+/', (string) $ids, -1, PREG_SPLIT_NO_EMPTY );
			return array_values( array_unique( array_merge( array_filter( array_map( 'absint', $ids ) ), paulus_superseded_ids() ) ) );
		}
	);
	add_filter(
		'rank_math/sitemap/entry',
		static function ( $url, $type, $object ) {
			return 'post' === $type && $object instanceof WP_Post && in_array( (int) $object->ID, paulus_superseded_ids(), true ) ? false : $url;
		},
		10,
		3
	);
	add_action(
		'wp',
		static function () {
			if ( is_singular() && in_array( (int) get_queried_object_id(), paulus_superseded_ids(), true ) ) {
				add_filter( 'wpseo_robots_array', '__return_empty_array' );
			}
		}
	);
}
