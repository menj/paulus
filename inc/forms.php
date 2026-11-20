<?php
/**
 * Contact forms: a native form of the plugin Forms 6.2.5 (Rock
 * Lobster Inc., GPL v2 or later). The form editor, the `[contact-form-7]`
 * shortcode, the form tags, mail, validation, the REST route, the block, the
 * spam modules and the Contact menu are the plugin's own code, in inc/forms/,
 * with its licence beside it.
 *
 * What changes in the theme: the plugin's files stay in their own folders
 * (includes/js, includes/css, admin/includes and modules), so that its asset
 * manifests keep working; paulus_form_plugin_url() points at inc/forms/; the plugin's
 * `plugins_loaded` and activation hooks are replaced by a direct call to
 * paulus_forms_boot() here (a theme loads after `plugins_loaded`) and a one-time
 * paulus_form_install() on `admin_init`. The forms are the `paulus_form_contact_form`
 * post type and the `paulus-form` option, and keep those names: the plugin's
 * uninstall deletes them only when WPCF7_VERSION is not defined, and the
 * theme defines it, so deleting the plugin takes nothing the theme uses.
 *
 * The theme no longer receives the plugin's own security updates. Replace
 * inc/forms/ when the plugin issues a fix.
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
function paulus_forms_ready() {
	foreach ( array( 'load', 'includes/functions', 'includes/controller', 'admin/admin', 'vendor/autoload', 'modules/text' ) as $file ) {
		if ( ! is_readable( PAULUS_DIR . '/inc/forms/' . $file . '.php' ) ) {
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
if ( ! paulus_forms_ready() ) {
	return;
}

define( 'PAULUS_FORM_VERSION', '6.2.5' );
// The one name kept from the plugin: while it is defined, a leftover copy of the plugin's uninstall routine keeps the forms.
define( 'WPCF7_VERSION', PAULUS_FORM_VERSION );
define( 'PAULUS_FORM_REQUIRED_WP_VERSION', '7.1' );
define( 'PAULUS_FORM_TEXT_DOMAIN', 'paulus' );
define( 'PAULUS_FORM_PLUGIN', PAULUS_DIR . '/inc/forms/load.php' );
define( 'PAULUS_FORM_PLUGIN_NAME', 'paulus' );
define( 'PAULUS_FORM_PLUGIN_DIR', PAULUS_DIR . '/inc/forms' );
define( 'PAULUS_FORM_PLUGIN_MODULES_DIR', PAULUS_FORM_PLUGIN_DIR . '/modules' );
define( 'PAULUS_FORM_PLUGIN_URL', untrailingslashit( PAULUS_URI ) . '/inc/forms' );

foreach ( array(
	'PAULUS_FORM_LOAD_JS'                    => true,
	'PAULUS_FORM_LOAD_CSS'                   => true,
	'PAULUS_FORM_AUTOP'                      => true,
	'PAULUS_FORM_USE_PIPE'                   => true,
	'PAULUS_FORM_ADMIN_READ_CAPABILITY'      => 'edit_posts',
	'PAULUS_FORM_ADMIN_READ_WRITE_CAPABILITY' => 'publish_pages',
	'PAULUS_FORM_VERIFY_NONCE'               => false,
	'PAULUS_FORM_USE_REALLY_SIMPLE_CAPTCHA'  => false,
	'PAULUS_FORM_VALIDATE_CONFIGURATION'     => true,
) as $paulus_forms_name => $paulus_forms_value ) {
	if ( ! defined( $paulus_forms_name ) ) {
		define( $paulus_forms_name, $paulus_forms_value );
	}
}

require_once PAULUS_FORM_PLUGIN_DIR . '/load.php';

// What the plugin did on `plugins_loaded`.
paulus_forms_boot();

// What the plugin did on activation, once, when no forms exist yet.
add_action( 'admin_init', function () {
	if ( false === get_option( 'wpcf7' ) ) {
		paulus_form_install();
	}
}, 9 );
