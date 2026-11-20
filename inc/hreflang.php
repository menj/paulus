<?php
/**
 * Hreflang tags: meta boxes for posts, pages, custom types and taxonomy terms,
 * the `<link rel="alternate" hreflang>` tags in the head, the per-page HTML lang
 * attribute, a bulk editor, a code generator, validation tools and a tabbed
 * Hreflang screen with four colour schemes. The code is in inc/hreflang/; the
 * stylesheet and scripts are assets/css/paulus-hreflang-admin.css and
 * assets/js/paulus-hreflang-*.js. The taxonomy fields are added on `init`.
 *
 * On the first load, paulus_hreflang_import() takes over the settings of an
 * earlier standalone copy as `paulus_hreflang_post_types`, `_admin_bar`,
 * `_html_lang`, `_woo_shop` and `_scheme`. The tags themselves are post and term
 * meta (`hreflang-<code>`), which keep their names.
 *
 * inc/plugin-retire.php switches a leftover copy of that earlier plugin off, and
 * this module adds nothing while it is still loaded.
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

/**
 * Defined before anything can stand aside: inc/plugin-retire.php switches
 * the plugin off only while this module is loaded.
 *
 * @return bool
 */
function paulus_hreflang_ready() {
	foreach ( array( 'init', 'admin', 'includes/notices', 'includes/variables', 'includes/functions', 'tabs/dashboard', 'tabs/bulk-editor', 'tabs/generator', 'tabs/validation-tool', 'tabs/settings' ) as $file ) {
		if ( ! is_readable( PAULUS_DIR . '/inc/hreflang/' . $file . '.php' ) ) {
			return false;
		}
	}
	return true;
}

/**
 * Take over the plugin's settings, once. Runs only while the plugin is not loaded.
 */
function paulus_hreflang_import() {
	if ( get_option( 'paulus_hreflang_imported' ) ) {
		return;
	}
	$map = array(
		'hrefmaster_post_types'       => 'paulus_hreflang_post_types',
		'hrefmaster_enable_admin_bar' => 'paulus_hreflang_admin_bar',
		'hrefmaster_enable_html_lang' => 'paulus_hreflang_html_lang',
		'hrefmaster_woo_shop_page'    => 'paulus_hreflang_woo_shop',
		'hrefmaster_color_scheme'     => 'paulus_hreflang_scheme',
	);
	foreach ( $map as $old => $new ) {
		$value = get_option( $old, null );
		if ( null !== $value && false === get_option( $new, false ) ) {
			update_option( $new, $value );
		}
	}
	update_option( 'paulus_hreflang_imported', 1, false );
}

if ( defined( 'HREFMASTER_VERSION' ) ) {
	return;
}

// A half-uploaded theme: without every file the module stays out, and
// the plugin is not switched off (the ready function says so).
if ( ! paulus_hreflang_ready() ) {
	return;
}

define( 'PAULUS_HREFLANG_VERSION', '3.0.1' );
define( 'PAULUS_HREFLANG_PATH', PAULUS_DIR . '/inc/hreflang/' );

paulus_hreflang_import();

require_once PAULUS_HREFLANG_PATH . 'init.php';
