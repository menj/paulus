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
	wp_enqueue_style( 'paulus-admin', PAULUS_URI . '/assets/css/admin.css', array( 'paulus-fonts' ), PAULUS_VERSION );
	wp_enqueue_script( 'paulus-admin', PAULUS_URI . '/assets/js/admin.js', array(), PAULUS_VERSION, true );
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
 * Render one field.
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
			printf( '<textarea id="%s" name="%s" rows="4">%s</textarea>', esc_attr( $id ), esc_attr( $name ), esc_textarea( $value ) );
			break;
		case 'html':
			printf( '<textarea id="%s" name="%s" rows="4" class="code">%s</textarea>', esc_attr( $id ), esc_attr( $name ), esc_textarea( $value ) );
			break;
		case 'checkbox':
			printf( '<input type="hidden" name="%2$s" value="0"><label class="paulus-check"><input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s> %4$s</label>', esc_attr( $id ), esc_attr( $name ), checked( 1, (int) $value, false ), esc_html__( 'Enabled', 'paulus' ) );
			break;
		case 'scheme':
			echo '<div class="paulus-swatches">';
			foreach ( paulus_schemes() as $slug => $label ) {
				printf(
					'<label class="paulus-swatch paulus-swatch--%1$s"><input type="radio" name="%2$s" value="%1$s" %3$s><span class="paulus-swatch__chip" aria-hidden="true"></span><span>%4$s</span></label>',
					esc_attr( $slug ),
					esc_attr( $name ),
					checked( $value, $slug, false ),
					esc_html( $label )
				);
			}
			echo '</div>';
			break;
		case 'image':
			echo '<div class="paulus-images">';
			foreach ( paulus_images() as $slug => $label ) {
				printf(
					'<label class="paulus-image"><input type="radio" name="%1$s" value="%2$s" %3$s><img src="%4$s" alt="" loading="lazy"><span>%5$s</span></label>',
					esc_attr( $name ),
					esc_attr( $slug ),
					checked( $value, $slug, false ),
					esc_url( paulus_image_url( $slug ) ),
					esc_html( $label )
				);
			}
			echo '</div>';
			break;
		default:
			$type = in_array( $def[1], array( 'url', 'email' ), true ) ? $def[1] : 'text';
			printf( '<input type="%s" id="%s" name="%s" value="%s" class="regular-text">', esc_attr( $type ), esc_attr( $id ), esc_attr( $name ), esc_attr( $value ) );
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
	?>
	<?php
	$scheme = array_key_exists( paulus_option( 'scheme' ), paulus_schemes() ) ? paulus_option( 'scheme' ) : 'ochre';
	$icon   = has_site_icon() ? get_site_icon_url( 96 ) : PAULUS_URI . '/assets/images/site-icon-96.webp';
	?>
	<div class="wrap paulus-wrap paulus-wrap--<?php echo esc_attr( $scheme ); ?>">
		<header class="paulus-masthead">
			<img class="paulus-masthead__icon" src="<?php echo esc_url( $icon ); ?>" alt="" width="56" height="56">
			<div class="paulus-masthead__text">
				<p class="paulus-masthead__eyebrow"><?php echo esc_html( get_bloginfo( 'name' ) ); ?> <span aria-hidden="true">·</span> Paulus <?php echo esc_html( PAULUS_VERSION ); ?></p>
				<h1 class="paulus-masthead__title"><?php esc_html_e( 'Theme Options', 'paulus' ); ?></h1>
			</div>
			<a class="paulus-masthead__visit" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View site', 'paulus' ); ?></a>
		</header>
		<hr class="wp-header-end">
		<?php settings_errors(); ?>
		<?php paulus_importer_notice(); ?>

		<nav class="paulus-tabs" role="tablist">
			<?php $first = true; foreach ( $fields as $tab_id => $tab ) : ?>
				<button type="button" role="tab" class="paulus-tab" id="tab-<?php echo esc_attr( $tab_id ); ?>" aria-controls="panel-<?php echo esc_attr( $tab_id ); ?>" aria-selected="<?php echo $first ? 'true' : 'false'; ?>"><?php echo esc_html( $tab['label'] ); ?></button>
			<?php $first = false; endforeach; ?>
			<button type="button" role="tab" class="paulus-tab" id="tab-content" aria-controls="panel-content" aria-selected="false"><?php esc_html_e( 'Content', 'paulus' ); ?></button>
		</nav>

		<form method="post" action="options.php">
			<?php settings_fields( 'paulus_options_group' ); ?>
			<?php $first = true; foreach ( $fields as $tab_id => $tab ) : ?>
				<section class="paulus-panel" role="tabpanel" id="panel-<?php echo esc_attr( $tab_id ); ?>" aria-labelledby="tab-<?php echo esc_attr( $tab_id ); ?>" <?php echo $first ? '' : 'hidden'; ?>>
					<table class="form-table" role="presentation">
						<?php foreach ( $tab['fields'] as $key => $def ) : ?>
							<tr class="paulus-row paulus-row--<?php echo esc_attr( $def[1] ); ?>">
								<th scope="row"><label for="paulus-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $def[0] ); ?></label></th>
								<td><?php paulus_render_field( $key, $def ); ?></td>
							</tr>
						<?php endforeach; ?>
					</table>
				</section>
			<?php $first = false; endforeach; ?>
			<div class="paulus-save"><?php submit_button( __( 'Save changes', 'paulus' ), 'primary paulus-button', 'submit', false ); ?></div>
		</form>

		<section class="paulus-panel" role="tabpanel" id="panel-content" aria-labelledby="tab-content" hidden>
			<?php paulus_importer_panel(); ?>
		</section>
	</div>
	<?php
}
