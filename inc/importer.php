<?php
/**
 * Starter content installer.
 *
 * Builds the site architecture from content/manifest.php: the sections as
 * categories, the articles as posts in reading order, the standalone pages
 * and the primary navigation menu. Idempotent: existing items
 * keep their content; only their structural data (part, order, parent) is
 * brought in line with the manifest.
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

/**
 * Manifest of bundled content.
 *
 * @return array
 */
function paulus_manifest() {
	static $manifest = null;
	if ( null === $manifest ) {
		$manifest = require PAULUS_DIR . '/content/manifest.php';
	}
	return $manifest;
}

/**
 * Read a bundled content file.
 *
 * @param string $file File name in content/articles/.
 * @return string
 */
function paulus_content_file( $file ) {
	$path = PAULUS_DIR . '/content/articles/' . basename( $file );
	return file_exists( $path ) ? (string) file_get_contents( $path ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions
}

/**
 * Bundled illustrations redrawn since first release, with the MD5 of the
 * file they replaced. A site that imported one before hashes were recorded
 * holds exactly that file, so it is refreshed once.
 *
 * @return array<string, string>
 */
function paulus_replaced_images() {
	return array(
		'paul-portrait' => '18c58266558bf775d5133e0254a4253d',
		'paul-portrait-face' => '7068d8fc2a60569264031fd6ee67894f',
		'paul-portrait-hands' => '1a06657d1e82175be5c0368fae9709a2',
		'paul-portrait-ink' => '3210a694eb41e6a5240e035988cb93a8',
		'paul-portrait-oxblood' => '09b951ac4dab2657aeb37e860634fd7c',
		'paul-portrait-mirror' => '445fa79e1e1eaf06062364f470fb9dff',
		'paul-portrait-stone' => '2d0c08087a461e0fc6b109bd7b10a8a8',
		'paul-portrait-mirror-stone' => '6a98582f41ff290ebcbe6f2da53f905a',
	);
}

/**
 * Keep an imported illustration in step with the theme's file. The theme's
 * featured images are AVIF: a media entry that still holds an older file
 * (a JPEG from an earlier release, or a redrawn illustration) takes the
 * current one. The old file and every size WordPress made from it are
 * deleted first, so nothing is left behind; the entry keeps its ID, so every
 * page keeps its featured image.
 *
 * @param string $slug Image slug.
 * @param int    $id   Attachment ID.
 */
function paulus_refresh_attachment( $slug, $id ) {
	$source = PAULUS_DIR . '/assets/images/' . sanitize_file_name( $slug ) . '.avif';
	if ( ! file_exists( $source ) ) {
		return;
	}
	$hashes  = get_option( 'paulus_attachment_hashes', array() );
	$current = md5_file( $source );
	$stored  = $hashes[ $slug ] ?? '';
	$file    = (string) get_attached_file( $id );
	$is_avif = (bool) preg_match( '/\.avif$/i', $file );
	if ( $is_avif && $stored === $current ) {
		return;
	}
	if ( $is_avif && '' === $stored && ! isset( paulus_replaced_images()[ $slug ] ) ) {
		// Imported before hashes were kept, and never redrawn: record it.
		$hashes[ $slug ] = $current;
		update_option( 'paulus_attachment_hashes', $hashes, false );
		return;
	}
	// A conversion waits for the next page load if this one's time is spent.
	if ( function_exists( 'paulus_sync_has_time' ) && ! paulus_sync_has_time() ) {
		return;
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	// The new file goes in first. Only once it is safely written is the old
	// one removed: a failed upload leaves the entry exactly as it was, still
	// showing its old image, and the conversion is tried again next time.
	$upload = wp_upload_bits( $slug . '-' . substr( $current, 0, 8 ) . '.avif', null, file_get_contents( $source ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( ! empty( $upload['error'] ) || empty( $upload['file'] ) || ! file_exists( $upload['file'] ) || ! filesize( $upload['file'] ) ) {
		return;
	}
	if ( '' !== $file && $file !== $upload['file'] ) {
		$meta   = wp_get_attachment_metadata( $id );
		$backup = get_post_meta( $id, '_wp_attachment_backup_sizes', true );
		wp_delete_attachment_files( $id, is_array( $meta ) ? $meta : array(), is_array( $backup ) ? $backup : array(), $file );
	}
	update_attached_file( $id, $upload['file'] );
	wp_update_post( array( 'ID' => $id, 'post_mime_type' => 'image/avif' ) );
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );
	update_post_meta( $id, '_wp_attachment_image_alt', paulus_image_alts()[ $slug ] ?? __( 'Illustration of Paul of Tarsus', 'paulus' ) );
	$hashes[ $slug ] = $current;
	update_option( 'paulus_attachment_hashes', $hashes, false );
}

/**
 * Import a bundled image once and return its attachment ID.
 *
 * @param string $slug Image slug.
 * @return int
 */
function paulus_attach_image( $slug ) {
	$map = get_option( 'paulus_attachments', array() );
	if ( ! empty( $map[ $slug ] ) && get_post( $map[ $slug ] ) ) {
		paulus_refresh_attachment( $slug, (int) $map[ $slug ] );
		return (int) $map[ $slug ];
	}
	// A new import waits for the next page load if this one's time is spent.
	if ( function_exists( 'paulus_sync_has_time' ) && ! paulus_sync_has_time() ) {
		return 0;
	}
	$source = PAULUS_DIR . '/assets/images/' . sanitize_file_name( $slug ) . '.avif';
	if ( ! file_exists( $source ) ) {
		return 0;
	}
	$upload = wp_upload_bits( $slug . '.avif', null, file_get_contents( $source ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( ! empty( $upload['error'] ) ) {
		return 0;
	}
	$id = wp_insert_attachment(
		array(
			'post_mime_type' => 'image/avif',
			'post_title'     => paulus_images()[ $slug ] ?? $slug,
			'post_status'    => 'inherit',
		),
		$upload['file']
	);
	if ( is_wp_error( $id ) || ! $id ) {
		return 0;
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );
	update_post_meta( $id, '_wp_attachment_image_alt', paulus_image_alts()[ $slug ] ?? __( 'Illustration of Paul of Tarsus', 'paulus' ) );
	$map[ $slug ] = $id;
	update_option( 'paulus_attachments', $map, false );
	$hashes          = get_option( 'paulus_attachment_hashes', array() );
	$hashes[ $slug ] = md5_file( $source );
	update_option( 'paulus_attachment_hashes', $hashes, false );
	return (int) $id;
}

/**
 * Bring article and page illustrations in line with the manifest.
 *
 * Only images the theme installed are touched: a featured image chosen by
 * hand is never replaced or removed.
 */
function paulus_sync_images() {
	$manifest = paulus_manifest();
	$ours     = array_map( 'intval', array_values( get_option( 'paulus_attachments', array() ) ) );
	$items    = array();
	foreach ( $manifest['posts'] as $item ) {
		$items[] = array( get_page_by_path( $item['slug'], OBJECT, 'post' ), $item['image'] ?? '' );
	}
	foreach ( $manifest['pages'] as $item ) {
		$path    = ! empty( $item['parent'] ) ? $item['parent'] . '/' . $item['slug'] : $item['slug'];
		$items[] = array( get_page_by_path( $path ), $item['image'] ?? '' );
	}
	foreach ( $items as list( $post, $image ) ) {
		if ( ! $post ) {
			continue;
		}
		$current = (int) get_post_thumbnail_id( $post );
		if ( $current && ! in_array( $current, $ours, true ) ) {
			continue;
		}
		if ( $image ) {
			$id = paulus_attach_image( $image );
			if ( $id && $id !== $current ) {
				set_post_thumbnail( $post, $id );
			}
		} elseif ( $current ) {
			delete_post_thumbnail( $post );
		}
	}
}

/**
 * Descriptions shipped in earlier releases, from the manifest.
 *
 * @return string[]
 */
function paulus_prior_meta() {
	return array_map( 'strval', (array) ( paulus_manifest()['prior_meta'] ?? array() ) );
}

/**
 * Search title and description for an article or page. Each is written
 * when the field is empty or still holds a description this theme shipped
 * earlier; anything typed in by hand is left alone.
 *
 * @param int   $id   Post ID.
 * @param array $item Manifest entry.
 */
function paulus_sync_search_fields( $id, $item ) {
	foreach ( array( 'meta' => '_paulus_meta', 'seo_title' => '_paulus_seo_title' ) as $key => $meta_key ) {
		if ( empty( $item[ $key ] ) ) {
			continue;
		}
		$current = (string) get_post_meta( $id, $meta_key, true );
		if ( '' === $current || in_array( $current, paulus_prior_meta(), true ) ) {
			update_post_meta( $id, $meta_key, $item[ $key ] );
		}
	}
}

/**
 * The front-page description option follows the new default while it still
 * holds an earlier shipped default.
 */
function paulus_sync_front_meta() {
	$opts = get_option( 'paulus_options', array() );
	if ( is_array( $opts ) && isset( $opts['site_meta'] ) && in_array( $opts['site_meta'], paulus_prior_meta(), true ) ) {
		$opts['site_meta'] = paulus_defaults()['site_meta'];
		update_option( 'paulus_options', $opts );
	}
}

/**
 * Create or update the part categories.
 *
 * @return array<string,int> Part slug => term ID.
 */
function paulus_install_parts() {
	$ids = array();
	foreach ( paulus_manifest()['parts'] as $i => $part ) {
		$term = get_term_by( 'slug', $part['slug'], 'category' );
		if ( ! $term ) {
			$new = wp_insert_term( $part['name'], 'category', array( 'slug' => $part['slug'], 'description' => $part['description'] ) );
			if ( is_wp_error( $new ) ) {
				continue;
			}
			$term_id = (int) $new['term_id'];
		} else {
			$term_id = (int) $term->term_id;
			$stale   = array(
				'',
				'What Muslim scholars have said about Paul since the ninth century, and a letter from one who once followed him.',
			);
			if ( in_array( $term->description, $stale, true ) && $term->description !== $part['description'] ) {
				wp_update_term( $term_id, 'category', array( 'description' => $part['description'] ) );
			}
			// A section whose name differs from the manifest's only in
			// capitals takes the manifest's wording ("The man" becomes "The
			// Man"); a name the owner has changed in any other way is kept.
			if ( $term->name !== $part['name'] && 0 === strcasecmp( $term->name, $part['name'] ) ) {
				wp_update_term( $term_id, 'category', array( 'name' => $part['name'] ) );
			}
		}
		update_term_meta( $term_id, 'paulus_order', $i + 1 );
		foreach ( array( 'meta' => 'paulus_meta', 'seo_title' => 'paulus_seo_title' ) as $key => $meta_key ) {
			$current = (string) get_term_meta( $term_id, $meta_key, true );
			if ( ! empty( $part[ $key ] ) && ( '' === $current || in_array( $current, paulus_prior_meta(), true ) ) ) {
				update_term_meta( $term_id, $meta_key, $part[ $key ] );
			}
		}
		$ids[ $part['slug'] ] = $term_id;
	}

	// Sections from earlier versions drop out of the structure.
	$stale = get_terms(
		array(
			'taxonomy'   => 'category',
			'hide_empty' => false,
			'meta_key'   => 'paulus_order', // phpcs:ignore WordPress.DB.SlowDBQuery
			'exclude'    => array_values( $ids ),
			'fields'     => 'ids',
		)
	);
	if ( ! is_wp_error( $stale ) ) {
		foreach ( $stale as $term_id ) {
			delete_term_meta( $term_id, 'paulus_order' );
		}
	}
	return $ids;
}

/**
 * Create the primary navigation menu, or rebuild it when the manifest's
 * nav_version is newer than the menu on the site.
 *
 * @param array<string,int> $parts Section slug => term ID.
 */
function paulus_install_navigation( $parts ) {
	$version  = (int) ( paulus_manifest()['nav_version'] ?? 1 );
	$existing = (int) get_option( 'paulus_nav_id' );
	$current  = $existing && get_post( $existing );
	if ( $current && (int) get_option( 'paulus_nav_version', 1 ) >= $version ) {
		return;
	}
	// A menu edited in the Site Editor is never overwritten. The stored menu
	// is rebuilt only while it still holds exactly the links the theme put
	// there (the sections, Answers, The book, and the Journal link of 2.46,
	// which the rebuild now drops: the Journal has its own block in the
	// header); otherwise it is kept as it is.
	if ( $current ) {
		$labels = array();
		foreach ( parse_blocks( (string) get_post_field( 'post_content', $existing ) ) as $b ) {
			if ( 'core/navigation-link' === ( $b['blockName'] ?? '' ) ) {
				$labels[] = (string) ( $b['attrs']['label'] ?? '' );
			}
		}
		// Every set of links the theme has placed there: the current one (the
		// sections, The Verdict, The book), and the earlier ones with Answers
		// in place of The Verdict, each with or without the Journal link 2.46
		// added. Compared without regard to capitals.
		$names  = wp_list_pluck( paulus_manifest()['parts'], 'name' );
		$sets   = array();
		foreach ( array( 'The Verdict', 'Answers' ) as $fourth ) {
			$base   = array_merge( $names, array( $fourth, 'The book' ) );
			$sets[] = $base;
			$sets[] = array_merge( $base, array( (string) paulus_option( 'journal_title' ) ) );
		}
		$norm   = static function ( $list ) {
			$list = array_map( 'strtolower', $list );
			sort( $list );
			return $list;
		};
		$labels = $norm( $labels );
		if ( ! in_array( $labels, array_map( $norm, $sets ), true ) ) {
			update_option( 'paulus_nav_version', $version );
			update_option( 'paulus_nav_kept', 1, false );
			return;
		}
	}
	delete_option( 'paulus_nav_kept' );

	$link = static function ( $label, $type, $id, $url, $kind, $class = '' ) {
		$attrs = compact( 'label', 'type', 'id', 'url', 'kind' );
		if ( $class ) {
			$attrs['className'] = $class;
		}
		return sprintf( '<!-- wp:navigation-link %s /-->', wp_json_encode( $attrs ) );
	};
	$page_link = static function ( $path, $label, $class = '' ) use ( $link ) {
		$page = get_page_by_path( $path );
		return $page ? $link( $label, 'page', $page->ID, get_permalink( $page ), 'post-type', $class ) : '';
	};

	$blocks = '';
	foreach ( paulus_manifest()['parts'] as $part ) {
		if ( ! empty( $parts[ $part['slug'] ] ) ) {
			$blocks .= $link( $part['name'], 'category', $parts[ $part['slug'] ], get_term_link( $parts[ $part['slug'] ] ), 'taxonomy' );
		}
	}
	// After the sections, The Verdict closes the case; the Reference pages
	// live in the footer menu.
	$blocks .= $page_link( 'the-verdict', 'The Verdict' );
	$blocks .= $page_link( 'the-book', 'The book', 'paulus-nav-cta' );

	if ( $current ) {
		wp_update_post( array( 'ID' => $existing, 'post_content' => $blocks ) );
	} else {
		$nav_id = wp_insert_post(
			array(
				'post_type'    => 'wp_navigation',
				'post_status'  => 'publish',
				'post_title'   => 'Paulus primary',
				'post_content' => $blocks,
			)
		);
		if ( ! $nav_id || is_wp_error( $nav_id ) ) {
			return;
		}
		update_option( 'paulus_nav_id', (int) $nav_id );
	}
	update_option( 'paulus_nav_version', $version );
}

/**
 * Handle the install request.
 */
function paulus_handle_import() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to install content.', 'paulus' ) );
	}
	check_admin_referer( 'paulus_import' );

	$created = paulus_run_install();

	$back = wp_get_referer();
	if ( $back && 0 !== strpos( $back, admin_url() ) ) {
		wp_safe_redirect( $back );
		exit;
	}
	$flag = false === $created ? 'partial' : (string) $created;
	wp_safe_redirect( admin_url( 'themes.php?page=paulus-options&paulus_imported=' . $flag . '#content' ) );
	exit;
}
add_action( 'admin_post_paulus_import', 'paulus_handle_import' );

/**
 * Create or update the whole structure. Used by the install button and by
 * upgrades that add content.
 *
 * @return int Number of items created.
 */
function paulus_run_install() {
	$created  = 0;
	$failed   = false;
	$manifest = paulus_manifest();

	// Name the site on the first run only, so a later rename in
	// Settings, General is never overwritten.
	if ( ! get_option( 'paulus_site_named' ) ) {
		$defaults = paulus_defaults();
		update_option( 'blogname', $defaults['site_name'] );
		$tagline = (string) get_option( 'blogdescription' );
		if ( '' === $tagline || 'Just another WordPress site' === $tagline ) {
			update_option( 'blogdescription', $defaults['site_tagline'] );
		}
		update_option( 'paulus_site_named', 1, false );
	}
	$parts    = paulus_install_parts();

	// Articles, in reading order. Dates ascend so date-based ordering agrees.
	$keep = array();
	$date = time() - DAY_IN_SECONDS * ( count( $manifest['posts'] ) + 1 );
	foreach ( $manifest['posts'] as $i => $item ) {
		$date += DAY_IN_SECONDS;
		$post  = get_page_by_path( $item['slug'], OBJECT, 'post' );
		if ( ! $post ) {
			$id = wp_insert_post(
				array(
					'post_type'     => 'post',
					'post_status'   => 'publish',
					'post_name'     => $item['slug'],
					'post_title'    => $item['title'],
					'post_excerpt'  => $item['excerpt'],
					'post_content'  => paulus_content_file( $item['file'] ),
					'post_date'     => get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $date ) ),
					'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $date ),
				)
			);
			if ( ! $id || is_wp_error( $id ) ) {
				$failed = true;
				continue;
			}
			paulus_refresh_unedited( get_post( $id ), $item );
			++$created;
		} else {
			$id = $post->ID;
			paulus_refresh_unedited( $post, $item );
		}
		// The category is reassigned only when it still matches what this
		// site last shipped for the article: a manual recategorisation
		// (or the addition of another category) is left alone, and the
		// baseline is not touched in that case either, so the change
		// keeps being recognised as intentional on later syncs.
		$target = $parts[ $item['part'] ] ?? 0;
		if ( $target ) {
			$assigned = (int) get_post_meta( $id, '_paulus_category', true );
			$current  = wp_get_post_categories( $id );
			if ( $assigned ) {
				$unedited = array( $assigned ) === $current;
			} else {
				// No baseline yet (a fresh insert, or a site upgrading
				// through this fix). Only an untouched state counts: no
				// category, WordPress's default one, or already the target.
				// Anything else may be a manual choice and is left alone.
				$unedited = in_array( $current, array( array(), array( (int) get_option( 'default_category' ) ), array( (int) $target ) ), true );
			}
			if ( $unedited ) {
				wp_set_post_categories( $id, array( $target ), false );
				update_post_meta( $id, '_paulus_category', $target );
			}
		}
		update_post_meta( $id, '_paulus_order', $i + 1 );
		update_post_meta( $id, '_paulus_label', $item['label'] );
		paulus_sync_search_fields( $id, $item );
		foreach ( array( 'series', 'series_title', 'part_no', 'parts' ) as $key ) {
			if ( isset( $item[ $key ] ) ) {
				update_post_meta( $id, '_paulus_' . $key, $item[ $key ] );
			} else {
				delete_post_meta( $id, '_paulus_' . $key );
			}
		}
		if ( ! empty( $item['questions'] ) ) {
			update_post_meta( $id, '_paulus_questions', 1 );
		} else {
			delete_post_meta( $id, '_paulus_questions' );
		}
		$keep[] = (int) $id;
	}

	// Articles from earlier versions that the manifest no longer lists leave
	// the reading order. Their text is untouched.
	$ordered = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => '_paulus_order', // phpcs:ignore WordPress.DB.SlowDBQuery
			'post__not_in'   => $keep,
		)
	);
	foreach ( $ordered as $old_id ) {
		delete_post_meta( $old_id, '_paulus_order' );
		delete_post_meta( $old_id, '_paulus_questions' );
	}

	// Pages. Parents come first in the manifest.
	foreach ( $manifest['pages'] as $item ) {
		$parent_id = 0;
		if ( ! empty( $item['parent'] ) ) {
			$parent    = get_page_by_path( $item['parent'] );
			$parent_id = $parent ? (int) $parent->ID : 0;
		}
		$path = ! empty( $item['parent'] ) ? $item['parent'] . '/' . $item['slug'] : $item['slug'];
		$page = get_page_by_path( $path );

		// A page whose address changed in a later release ('was_slug'):
		// rename the existing page in place rather than installing a
		// second one beside it. Its content, edits and ID all carry over;
		// paulus_redirect_old_page_slugs() sends the old address to it.
		if ( ! $page && ! empty( $item['was_slug'] ) ) {
			$old_path = ! empty( $item['parent'] ) ? $item['parent'] . '/' . $item['was_slug'] : $item['was_slug'];
			$old      = get_page_by_path( $old_path );
			if ( $old ) {
				wp_update_post( array( 'ID' => $old->ID, 'post_name' => $item['slug'] ) );
				$page = get_post( $old->ID );
			}
		}

		// A page moved to a new parent in a later release ('was_parent'):
		// move the existing page in place, content, edits and ID intact;
		// paulus_redirect_old_page_slugs() sends the old address to it.
		if ( ! $page && $parent_id && ! empty( $item['was_parent'] ) ) {
			$moved = get_page_by_path( $item['was_parent'] . '/' . $item['slug'] );
			if ( $moved ) {
				wp_update_post( array( 'ID' => $moved->ID, 'post_parent' => $parent_id ) );
				$page = get_post( $moved->ID );
			}
		}

		// Move a page installed by an earlier version at the top level.
		if ( ! $page && $parent_id ) {
			$legacy = get_page_by_path( $item['slug'] );
			if ( $legacy && 0 === (int) $legacy->post_parent ) {
				wp_update_post( array( 'ID' => $legacy->ID, 'post_parent' => $parent_id, 'post_title' => $item['title'] ) );
				$page = get_post( $legacy->ID );
			}
		}
		if ( $page ) {
			// WordPress makes a draft Privacy Policy at install, at the same
			// address, holding its own "Suggested text" template. While it is
			// still an untouched draft, the theme's text takes its place (the
			// page stays a draft for the owner to review); a privacy page the
			// owner has written or published is never touched.
			if ( 'privacy-policy' === $item['slug'] && 'draft' === $page->post_status
				&& false !== strpos( $page->post_content, 'privacy-policy-tutorial' ) ) {
				wp_update_post(
					array(
						'ID'           => $page->ID,
						'post_title'   => $item['title'],
						'post_excerpt' => $item['excerpt'],
						'post_content' => paulus_content_file( $item['file'] ),
					)
				);
				$page = get_post( $page->ID );
			}
			if ( '' === $page->post_excerpt ) {
				wp_update_post( array( 'ID' => $page->ID, 'post_excerpt' => $item['excerpt'] ) );
			}
			paulus_sync_search_fields( $page->ID, $item );
			paulus_refresh_unedited( $page, $item );
			// Pages set up with the theme (the Contact page): an empty page at
			// the address takes the theme's text, since it holds nothing of the
			// owner's; an untouched draft is published. A page the owner has
			// written is left as it is.
			if ( ! empty( $item['fill_if_empty'] ) ) {
				$page = get_post( $page->ID );
				// Empty means nothing at all: block comments and blank paragraphs
				// only. A shortcode, a form block or any text counts as the owner's.
				$bare = preg_replace( '#<p>(\s|&nbsp;|<br\s*/?>)*</p>#i', '', preg_replace( '/<!--.*?-->/s', '', (string) $page->post_content ) );
				if ( '' === trim( (string) $bare ) ) {
					wp_update_post( array( 'ID' => $page->ID, 'post_content' => paulus_content_file( $item['file'] ) ) );
					if ( '' === get_post_field( 'post_excerpt', $page->ID ) && ! empty( $item['excerpt'] ) ) {
						wp_update_post( array( 'ID' => $page->ID, 'post_excerpt' => $item['excerpt'] ) );
					}
				}
			}
			if ( ! empty( $item['publish_if_draft'] ) ) {
				$page = get_post( $page->ID );
				if ( 'draft' === $page->post_status && trim( (string) $page->post_content ) === trim( paulus_content_file( $item['file'] ) ) ) {
					wp_update_post( array( 'ID' => $page->ID, 'post_status' => 'publish' ) );
				}
			}
			// menu_order and page_template follow the manifest only while
			// they still match what this site last shipped for this page
			// (the same protection the category assignment on articles
			// gets above): a manual reorder or template change is left
			// alone, and its baseline isn't touched in that case, so the
			// change keeps being recognised as intentional on later syncs.
			$wanted_template = (string) ( $item['template'] ?? '' );
			$current_template = (string) get_page_template_slug( $page->ID );

			$known_order = get_post_meta( $page->ID, '_paulus_menu_order', true );
			// With no baseline yet, only an order that already matches the
			// manifest is adopted; a differing one may be a manual reorder.
			$order_unedited = '' === $known_order
				? (int) $page->menu_order === (int) $item['order']
				: (int) $known_order === (int) $page->menu_order;
			if ( $order_unedited ) {
				if ( (int) $page->menu_order !== (int) $item['order'] ) {
					wp_update_post( array( 'ID' => $page->ID, 'menu_order' => $item['order'] ) );
				}
				update_post_meta( $page->ID, '_paulus_menu_order', $item['order'] );
			}

			$known_template = get_post_meta( $page->ID, '_paulus_page_template', true );
			$template_unedited = '' === $known_template
				? $current_template === $wanted_template
				: $known_template === $current_template;
			if ( $template_unedited ) {
				if ( $current_template !== $wanted_template ) {
					wp_update_post( array( 'ID' => $page->ID, 'page_template' => $wanted_template ) );
				}
				update_post_meta( $page->ID, '_paulus_page_template', $wanted_template );
			}
			continue;
		}
		$id = wp_insert_post(
			array(
				'post_type'     => 'page',
				// Pages the owner must review first (the legal pages) ship as drafts.
				'post_status'   => $item['status'] ?? 'publish',
				'post_name'     => $item['slug'],
				'post_title'    => $item['title'],
				'post_excerpt'  => $item['excerpt'],
				'post_content'  => paulus_content_file( $item['file'] ),
				'post_parent'   => $parent_id,
				'menu_order'    => $item['order'],
				'page_template' => $item['template'] ?? '',
			)
		);
		if ( $id && ! is_wp_error( $id ) ) {
			paulus_sync_search_fields( $id, $item );
			update_post_meta( $id, '_paulus_menu_order', $item['order'] );
			update_post_meta( $id, '_paulus_page_template', (string) ( $item['template'] ?? '' ) );
			paulus_refresh_unedited( get_post( $id ), $item );
			// WordPress's own privacy setting points to the theme's page if unset.
			if ( 'privacy-policy' === $item['slug'] && ! (int) get_option( 'wp_page_for_privacy_policy' ) ) {
				update_option( 'wp_page_for_privacy_policy', $id );
			}
			++$created;
		} else {
			$failed = true;
		}
	}

	// Word-based URLs, section then article, if permalinks are still plain.
	if ( '' === (string) get_option( 'permalink_structure' ) ) {
		update_option( 'permalink_structure', '/%category%/%postname%/' );
		flush_rewrite_rules();
	}

	paulus_sync_note( 'journal and navigation' );
	paulus_install_journal();
	paulus_install_navigation( $parts );
	paulus_sync_note( 'images' );
	paulus_sync_images();
	// With every featured image converted, clear what earlier releases left,
	// in the uploads and in the theme's own image folders.
	paulus_sync_front_meta();
	paulus_install_site_icon();

	foreach ( array_keys( paulus_images() ) as $slug ) {
		paulus_attach_image( $slug );
	}
	paulus_attach_image( 'book-cover' );

	// Images left for the next page load: not done yet, so the version is
	// not recorded and the next load continues where this one stopped.
	if ( ! empty( $GLOBALS['paulus_sync_incomplete'] ) ) {
		paulus_sync_note( 'images', 'pending' );
		return false;
	}
	// With every image in place, clear what earlier releases left.
	paulus_sync_note( 'housekeeping' );
	paulus_image_housekeeping( false );

	return $failed ? false : $created;
}

