<?php
/**
 * Justified text and the retiring of Auto Justify Content (inc/justify.php,
 * inc/plugin-retire.php): the import of the plugin's options, the body
 * classes, the excluded selectors, and when the plugin is switched off.
 * WordPress is replaced by small stubs.
 *
 * Run: php tests/justify-test.php
 *
 * @package Paulus
 */

// Run from the command line only: on a web server this file does nothing.
if ( 'cli' !== PHP_SAPI ) {
	exit;
}

define( 'ABSPATH', __DIR__ . '/' );
define( 'PAULUS_URI', 'https://example.org/theme' );
define( 'PAULUS_VERSION', '9.9.9' );
define( 'MINUTE_IN_SECONDS', 60 );

$GLOBALS['t'] = array(
	'options'   => array(),
	'singular'  => false,
	'active'    => array(),
	'network'   => array(),
	'multisite' => false,
	'off'       => array(),
	'transient' => array(),
	'styles'    => array(),
	'inline'    => array(),
	'filters'   => array(),
);
function add_action() {}
function add_filter() {}
function apply_filters( $hook, $value ) {
	return $value;
}
function __( $s ) {
	return $s;
}
function get_option( $key, $default = false ) {
	return array_key_exists( $key, $GLOBALS['t']['options'] ) ? $GLOBALS['t']['options'][ $key ] : $default;
}
function update_option( $key, $value ) {
	$GLOBALS['t']['options'][ $key ] = $value;
	return true;
}
function paulus_option( $key ) {
	$defaults = array(
		'justify_enabled'  => 1,
		'justify_scope'    => 'blog_only',
		'justify_mobile'   => 0,
		'justify_hyphen'   => 1,
		'justify_fallback' => 0,
		'justify_exclude'  => '',
	);
	$opts = get_option( 'paulus_options', array() );
	return array_key_exists( $key, $opts ) ? $opts[ $key ] : ( $defaults[ $key ] ?? '' );
}
function is_singular( $types = '' ) {
	return $GLOBALS['t']['singular'];
}
function wp_enqueue_style( $handle ) {
	$GLOBALS['t']['styles'][] = $handle;
}
function wp_add_inline_style( $handle, $css ) {
	$GLOBALS['t']['inline'][] = $css;
}
function is_plugin_active( $file ) {
	return in_array( $file, $GLOBALS['t']['active'], true );
}
function is_plugin_active_for_network( $file ) {
	return in_array( $file, $GLOBALS['t']['network'], true );
}
function is_multisite() {
	return $GLOBALS['t']['multisite'];
}
function deactivate_plugins( $file, $silent ) {
	$GLOBALS['t']['off'][] = array( $file, $silent );
	$GLOBALS['t']['active'] = array_diff( $GLOBALS['t']['active'], array( $file ) );
}
function set_transient( $key, $value ) {
	$GLOBALS['t']['transient'][ $key ] = $value;
}
function current_user_can() {
	return true;
}

require dirname( __DIR__ ) . '/inc/justify.php';
require dirname( __DIR__ ) . '/inc/plugin-retire.php';

$paulus_fail = 0;
function paulus_test_check( $ok, $what ) {
	global $paulus_fail;
	echo ( $ok ? 'PASS ' : 'FAIL ' ) . $what . "\n";
	$paulus_fail += $ok ? 0 : 1;
}

// Sanitising the excluded selectors keeps simple selectors and drops the rest.
paulus_test_check( '.no-justify, .sidebar' === paulus_justify_sanitize_exclude( " .no-justify ,\n.sidebar " ), 'selectors are trimmed and joined with a comma and a space' );
$dirty = paulus_justify_sanitize_exclude( '.a{color:red}<script>' );
paulus_test_check( false === strpos( $dirty, '{' ) && false === strpos( $dirty, '<' ), 'braces and angle brackets are dropped' );
paulus_test_check( '' === paulus_justify_sanitize_exclude( '   ' ), 'an empty field stays empty' );

