<?php
/**
 * End-of-article symbol: a native form of the plugin End mark 4.4.0 by MENJ
 * (GPL v3 or later; a fork of End mark by Colin Temple). The symbol or image
 * at the end of an article, the placement rules (last paragraph, before or
 * after footnotes, append, CSS selector), the exclusions, the per-post
 * switch, the [endmark] shortcode and block, and the optional schema are
 * the plugin's own code, in inc/mark/. The settings screen is under
 * Settings, End mark.
 *
 * What changes in the theme: the stylesheets and script are
 * assets/css/paulus-mark-*.css and assets/js/paulus-mark-admin.js; the text domain
 * is the theme's; the admin screen no longer loads a font from Google; and
 * the data is the theme's own. On the first load without the plugin,
 * paulus_mark_import() copies the plugin's options to `paulus_endmark`,
 * `paulus_mark_presets` and `paulus_mark_version` and renames the
 * per-post meta `_paulus_mark_disable` to `_paulus_mark_disable`, so deleting
 * the plugin later (its uninstall removes the old names) takes nothing the
 * theme uses.
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
