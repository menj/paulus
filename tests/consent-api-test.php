<?php
/**
 * Consent API built in (inc/consent-api.php): the module loads, stands aside
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
	require dirname( __DIR__ ) . '/inc/consent-api.php';
	echo json_encode(
		array(
			'version' => defined( 'WP_CONSENT_API_VERSION' ) ? WP_CONSENT_API_VERSION : null,
			'url'     => defined( 'WP_CONSENT_API_URL' ) ? WP_CONSENT_API_URL : null,
			'config'  => WP_Consent_API::$config instanceof WP_Consent_API_Config,
			'fn'      => function_exists( 'wp_has_consent' ) && function_exists( 'wp_set_consent' ),
			'self'    => function_exists( 'consent_api_registered' ) && consent_api_registered( 'wp-consent-api/wp-consent-api.php' ),
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
	paulus_test_check( true === $out['self'], 'the API counts itself as registered' );
}
paulus_test_check( is_readable( PAULUS_DIR . '/assets/js/wp-consent-api.min.js' ) && is_readable( PAULUS_DIR . '/assets/css/wp-consent-api.css' ), 'script and stylesheet sit in assets/js and assets/css' );

// With the plugin's class present the module defines only its ready marker.
eval( 'class WP_Consent_API {}' );
require PAULUS_DIR . '/inc/consent-api.php';
paulus_test_check( function_exists( 'paulus_consent_api_ready' ) && paulus_consent_api_ready(), 'ready marker defined before standing aside' );
paulus_test_check( ! defined( 'WP_CONSENT_API_PATH' ), 'stands aside while the plugin is loaded' );

$entry = file_get_contents( PAULUS_DIR . '/inc/plugin-retire.php' );
paulus_test_check( false !== strpos( $entry, "'wp-consent-api/wp-consent-api.php'" ) && false !== strpos( $entry, 'paulus_consent_api_ready' ), 'plugin-retire lists WP Consent API' );

exit( $paulus_fail ? 1 : 0 );