// Import of the plugin's options: once, with its values.
$GLOBALS['t']['options'] = array(
	'ajc_enabled'  => '1',
	'ajc_scope'    => 'entire_site',
	'ajc_hyphen'   => '',
	'ajc_mobile'   => '1',
	'ajc_exclude'  => '.elementor-testimonial',
	'paulus_options' => array( 'scheme' => 'ink' ),
);
paulus_justify_import();
$o = $GLOBALS['t']['options']['paulus_options'];
paulus_test_check( 'entire_site' === $o['justify_scope'] && 0 === $o['justify_hyphen'] && 1 === $o['justify_mobile'] && 1 === $o['justify_enabled'], 'the plugin settings are copied: scope, hyphenation, desktop only, enabled' );
paulus_test_check( '.elementor-testimonial' === $o['justify_exclude'], 'the excluded selectors are copied' );
paulus_test_check( 'ink' === $o['scheme'], 'other theme options are kept' );
paulus_test_check( ! array_key_exists( 'justify_fallback', $o ), 'a setting the plugin never stored keeps the theme default' );
$GLOBALS['t']['options']['ajc_scope'] = 'blog_only';
paulus_justify_import();
paulus_test_check( 'entire_site' === $GLOBALS['t']['options']['paulus_options']['justify_scope'], 'the import runs once' );

// Scope and body classes.
$GLOBALS['t']['options'] = array( 'paulus_options' => array() );
$GLOBALS['t']['singular'] = false;
paulus_test_check( array() === paulus_justify_body_class( array() ), 'blog scope: nothing on an archive' );
$GLOBALS['t']['singular'] = true;
$c = paulus_justify_body_class( array() );
paulus_test_check( in_array( 'paulus-justify', $c, true ) && in_array( 'paulus-justify--hyphen', $c, true ) && ! in_array( 'paulus-justify--desktop', $c, true ), 'blog scope: an article gets the class and hyphenation by default' );
$GLOBALS['t']['options']['paulus_options'] = array( 'justify_scope' => 'entire_site', 'justify_mobile' => 1, 'justify_fallback' => 1, 'justify_hyphen' => 0 );
$GLOBALS['t']['singular'] = false;
$c = paulus_justify_body_class( array( 'home' ) );
paulus_test_check( array( 'home', 'paulus-justify', 'paulus-justify--desktop', 'paulus-justify--fallback' ) === $c, 'whole site: classes follow the switches, in order' );
$GLOBALS['t']['options']['paulus_options'] = array( 'justify_enabled' => 0 );
paulus_test_check( array() === paulus_justify_body_class( array() ), 'switched off: no class' );

// Stylesheet and inline exclusion, before the plugin's class exists.
$GLOBALS['t']['options']['paulus_options'] = array( 'justify_exclude' => ".sidebar, .no-justify" );
$GLOBALS['t']['singular'] = true;
$GLOBALS['t']['styles']   = array();
$GLOBALS['t']['inline']   = array();
paulus_justify_assets();
paulus_test_check( array( 'paulus-justify' ) === $GLOBALS['t']['styles'], 'the stylesheet is enqueued where justification applies' );
paulus_test_check( 1 === count( $GLOBALS['t']['inline'] ) && false !== strpos( $GLOBALS['t']['inline'][0], '.sidebar, .no-justify p' ), 'the excluded selectors are written inline, with their p and li' );
$GLOBALS['t']['options']['paulus_options'] = array();
$GLOBALS['t']['inline'] = array();
paulus_justify_assets();
paulus_test_check( array() === $GLOBALS['t']['inline'], 'no inline rule when nothing is excluded' );

// The plugin's own class makes the theme stand aside.
eval( 'class AutoJustifyContent {}' );
$GLOBALS['t']['singular'] = true;
paulus_test_check( array() === paulus_justify_body_class( array() ), 'while the plugin is active the theme adds nothing' );
$GLOBALS['t']['styles'] = array();
paulus_justify_assets();
paulus_test_check( array() === $GLOBALS['t']['styles'], 'no stylesheet while the plugin is active' );

// Retiring.
$file = 'auto-justify-content/auto-justify-content.php';
$GLOBALS['t']['active'] = array( $file, 'other/other.php' );
$done = paulus_retire_plugins();
paulus_test_check( array( 'Auto Justify Content' ) === $done && array( array( $file, true ) ) === $GLOBALS['t']['off'], 'an active plugin in the list is switched off, silently' );
paulus_test_check( array( 'other/other.php' ) === array_values( $GLOBALS['t']['active'] ), 'other plugins are untouched' );
$GLOBALS['t']['off'] = array();
$done = paulus_retire_plugins();
paulus_test_check( array() === $done && array() === $GLOBALS['t']['off'], 'nothing to do once it is off' );
$GLOBALS['t']['active']    = array( $file );
$GLOBALS['t']['network']   = array( $file );
$GLOBALS['t']['multisite'] = true;
$done = paulus_retire_plugins();
paulus_test_check( array() === $done && array() === $GLOBALS['t']['off'] && array( 'Auto Justify Content' ) === $GLOBALS['t']['transient']['paulus_retired_blocked'], 'a network-active plugin is left, with a notice' );

exit( $paulus_fail ? 1 : 0 );
