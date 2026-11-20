<?php
/**
 * Citation built in (inc/cite.php): the module loads with the plugin's
 * hooks, stands aside while the plugin is active, and the theme-specific
 * guards work. WordPress is replaced by small stubs.
 *
 * Run: php tests/paulus-cite-test.php
 *
 * @package Paulus
 */

// Run from the command line only: on a web server this file does nothing.
if ( 'cli' !== PHP_SAPI ) {
	exit;
}

define( 'ABSPATH', __DIR__ . '/' );
define( 'PAULUS_DIR', dirname( __DIR__ ) );
define( 'PAULUS_URI', 'https://example.org/theme' );

$GLOBALS['t'] = array( 'actions' => array(), 'filters' => array(), 'shortcodes' => array(), 'options' => array() );
function add_action( $hook, $cb ) {
	$GLOBALS['t']['actions'][ $hook ][] = $cb;
}
function add_filter( $hook, $cb ) {
	$GLOBALS['t']['filters'][ $hook ][] = $cb;
}
function add_shortcode( $tag, $cb ) {
	$GLOBALS['t']['shortcodes'][ $tag ] = $cb;
}
function apply_filters( $hook, $value ) {
	return $value;
}
function get_option( $key, $default = false ) {
	return $GLOBALS['t']['options'][ $key ] ?? $default;
}
function wp_parse_args( $args, $defaults = array() ) {
	return array_merge( $defaults, is_array( $args ) ? $args : array() );
}
function __return_true() {
	return true;
}
function wp_clear_scheduled_hook() {}
function update_option( $key, $value ) {
	$GLOBALS['t']['options'][ $key ] = $value;
	return true;
}

$paulus_fail = 0;
function paulus_test_check( $ok, $what ) {
	global $paulus_fail;
	echo ( $ok ? 'PASS ' : 'FAIL ' ) . $what . "\n";
	$paulus_fail += $ok ? 0 : 1;
}

if ( isset( $argv[1] ) && 'native' === $argv[1] ) {
	// Native: the theme loads every file of the module. The plugin's settings
	// exist in the old option, so the import copies them.
	$GLOBALS['t']['options']['citepress_setting'] = array( 'et_al_threshold' => '5', 'auto_display' => 'yes' );
	function paulus_structured_data() {}
	require dirname( __DIR__ ) . '/inc/cite.php';
	echo json_encode(
		array(
			'version'    => defined( 'PAULUS_CITE_VERSION' ) ? PAULUS_CITE_VERSION : null,
			'shortcodes' => array_keys( $GLOBALS['t']['shortcodes'] ),
			'actions'    => array_keys( $GLOBALS['t']['actions'] ),
			'filters'    => array_keys( $GLOBALS['t']['filters'] ),
			'scholar'    => isset( $GLOBALS['t']['filters']['paulus_cite_output_scholar_meta'] ),
			'setting'    => $GLOBALS['paulus_cite_setting']['et_al_threshold'] ?? null,
			'copied'     => $GLOBALS['t']['options']['paulus_citepress'] ?? null,
			'seo_guard'  => function_exists( 'paulus_cite_seo_plugin_active' ) ? paulus_cite_seo_plugin_active() : null,
		)
	);
	exit( 0 );
}

// The plugin is active: the module only says it is ready.
define( 'CITEPRESS_VERSION', 'plugin' );
require dirname( __DIR__ ) . '/inc/cite.php';
paulus_test_check( function_exists( 'paulus_cite_ready' ) && paulus_cite_ready(), 'the ready function exists while the plugin is active' );
paulus_test_check( array() === $GLOBALS['t']['shortcodes'] && array() === $GLOBALS['t']['actions'], 'nothing is hooked while the plugin is active' );

// Native load, in a separate process so the constant above is not defined.
$out = shell_exec( 'php ' . escapeshellarg( __FILE__ ) . ' native 2>&1' );
$r   = json_decode( (string) $out, true );
paulus_test_check( is_array( $r ), 'the module loads every file without an error' . ( is_array( $r ) ? '' : ': ' . trim( (string) $out ) ) );
if ( is_array( $r ) ) {
	paulus_test_check( '3.0.1' === $r['version'], 'the module defines the plugin version' );
	paulus_test_check( in_array( 'cite', $r['shortcodes'], true ) && in_array( 'cite_bibliography', $r['shortcodes'], true ) && in_array( 'citepress_cite', $r['shortcodes'], true ), 'the [cite] and [cite_bibliography] shortcodes are registered, with their namespaced forms' );
	paulus_test_check( in_array( 'wp_head', $r['actions'], true ) && in_array( 'admin_menu', $r['actions'], true ) && in_array( 'switch_theme', $r['actions'], true ), 'head tags, the settings menu and the switch-theme clean-up are hooked' );
	paulus_test_check( true === $r['scholar'], 'the Google Scholar tags are switched on past the SEO guard' );
	paulus_test_check( '5' === (string) $r['setting'] && 'yes' === ( $r['copied']['auto_display'] ?? '' ), 'the plugin settings are copied to the theme option and read from it, over the defaults' );
	paulus_test_check( true === $r['seo_guard'], 'with the theme printing its own structured data, Citation adds neither JSON-LD nor Open Graph' );
}

exit( $paulus_fail ? 1 : 0 );
