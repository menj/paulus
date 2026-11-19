<?php
/**
 * Endmark built in (inc/endmark.php): the module loads, stands aside while
 * the plugin is active, takes over the plugin's data once, and the
 * placement of the symbol is the plugin's. WordPress is replaced by small
 * stubs.
 *
 * Run: php tests/endmark-test.php
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

$GLOBALS['t'] = array( 'actions' => array(), 'filters' => array(), 'shortcodes' => array(), 'options' => array(), 'queries' => array() );
function add_action( $hook, $cb ) {
	$GLOBALS['t']['actions'][ $hook ][] = $cb;
}
function add_filter( $hook, $cb ) {
	$GLOBALS['t']['filters'][ $hook ][] = $cb;
}
function add_shortcode( $tag, $cb ) {
	$GLOBALS['t']['shortcodes'][ $tag ] = $cb;
}
function get_option( $key, $default = false ) {
	return array_key_exists( $key, $GLOBALS['t']['options'] ) ? $GLOBALS['t']['options'][ $key ] : $default;
}
function update_option( $key, $value ) {
	$GLOBALS['t']['options'][ $key ] = $value;
	return true;
}
function is_multisite() {
	return false;
}
function wp_parse_args( $args, $defaults = array() ) {
	return array_merge( $defaults, is_array( $args ) ? $args : array() );
}
class Fake_WPDB {
	public $postmeta = 'wp_postmeta';
	public function prepare( $sql, ...$args ) {
		return vsprintf( str_replace( '%s', "'%s'", $sql ), $args );
	}
	public function query( $sql ) {
		$GLOBALS['t']['queries'][] = $sql;
		return 1;
	}
}
$wpdb = new Fake_WPDB();

$paulus_fail = 0;
function paulus_test_check( $ok, $what ) {
	global $paulus_fail;
	echo ( $ok ? 'PASS ' : 'FAIL ' ) . $what . "\n";
	$paulus_fail += $ok ? 0 : 1;
}

if ( isset( $argv[1] ) && 'native' === $argv[1] ) {
	// The plugin's settings and a per-post switch exist under the old names.
	$GLOBALS['t']['options']['endmark_settings'] = array( 'symbol' => '❧', 'post_types' => array( 'post', 'paulus_journal' ) );
	$GLOBALS['t']['options']['endmark_version']  = '4.4.0';
	require dirname( __DIR__ ) . '/inc/endmark.php';
	$settings = Endmark_Plugin::get_instance()->get_settings();
	echo json_encode(
		array(
			'symbol'   => $settings['symbol'],
			'types'    => $settings['post_types'],
			'option'   => $GLOBALS['t']['options']['paulus_endmark']['symbol'] ?? null,
			'version'  => $GLOBALS['t']['options']['paulus_endmark_version'] ?? null,
			'imported' => $GLOBALS['t']['options']['paulus_endmark_imported'] ?? null,
			'queries'  => $GLOBALS['t']['queries'],
			'meta'     => Endmark_Plugin::META_DISABLE,
			'init'     => isset( $GLOBALS['t']['actions']['init'] ),
			'content'  => isset( $GLOBALS['t']['filters']['the_content'] ),
		)
	);
	exit( 0 );
}

// The plugin is active: the module only says it is ready.
eval( 'class Endmark_Plugin {}' );
require dirname( __DIR__ ) . '/inc/endmark.php';
paulus_test_check( function_exists( 'paulus_endmark_ready' ) && paulus_endmark_ready(), 'the ready function exists while the plugin is active' );
paulus_test_check( array() === $GLOBALS['t']['actions'] && array() === $GLOBALS['t']['filters'], 'nothing is hooked while the plugin is active' );

// Native load, in a separate process so the stand-in class above does not exist.
$out = shell_exec( 'php ' . escapeshellarg( __FILE__ ) . ' native 2>&1' );
$r   = json_decode( (string) $out, true );
paulus_test_check( is_array( $r ), 'the module loads every file without an error' . ( is_array( $r ) ? '' : ': ' . trim( (string) $out ) ) );
if ( is_array( $r ) ) {
	paulus_test_check( '❧' === $r['symbol'] && array( 'post', 'paulus_journal' ) === $r['types'], 'the plugin settings are read from the theme option' );
	paulus_test_check( '❧' === $r['option'] && '4.4.0' === $r['version'] && 1 === $r['imported'], 'the plugin options are copied once under the theme names' );
	paulus_test_check( 1 === count( $r['queries'] ) && false !== strpos( $r['queries'][0], '_paulus_endmark_disable' ) && false !== strpos( $r['queries'][0], '_endmark_disable' ), 'the per-post switch is renamed in the database' );
	paulus_test_check( '_paulus_endmark_disable' === $r['meta'], 'the plugin class uses the new meta key' );
	paulus_test_check( true === $r['init'] && true === $r['content'], 'the shortcode and block (on init) and the content filter are hooked' );
}

exit( $paulus_fail ? 1 : 0 );
