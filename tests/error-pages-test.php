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
define( 'PAULUS_URI', 'https://example.test' . ( getenv( 'PAULUS_TEST_SUB' ) ?: '' ) . '/wp-content/themes/paulus' );
define( 'PAULUS_VERSION', '9.9.9' );
// A fresh directory of its own, so the cleanup at the end removes only what this test wrote.
$paulus_tmp = sys_get_temp_dir() . '/paulus-err-' . getmypid() . '-' . bin2hex( random_bytes( 6 ) );
if ( ! mkdir( $paulus_tmp, 0700 ) ) {
	fwrite( STDERR, "cannot create $paulus_tmp\n" );
	exit( 1 );
}
define( 'WP_CONTENT_DIR', $paulus_tmp );
$GLOBALS['t'] = array( 'options' => array() );
function add_action() {}
function __( $s ) { return $s; }
function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function wp_specialchars_decode( $s ) { return $s; }
function get_bloginfo() { return 'Apostle of Doom'; }
function wp_parse_url( $u, $c = -1 ) { return parse_url( $u, $c ); }
function home_url( $p = '' ) { return 'https://example.test' . ( getenv( 'PAULUS_TEST_SUB' ) ?: '' ) . $p; }
function content_url( $p = '' ) { return 'https://example.test' . ( getenv( 'PAULUS_TEST_SUB' ) ?: '' ) . '/wp-content' . $p; }
function wp_mkdir_p( $d ) { return is_dir( $d ) || mkdir( $d, 0755, true ); }
function get_main_site_id() { return 1; }
function get_blog_option( $id, $k ) { return 1 === $id ? ( $GLOBALS['t']['main_theme'] ?? 'paulus' ) : 'paulus'; }
function get_current_blog_id() { return $GLOBALS['t']['current'] ?? 2; }
function get_sites( $a ) { return ( $a['offset'] ?? 0 ) ? array() : array( 1, 2, 3 ); }
function untrailingslashit( $s ) { return rtrim( $s, '/' ); }
function wp_json_encode( $v ) { return json_encode( $v ); }
function is_admin() { return true; }
function wp_doing_ajax() { return false; }
function current_user_can() { return true; }
function is_multisite() { return ! empty( $GLOBALS['t']['subsite'] ); }
function is_main_site() { return empty( $GLOBALS['t']['subsite'] ); }
define( 'HOUR_IN_SECONDS', 3600 );
function get_transient( $k ) { return $GLOBALS['t']['transients'][ $k ] ?? false; }
function set_transient( $k, $v ) { $GLOBALS['t']['transients'][ $k ] = $v; return true; }
function delete_transient( $k ) { unset( $GLOBALS['t']['transients'][ $k ] ); return true; }
function esc_html_e( $s ) { echo esc_html( $s ); }
function esc_attr_e( $s ) { echo esc_attr( $s ); }
function esc_url( $s ) { return (string) $s; }
function esc_textarea( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function admin_url( $p = '' ) { return 'https://example.test/wp-admin/' . $p; }
function wp_nonce_field() { echo '<input type="hidden" name="_wpnonce" value="t">'; }
function submit_button( $t ) { echo '<button>' . esc_html( $t ) . '</button>'; }
function sanitize_key( $s ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $s ) ); }
function wp_unslash( $s ) { return $s; }
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
paulus_check( ! in_array( 'failed', get_option( 'paulus_dropins_status' ), true ) && false === get_transient( 'paulus_dropins_retry' ), 'a full install records no failure and sets no retry wait' );

function paulus_render_card() {
	ob_start();
	paulus_render_dropins_status();
	return (string) ob_get_clean();
}
$_GET['paulus_dropins'] = '1';
$paulus_html            = paulus_render_card();
paulus_check( false !== strpos( $paulus_html, 'paulus_install_dropins' ) && false !== strpos( $paulus_html, 'written again' ) && false === strpos( $paulus_html, 'whole network' ), 'the main site shows the button and the success notice' );
$_GET['paulus_dropins'] = 'failed';
update_option( 'paulus_dropins_status', array( 'db-error.php' => 'kept', 'php-error.php' => 'failed' ) );
unlink( "$paulus_tmp/php-error.php" );
$paulus_html = paulus_render_card();
paulus_check( false !== strpos( $paulus_html, 'notice-error' ) && false === strpos( $paulus_html, 'written again' ), 'a failed install shows the error notice, not the success one' );
paulus_check( false !== strpos( $paulus_html, 'wp-content/php-error.php</code></td><td>Could not be written' ), 'the failed file reads as failed' );
$GLOBALS['t']['subsite'] = true;
$paulus_html             = paulus_render_card();
paulus_check( false === strpos( $paulus_html, 'paulus_install_dropins' ) && false === strpos( $paulus_html, 'notice' ) && false !== strpos( $paulus_html, 'whole network shares wp-content' ), 'a subsite shows the network note and no button or notice' );
paulus_check( null === paulus_install_dropins( true ), 'a subsite writes nothing while the main site runs the theme' );
$GLOBALS['t']['main_theme'] = 'twentytwentyfive';
paulus_check( paulus_dropins_writer() && is_array( paulus_install_dropins( true ) ), 'a subsite writes when the main site runs another theme' );
$GLOBALS['t']['current'] = 3;
paulus_check( ! paulus_dropins_writer(), 'a second subsite leaves the writing to the first' );
unset( $GLOBALS['t']['main_theme'], $GLOBALS['t']['current'] );
unlink( "$paulus_tmp/php-error.php" );
$GLOBALS['t']['subsite'] = false;
unset( $_GET['paulus_dropins'] );

