<?php
/**
 * Justified text: a native form of the plugin Auto Justify Content by MENJ
 * (GPL v2 or later). It keeps the plugin's six settings and reads the
 * plugin's own options once (ajc_enabled, ajc_scope, ajc_hyphen, ajc_mobile,
 * ajc_fallback, ajc_exclude), so a site that used the plugin keeps its
 * choices. The settings live in Theme Options, Justify.
 *
 * The rules are a stylesheet, assets/css/justify.css, that switches on the
 * body classes added here; only the excluded selectors are written inline.
 * While the plugin itself is active the theme adds nothing, and
 * inc/plugin-retire.php switches the plugin off.
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

/**
 * The plugin's option names and the theme option each one becomes.
 *
 * @return array<string,string>
 */
function paulus_justify_plugin_options() {
	return array(
		'ajc_enabled'  => 'justify_enabled',
		'ajc_scope'    => 'justify_scope',
		'ajc_hyphen'   => 'justify_hyphen',
		'ajc_mobile'   => 'justify_mobile',
		'ajc_fallback' => 'justify_fallback',
		'ajc_exclude'  => 'justify_exclude',
	);
}

/**
 * Selectors to leave unjustified: letters, digits and the characters of
 * simple CSS selectors, split on commas. Anything else is dropped.
 *
 * @param mixed $value Raw field.
 * @return string
 */
function paulus_justify_sanitize_exclude( $value ) {
	$value = (string) $value;
	$value = str_replace( array( "\r", "\n", "\t" ), ' ', $value );
	$value = preg_replace( '/\s+/', ' ', $value );
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return '';
	}
	$value = preg_replace( '/[^a-zA-Z0-9\.\#\-\_\s\,\:\[\]\=\(\)\"\']/', '', $value );
	$parts = array_filter( array_map( 'trim', explode( ',', (string) $value ) ) );
	return implode( ', ', $parts );
}

/**
 * Whether the module is whole: inc/plugin-retire.php switches the plugin off
 * only while this answers true.
 *
 * @return bool
 */
function paulus_justify_ready() {
	return is_readable( PAULUS_DIR . '/assets/css/justify.css' );
}

/**
 * Copy the plugin's stored settings into Theme Options, once. A setting the
 * plugin never stored keeps the theme's default. The plugin's own options
 * stay where they are (the plugin's uninstall removes them if the plugin is
 * deleted later; the theme no longer reads them).
 */
function paulus_justify_import() {
	if ( get_option( 'paulus_justify_imported' ) ) {
		return;
	}
	$found = array();
	foreach ( paulus_justify_plugin_options() as $old => $new ) {
		$value = get_option( $old, null );
		if ( null === $value ) {
			continue;
		}
		if ( 'justify_scope' === $new ) {
			$value = in_array( $value, array( 'blog_only', 'entire_site' ), true ) ? $value : 'blog_only';
		} elseif ( 'justify_exclude' === $new ) {
			$value = paulus_justify_sanitize_exclude( $value );
		} else {
			$value = empty( $value ) ? 0 : 1;
		}
		$found[ $new ] = $value;
	}
	if ( $found ) {
		$opts = get_option( 'paulus_options', array() );
		$opts = is_array( $opts ) ? $opts : array();
		update_option( 'paulus_options', array_merge( $opts, $found ) );
	}
	update_option( 'paulus_justify_imported', 1, false );
}
add_action( 'init', 'paulus_justify_import', 5 );

/**
 * Whether justification is on for the page being shown. Articles and Journal
 * entries are the theme's "blog posts"; the other scope is the whole site.
 *
 * @return bool
 */
function paulus_justify_applies() {
	if ( class_exists( 'AutoJustifyContent' ) ) {
		return false; // The plugin does it until the theme switches it off.
	}
	if ( ! paulus_option( 'justify_enabled' ) ) {
		return false;
	}
	if ( 'entire_site' === paulus_option( 'justify_scope' ) ) {
		return true;
	}
	return is_singular( array( 'post', 'paulus_journal' ) );
}

/**
 * Body classes the stylesheet switches on.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function paulus_justify_body_class( $classes ) {
	if ( ! paulus_justify_applies() ) {
		return $classes;
	}
	$classes[] = 'paulus-justify';
	if ( paulus_option( 'justify_hyphen' ) ) {
		$classes[] = 'paulus-justify--hyphen';
	}
	if ( paulus_option( 'justify_mobile' ) ) {
		$classes[] = 'paulus-justify--desktop';
	}
	if ( paulus_option( 'justify_fallback' ) ) {
		$classes[] = 'paulus-justify--fallback';
	}
	return $classes;
}
add_filter( 'body_class', 'paulus_justify_body_class' );

/**
 * The stylesheet, and the excluded selectors after it. Enqueued with the
 * theme's own styles, after them.
 */
function paulus_justify_assets() {
	if ( ! paulus_justify_applies() ) {
		return;
	}
	wp_enqueue_style( 'paulus-justify', PAULUS_URI . '/assets/css/justify.css', array( 'paulus-theme' ), PAULUS_VERSION );
	$exclude = paulus_justify_sanitize_exclude( paulus_option( 'justify_exclude' ) );
	if ( '' !== $exclude ) {
		wp_add_inline_style(
			'paulus-justify',
			sprintf(
				'%1$s, %1$s p, %1$s li { text-align: inherit !important; text-align-last: auto !important; text-justify: auto !important; -webkit-hyphens: manual !important; hyphens: manual !important; }',
				$exclude
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'paulus_justify_assets', 21 );
