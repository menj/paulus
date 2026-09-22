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
 * Import a bundled image once and return its attachment ID.
 *
 * @param string $slug Image slug.
 * @return int
 */
function paulus_attach_image( $slug ) {
	$map = get_option( 'paulus_attachments', array() );
	if ( ! empty( $map[ $slug ] ) && get_post( $map[ $slug ] ) ) {
		return (int) $map[ $slug ];
	}
	$source = PAULUS_DIR . '/assets/images/' . sanitize_file_name( $slug ) . '.jpg';
	if ( ! file_exists( $source ) ) {
		return 0;
	}
	$upload = wp_upload_bits( $slug . '.jpg', null, file_get_contents( $source ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( ! empty( $upload['error'] ) ) {
		return 0;
	}
	$id = wp_insert_attachment(
		array(
			'post_mime_type' => 'image/jpeg',
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
	// The verdict and the Reference pages live in the footer menu.
	$blocks .= $page_link( 'answers', 'Answers' );
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

		// Move a page installed by an earlier version at the top level.
		if ( ! $page && $parent_id ) {
			$legacy = get_page_by_path( $item['slug'] );
			if ( $legacy && 0 === (int) $legacy->post_parent ) {
				wp_update_post( array( 'ID' => $legacy->ID, 'post_parent' => $parent_id, 'post_title' => $item['title'] ) );
				$page = get_post( $legacy->ID );
			}
		}
		if ( $page ) {
			if ( '' === $page->post_excerpt ) {
				wp_update_post( array( 'ID' => $page->ID, 'post_excerpt' => $item['excerpt'] ) );
			}
			paulus_sync_search_fields( $page->ID, $item );
			paulus_refresh_unedited( $page, $item );
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
				'post_status'   => 'publish',
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

	paulus_install_navigation( $parts );
	paulus_sync_images();
	paulus_sync_front_meta();
	paulus_install_site_icon();

	foreach ( array_keys( paulus_images() ) as $slug ) {
		paulus_attach_image( $slug );
	}
	paulus_attach_image( 'book-cover' );

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
