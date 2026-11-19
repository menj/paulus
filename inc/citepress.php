<?php
/**
 * Citations: a native form of the plugin CitePress 3.0.1 by MENJ (GPL v2 or
 * later). The citation box with its 20 formats and six export types, the
 * [cite] and [cite_bibliography] shortcodes, the Citation Box block, the
 * Google Scholar tags and the optional analytics are the plugin's own code,
 * in inc/citepress/, with its settings screen (the CitePress menu). The
 * plugin's settings option and analytics table are taken over once under the
 * theme's own names (paulus_citepress_import()), so a site that used the
 * plugin carries on with its settings and its history.
 *
 * What changes in the theme:
 * - Paulus prints its own Open Graph tags and JSON-LD graph, so CitePress
 *   prints only the Google Scholar tags.
 * - The stylesheets and scripts are in assets/css/citepress-*.css and
 *   assets/js/citepress-*.js; the text domain is the theme's.
 * - No activation hook: the analytics table and the daily purge are set up
 *   by the plugin's own upgrade routine on the next admin page, and the
 *   purge is cleared when the theme is switched.
 *
 * While the plugin itself is active the theme adds nothing (the plugin
 * defines CITEPRESS_VERSION first), and inc/plugin-retire.php switches the
 * plugin off.
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
function paulus_citepress_ready() {
	foreach ( array( 'analytics', 'settings', 'shortcodes', 'metadata', 'assets' ) as $file ) {
		if ( ! is_readable( PAULUS_DIR . '/inc/citepress/' . $file . '.php' ) ) {
			return false;
		}
	}
	return true;
}

/**
 * Take over the plugin's data, once: its settings option and its analytics
 * table become the theme's own (`paulus_citepress`, `{prefix}paulus_citepress_analytics`),
 * so deleting the plugin later (its uninstall removes the old names) takes
 * nothing the theme uses. The table is renamed, not copied, so the history
 * stays. Runs only while the plugin is not loaded.
 */
function paulus_citepress_import() {
	if ( get_option( 'paulus_citepress_imported' ) ) {
		return;
	}
	$old = get_option( 'citepress_setting', null );
	if ( null !== $old && false === get_option( 'paulus_citepress', false ) ) {
		update_option( 'paulus_citepress', $old );
	}
	global $wpdb;
	if ( isset( $wpdb ) && is_object( $wpdb ) && ! empty( $wpdb->prefix ) ) {
		$prefix = preg_replace( '/[^A-Za-z0-9_]/', '', (string) $wpdb->prefix );
		$from   = $prefix . 'citepress_analytics';
		$to     = $prefix . 'paulus_citepress_analytics';
		$has    = static function ( $table ) use ( $wpdb ) {
			return $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		};
		$moved = true;
		if ( $has( $from ) && ! $has( $to ) ) {
			$moved = false !== $wpdb->query( "RENAME TABLE `{$from}` TO `{$to}`" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		} elseif ( $has( $from ) && $has( $to ) ) {
			// Both exist (the theme's table was made first): copy the history across.
			$moved = false !== $wpdb->query( "INSERT INTO `{$to}` SELECT * FROM `{$from}`" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
		if ( ! $moved ) {
			// The history was not moved: leave the flag unset so the step is tried again.
			return;
		}
	}
	update_option( 'paulus_citepress_imported', 1, false );
}

if ( defined( 'CITEPRESS_VERSION' ) ) {
	return;
}

// A half-uploaded theme: without every file the module stays out, and
// the plugin is not switched off (the ready function says so).
if ( ! paulus_citepress_ready() ) {
	return;
}

define( 'CITEPRESS_VERSION', '3.0.1' );
define( 'CITEPRESS_PLUGIN_DIR', PAULUS_DIR . '/inc/citepress/' );
define( 'CITEPRESS_PLUGIN_URL', PAULUS_URI . '/inc/citepress/' );
define( 'CITEPRESS_ASSETS_URL', PAULUS_URI . '/assets/' );

/** DB schema version of the analytics table; the upgrade routine in analytics.php compares it. */
define( 'CITEPRESS_DB_VERSION', '1.1' );

// The plugin kept its state in globals; the theme loads this file inside a
// function, so each one is declared global first.
global $citepress_default, $citepress_setting, $citepress_citation_data, $citepress_cited_posts,
	$citepress_ref_counter, $citepress_bibliography_mode, $citepress_instance_counter;

$citepress_default = apply_filters(
	'citepress_default_setting',
	array(
		'auto_display'                  => 'no',
		'display_position'              => 'bottom',
		'show_toggle'                   => 'yes',
		'enable_analytics'              => 'no',
		'require_consent_for_analytics' => 'yes',
		'analytics_cooldown_mode'       => 'session',
		'analytics_retention_days'      => '90',
		'post_types'                    => array( 'post' ),
		'excluded_posts'                => '',
		'show_google_scholar'           => 'yes',
		'show_jsonld'                   => 'yes',
		'show_og_academic'              => 'yes',
		'et_al_threshold'               => '3',
		'format_display_mode'           => 'dropdown',
		'box_style'                     => 'classic',
		'sharp_corners'                 => 'no',
		'color_scheme'                  => 'auto',
		'enabled_export_formats'        => array( 'bibtex', 'ris', 'endnote', 'csl-json', 'cff', 'dublin-core' ),
		'enabled_formats'               => array(
			'apa', 'mla', 'ieee', 'harvard', 'chicago', 'vancouver',
			'ama', 'asa', 'turabian', 'wikipedia',
			'acs', 'aip', 'nlm', 'aaa', 'apsa', 'oscola',
			'nature', 'acm', 'bluebook', 'iso690',
		),
	)
);
paulus_citepress_import();
$citepress_setting           = wp_parse_args( get_option( 'paulus_citepress' ), $citepress_default );
$citepress_citation_data     = array();
$citepress_cited_posts       = array();
$citepress_ref_counter       = 0;
$citepress_bibliography_mode = false;
$citepress_instance_counter  = 0;

require_once CITEPRESS_PLUGIN_DIR . 'analytics.php';
require_once CITEPRESS_PLUGIN_DIR . 'settings.php';
require_once CITEPRESS_PLUGIN_DIR . 'shortcodes.php';
require_once CITEPRESS_PLUGIN_DIR . 'metadata.php';
require_once CITEPRESS_PLUGIN_DIR . 'assets.php';

// With Paulus printing the Open Graph tags and the JSON-LD, the Scholar
// tags are the one head output of CitePress that stays.
add_filter( 'citepress_output_scholar_meta', '__return_true' );

// What the plugin's deactivation hook did.
add_action(
	'switch_theme',
	static function () {
		wp_clear_scheduled_hook( 'citepress_daily_purge_event' );
	}
);