/**
 * Result notice after install.
 */
function paulus_importer_notice() {
	if ( ! isset( $_GET['paulus_imported'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	$raw = sanitize_text_field( wp_unslash( $_GET['paulus_imported'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	if ( 'partial' === $raw ) {
		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html__( 'Some items could not be installed. The site structure was not marked complete, so this will be retried the next time the structure is installed or updated.', 'paulus' )
		);
		return;
	}
	$n = absint( $raw );
	printf(
		'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
		esc_html(
			$n
				/* translators: %d: number of items created. */
				? sprintf( _n( '%d item installed. Site structure updated.', '%d items installed. Site structure updated.', $n, 'paulus' ), $n )
				: __( 'All starter content already exists. Parts, reading order and page hierarchy were checked and updated.', 'paulus' )
		)
	);
}

/**
 * Content tab.
 */
function paulus_importer_panel() {
	$manifest = paulus_manifest();
	$by_part  = array();
	foreach ( $manifest['posts'] as $item ) {
		$by_part[ $item['part'] ][] = $item;
	}
	$installed = static function ( $path, $type ) {
		return (bool) get_page_by_path( $path, OBJECT, $type );
	};
	?>
	<h2><?php esc_html_e( 'Site structure', 'paulus' ); ?></h2>
	<p><?php echo esc_html( sprintf( /* translators: 1: version applied, 2: version shipped. */ __( 'Structure version on this site: %1$s. Shipped with this theme: %2$s.', 'paulus' ), get_option( 'paulus_content_version', __( 'none recorded', 'paulus' ) ), paulus_manifest()['content_version'] ?? '' ) ); ?></p>
	<p><?php echo esc_html( sprintf( /* translators: %s: site name. */ __( 'On the first run the site title is set to %s. You can change it later under Settings, General.', 'paulus' ), paulus_defaults()['site_name'] ) ); ?></p>
	<p><?php esc_html_e( 'Installs the site as a case file against Paul: a profile of the man, six counts against him, and the witnesses, followed by the answers, the verdict, the reference pages and the book page. The primary menu is created, or rebuilt when this version changes the structure. Items that already exist keep their text; only their section, reading order and place in the page hierarchy are updated.', 'paulus' ); ?></p>

	<?php foreach ( $manifest['parts'] as $part ) : ?>
		<h3><?php echo esc_html( $part['name'] ); ?></h3>
		<ul class="paulus-import-list">
			<?php foreach ( $by_part[ $part['slug'] ] ?? array() as $item ) : ?>
				<li>
					<?php echo esc_html( $item['label'] . ': ' . $item['title'] ); ?>
					<?php if ( $installed( $item['slug'], 'post' ) ) : ?><em>(<?php esc_html_e( 'installed', 'paulus' ); ?>)</em><?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endforeach; ?>

	<h3><?php esc_html_e( 'Pages', 'paulus' ); ?></h3>
	<ul class="paulus-import-list">
		<?php foreach ( $manifest['pages'] as $item ) : ?>
			<?php $path = ! empty( $item['parent'] ) ? $item['parent'] . '/' . $item['slug'] : $item['slug']; ?>
			<li>
				<?php echo esc_html( '/' . $path . '/' ); ?> <?php echo esc_html( $item['title'] ); ?>
				<?php if ( $installed( $path, 'page' ) ) : ?><em>(<?php esc_html_e( 'installed', 'paulus' ); ?>)</em><?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="paulus_import">
		<?php wp_nonce_field( 'paulus_import' ); ?>
		<?php submit_button( __( 'Install or update site structure', 'paulus' ), 'primary', 'submit', false ); ?>
	</form>
	<?php
}

/**
 * Whether the site structure has been installed: at least one article in
 * reading order and the book page.
 *
 * @return bool
 */
function paulus_is_installed() {
	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_paulus_order', // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	return ! empty( $posts ) && (bool) get_page_by_path( 'the-book' );
}

/**
 * The install form, reused by the admin notice and the front-page prompt.
 *
 * @param string $label Button label.
 * @return string
 */
function paulus_install_form( $label ) {
	return '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="paulus-install-form">'
		. '<input type="hidden" name="action" value="paulus_import">'
		. wp_nonce_field( 'paulus_import', '_wpnonce', true, false )
		. '<button type="submit" class="button button-primary paulus-button">' . esc_html( $label ) . '</button>'
		. '</form>';
}

/**
 * Admin notice until the structure is installed. Activating the theme alone
 * creates no articles or pages, so the front page would otherwise show only
 * the hero.
 */
function paulus_install_notice() {
	if ( ! current_user_can( 'edit_theme_options' ) || paulus_is_installed() ) {
		return;
	}
	echo '<div class="notice notice-warning"><p><strong>' . esc_html__( 'Paulus: the site content is not installed yet.', 'paulus' ) . '</strong> '
		. esc_html__( 'Activating the theme does not create the articles, sections, pages or menu. Install them now; nothing you have written is overwritten.', 'paulus' )
		. '</p>' . paulus_install_form( __( 'Install site content', 'paulus' ) ) // phpcs:ignore WordPress.Security.EscapeOutput
		. '<p></p></div>';
}
add_action( 'admin_notices', 'paulus_install_notice' );

/**
 * Front-page prompt for administrators when the structure is missing.
 * Visitors see nothing.
 *
 * @return string
 */
function paulus_sc_setup_prompt() {
	if ( ! current_user_can( 'edit_theme_options' ) || paulus_is_installed() ) {
		return '';
	}
	return '<section class="paulus-setup"><p class="paulus-setup__label">' . esc_html__( 'Only administrators see this', 'paulus' ) . '</p>'
		. '<h2 class="paulus-setup__title">' . esc_html__( 'The site content is not installed yet', 'paulus' ) . '</h2>'
		. '<p>' . esc_html__( 'The charges, the man, the witnesses, the answers and the book page appear here once the articles and pages exist. Install them in one step:', 'paulus' ) . '</p>'
		. paulus_install_form( __( 'Install site content', 'paulus' ) )
		. '</section>';
}
add_shortcode( 'paulus_setup_prompt', 'paulus_sc_setup_prompt' );

/**
 * Set the site icon (favicon) to Paul's portrait, if none is set yet.
 * Uses the same option as Settings, General, so it can be changed there.
 */
function paulus_install_site_icon() {
	if ( get_option( 'site_icon' ) ) {
		return;
	}
	$source = PAULUS_DIR . '/assets/images/site-icon.png';
	if ( ! file_exists( $source ) ) {
		return;
	}
	$upload = wp_upload_bits( 'apostle-of-doom-icon.png', null, file_get_contents( $source ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( ! empty( $upload['error'] ) ) {
		return;
	}
	$id = wp_insert_attachment(
		array(
			'post_mime_type' => 'image/png',
			'post_title'     => __( 'Site icon', 'paulus' ),
			'post_status'    => 'inherit',
		),
		$upload['file']
	);
	if ( is_wp_error( $id ) || ! $id ) {
		return;
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );
	update_post_meta( $id, '_wp_attachment_context', 'site-icon' );
	update_option( 'site_icon', (int) $id );
}

/**
 * Fallback favicon from the theme when no site icon is set, so the tab is
 * never blank before the content is installed.
 */
function paulus_fallback_icon() {
	if ( has_site_icon() ) {
		return;
	}
	$url = PAULUS_URI . '/assets/images/site-icon.png';
	printf( '<link rel="icon" href="%1$s" sizes="512x512"><link rel="apple-touch-icon" href="%1$s">' . "\n", esc_url( $url ) );
}
add_action( 'wp_head', 'paulus_fallback_icon', 1 );

/**
 * Refresh a post's text from the shipped file when the post was never edited
 * by hand: its content still matches the file as shipped in an earlier
 * release (prior_hashes in the manifest) or the hash recorded when this site
 * last installed it. Edited posts are left alone.
 *
 * @param WP_Post $post Existing post or page.
 * @param array   $item Manifest entry.
 */
function paulus_refresh_unedited( $post, $item ) {
	if ( ! $post || empty( $item['file'] ) ) {
		return;
	}
	$file = basename( $item['file'] );
	$new  = paulus_content_file( $file );
	if ( '' === $new ) {
		// The shipped source is missing (a renamed or removed bundled
		// file). Never write an empty body over existing content.
		return;
	}
	$current = md5( $post->post_content );
	$known   = (array) get_option( 'paulus_content_hashes', array() );
	$entry   = $known[ $file ] ?? array();
	// Back-compat: earlier releases stored a bare content-hash string
	// instead of an array with content/title/excerpt baselines.
	if ( is_string( $entry ) ) {
		$entry = array( 'content' => $entry );
	}

	$prior = array();
	$names = array( $file );
	if ( ! empty( $item['was'] ) ) {
		$names[] = basename( $item['was'] );
	}
	foreach ( (array) ( paulus_manifest()['prior_hashes'] ?? array() ) as $key => $hash ) {
		foreach ( $names as $name ) {
			if ( $name === $key || 0 === strpos( $key, $name . '#' ) ) {
				$prior[] = $hash;
			}
		}
	}
	if ( ! empty( $item['was'] ) && isset( $known[ basename( $item['was'] ) ] ) ) {
		$was_entry = $known[ basename( $item['was'] ) ];
		$prior[]   = is_string( $was_entry ) ? $was_entry : ( $was_entry['content'] ?? null );
	}
	$prior = array_filter( $prior );

	// The body is refreshed only when it still matches the content this
	// site last shipped (recorded below) or a documented earlier release.
	// The baseline recorded at the end of this function is always the
	// shipped hash, never whatever happens to be in the post right now,
	// so a hand edit that survives one sync is never later mistaken for
	// "unedited" once a further release changes the shipped text.
	if ( md5( $new ) !== $current && ( $current === ( $entry['content'] ?? '' ) || in_array( $current, $prior, true ) ) ) {
		wp_update_post( array( 'ID' => $post->ID, 'post_content' => $new ) );
		$post->post_content = $new;
		if ( ! empty( $item['meta'] ) ) {
			update_post_meta( $post->ID, '_paulus_meta', $item['meta'] );
		}
	}

	// Title and excerpt are protected independently of the body: each is
	// only refreshed when it still matches what this site last shipped
	// for it. With no recorded baseline yet (a site upgrading through
	// this fix for the first time), nothing is touched until a baseline
	// exists, so an unrecognised hand edit is never guessed away.
	if ( ! empty( $item['title'] ) && $item['title'] !== $post->post_title
		&& array_key_exists( 'title', $entry ) && $entry['title'] === $post->post_title ) {
		wp_update_post( array( 'ID' => $post->ID, 'post_title' => $item['title'] ) );
		$post->post_title = $item['title'];
	}
	if ( ! empty( $item['excerpt'] ) && $item['excerpt'] !== $post->post_excerpt
		&& array_key_exists( 'excerpt', $entry ) && $entry['excerpt'] === $post->post_excerpt ) {
		wp_update_post( array( 'ID' => $post->ID, 'post_excerpt' => $item['excerpt'] ) );
		$post->post_excerpt = $item['excerpt'];
	}

	$known[ $file ] = array(
		'content' => md5( $new ),
		'title'   => $item['title'] ?? null,
		'excerpt' => $item['excerpt'] ?? null,
	);
	update_option( 'paulus_content_hashes', $known, false );
}

/**
 * Create the Journal entries the theme ships, each once. An entry is made
 * on the first sync after it appears in the manifest, dated as the manifest
 * gives, with its featured image, search title and description. Its slug is
 * then recorded, so an entry the owner edits keeps the edits and one the
 * owner deletes is not made again.
 */
function paulus_install_journal() {
	if ( ! post_type_exists( 'paulus_journal' ) ) {
		return;
	}
	$done = (array) get_option( 'paulus_journal_shipped', array() );
	foreach ( (array) ( paulus_manifest()['journal'] ?? array() ) as $e ) {
		if ( empty( $e['slug'] ) ) {
			continue;
		}
		$existing = get_posts( array( 'post_type' => 'paulus_journal', 'name' => $e['slug'], 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ) );
		// An entry the theme shipped takes its revised text, as pages do, while
		// it still holds a text the theme shipped (never once the owner edits it).
		if ( $existing && function_exists( 'paulus_refresh_unedited' ) ) {
			paulus_refresh_unedited( get_post( (int) $existing[0] ), $e );
		}
		if ( in_array( $e['slug'], $done, true ) ) {
			continue;
		}
		if ( ! $existing ) {
			$content = paulus_content_file( $e['file'] );
			if ( '' === $content ) {
				continue;
			}
			$date = $e['date'] ?? current_time( 'mysql' );
			$id   = wp_insert_post(
				array(
					'post_type'     => 'paulus_journal',
					'post_status'   => 'publish',
					'post_name'     => $e['slug'],
					'post_title'    => $e['title'],
					'post_excerpt'  => $e['excerpt'] ?? '',
					'post_content'  => $content,
					'post_date'     => $date,
					'post_date_gmt' => get_gmt_from_date( $date ),
					'post_author'   => get_current_user_id(),
				),
				true
			);
			if ( is_wp_error( $id ) || ! $id ) {
				continue;
			}
			if ( ! empty( $e['image'] ) ) {
				$thumb = paulus_attach_image( $e['image'] );
				if ( $thumb ) {
					set_post_thumbnail( $id, $thumb );
				}
			}
			if ( ! empty( $e['meta'] ) ) {
				update_post_meta( $id, '_paulus_meta', $e['meta'] );
			}
			if ( ! empty( $e['seo_title'] ) ) {
				update_post_meta( $id, '_paulus_seo_title', $e['seo_title'] );
			}
		}
		$done[] = $e['slug'];
		update_option( 'paulus_journal_shipped', $done, false );
	}
}

/**
 * Remove superseded copies of the theme's illustrations from the uploads
 * folder: files named after a bundled illustration (<slug>.jpg,
 * <slug>-<hash>.jpg, their sizes, in JPEG or WebP) that no media entry uses.
 * Earlier refreshes left them behind. Files of the owner's own are never
 * touched: only the theme's own illustration names are considered, and only
 * files no attachment points to. Runs once per content version.
 *
 * @return int Files removed.
 */
function paulus_cleanup_image_residue( $force = false ) {
	$version = (string) ( paulus_manifest()['content_version'] ?? '' );
	if ( ! $force && get_option( 'paulus_residue_cleaned' ) === $version ) {
		return 0;
	}
	$uploads = wp_get_upload_dir();
	$base    = trailingslashit( $uploads['basedir'] );
	if ( ! is_dir( $base ) ) {
		return 0;
	}
	// Every file a media entry uses, with its sizes.
	global $wpdb;
	$keep = array();
	foreach ( $wpdb->get_col( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file'" ) as $rel ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$keep[ $base . $rel ] = true;
	}
	foreach ( $wpdb->get_col( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attachment_metadata'" ) as $raw ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$meta = maybe_unserialize( $raw );
		if ( is_array( $meta ) && ! empty( $meta['file'] ) ) {
			$dir = trailingslashit( dirname( $base . $meta['file'] ) );
			foreach ( (array) ( $meta['sizes'] ?? array() ) as $size ) {
				if ( ! empty( $size['file'] ) ) {
					$keep[ $dir . $size['file'] ] = true;
				}
			}
			if ( ! empty( $meta['original_image'] ) ) {
				$keep[ $dir . $meta['original_image'] ] = true;
			}
		}
	}
	$slugs = array_map( 'preg_quote', array_merge( array_keys( paulus_images() ), array( 'book-cover' ) ) );
	$re    = '/^(' . implode( '|', $slugs ) . ')(-[0-9a-f]{8})?(-\d+x\d+|-scaled)?\.(jpe?g|webp)$/i';
	$gone  = 0;
	$it    = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $base, FilesystemIterator::SKIP_DOTS ) );
	foreach ( $it as $f ) {
		$path = $f->getPathname();
		if ( $f->isFile() && preg_match( $re, $f->getFilename() ) && empty( $keep[ $path ] ) && 0 === strpos( $path, $base ) ) {
			wp_delete_file( $path );
			$gone += file_exists( $path ) ? 0 : 1;
		}
	}
	update_option( 'paulus_residue_cleaned', $version, false );
	return $gone;
}

/**
 * Remove images from the theme's own image folders (assets/images,
 * assets/images/church, assets/social) that this version does not ship. A
 * theme uploaded by FTP, or unzipped over the old folder, overwrites files
 * but deletes none, so images a newer version dropped would stay. The list
 * of what ships is assets/files.json, written when the theme is packaged;
 * without it nothing is removed. Only image files in those folders are
 * considered.
 *
 * @return int Files removed.
 */
function paulus_cleanup_theme_residue() {
	$list = PAULUS_DIR . '/assets/files.json';
	if ( ! file_exists( $list ) ) {
		return 0;
	}
	$ships = array_flip( (array) json_decode( (string) file_get_contents( $list ), true ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( ! $ships ) {
		return 0;
	}
	$gone = 0;
	foreach ( array( 'assets/images', 'assets/images/church', 'assets/social' ) as $dir ) {
		foreach ( (array) glob( PAULUS_DIR . '/' . $dir . '/*.{avif,webp,jpg,jpeg,png,gif}', GLOB_BRACE ) as $path ) {
			$rel = $dir . '/' . basename( $path );
			if ( is_file( $path ) && ! isset( $ships[ $rel ] ) ) {
				wp_delete_file( $path );
				$gone += file_exists( $path ) ? 0 : 1;
			}
		}
	}
	return $gone;
}

/**
 * Image housekeeping: both clean-ups, with the run recorded for the
 * dashboard. Runs weekly through WP-Cron, and after each content update.
 *
 * @param bool $force Run the uploads clean-up even if it ran for this version.
 */
function paulus_image_housekeeping( $force = true ) {
	$uploads = paulus_cleanup_image_residue( $force );
	$theme   = paulus_cleanup_theme_residue();
	update_option(
		'paulus_housekeeping',
		array(
			'time'    => time(),
			'uploads' => (int) $uploads,
			'theme'   => (int) $theme,
			'total'   => (int) ( get_option( 'paulus_housekeeping' )['total'] ?? 0 ) + $uploads + $theme,
		),
		false
	);
}
// WP-Cron passes an empty argument to an event scheduled without any, which
// would read as "not forced": the scheduled run always forces the clean-up.
add_action( 'paulus_image_housekeeping', static function () {
	paulus_image_housekeeping( true );
} );

// On activation (Appearance, Themes, Activate) the old files go at once.
add_action( 'after_switch_theme', static function () {
	paulus_image_housekeeping( true );
	update_option( 'paulus_housekeeping_version', PAULUS_VERSION, false );
} );

// On an update of the active theme there is no activation, and a hook fired
// during the update would run the old version's code. So the new version
// runs the clean-up on the first admin page it serves, once per version.
add_action( 'admin_init', static function () {
	if ( get_option( 'paulus_housekeeping_version' ) !== PAULUS_VERSION && current_user_can( 'manage_options' ) ) {
		update_option( 'paulus_housekeeping_version', PAULUS_VERSION, false );
		paulus_image_housekeeping( true );
	}
} );

// The weekly run is scheduled while the theme is active, and removed when
// another theme is switched in.
add_action( 'init', static function () {
	if ( ! wp_next_scheduled( 'paulus_image_housekeeping' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'weekly', 'paulus_image_housekeeping' );
	}
} );
add_action( 'switch_theme', static function () {
	wp_clear_scheduled_hook( 'paulus_image_housekeeping' );
} );

/**
 * The update's time budget. Image imports and conversions are slow on a
 * server that makes AVIF thumbnails, so each page load does as many as fit
 * in about fifteen seconds, well inside PHP's usual thirty-second limit; the
 * rest follow on the next load, and the version is recorded only once all
 * are done.
 *
 * @return bool Whether time remains for another image.
 */
function paulus_sync_has_time() {
	if ( ! isset( $GLOBALS['paulus_sync_started'] ) ) {
		$GLOBALS['paulus_sync_started'] = microtime( true );
	}
	if ( microtime( true ) - $GLOBALS['paulus_sync_started'] < (float) apply_filters( 'paulus_sync_budget', 15 ) ) {
		return true;
	}
	$GLOBALS['paulus_sync_incomplete'] = true;
	return false;
}

/**
 * Record the update's progress, for the dashboard: the stage it has reached,
 * and, if it stopped, why.
 *
 * @param string $stage   Stage name.
 * @param string $state   running, pending, done or failed.
 * @param string $message Error message, if any.
 */
function paulus_sync_note( $stage, $state = 'running', $message = '' ) {
	update_option(
		'paulus_sync_status',
		array(
			'stage'   => $stage,
			'state'   => $state,
			'message' => $message,
			'time'    => time(),
			'target'  => (string) ( paulus_manifest()['content_version'] ?? '' ),
		),
		false
	);
}
