<?php
/**
 * Theme Options: the tabbed screen markup and field rendering.
 *
 * Split out of inc/options.php; field definitions and sanitisation live in
 * inc/options-fields.php.
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin assets, only on our screen.
 *
 * @param string $hook Current admin page.
 */
function paulus_admin_assets( $hook ) {
	if ( 'appearance_page_paulus-options' !== $hook ) {
		return;
	}
	wp_enqueue_style( 'paulus-fonts', PAULUS_URI . '/assets/css/fonts.css', array(), PAULUS_VERSION );
	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_style( 'dashicons' );
	wp_enqueue_style( 'paulus-admin', PAULUS_URI . '/assets/css/admin.css', array( 'paulus-fonts', 'wp-color-picker' ), PAULUS_VERSION );
	wp_enqueue_script( 'paulus-admin', PAULUS_URI . '/assets/js/admin.js', array( 'jquery', 'wp-color-picker' ), PAULUS_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'paulus_admin_assets' );

/**
 * Whether the current admin screen is Theme Options.
 *
 * @return bool
 */
function paulus_is_options_screen() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	return $screen && 'appearance_page_paulus-options' === $screen->id;
}

/**
 * Credit line in the admin footer on Theme Options, in place of WordPress's.
 *
 * @param string $text Default footer text.
 * @return string
 */
function paulus_admin_footer_text( $text ) {
	if ( ! paulus_is_options_screen() ) {
		return $text;
	}
	$theme = wp_get_theme( 'paulus' );
	return sprintf(
		/* translators: 1: theme name, 2: author link, 3: site name. */
		__( '%1$s, built by %2$s for %3$s.', 'paulus' ),
		'<strong>' . esc_html( $theme->get( 'Name' ) ) . '</strong>',
		'<a href="' . esc_url( $theme->get( 'ThemeURI' ) ) . '" target="_blank" rel="noopener">' . esc_html( $theme->get( 'Author' ) ) . '</a>',
		'<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html( get_bloginfo( 'name' ) ) . '</a>'
	);
}
add_filter( 'admin_footer_text', 'paulus_admin_footer_text', 20 );

/**
 * Theme version in place of the WordPress version, on Theme Options.
 *
 * @param string $text Default version text.
 * @return string
 */
function paulus_admin_footer_version( $text ) {
	if ( ! paulus_is_options_screen() ) {
		return $text;
	}
	/* translators: %s: theme version. */
	return esc_html( sprintf( __( 'Paulus %s', 'paulus' ), PAULUS_VERSION ) );
}
add_filter( 'update_footer', 'paulus_admin_footer_version', 20 );

/**
 * Icon (a Dashicons class) and one-line usage note for each tab.
 *
 * @return array<string,array{0:string,1:string}>
 */
function paulus_options_tab_meta() {
	return array(
		'book'       => array( 'dashicons-book', __( 'The book the site is built on: titles, author, catalogue figures and the order link. The author card, the book page and the structured data read these fields.', 'paulus' ) ),
		'catalogue'  => array( 'dashicons-index-card', __( 'The national library record of the book, as the footer colophon prints it.', 'paulus' ) ),
		'publisher'  => array( 'dashicons-building', __( 'The publisher named in the colophon and the structured data. The address and telephone go into the structured data only.', 'paulus' ) ),
		'appearance' => array( 'dashicons-art', __( 'Pick a colour scheme. This screen takes it at once, so you can judge it before you save. The ornament layer adds the first-century textures and borders.', 'paulus' ) ),
		'reading'    => array( 'dashicons-visibility', __( 'Reading aids on articles and pages: the progress bar, arrow-key paging, resume reading, the copy-link button and back to top.', 'paulus' ) ),
		'media'      => array( 'dashicons-format-image', __( 'What WordPress does with new uploads. Files already in the media library stay as they are.', 'paulus' ) ),
		'login'      => array( 'dashicons-lock', __( 'The login screen, application passwords and the security headers sent with every page.', 'paulus' ) ),
		'security'   => array( 'dashicons-shield', __( 'XML-RPC, REST API writes and the spam gate. The cleanup tools and reports sit under the switches.', 'paulus' ) ),
		'upkeep'     => array( 'dashicons-admin-tools', __( 'Maintenance mode for FTP updates, and an email when new fatal errors appear.', 'paulus' ) ),
		'links'      => array( 'dashicons-admin-links', __( 'Links added to the first mention of a subject, to this site and to the other sites, with limits and your own phrases.', 'paulus' ) ),
		'journal'    => array( 'dashicons-edit-page', __( 'The title, introduction and search description of the Journal page.', 'paulus' ) ),
		'front'      => array( 'dashicons-admin-home', __( 'The front page hero, its title and description for search, and the footer text and badges.', 'paulus' ) ),
		'content'    => array( 'dashicons-download', __( 'Install or update the shipped articles, pages and menu. Text you have edited is kept.', 'paulus' ) ),
	);
}

