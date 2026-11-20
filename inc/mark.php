<?php
/**
 * The end mark: a small image at the end of an article's text, placed in the
 * last paragraph (never in the questions or the notes), with exclusions, a
 * per-post switch, the [endmark] shortcode and block and the optional schema.
 * The settings screen is under Settings, End mark; it controls where and when
 * the mark appears, not what it shows. The code is in inc/mark/; the
 * stylesheets and script are assets/css/paulus-mark-*.css and
 * assets/js/paulus-mark-admin.js.
 *
 * On the first load, paulus_mark_import() takes over the settings and the
 * per-post switch of an earlier standalone copy, under the theme's own names.
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
function paulus_mark_ready() {
	foreach ( array( 'class-mark-plugin', 'class-mark-admin', 'class-mark-frontend', 'class-mark-schema', 'class-mark-block', 'class-mark-meta', 'templates/admin-settings' ) as $file ) {
		if ( ! is_readable( PAULUS_DIR . '/inc/mark/' . $file . '.php' ) ) {
			return false;
		}
	}
	return true;
}

/**
 * Take over the plugin's data, once. Runs only while the plugin is not loaded.
 */
function paulus_mark_import() {
	if ( get_option( 'paulus_endmark_imported' ) ) {
		return;
	}
	$map = array(
		'endmark_settings' => 'paulus_endmark',
		'endmark_presets'  => 'paulus_endmark_presets',
		'endmark_version'  => 'paulus_endmark_version',
	);
	foreach ( $map as $old => $new ) {
		$value = get_option( $old, null );
		if ( null !== $value && false === get_option( $new, false ) ) {
			update_option( $new, $value );
		}
	}
	if ( is_multisite() ) {
		$network = get_site_option( 'paulus_mark_network_settings', null );
		if ( null !== $network && false === get_site_option( 'paulus_endmark_network', false ) ) {
			update_site_option( 'paulus_endmark_network', $network );
		}
	}
	global $wpdb;
	if ( isset( $wpdb ) && is_object( $wpdb ) && ! empty( $wpdb->postmeta ) ) {
		// A post that already has the new key keeps it; the old row stays.
		$wpdb->query( $wpdb->prepare( "UPDATE IGNORE {$wpdb->postmeta} SET meta_key = %s WHERE meta_key = %s", '_paulus_endmark_disable', '_endmark_disable' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
	update_option( 'paulus_endmark_imported', 1, false );
}

if ( class_exists( 'Endmark_Plugin' ) ) {
	return;
}

// A half-uploaded theme: without every file the module stays out, and
// the plugin is not switched off (the ready function says so).
if ( ! paulus_mark_ready() ) {
	return;
}

define( 'PAULUS_MARK_PLUGIN_DIR', PAULUS_DIR . '/inc/mark/' );

paulus_mark_import();

require_once PAULUS_MARK_PLUGIN_DIR . 'class-mark-plugin.php';
require_once PAULUS_MARK_PLUGIN_DIR . 'class-mark-admin.php';
require_once PAULUS_MARK_PLUGIN_DIR . 'class-mark-frontend.php';
require_once PAULUS_MARK_PLUGIN_DIR . 'class-mark-schema.php';
require_once PAULUS_MARK_PLUGIN_DIR . 'class-mark-block.php';
require_once PAULUS_MARK_PLUGIN_DIR . 'class-mark-meta.php';

Paulus_Mark::get_instance();
