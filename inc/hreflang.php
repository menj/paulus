<?php
/**
 * Hreflang tags: a native form of the plugin Hreflang 3.0.1 (GPL v2 or
 * later). The hreflang meta boxes for posts, pages, custom types and
 * taxonomy terms, the `<link rel="alternate" hreflang>` tags in the head, the
 * per-page HTML lang attribute, the bulk editor, the code generator, the
 * validation tools and the tabbed Hreflang screen with its four colour
 * schemes are the plugin's own code, in inc/hreflang/.
 *
 * What changes in the theme: the stylesheet and scripts are
 * assets/css/paulus-hreflang-admin.css and assets/js/paulus-hreflang-*.js; the text
 * domain is the theme's; the taxonomy fields are added on `init` (a theme
 * loads after `plugins_loaded`); and the settings are the theme's own. On the
 * first load without the plugin, paulus_hreflang_import() copies the
 * plugin's five options to `paulus_hreflang_post_types`, `_admin_bar`,
 * `_html_lang`, `_woo_shop` and `_scheme`, so deleting the plugin later (its
 * uninstall removes the old names) takes nothing the theme uses. The tags
 * themselves are post and term meta (`hreflang-<code>` and
 * `_hrefmaster_html_lang`), which the plugin's uninstall leaves alone, so
 * they keep their names.
 *
 * While the plugin itself is active the theme adds nothing, and
 * inc/plugin-retire.php switches the plugin off.
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
