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
			if ( ! empty( $item['file'] ) && '' === paulus_content_file( basename( $item['file'] ) ) ) {
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
		if ( $old_post && $new_post && $old_post->ID !== $new_post->ID && ! isset( $kept[ $old_post->ID ] ) ) {
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
	update_option( 'paulus_manifest_count', paulus_manifest_count(), false );
	return $out;
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
