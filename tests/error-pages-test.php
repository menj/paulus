<?php
/**
 * The error pages (inc/dropins.php, dropins/, errors/): each drop-in is built
 * from its own source with its own status and wording, runs without
 * WordPress, and the installer keeps a file it did not write and tells an
 * outdated copy from a current one. WordPress is replaced by small stubs.
 *
 * Run: php tests/error-pages-test.php
 *
 * @package Paulus
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'PAULUS_DIR', dirname( __DIR__ ) );
define( 'PAULUS_URI', 'https://example.test/wp-content/themes/paulus' );
define( 'PAULUS_VERSION', '9.9.9' );
$paulus_tmp = sys_get_temp_dir() . '/paulus-err-' . getmypid();
@mkdir( $paulus_tmp );
define( 'WP_CONTENT_DIR', $paulus_tmp );
$GLOBALS['t'] = array( 'options' => array() );
function add_action() {}
function __( $s ) { return $s; }
function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function wp_specialchars_decode( $s ) { return $s; }
function get_bloginfo() { return 'Apostle of Doom'; }
function wp_parse_url( $u, $c = -1 ) { return parse_url( $u, $c ); }
function home_url( $p = '' ) { return 'https://example.test' . $p; }
function content_url( $p = '' ) { return 'https://example.test/wp-content' . $p; }
function untrailingslashit( $s ) { return rtrim( $s, '/' ); }
function wp_json_encode( $v ) { return json_encode( $v ); }
function is_admin() { return true; }
function wp_doing_ajax() { return false; }
function current_user_can() { return true; }
function is_multisite() { return false; }
function get_option( $k, $d = false ) { return $GLOBALS['t']['options'][ $k ] ?? $d; }
function update_option( $k, $v ) { $GLOBALS['t']['options'][ $k ] = $v; return true; }
function delete_option( $k ) { unset( $GLOBALS['t']['options'][ $k ] ); }
require dirname( __DIR__ ) . '/inc/dropins.php';

$paulus_fail = 0;
function paulus_check( $ok, $label ) {
	global $paulus_fail;
	echo ( $ok ? 'ok   ' : 'FAIL ' ) . $label . "\n";
	$paulus_fail += $ok ? 0 : 1;
}

$paulus_src = array();
foreach ( paulus_dropin_files() as $paulus_f ) {
	$paulus_src[ $paulus_f ] = paulus_dropin_text( $paulus_f );
	paulus_check( false === strpos( $paulus_src[ $paulus_f ], '{{' ), "$paulus_f has every placeholder filled" );
	paulus_check( false !== strpos( $paulus_src[ $paulus_f ], 'X-Robots-Tag: noindex' ) && false !== strpos( $paulus_src[ $paulus_f ], 'no-store' ), "$paulus_f sends noindex and no-store" );
	$paulus_file = "$paulus_tmp/run-$paulus_f";
	file_put_contents( $paulus_file, $paulus_src[ $paulus_f ] );
	$paulus_out = (string) shell_exec( escapeshellarg( PHP_BINARY ) . ' -d display_errors=1 -d error_reporting=-1 ' . escapeshellarg( $paulus_file ) . ' 2>&1' );
	paulus_check( false !== strpos( $paulus_out, '<!DOCTYPE html>' ) && ! preg_match( '/(Warning|Notice|Fatal error|Deprecated):/', $paulus_out ), "$paulus_f runs without WordPress" );
	unlink( $paulus_file );
	@unlink( "$paulus_tmp/paulus-incidents.log.php" );
}
paulus_check( false !== strpos( $paulus_src['maintenance.php'], "503 Service Unavailable', true, 503" ) && false !== strpos( $paulus_src['maintenance.php'], 'Retry-After' ), 'maintenance.php sends 503 and Retry-After' );
paulus_check( false !== strpos( $paulus_src['db-error.php'], "503 Service Unavailable', true, 503" ) && false !== strpos( $paulus_src['db-error.php'], 'We cannot reach our database' ), 'db-error.php sends 503 and names the database' );
paulus_check( false !== strpos( $paulus_src['php-error.php'], "500 Internal Server Error', true, 500" ) && false !== strpos( $paulus_src['php-error.php'], 'Something went wrong' ), 'php-error.php sends 500, "Something went wrong"' );
paulus_check( $paulus_src['maintenance.php'] !== $paulus_src['php-error.php'] && $paulus_src['maintenance.php'] !== $paulus_src['db-error.php'], 'each drop-in has its own page' );

paulus_check( 'missing' === paulus_dropin_state( 'php-error.php' ), 'missing reads as missing' );
file_put_contents( "$paulus_tmp/db-error.php", "<?php // someone else's\n" );
paulus_install_dropins( true );
paulus_check( 'installed' === paulus_dropin_state( 'php-error.php' ) && 'installed' === paulus_dropin_state( 'paulus-error-page.css' ), 'the installer writes the theme files' );
paulus_check( 'foreign' === paulus_dropin_state( 'db-error.php' ) && "<?php // someone else's\n" === file_get_contents( "$paulus_tmp/db-error.php" ), 'a file the theme did not write is left alone' );
file_put_contents( "$paulus_tmp/php-error.php", $paulus_src['maintenance.php'] );
paulus_check( 'outdated' === paulus_dropin_state( 'php-error.php' ), 'the maintenance page in php-error.php reads as outdated' );
paulus_install_dropins( true );
paulus_check( file_get_contents( "$paulus_tmp/php-error.php" ) === $paulus_src['php-error.php'], 'the installer puts the right page back' );
paulus_check( array() === glob( "$paulus_tmp/*.tmp" ), 'no temporary file is left' );

exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( dirname( __DIR__ ) . '/bin/build-error-pages.php' ) . ' --check 2>&1', $paulus_o, $paulus_code );
paulus_check( 0 === $paulus_code, 'errors/*.html match the builder' );
foreach ( array( '403', '500', '502', '503' ) as $paulus_c ) {
	$paulus_h = (string) file_get_contents( dirname( __DIR__ ) . "/errors/$paulus_c.html" );
	paulus_check( false !== strpos( $paulus_h, 'noindex' ) && false === strpos( $paulus_h, '{{' ) && preg_match( '~<title>[^<|]{1,32} \| Apostle of Doom</title>~', $paulus_h ), "errors/$paulus_c.html is filled, noindex and keeps the title rule" );
}
paulus_check( false !== strpos( paulus_error_htaccess_snippet(), 'ErrorDocument 503 /wp-content/themes/paulus/errors/503.html' ), '.htaccess lines point at errors/' );

array_map( 'unlink', array_filter( glob( "$paulus_tmp/{,.}*", GLOB_BRACE ) ?: array(), 'is_file' ) );
@rmdir( $paulus_tmp );
echo $paulus_fail ? "$paulus_fail failed\n" : "all passed\n";
exit( $paulus_fail ? 1 : 0 );
