<?php
/**
 * Contact Form 7 built in (inc/cf7.php): the loader stands aside while the
 * plugin is active, every bundled file parses, and the changes the theme
 * makes to the plugin's code are in place. WordPress is not loaded.
 *
 * Run: php tests/cf7-test.php
 *
 * @package Paulus
 */

if ( 'cli' !== PHP_SAPI ) {
	exit;
}

define( 'ABSPATH', __DIR__ . '/' );
define( 'PAULUS_DIR', dirname( __DIR__ ) );
define( 'PAULUS_URI', 'https://example.org/theme' );

$paulus_fail = 0;
function paulus_test_check( $ok, $what ) {
	global $paulus_fail;
	echo ( $ok ? 'PASS ' : 'FAIL ' ) . $what . "\n";
	$paulus_fail += $ok ? 0 : 1;
}

// With the plugin loaded the module defines only its ready marker.
define( 'WPCF7_VERSION', '6.2.5' );
require PAULUS_DIR . '/inc/cf7.php';
paulus_test_check( function_exists( 'paulus_cf7_ready' ) && paulus_cf7_ready(), 'ready marker defined before standing aside' );
paulus_test_check( ! defined( 'WPCF7_PLUGIN_DIR' ), 'stands aside while the plugin is loaded' );

$loader = file_get_contents( PAULUS_DIR . '/inc/cf7.php' );
paulus_test_check( false !== strpos( $loader, "define( 'WPCF7_PLUGIN_DIR', PAULUS_DIR . '/inc/cf7' )" ), 'plugin directory is inc/cf7' );
paulus_test_check( false !== strpos( $loader, "'WPCF7_VERSION', '6.2.5'" ), 'defines WPCF7_VERSION so the plugin uninstall keeps the data' );

$load = file_get_contents( PAULUS_DIR . '/inc/cf7/load.php' );
paulus_test_check( false === strpos( $load, "add_action( 'plugins_loaded'" ), 'no plugins_loaded hook (a theme loads after it)' );
paulus_test_check( false === strpos( $load, "add_action( 'activate_'" ), 'no activation hook' );
paulus_test_check( false !== strpos( $loader, "\nwpcf7();" ), 'loader calls wpcf7() directly' );

$fn = file_get_contents( PAULUS_DIR . '/inc/cf7/includes/functions.php' );
paulus_test_check( false !== strpos( $fn, "'inc/cf7/'" ) && false === strpos( $fn, 'plugins_url' ), 'asset URLs point into the theme' );

$entry = file_get_contents( PAULUS_DIR . '/inc/plugin-retire.php' );
paulus_test_check( false !== strpos( $entry, "'contact-form-7/wp-contact-form-7.php'" ) && false !== strpos( $entry, 'paulus_cf7_ready' ), 'plugin-retire lists Contact Form 7' );

// Every bundled PHP file parses.
$bad = 0;
$it  = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( PAULUS_DIR . '/inc/cf7', FilesystemIterator::SKIP_DOTS ) );
foreach ( $it as $file ) {
	if ( 'php' === $file->getExtension() ) {
		exec( 'php -l ' . escapeshellarg( $file->getPathname() ) . ' 2>&1', $out, $rc );
		$bad += $rc ? 1 : 0;
	}
}
paulus_test_check( 0 === $bad, 'every bundled PHP file parses' );
paulus_test_check( is_readable( PAULUS_DIR . '/inc/cf7/license.txt' ), 'plugin licence is kept' );

exit( $paulus_fail ? 1 : 0 );
