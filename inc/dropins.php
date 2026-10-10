<?php
/**
 * The error pages WordPress shows when it cannot run the theme: the
 * maintenance page (maintenance.php), the database error page (db-error.php)
 * and the PHP fatal error page (php-error.php). WordPress looks for them in
 * the wp-content folder, so the theme copies them there from its dropins
 * folder, filling in the site name, the home address and the address of the
 * theme's stylesheets. They hold no PHP beyond the status headers and need
 * nothing from the database, so they work when the site does not. Their
 * stylesheet is assets/css/error-page.css.
 *
 * A file in wp-content that the theme did not write is left alone. A copy
 * written by the Paulus theme, or by the Abrahamic theme (whose files say
 * they are safe to delete), is replaced; switching themes removes them.
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

/**
 * The drop-in file names.
 *
 * @return string[]
 */
function paulus_dropin_files() {
	return array( 'maintenance.php', 'db-error.php', 'php-error.php' );
}

/**
 * Whether a file in wp-content was written by this theme (or is a copy the
 * Abrahamic theme wrote and marked safe to delete).
 *
 * @param string $path File path.
 * @return bool
 */
function paulus_dropin_is_ours( $path ) {
	$head = (string) file_get_contents( $path, false, null, 0, 300 );
	return false !== strpos( $head, 'Paulus error page drop-in' ) || false !== strpos( $head, 'Written by the Abrahamic theme' );
}

/**
 * Copy the drop-ins into wp-content, when the theme, site name or addresses
 * have changed since the last copy. Runs on any page load, so a fresh install
 * or update needs no visit to the admin.
 */
function paulus_install_dropins() {
	$theme_url = (string) wp_parse_url( PAULUS_URI, PHP_URL_PATH );
	$home_url  = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	$tokens    = array(
		'{{VERSION}}'   => PAULUS_VERSION,
		'{{SITE_NAME}}' => esc_html( wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) ),
		'{{THEME_URL}}' => esc_attr( $theme_url ),
		'{{HOME_URL}}'  => esc_attr( '' !== $home_url ? $home_url : '/' ),
	);
	$stamp     = md5( wp_json_encode( $tokens ) );
	if ( get_option( 'paulus_dropins_stamp' ) === $stamp ) {
		return;
	}
	foreach ( paulus_dropin_files() as $file ) {
		$source = PAULUS_DIR . '/dropins/' . $file;
		$target = WP_CONTENT_DIR . '/' . $file;
		if ( ! is_readable( $source ) || ( file_exists( $target ) && ! paulus_dropin_is_ours( $target ) ) ) {
			continue;
		}
		file_put_contents( $target, strtr( (string) file_get_contents( $source ), $tokens ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
	update_option( 'paulus_dropins_stamp', $stamp, false );
}
add_action( 'init', 'paulus_install_dropins', 20 );

/**
 * Remove the drop-ins when the theme is switched off: their stylesheet goes
 * with it.
 */
function paulus_remove_dropins() {
	foreach ( paulus_dropin_files() as $file ) {
		$target = WP_CONTENT_DIR . '/' . $file;
		if ( file_exists( $target ) && paulus_dropin_is_ours( $target ) ) {
			unlink( $target ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
	}
	delete_option( 'paulus_dropins_stamp' );
}
add_action( 'switch_theme', 'paulus_remove_dropins' );
