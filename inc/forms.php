<?php
/**
 * Forms: the form editor, the `[contact-form-7]` shortcode, form tags, mail,
 * validation, the REST route, the block, the spam modules and the Contact menu.
 * The code is in inc/forms/, with its licence beside it. The forms are stored as
 * the `wpcf7_contact_form` post type and the `wpcf7` option, and the shortcode
 * keeps its name, so a site's existing forms and pages carry on.
 *
 * A theme loads after `plugins_loaded`, so paulus_forms_boot() is called here
 * and paulus_form_install() runs once on `admin_init`. WPCF7_VERSION is defined
 * as well: while it is defined, a leftover copy of the earlier plugin's
 * uninstall routine keeps the forms.
 *
 * The theme does not receive security updates for this code from anywhere: replace
 * inc/forms/ when a fix is needed. inc/plugin-retire.php switches a leftover copy
 * of the earlier plugin off, and this module adds nothing while it is still loaded.
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