/**
 * Render one field.
 *
 * Every control keeps the name paulus_options[<key>] and the id
 * paulus-<key>, so saving and the sanitiser are unchanged.
 *
 * @param string $key Field key.
 * @param array  $def Field definition.
 */
function paulus_render_field( $key, $def ) {
	$name  = 'paulus_options[' . $key . ']';
	$value = paulus_option( $key );
	$id    = 'paulus-' . $key;

	switch ( $def[1] ) {
		case 'bio':
		case 'textarea':
			printf( '<textarea id="%s" name="%s" rows="4" class="paulus-input">%s</textarea>', esc_attr( $id ), esc_attr( $name ), esc_textarea( $value ) );
			break;
		case 'html':
			printf( '<textarea id="%s" name="%s" rows="4" class="paulus-input code">%s</textarea>', esc_attr( $id ), esc_attr( $name ), esc_textarea( $value ) );
			break;
		case 'checkbox':
			printf(
				'<input type="hidden" name="%2$s" value="0"><span class="paulus-toggle"><input type="checkbox" role="switch" id="%1$s" name="%2$s" value="1" %3$s><span class="paulus-toggle__track" aria-hidden="true"></span></span>',
				esc_attr( $id ),
				esc_attr( $name ),
				checked( 1, (int) $value, false )
			);
			break;
		case 'select':
			printf( '<select id="%s" name="%s" class="paulus-select">', esc_attr( $id ), esc_attr( $name ) );
			foreach ( (array) ( $def[2] ?? array() ) as $val => $label ) {
				printf( '<option value="%s" %s>%s</option>', esc_attr( $val ), selected( $value, $val, false ), esc_html( $label ) );
			}
			echo '</select>';
			break;
		case 'number':
			$range = isset( $def[2] ) && is_array( $def[2] ) ? $def[2] : array( 0, 100 );
			printf(
				'<span class="paulus-range"><input type="range" id="%1$s" name="%2$s" value="%3$d" min="%4$d" max="%5$d" step="1" aria-describedby="%1$s-value"><output id="%1$s-value" for="%1$s" class="paulus-range__value" aria-live="polite">%3$d</output></span>',
				esc_attr( $id ),
				esc_attr( $name ),
				(int) $value,
				(int) $range[0],
				(int) $range[1]
			);
			break;
		case 'color':
			printf( '<input type="text" id="%s" name="%s" value="%s" class="paulus-color" placeholder="#7a2e1a" maxlength="7" autocomplete="off">', esc_attr( $id ), esc_attr( $name ), esc_attr( (string) $value ) );
			break;
		case 'scheme':
			echo '<div class="paulus-swatches">';
			foreach ( paulus_schemes() as $slug => $label ) {
				printf(
					'<label class="paulus-swatch paulus-swatch--%1$s"><input type="radio" name="%2$s" value="%1$s" %3$s %5$s><span class="paulus-swatch__chip" aria-hidden="true"></span><span>%4$s</span></label>',
					esc_attr( $slug ),
					esc_attr( $name ),
					checked( $value, $slug, false ),
					esc_html( $label ),
					array_key_first( paulus_schemes() ) === $slug ? 'id="' . esc_attr( $id ) . '"' : ''
				);
			}
			echo '</div>';
			break;
		case 'image':
			echo '<div class="paulus-images">';
			$first = true;
			foreach ( paulus_images() as $slug => $label ) {
				printf(
					'<label class="paulus-image"><input type="radio" name="%1$s" value="%2$s" %3$s %6$s><img src="%4$s" alt="" loading="lazy"><span>%5$s</span></label>',
					esc_attr( $name ),
					esc_attr( $slug ),
					checked( $value, $slug, false ),
					esc_url( paulus_image_url( $slug ) ),
					esc_html( $label ),
					$first ? 'id="' . esc_attr( $id ) . '"' : ''
				);
				$first = false;
			}
			echo '</div>';
			break;
		default:
			$type = in_array( $def[1], array( 'url', 'email' ), true ) ? $def[1] : 'text';
			printf( '<input type="%s" id="%s" name="%s" value="%s" class="paulus-input">', esc_attr( $type ), esc_attr( $id ), esc_attr( $name ), esc_attr( $value ) );
	}
}

