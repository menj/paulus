<?php
/**
 * Citations: a native form of the plugin Citation 3.0.1 by MENJ (GPL v2 or
 * later). The citation box with its 20 formats and six export types, the
 * [cite] and [cite_bibliography] shortcodes, the Citation Box block, the
 * Google Scholar tags and the optional analytics are the plugin's own code,
 * in inc/cite/, with its settings screen (the Citation menu). The
 * plugin's settings option and analytics table are taken over once under the
 * theme's own names (paulus_cite_import()), so a site that used the
 * plugin carries on with its settings and its history.
 *
 * What changes in the theme:
 * - Paulus prints its own Open Graph tags and JSON-LD graph, so Citation
 *   prints only the Google Scholar tags.
 * - The stylesheets and scripts are in assets/css/paulus-cite-*.css and
 *   assets/js/paulus-cite-*.js; the text domain is the theme's.
 * - No activation hook: the analytics table and the daily purge are set up
 *   by the plugin's own upgrade routine on the next admin page, and the
 *   purge is cleared when the theme is switched.
 *
 * While the plugin itself is active the theme adds nothing (the plugin
 * defines PAULUS_CITE_VERSION first), and inc/plugin-retire.php switches the
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
function paulus_cite_ready() {
	foreach ( array( 'analytics', 'settings', 'shortcodes', 'metadata', 'assets' ) as $file ) {
		if ( ! is_readable( PAULUS_DIR . '/inc/cite/' . $file . '.php' ) ) {
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
function paulus_cite_import() {
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
		$from   = $prefix . 'paulus_cite_analytics';
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

if ( defined( 'CITEPRESS_VERSION' ) ) { // The plugin itself is loaded: stand aside.
	return;
}

// A half-uploaded theme: without every file the module stays out, and
// the plugin is not switched off (the ready function says so).
if ( ! paulus_cite_ready() ) {
	return;
}

define( 'PAULUS_CITE_VERSION', '3.0.1' );
define( 'PAULUS_CITE_PLUGIN_DIR', PAULUS_DIR . '/inc/cite/' );
define( 'PAULUS_CITE_PLUGIN_URL', PAULUS_URI . '/inc/cite/' );
define( 'PAULUS_CITE_ASSETS_URL', PAULUS_URI . '/assets/' );

/** DB schema version of the analytics table; the upgrade routine in analytics.php compares it. */
define( 'PAULUS_CITE_DB_VERSION', '1.1' );

// The plugin kept its state in globals; the theme loads this file inside a
// function, so each one is declared global first.
global $paulus_cite_default, $paulus_cite_setting, $paulus_cite_citation_data, $paulus_cite_cited_posts,
	$paulus_cite_ref_counter, $paulus_cite_bibliography_mode, $paulus_cite_instance_counter;

$paulus_cite_default = apply_filters(
	'paulus_cite_default_setting',
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
paulus_cite_import();
$paulus_cite_setting           = wp_parse_args( get_option( 'paulus_citepress' ), $paulus_cite_default );
$paulus_cite_citation_data     = array();
$paulus_cite_cited_posts       = array();
$paulus_cite_ref_counter       = 0;
$paulus_cite_bibliography_mode = false;
$paulus_cite_instance_counter  = 0;

require_once PAULUS_CITE_PLUGIN_DIR . 'analytics.php';
require_once PAULUS_CITE_PLUGIN_DIR . 'settings.php';
require_once PAULUS_CITE_PLUGIN_DIR . 'shortcodes.php';
require_once PAULUS_CITE_PLUGIN_DIR . 'metadata.php';
require_once PAULUS_CITE_PLUGIN_DIR . 'assets.php';

// With Paulus printing the Open Graph tags and the JSON-LD, the Scholar
// tags are the one head output of Citation that stays.
add_filter( 'paulus_cite_output_scholar_meta', '__return_true' );

// What the plugin's deactivation hook did.
add_action(
	'switch_theme',
	static function () {
		wp_clear_scheduled_hook( 'paulus_cite_daily_purge_event' );
	}
);
