<?php
/**
 * The fixes from the live logs of November 2026: /?p=<number> answered 410
 * when no post has it, the development files removed from the theme folder,
 * the save_post watchers set aside during the sync, and the .htaccess block
 * that denies WordPress's own files. The functions are read from the theme's
 * files and WordPress is replaced by small stubs.
 *
 * Run: php tests/live-logs-test.php
 *
 * @package Paulus
 */

// Run from the command line only: on a web server this file does nothing.
if ( 'cli' !== PHP_SAPI ) {
	exit;
}

define( 'ABSPATH', '/srv/site/' );
define( 'MINUTE_IN_SECONDS', 60 );

// Load named functions from a theme file, without the rest of it.
function t_load( $file, $names ) {
	$src = (string) file_get_contents( dirname( __DIR__ ) . '/' . $file );
	foreach ( $names as $name ) {
		if ( ! preg_match( '/^function ' . $name . '\(.*?^}\n/ms', $src, $m ) ) {
			fwrite( STDERR, "not found: $name\n" );
			exit( 1 );
		}
		eval( $m[0] ); // phpcs:ignore Squiz.PHP.Eval
	}
}

$GLOBALS['t'] = array( 'env' => 'production', 'options' => array(), 'deleted' => array() );
function wp_get_environment_type() { return $GLOBALS['t']['env']; }
function wp_normalize_path( $p ) { return str_replace( '\\', '/', $p ); }
function trailingslashit( $p ) { return rtrim( $p, '/\\' ) . '/'; }
function apply_filters( $hook, $v ) { return $v; }
function get_option( $k, $d = false ) { return $GLOBALS['t']['options'][ $k ] ?? $d; }
function add_option( $k, $v ) {
	if ( array_key_exists( $k, $GLOBALS['t']['options'] ) ) {
		return false;
	}
	$GLOBALS['t']['options'][ $k ] = $v;
	return true;
}
function update_option( $k, $v ) { $GLOBALS['t']['options'][ $k ] = $v; return true; }
function delete_option( $k ) { unset( $GLOBALS['t']['options'][ $k ] ); return true; }
function error_log_stub() {}
function wp_delete_file( $p ) { @unlink( $p ); }

t_load( 'inc/watch.php', array( 'paulus_is_gone_post_number', 'paulus_keeps_dev_files', 'paulus_hidden_entries', 'paulus_remove_tree' ) );
t_load( 'inc/security.php', array( 'paulus_sec_core_lines', 'paulus_sec_core_prefix' ) );
t_load( 'inc/importer.php', array( 'paulus_quiet_watcher_classes', 'paulus_without_post_watchers' ) );

$fail = 0;
$n    = 0;
function check( $label, $ok ) {
	global $fail, $n;
	++$n;
	if ( ! $ok ) {
		++$fail;
		echo "FAIL: $label\n";
	}
}

// 410 for a post number no post has.
check( '/?p=739 is gone', paulus_is_gone_post_number( array( 'p' => '739' ) ) );
check( 'a crawler\'s page_id beside it changes nothing', paulus_is_gone_post_number( array( 'p' => '739', 'page_id' => '2' ) ) );
check( 'p=0 is the home page, never gone', ! paulus_is_gone_post_number( array( 'p' => '0' ) ) && ! paulus_is_gone_post_number( array( 'p' => '000' ) ) );
check( 'a preview is left alone', ! paulus_is_gone_post_number( array( 'p' => '739', 'preview' => 'true' ) ) );
check( 'a search is left alone', ! paulus_is_gone_post_number( array( 'p' => '739', 's' => 'paul' ) ) );
check( 'no p, or p that is not a number, is left alone', ! paulus_is_gone_post_number( array() ) && ! paulus_is_gone_post_number( array( 'p' => '7a' ) ) && ! paulus_is_gone_post_number( array( 'p' => array( '7' ) ) ) );

// Development files in the theme folder.
$root = sys_get_temp_dir() . '/paulus-live-' . getmypid();
define( 'PAULUS_DIR', $root );
foreach ( array( '.git/objects/ab', '.claude', 'tests', 'tools', 'docs', 'assets/docs', 'inc' ) as $d ) {
	mkdir( "$root/$d", 0755, true );
}
foreach ( array( '.git/objects/ab/cd', '.git/config', '.claude/CLAUDE.md', 'tests/a-test.php', 'tools/x.php', 'docs/kw.csv', 'assets/docs/notes.pdf', 'inc/x.php', '.htaccess', '.gitignore', 'README.md' ) as $f ) {
	file_put_contents( "$root/$f", 'x' );
}
chmod( "$root/.git/objects/ab/cd", 0444 );
$list = array_map( 'basename', paulus_hidden_entries() );
sort( $list );
check( 'the dot entries and tests, tools and docs are listed; .htaccess is not', array( '.claude', '.git', '.gitignore', 'docs', 'tests', 'tools' ) === $list );
$GLOBALS['t']['env'] = 'development';
check( 'a development site keeps them all', array() === paulus_hidden_entries() );
$GLOBALS['t']['env'] = 'production';
$gone = 0;
foreach ( paulus_hidden_entries() as $p ) {
	$gone += is_dir( $p ) ? paulus_remove_tree( $p ) : ( unlink( $p ) ? 1 : 0 );
}
check( 'every development file goes', ! file_exists( "$root/.git" ) && ! file_exists( "$root/tests" ) && ! file_exists( "$root/tools" ) && ! file_exists( "$root/docs" ) && ! file_exists( "$root/.claude" ) && 7 === $gone );
check( 'assets/docs, the theme\'s code, .htaccess and README.md stay', file_exists( "$root/assets/docs/notes.pdf" ) && file_exists( "$root/inc/x.php" ) && file_exists( "$root/.htaccess" ) && file_exists( "$root/README.md" ) );
foreach ( array( 'assets/docs/notes.pdf', 'inc/x.php', '.htaccess', 'README.md' ) as $f ) {
	unlink( "$root/$f" );
}
rmdir( "$root/assets/docs" );
rmdir( "$root/assets" );
rmdir( "$root/inc" );
rmdir( $root );