/**
 * One tab's fields as card rows. Colour fields share one row, as a grid.
 *
 * @param array $fields Field map of the tab.
 */
function paulus_render_rows( $fields ) {
	$colors = array_filter( $fields, static fn( $def ) => 'color' === $def[1] );
	$done   = false;
	foreach ( $fields as $key => $def ) {
		if ( 'color' === $def[1] ) {
			if ( $done ) {
				continue;
			}
			$done = true;
			echo '<div class="paulus-row paulus-row--colors"><div class="paulus-colors">';
			foreach ( $colors as $ckey => $cdef ) {
				printf( '<div class="paulus-colors__item"><label for="paulus-%1$s">%2$s</label>', esc_attr( $ckey ), esc_html( $cdef[0] ) );
				paulus_render_field( $ckey, $cdef );
				echo '</div>';
			}
			echo '</div></div>';
			continue;
		}
		$group = in_array( $def[1], array( 'scheme', 'image' ), true );
		printf( '<div class="paulus-row paulus-row--%s">', esc_attr( $def[1] ) );
		if ( $group ) {
			// Radio cards: the label names the group, the first card takes the id.
			printf( '<div class="paulus-row__label"><label for="paulus-%s" id="paulus-%s-label">%s</label></div>', esc_attr( $key ), esc_attr( $key ), esc_html( $def[0] ) );
			printf( '<div class="paulus-row__control" role="radiogroup" aria-labelledby="paulus-%s-label">', esc_attr( $key ) );
		} else {
			printf( '<div class="paulus-row__label"><label for="paulus-%s">%s</label></div><div class="paulus-row__control">', esc_attr( $key ), esc_html( $def[0] ) );
		}
		paulus_render_field( $key, $def );
		echo '</div></div>';
	}
}

/**
 * Options page.
 */
