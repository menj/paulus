<?php
/**
 * Contact forms: a native form of the plugin Contact Form 7 6.2.5 (Rock
 * Lobster Inc., GPL v2 or later). The form editor, the `[contact-form-7]`
 * shortcode, the form tags, mail, validation, the REST route, the block, the
 * spam modules and the Contact menu are the plugin's own code, in inc/cf7/,
 * with its licence beside it.
 *
 * What changes in the theme: the plugin's files stay in their own folders
 * (includes/js, includes/css, admin/includes and modules), so that its asset
 * manifests keep working; wpcf7_plugin_url() points at inc/cf7/; the plugin's
 * `plugins_loaded` and activation hooks are replaced by a direct call to
 * wpcf7() here (a theme loads after `plugins_loaded`) and a one-time
 * wpcf7_install() on `admin_init`. The forms are the `wpcf7_contact_form`
 * post type and the `wpcf7` option, and keep those names: the plugin's
 * uninstall deletes them only when WPCF7_VERSION is not defined, and the
 * theme defines it, so deleting the plugin takes nothing the theme uses.
 *
 * The theme no longer receives the plugin's own security updates. Replace
 * inc/cf7/ when the plugin issues a fix.
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
function paulus_cf7_ready() {
	foreach ( array( 'load', 'includes/functions', 'includes/controller', 'admin/admin', 'vendor/autoload', 'modules/text' ) as $file ) {
		if ( ! is_readable( PAULUS_DIR . '/inc/cf7/' . $file . '.php' ) ) {
			return false;
		}
	}
	return true;
}

if ( defined( 'WPCF7_VERSION' ) ) {
	return;
}

// A half-uploaded theme: without every file the module stays out, and
// the plugin is not switched off (the ready function says so).
if ( ! paulus_cf7_ready() ) {
	return;
}

define( 'WPCF7_VERSION', '6.2.5' );
define( 'WPCF7_REQUIRED_WP_VERSION', '7.1' );
define( 'WPCF7_TEXT_DOMAIN', 'contact-form-7' );
define( 'WPCF7_PLUGIN', PAULUS_DIR . '/inc/cf7/load.php' );
define( 'WPCF7_PLUGIN_BASENAME', 'contact-form-7/wp-contact-form-7.php' );
define( 'WPCF7_PLUGIN_NAME', 'contact-form-7' );
define( 'WPCF7_PLUGIN_DIR', PAULUS_DIR . '/inc/cf7' );
define( 'WPCF7_PLUGIN_MODULES_DIR', WPCF7_PLUGIN_DIR . '/modules' );
define( 'WPCF7_PLUGIN_URL', untrailingslashit( PAULUS_URI ) . '/inc/cf7' );

foreach ( array(
	'WPCF7_LOAD_JS'                    => true,
	'WPCF7_LOAD_CSS'                   => true,
	'WPCF7_AUTOP'                      => true,
	'WPCF7_USE_PIPE'                   => true,
	'WPCF7_ADMIN_READ_CAPABILITY'      => 'edit_posts',
	'WPCF7_ADMIN_READ_WRITE_CAPABILITY' => 'publish_pages',
	'WPCF7_VERIFY_NONCE'               => false,
	'WPCF7_USE_REALLY_SIMPLE_CAPTCHA'  => false,
	'WPCF7_VALIDATE_CONFIGURATION'     => true,
) as $paulus_cf7_name => $paulus_cf7_value ) {
	if ( ! defined( $paulus_cf7_name ) ) {
		define( $paulus_cf7_name, $paulus_cf7_value );
	}
}

require_once WPCF7_PLUGIN_DIR . '/load.php';

// What the plugin did on `plugins_loaded`.
wpcf7();

// What the plugin did on activation, once, when no forms exist yet.
add_action( 'admin_init', function () {
	if ( false === get_option( 'wpcf7' ) ) {
		wpcf7_install();
	}
}, 9 );
