<?php
/**
 * Wikipedia previews: a native form of the plugin Wikipedia Preview 1.17.1
 * (Wikimedia Foundation, MIT licence; the licence is kept in
 * inc/wikipedia-preview/license.txt). A reader who clicks or hovers over a
 * link to Wikipedia sees a card with the start of the article. The preview
 * library, its stylesheet and the editor's link button are the plugin's own
 * files, now assets/js/wikipedia-preview*.js and assets/css/wikipedia-preview*.css.
 *
 * What the theme leaves out: the plugin's review banner, its welcome page and
 * its activation redirect, which asked for a review on wordpress.org and
 * served a plugin's first run. What stays: the per-post "detect links"
 * setting (post meta `wikipediapreview_detectlinks`, on by default), the
 * filter `wikipediapreview_enabled_post_types`, and the editor's REST routes
 * under `wikipediapreview/v1`, which the editor script calls. The plugin
 * stores nothing the theme needs, so there is nothing to copy.
 *
 * The cards load text and images from Wikipedia in the reader's browser, so
 * the reader's address reaches Wikimedia when a card opens.
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
function paulus_wikipreview_ready() {
	return true;
}

if ( defined( 'WIKIPEDIA_PREVIEW_PLUGIN_VERSION' ) ) {
	return;
}

define( 'WIKIPEDIA_PREVIEW_PLUGIN_VERSION', '1.17.1' );

/**
 * The post types the previews work on: the public ones, or what the filter says.
 *
 * @param string $post_type Post type.
 * @return bool
 */
function paulus_wikipreview_enabled( $post_type ) {
	$public = array_keys( get_post_types( array( 'public' => true ) ) );
	return in_array( $post_type, (array) apply_filters( 'wikipediapreview_enabled_post_types', $public ), true );
}

/** The reader's side: the library, its start-up and the link style. */
function paulus_wikipreview_front() {
	if ( ! paulus_wikipreview_enabled( get_post_type() ) ) {
		return;
	}
	$v = WIKIPEDIA_PREVIEW_PLUGIN_VERSION;
	wp_enqueue_script( 'wikipedia-preview', PAULUS_URI . '/assets/js/wikipedia-preview.js', array(), $v, true );
	wp_enqueue_script( 'wikipedia-preview-init', PAULUS_URI . '/assets/js/wikipedia-preview-init.js', array( 'wikipedia-preview' ), $v, true );
	$id = get_the_ID();
	wp_localize_script(
		'wikipedia-preview-init',
		'wikipediapreview_init_options',
		array( 'detectLinks' => $id ? (bool) get_post_meta( $id, 'wikipediapreview_detectlinks', true ) : true )
	);
	wp_enqueue_style( 'wikipedia-preview-link-style', PAULUS_URI . '/assets/css/wikipedia-preview-link.css', array(), $v );
}
add_action( 'wp_enqueue_scripts', 'paulus_wikipreview_front' );

/** The editor's side: the link button and its styles. */
function paulus_wikipreview_editor() {
	if ( ! paulus_wikipreview_enabled( get_post_type() ) ) {
		return;
	}
	$v = WIKIPEDIA_PREVIEW_PLUGIN_VERSION;
	wp_enqueue_script( 'wikipedia-preview-edit-link', PAULUS_URI . '/assets/js/wikipedia-preview-editor.js', array(), $v, true );
	// The editor script reads this object; it has no file of its own.
	wp_register_script( 'wikipedia-preview-tooltip', false, array(), $v, true );
	wp_enqueue_script( 'wikipedia-preview-tooltip' );
	wp_localize_script(
		'wikipedia-preview-tooltip',
		'wikipediapreviewCustomTooltip',
		array(
			'tooltipCount'    => (int) get_option( 'wikipediapreview_tooltip_count', 0 ),
			'tooltipDuration' => (int) get_option( 'wikipediapreview_tooltip_duration', 0 ),
			'nonce'           => wp_create_nonce( 'wp_rest' ),
		)
	);
	wp_enqueue_style( 'wikipedia-preview-core-style', PAULUS_URI . '/assets/css/wikipedia-preview.css', array(), $v );
	wp_enqueue_style( 'wikipedia-preview-style', PAULUS_URI . '/assets/css/wikipedia-preview-editor.css', array(), $v );
	wp_enqueue_style( 'wikipedia-preview-link-style', PAULUS_URI . '/assets/css/wikipedia-preview-link.css', array(), $v );
}
add_action( 'enqueue_block_editor_assets', 'paulus_wikipreview_editor' );

/** The per-post switch for detecting Wikipedia links: on unless an editor turns it off. */
function paulus_wikipreview_meta() {
	register_post_meta(
		'',
		'wikipediapreview_detectlinks',
		array(
			'show_in_rest'  => true,
			'auth_callback' => true,
			'single'        => true,
			'type'          => 'boolean',
			'default'       => true,
		)
	);
}
add_action( 'init', 'paulus_wikipreview_meta' );

/** The editor's tooltip counters, as the plugin's routes kept them. */
function paulus_wikipreview_rest() {
	$can = static function () {
		return current_user_can( 'manage_options' );
	};
	register_rest_route(
		'wikipediapreview/v1',
		'/count/',
		array(
			'methods'             => 'POST',
			'callback'            => static function () {
				update_option( 'wikipediapreview_tooltip_count', (int) get_option( 'wikipediapreview_tooltip_count', 0 ) + 1 );
			},
			'permission_callback' => $can,
		)
	);
	register_rest_route(
		'wikipediapreview/v1',
		'/duration/',
		array(
			'methods'             => 'POST',
			'callback'            => static function () {
				update_option( 'wikipediapreview_tooltip_duration', 1 );
			},
			'permission_callback' => $can,
		)
	);
	register_rest_route(
		'wikipediapreview/v1',
		'/reset/',
		array(
			'methods'             => 'POST',
			'callback'            => static function () {
				update_option( 'wikipediapreview_tooltip_count', 0 );
				update_option( 'wikipediapreview_tooltip_duration', 0 );
			},
			'permission_callback' => $can,
		)
	);
}
add_action( 'rest_api_init', 'paulus_wikipreview_rest' );
