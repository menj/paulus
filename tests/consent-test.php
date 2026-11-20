<?php
/**
 * Consent API built in (inc/consent.php): the module loads, stands aside
 * while the plugin is active, and points the plugin's assets into the theme.
 * WordPress is replaced by small stubs.
 *
 * Run: php tests/consent-api-test.php
 *
 * @package Paulus
 */

if ( 'cli' !== PHP_SAPI ) {
	exit;
}

define( 'ABSPATH', __DIR__ . '/' );
define( 'PAULUS_DIR', dirname( __DIR__ ) );
define( 'PAULUS_URI', 'https://example.org/theme' );

$GLOBALS['t'] = array( 'actions' => array(), 'filters' => array() );
function add_action( $hook, $cb ) {
	$GLOBALS['t']['actions'][ $hook ][] = $cb;
}
function add_filter( $hook, $cb ) {
	$GLOBALS['t']['filters'][ $hook ][] = $cb;
}
function apply_filters( $hook, $value ) {
	return $value;
}
function trailingslashit( $s ) {
	return rtrim( $s, '/' ) . '/';
}

$paulus_fail = 0;
function paulus_test_check( $ok, $what ) {
	global $paulus_fail;
	echo ( $ok ? 'PASS ' : 'FAIL ' ) . $what . "\n";
	$paulus_fail += $ok ? 0 : 1;
}

if ( isset( $argv[1] ) && 'native' === $argv[1] ) {
	require dirname( __DIR__ ) . '/inc/consent.php';
	echo json_encode(
		array(
			'version' => defined( 'PAULUS_CONSENT_VERSION' ) ? PAULUS_CONSENT_VERSION : null,
			'url'     => defined( 'PAULUS_CONSENT_URL' ) ? PAULUS_CONSENT_URL : null,
			'config'  => Paulus_Consent::$config instanceof Paulus_Consent_Config,
			'fn'      => function_exists( 'wp_has_consent' ) && function_exists( 'wp_set_consent' ),
			'self'    => function_exists( 'consent_api_registered' ) && false === consent_api_registered( 'some/plugin.php' ),
		)
	);
	exit;
}

$out = json_decode( (string) shell_exec( 'php ' . escapeshellarg( __FILE__ ) . ' native 2>&1' ), true );
paulus_test_check( is_array( $out ), 'module loads without the plugin' );
if ( is_array( $out ) ) {
	paulus_test_check( '2.1.0' === $out['version'], 'version constant is 2.1.0' );
	paulus_test_check( 'https://example.org/theme/' === $out['url'], 'asset URL is the theme root' );
	paulus_test_check( true === $out['config'], 'config object is built' );
	paulus_test_check( true === $out['fn'], 'wp_has_consent() and wp_set_consent() exist' );
	paulus_test_check( true === $out['self'], 'a plugin that has not declared the API is reported as unregistered' );
}
paulus_test_check( is_readable( PAULUS_DIR . '/assets/js/paulus-consent.min.js' ) && is_readable( PAULUS_DIR . '/assets/css/paulus-consent.css' ), 'script and stylesheet sit in assets/js and assets/css' );

// With the plugin's class present the module defines only its ready marker.
eval( 'class WP_Consent_API {}' );
require PAULUS_DIR . '/inc/consent.php';
paulus_test_check( function_exists( 'paulus_consent_ready' ) && paulus_consent_ready(), 'ready marker defined before standing aside' );
paulus_test_check( ! defined( 'PAULUS_CONSENT_PATH' ), 'stands aside while the plugin is loaded' );

$entry = file_get_contents( PAULUS_DIR . '/inc/plugin-retire.php' );
paulus_test_check( false !== strpos( $entry, "'wp-consent-api/wp-consent-api.php'" ) && false !== strpos( $entry, 'paulus_consent_ready' ), 'plugin-retire lists Consent API' );

exit( $paulus_fail ? 1 : 0 );
