<?php
/**
 * Hreflang built in (inc/hreflang.php): the module loads, stands aside
 * while the plugin is active, and takes over the plugin's settings once.
 * WordPress is replaced by small stubs.
 *
 * Run: php tests/paulus-hreflang-test.php
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

$GLOBALS['t'] = array( 'actions' => array(), 'filters' => array(), 'options' => array() );
function add_action( $hook, $cb ) {
	$GLOBALS['t']['actions'][ $hook ][] = $cb;
}
function add_filter( $hook, $cb ) {
	$GLOBALS['t']['filters'][ $hook ][] = $cb;
}
function get_option( $key, $default = false ) {
	return array_key_exists( $key, $GLOBALS['t']['options'] ) ? $GLOBALS['t']['options'][ $key ] : $default;
}
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
	// The plugin's settings exist under the old names; the admin bar and the
	// HTML lang switches are on.
	$GLOBALS['t']['options'] = array(
		'hrefmaster_post_types'       => array( 'post', 'page' ),
		'hrefmaster_enable_admin_bar' => 'true',
		'hrefmaster_enable_html_lang' => 'true',
		'hrefmaster_color_scheme'     => 'slate',
	);
	require dirname( __DIR__ ) . '/inc/hreflang.php';
	echo json_encode(
		array(
			'version'  => defined( 'PAULUS_HREFLANG_VERSION' ) ? PAULUS_HREFLANG_VERSION : null,
			'types'    => $GLOBALS['t']['options']['paulus_hreflang_post_types'] ?? null,
			'scheme'   => $GLOBALS['t']['options']['paulus_hreflang_scheme'] ?? null,
			'imported' => $GLOBALS['t']['options']['paulus_hreflang_imported'] ?? null,
			'woo'      => array_key_exists( 'paulus_hreflang_woo_shop', $GLOBALS['t']['options'] ),
			'actions'  => array_keys( $GLOBALS['t']['actions'] ),
			'adminbar' => isset( $GLOBALS['t']['actions']['wp_before_admin_bar_render'] ),
			'lang'     => isset( $GLOBALS['t']['filters']['language_attributes'] ),
		)
	);
	exit( 0 );
}

// The plugin is active: the module only says it is ready.
define( 'HREFMASTER_VERSION', 'plugin' );
require dirname( __DIR__ ) . '/inc/hreflang.php';
paulus_test_check( function_exists( 'paulus_hreflang_ready' ) && paulus_hreflang_ready(), 'the ready function exists while the plugin is active' );
paulus_test_check( array() === $GLOBALS['t']['actions'], 'nothing is hooked while the plugin is active' );

// Native load, in a separate process so the constant above is not defined.
$out = shell_exec( 'php ' . escapeshellarg( __FILE__ ) . ' native 2>&1' );
$r   = json_decode( (string) $out, true );
paulus_test_check( is_array( $r ), 'the module loads every file without an error' . ( is_array( $r ) ? '' : ': ' . trim( (string) $out ) ) );
if ( is_array( $r ) ) {
	paulus_test_check( '3.0.1' === $r['version'], 'the module defines its own version' );
	paulus_test_check( array( 'post', 'page' ) === $r['types'] && 'slate' === $r['scheme'] && 1 === $r['imported'], 'the plugin settings are copied once under the theme names' );
	paulus_test_check( false === $r['woo'], 'a setting the plugin never stored is not invented' );
	paulus_test_check( true === $r['adminbar'] && true === $r['lang'], 'the admin bar link and the HTML lang filter follow the copied switches' );
	paulus_test_check( in_array( 'wp_head', $r['actions'], true ) && in_array( 'admin_menu', $r['actions'], true ) && in_array( 'save_post', $r['actions'], true ) && in_array( 'init', $r['actions'], true ), 'head tags, the menu, the meta boxes and the taxonomy fields (on init) are hooked' );
}

exit( $paulus_fail ? 1 : 0 );
