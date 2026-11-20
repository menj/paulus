<?php
/**
 * Forms built in (inc/forms.php): the loader stands aside while the
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
require PAULUS_DIR . '/inc/forms.php';
paulus_test_check( function_exists( 'paulus_forms_ready' ) && paulus_forms_ready(), 'ready marker defined before standing aside' );
paulus_test_check( ! defined( 'PAULUS_FORM_PLUGIN_DIR' ), 'stands aside while the plugin is loaded' );

$loader = file_get_contents( PAULUS_DIR . '/inc/forms.php' );
paulus_test_check( false !== strpos( $loader, "define( 'PAULUS_FORM_PLUGIN_DIR', PAULUS_DIR . '/inc/forms' )" ), 'plugin directory is inc/forms' );
paulus_test_check( false !== strpos( $loader, "'WPCF7_VERSION', PAULUS_FORM_VERSION" ), 'defines WPCF7_VERSION so the plugin uninstall keeps the data' );

$load = file_get_contents( PAULUS_DIR . '/inc/forms/load.php' );
paulus_test_check( false === strpos( $load, "add_action( 'plugins_loaded'" ), 'no plugins_loaded hook (a theme loads after it)' );
paulus_test_check( false === strpos( $load, "add_action( 'activate_'" ), 'no activation hook' );
paulus_test_check( false !== strpos( $loader, "\npaulus_forms_boot();" ), 'loader calls paulus_forms_boot() directly' );

$fn = file_get_contents( PAULUS_DIR . '/inc/forms/includes/functions.php' );
paulus_test_check( false !== strpos( $fn, "'inc/forms/'" ) && false === strpos( $fn, 'plugins_url' ), 'asset URLs point into the theme' );

$entry = file_get_contents( PAULUS_DIR . '/inc/plugin-retire.php' );
paulus_test_check( false !== strpos( $entry, "'contact-form-7/wp-contact-form-7.php'" ) && false !== strpos( $entry, 'paulus_forms_ready' ), 'plugin-retire lists Forms' );

// Every bundled PHP file parses.
$bad = 0;
$it  = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( PAULUS_DIR . '/inc/forms', FilesystemIterator::SKIP_DOTS ) );
foreach ( $it as $file ) {
	if ( 'php' === $file->getExtension() ) {
		exec( 'php -l ' . escapeshellarg( $file->getPathname() ) . ' 2>&1', $out, $rc );
		$bad += $rc ? 1 : 0;
	}
}
paulus_test_check( 0 === $bad, 'every bundled PHP file parses' );
paulus_test_check( is_readable( PAULUS_DIR . '/inc/forms/license.txt' ), 'plugin licence is kept' );

// The bundled form libraries load under their new namespace.
require_once PAULUS_DIR . '/inc/forms/vendor/autoload.php';
paulus_test_check( class_exists( 'PaulusFormLibs\\Swv\\CompositeRule' ) && class_exists( 'PaulusFormLibs\\FormDataTree\\FormDataTree' ), 'the bundled libraries autoload under PaulusFormLibs' );

exit( $paulus_fail ? 1 : 0 );