set_transient( 'paulus_dropins_retry', 1 );
paulus_check( null === paulus_install_dropins() && ! file_exists( "$paulus_tmp/php-error.php" ), 'within the hour after a failure an admin page does not try again' );
paulus_check( null !== paulus_install_dropins( true ) && file_exists( "$paulus_tmp/php-error.php" ) && false === get_transient( 'paulus_dropins_retry' ), 'the button tries at once, and success clears the wait' );
if ( function_exists( 'posix_geteuid' ) && 0 !== posix_geteuid() ) {
	unlink( "$paulus_tmp/php-error.php" );
	chmod( $paulus_tmp, 0500 );
	$paulus_r = paulus_install_dropins();
	chmod( $paulus_tmp, 0700 );
	paulus_check( 'failed' === $paulus_r['php-error.php'] && get_transient( 'paulus_dropins_retry' ), 'a failed write is recorded and starts the hour wait' );
	paulus_check( null === paulus_install_dropins(), 'the next admin page waits instead of writing again' );
	delete_transient( 'paulus_dropins_retry' );
}

exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( dirname( __DIR__ ) . '/bin/build-error-pages.php' ) . ' --check 2>&1', $paulus_o, $paulus_code );
paulus_check( 0 === $paulus_code, 'errors/*.html match the builder' );
foreach ( array( '403', '500', '502', '503' ) as $paulus_c ) {
	$paulus_h = (string) file_get_contents( dirname( __DIR__ ) . "/errors/$paulus_c.html" );
	paulus_check( false !== strpos( $paulus_h, 'noindex' ) && false === strpos( $paulus_h, '{{' ) && preg_match( '~<title>[^<|]{1,32} \| Apostle of Doom</title>~', $paulus_h ), "errors/$paulus_c.html is filled, noindex and keeps the title rule" );
}

// The copies of the static pages: errors/ as it is at the root, this
// site's addresses in a subfolder (a second run, since PAULUS_URI is fixed).
paulus_install_dropins( true );
$paulus_copy = (string) file_get_contents( "$paulus_tmp/paulus-errors/403.html" );
paulus_check( paulus_dropin_is_ours( "$paulus_tmp/paulus-errors/403.html" ), 'the copy carries the marker' );
if ( ! getenv( 'PAULUS_TEST_SUB' ) ) {
	paulus_check( false !== strpos( paulus_error_htaccess_snippet(), 'ErrorDocument 503 /wp-content/paulus-errors/503.html' ), '.htaccess lines point at the copies in wp-content' );
	paulus_check( substr( $paulus_copy, strpos( $paulus_copy, "\n" ) + 1 ) === file_get_contents( dirname( __DIR__ ) . '/errors/403.html' ), 'a root install copies errors/403.html as it is' );
	exec( 'PAULUS_TEST_SUB=/blog ' . escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( __FILE__ ) . ' 2>&1', $paulus_so, $paulus_sc );
	paulus_check( 0 === $paulus_sc, 'the checks pass for a site in a subfolder: ' . implode( ' ', preg_grep( '/^FAIL/', $paulus_so ) ) );
} else {
	paulus_check( false !== strpos( paulus_error_htaccess_snippet(), 'ErrorDocument 403 /blog/wp-content/paulus-errors/403.html' ), 'the .htaccess lines follow the subfolder' );
	paulus_check( false !== strpos( $paulus_copy, 'href="/blog/"' ) && false !== strpos( $paulus_copy, 'href="/blog/wp-content/themes/paulus/assets/css/error-page.css"' ) && false === strpos( $paulus_copy, 'href="/"' ) && false === strpos( $paulus_copy, 'href="/wp-content/' ), 'the copy links the home page and stylesheets in the subfolder' );
}
paulus_remove_dropins();
paulus_check( ! is_dir( "$paulus_tmp/paulus-errors" ), 'a theme switch removes the copies and their folder' );

array_map( 'unlink', array_filter( glob( "$paulus_tmp/paulus-errors/*" ) ?: array(), 'is_file' ) );
@rmdir( "$paulus_tmp/paulus-errors" );
array_map( 'unlink', array_filter( glob( "$paulus_tmp/{,.}*", GLOB_BRACE ) ?: array(), 'is_file' ) );
@rmdir( $paulus_tmp );
echo $paulus_fail ? "$paulus_fail failed\n" : "all passed\n";
exit( $paulus_fail ? 1 : 0 );
