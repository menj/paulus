<?php
/**
 * End-of-article symbol: a native form of the plugin Endmark 4.4.0 by MENJ
 * (GPL v3 or later; a fork of Endmark by Colin Temple). The symbol or image
 * at the end of an article, the placement rules (last paragraph, before or
 * after footnotes, append, CSS selector), the exclusions, the per-post
 * switch, the [endmark] shortcode and block, and the optional schema are
 * the plugin's own code, in inc/endmark/. The settings screen is under
 * Settings, Endmark.
 *
 * What changes in the theme: the stylesheets and script are
 * assets/css/endmark-*.css and assets/js/endmark-admin.js; the text domain
 * is the theme's; the admin screen no longer loads a font from Google; and
 * the data is the theme's own. On the first load without the plugin,
 * paulus_endmark_import() copies the plugin's options to `paulus_endmark`,
 * `paulus_endmark_presets` and `paulus_endmark_version` and renames the
 * per-post meta `_endmark_disable` to `_paulus_endmark_disable`, so deleting
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
function paulus_endmark_ready() {
	return true;
}

/**
 * Take over the plugin's data, once. Runs only while the plugin is not loaded.
 */
function paulus_endmark_import() {
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
		$network = get_site_option( 'endmark_network_settings', null );
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

/**
 * Give the theme's own end mark (assets/images/endmark.svg) to a site that
 * still has the plain default symbol, once. A symbol, an image or a post
 * type list the owner chose is left alone, and so is every later change.
 */
function paulus_endmark_adopt_image() {
	if ( get_option( 'paulus_endmark_image_adopted' ) ) {
		return;
	}
	$saved = get_option( 'paulus_endmark', null );
	if ( is_array( $saved ) && ( ( $saved['type'] ?? 'symbol' ) !== 'symbol' || ( $saved['symbol'] ?? '∎' ) !== '∎' ) ) {
		update_option( 'paulus_endmark_image_adopted', 1, false );
		return;
	}
	$saved               = is_array( $saved ) ? $saved : array();
	$saved['type']       = 'image';
	$saved['image_url']  = PAULUS_URI . '/assets/images/endmark.svg';
	$saved['image_id']   = 0;
	$saved['allow_svg']  = true;
	$saved['image_size_px'] = 28;
	$types               = isset( $saved['post_types'] ) ? (array) $saved['post_types'] : array( 'post' );
	$saved['post_types'] = array_values( array_unique( array_merge( $types, array( 'post', 'paulus_journal' ) ) ) );
	update_option( 'paulus_endmark', $saved );
	update_option( 'paulus_endmark_image_adopted', 1, false );
}

if ( class_exists( 'Endmark_Plugin' ) ) {
	return;
}

// A half-uploaded theme: without every file the module stays out.
foreach ( array( 'class-endmark-plugin', 'class-endmark-admin', 'class-endmark-frontend', 'class-endmark-schema', 'class-endmark-block', 'class-endmark-meta', 'templates/admin-settings' ) as $paulus_em_file ) {
	if ( ! is_readable( PAULUS_DIR . '/inc/endmark/' . $paulus_em_file . '.php' ) ) {
		return;
	}
}

define( 'ENDMARK_PLUGIN_DIR', PAULUS_DIR . '/inc/endmark/' );

paulus_endmark_import();
paulus_endmark_adopt_image();

require_once ENDMARK_PLUGIN_DIR . 'class-endmark-plugin.php';
require_once ENDMARK_PLUGIN_DIR . 'class-endmark-admin.php';
require_once ENDMARK_PLUGIN_DIR . 'class-endmark-frontend.php';
require_once ENDMARK_PLUGIN_DIR . 'class-endmark-schema.php';
require_once ENDMARK_PLUGIN_DIR . 'class-endmark-block.php';
require_once ENDMARK_PLUGIN_DIR . 'class-endmark-meta.php';

Endmark_Plugin::get_instance();