// The save_post watchers.
class WP_Hook {
	public $callbacks = array();
}
// phpcs:disable
eval( 'namespace RankMath\Analytics; class Watcher { public function update_post_info() {} }' );
// phpcs:enable
class Other_Watcher {
	public function on_save() {}
}
$GLOBALS['wp_filter'] = array();
function add_action( $hook, $fn, $prio = 10, $args = 1 ) {
	if ( ! isset( $GLOBALS['wp_filter'][ $hook ] ) ) {
		$GLOBALS['wp_filter'][ $hook ] = new WP_Hook();
	}
	$GLOBALS['wp_filter'][ $hook ]->callbacks[ $prio ][] = array( 'function' => $fn, 'accepted_args' => $args );
}
function remove_action( $hook, $fn, $prio = 10 ) {
	foreach ( $GLOBALS['wp_filter'][ $hook ]->callbacks[ $prio ] as $i => $cb ) {
		if ( $cb['function'] === $fn ) {
			unset( $GLOBALS['wp_filter'][ $hook ]->callbacks[ $prio ][ $i ] );
		}
	}
}
function t_count( $hook ) {
	return array_sum( array_map( 'count', $GLOBALS['wp_filter'][ $hook ]->callbacks ) );
}
$rm    = new RankMath\Analytics\Watcher();
$other = new Other_Watcher();
add_action( 'save_post', array( $rm, 'update_post_info' ), 99, 2 );
add_action( 'save_post', array( $other, 'on_save' ) );
add_action( 'save_post', 'strlen' );
$inside = null;
$out    = paulus_without_post_watchers(
	static function () use ( &$inside ) {
		$inside = t_count( 'save_post' );
		return 'ran';
	}
);
check( 'Rank Math\'s watcher is off during the run, the others stay', 2 === $inside && 'ran' === $out );
check( 'and back afterwards, at its own priority and argument count', 3 === t_count( 'save_post' ) && 2 === ( $GLOBALS['wp_filter']['save_post']->callbacks[99] ? end( $GLOBALS['wp_filter']['save_post']->callbacks[99] )['accepted_args'] : 0 ) );
try {
	paulus_without_post_watchers( static function () { throw new RuntimeException( 'x' ); } );
} catch ( RuntimeException $e ) {
	unset( $e );
}
check( 'it is put back even when the run throws', 3 === t_count( 'save_post' ) );

// The .htaccess block for WordPress's own files.
check( 'WordPress at the .htaccess folder: no prefix', '' === paulus_sec_core_prefix( '/srv/site/', '/srv/site/' ) && '' === paulus_sec_core_prefix( '/srv/site', '/srv/site/' ) );
check( 'WordPress in its own subfolder: its folder', 'wp/' === paulus_sec_core_prefix( '/srv/site/', '/srv/site/wp/' ) );
check( 'WordPress outside the .htaccess folder: no block', null === paulus_sec_core_prefix( '/srv/site/', '/opt/wp/' ) );
$lines = implode( "\n", paulus_sec_core_lines( 'wp/' ) );
check( 'the rules carry the subfolder', false !== strpos( $lines, 'RewriteRule ^wp/wp-includes/' ) && false !== strpos( $lines, 'RewriteRule ^wp/readme\.html$ - [F,L]' ) );
$rx = array();
foreach ( paulus_sec_core_lines( '' ) as $line ) {
	if ( preg_match( '/^RewriteRule (\S+) - \[F,L\]$/', $line, $m ) ) {
		$rx[] = '#' . $m[1] . '#';
	}
}
$denied = static function ( $path ) use ( $rx ) {
	foreach ( $rx as $r ) {
		if ( preg_match( $r, $path ) ) {
			return true;
		}
	}
	return false;
};
foreach ( array( 'wp-includes/blocks/rss.php', 'wp-includes/version.php', 'wp-includes/Requests/src/Autoload.php', 'wp-settings.php', 'wp-config-sample.php', 'readme.html', 'wp-admin/install.php' ) as $p ) {
	check( "$p is denied", $denied( $p ) );
}
foreach ( array( 'wp-includes/js/tinymce/wp-tinymce.php', 'wp-includes/ms-files.php', 'wp-includes/css/dashicons.min.css', 'wp-login.php', 'index.php', 'wp-admin/index.php', 'wp-content/themes/paulus/readme.html' ) as $p ) {
	check( "$p stays open", ! $denied( $p ) );
}

echo "Checks: $n\n", $fail ? "FAIL ($fail)\n" : "PASS\n";
exit( $fail ? 1 : 0 );