function paulus_options_page() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	$fields = paulus_fields();
	$meta   = paulus_options_tab_meta();
	$tabs   = array_map( static fn( $tab ) => $tab['label'], $fields ) + array( 'content' => __( 'Content', 'paulus' ) );
	$scheme = array_key_exists( paulus_option( 'scheme' ), paulus_schemes() ) ? paulus_option( 'scheme' ) : 'ochre';
	// The panel the screen opens on: ?tab= when it names a tab, else the first.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- tab selection only.
	$open   = isset( $_GET['tab'] ) && is_string( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
	$open   = isset( $tabs[ $open ] ) ? $open : (string) array_key_first( $tabs );
	$icon   = has_site_icon() ? get_site_icon_url( 96 ) : PAULUS_URI . '/assets/images/site-icon-96.avif';
	$card   = static function ( $tab_id ) use ( $tabs, $meta ) {
		printf(
			'<div class="paulus-card__head"><span class="dashicons %1$s" aria-hidden="true"></span><div><h2 class="paulus-card__title">%2$s</h2><p class="paulus-card__desc">%3$s</p></div></div>',
			esc_attr( $meta[ $tab_id ][0] ?? 'dashicons-admin-generic' ),
			esc_html( $tabs[ $tab_id ] ),
			esc_html( $meta[ $tab_id ][1] ?? '' )
		);
	};
	?>
	<div class="wrap paulus-wrap paulus-wrap--<?php echo esc_attr( $scheme ); ?>">
		<header class="paulus-masthead">
			<img class="paulus-masthead__icon" src="<?php echo esc_url( $icon ); ?>" alt="" width="56" height="56">
			<div class="paulus-masthead__text">
				<h1 class="paulus-masthead__title"><?php esc_html_e( 'Theme Options', 'paulus' ); ?></h1>
				<p class="paulus-masthead__desc"><?php echo esc_html( sprintf( /* translators: %s: site name. */ __( 'Settings for %s: the book, the look of the site, reading aids, security and content.', 'paulus' ), get_bloginfo( 'name' ) ) ); ?></p>
			</div>
			<span class="paulus-masthead__badge"><?php echo esc_html( sprintf( /* translators: %s: theme version. */ __( 'Version %s', 'paulus' ), PAULUS_VERSION ) ); ?></span>
			<a class="paulus-masthead__visit" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View site', 'paulus' ); ?><span class="screen-reader-text"> <?php esc_html_e( '(opens in a new tab)', 'paulus' ); ?></span></a>
		</header>
		<hr class="wp-header-end">
		<?php settings_errors(); ?>
		<?php paulus_importer_notice(); ?>

		<div class="paulus-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Theme Options sections', 'paulus' ); ?>">
			<?php foreach ( $tabs as $tab_id => $label ) : ?>
				<button type="button" role="tab" class="paulus-tab" id="tab-<?php echo esc_attr( $tab_id ); ?>" aria-controls="panel-<?php echo esc_attr( $tab_id ); ?>" aria-selected="<?php echo $open === $tab_id ? 'true' : 'false'; ?>"><span class="dashicons <?php echo esc_attr( $meta[ $tab_id ][0] ?? 'dashicons-admin-generic' ); ?>" aria-hidden="true"></span><span><?php echo esc_html( $label ); ?></span></button>
			<?php endforeach; ?>
		</div>

		<form method="post" action="options.php" class="paulus-form">
			<?php settings_fields( 'paulus_options_group' ); ?>
			<?php foreach ( $fields as $tab_id => $tab ) : ?>
				<section class="paulus-panel paulus-card" role="tabpanel" tabindex="0" id="panel-<?php echo esc_attr( $tab_id ); ?>" aria-labelledby="tab-<?php echo esc_attr( $tab_id ); ?>" <?php echo $open === $tab_id ? '' : 'hidden'; ?>>
					<?php $card( $tab_id ); ?>
					<div class="paulus-card__body">
						<?php paulus_render_rows( $tab['fields'] ); ?>
					</div>
					<?php
					/**
					 * After a tab's fields, inside the form (the Security tab adds its notes here).
					 *
					 * @param string $tab_id Tab.
					 */
					do_action( 'paulus_options_panel_after', $tab_id );
					?>
				</section>
			<?php endforeach; ?>
			<div class="paulus-save"<?php echo 'content' === $open ? ' hidden' : ''; ?>><?php submit_button( __( 'Save changes', 'paulus' ), 'primary paulus-button', 'submit', false ); ?></div>
		</form>
		<?php
		/** After the options form, outside it (the Security tab's tools, which carry forms of their own). */
		do_action( 'paulus_options_after_form' );
		?>

		<section class="paulus-panel paulus-card" role="tabpanel" tabindex="0" id="panel-content" aria-labelledby="tab-content" <?php echo 'content' === $open ? '' : 'hidden'; ?>>
			<?php $card( 'content' ); ?>
			<div class="paulus-card__body" id="paulus-content-body">
				<?php
				// The panel looks up every article and page of the manifest; build
				// it only on ?tab=content, or fetch it when the tab is first shown.
				if ( 'content' === $open ) {
					paulus_importer_panel();
					if ( function_exists( 'paulus_render_dropins_status' ) ) {
						paulus_render_dropins_status();
					}
				} else {
					printf(
						'<div data-paulus-lazy="paulus_importer_panel" data-nonce="%1$s"><p><a class="button" href="%2$s">%3$s</a></p></div>',
						esc_attr( wp_create_nonce( 'paulus_importer_panel' ) ),
						esc_url( admin_url( 'themes.php?page=paulus-options&tab=content#content' ) ),
						esc_html__( 'Open the Content tab', 'paulus' )
					);
				}
				?>
			</div>
		</section>

		<footer class="paulus-foot">
			<a href="<?php echo esc_url( PAULUS_URI . '/README.md' ); ?>"><?php esc_html_e( 'Theme reference (README)', 'paulus' ); ?></a>
			<a href="<?php echo esc_url( PAULUS_URI . '/UPGRADING.md' ); ?>"><?php esc_html_e( 'Before you update (UPGRADING)', 'paulus' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'site-health.php' ) ); ?>"><?php esc_html_e( 'Site Health', 'paulus' ); ?></a>
		</footer>
	</div>
	<?php
}

/**
 * The Content panel over admin-ajax, fetched by admin.js the first time the
 * tab is shown on a load that did not open on it.
 */
function paulus_ajax_importer_panel() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'You are not allowed to do this.', 'paulus' ) ), 403 );
	}
	check_ajax_referer( 'paulus_importer_panel', 'nonce' );
	ob_start();
	paulus_importer_panel();
	// The same panel as ?tab=content: the importer, then the error pages.
	if ( function_exists( 'paulus_render_dropins_status' ) ) {
		paulus_render_dropins_status();
	}
	wp_send_json_success( array( 'html' => ob_get_clean() ) );
}
add_action( 'wp_ajax_paulus_importer_panel', 'paulus_ajax_importer_panel' );
