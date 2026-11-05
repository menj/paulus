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
function update_option( $k, $v ) {
	if ( 'paulus_retired_version' === $k ) {
		$GLOBALS['t']['lock_at_version'] = $GLOBALS['t']['options']['paulus_retired_lock'] ?? null;
	}
	$GLOBALS['t']['options'][ $k ] = $v;
	return true;
}
function delete_option( $k ) { unset( $GLOBALS['t']['options'][ $k ] ); return true; }
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
function do_action( $hook ) {
	foreach ( $GLOBALS['wp_filter'][ $hook ]->callbacks ?? array() as $cbs ) {
		foreach ( $cbs as $cb ) {
			call_user_func( $cb['function'] );
		}
	}
}
function t_count( $hook ) {
	if ( ! isset( $GLOBALS['wp_filter'][ $hook ] ) ) {
		return 0;
	}

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

// The clean-up on a version change: one request runs it under a lease; a
// request that dies is retried once its lease is stale; a running clean-up
// renews its lease, so it is never displaced.
define( 'PAULUS_VERSION', '9.9.9' );
t_load( 'inc/watch.php', array( 'paulus_cleanup_on_version_change', 'paulus_renew_cleanup_lease', 'paulus_swap_lock', 'paulus_release_lock' ) );
// The options table, as far as the lock's compare-and-set UPDATE goes.
class T_DB {
	public $options = 'wp_options';
	public function prepare( $sql, ...$args ) {
		return $args;
	}
	public function query( $args ) {
		if ( 2 === count( $args ) ) {
			// DELETE … WHERE option_name = %s AND option_value = %s.
			list( $name, $held ) = $args;
			if ( ! array_key_exists( $name, $GLOBALS['t']['options'] ) || (string) $GLOBALS['t']['options'][ $name ] !== $held ) {
				return 0;
			}
			unset( $GLOBALS['t']['options'][ $name ] );
			return 1;
		}
		list( $next, $name, $held ) = $args;
		if ( ! array_key_exists( $name, $GLOBALS['t']['options'] ) || (string) $GLOBALS['t']['options'][ $name ] !== $held ) {
			return 0;
		}
		$GLOBALS['t']['options'][ $name ] = $next;
		return 1;
	}
}
$GLOBALS['wpdb'] = new T_DB();
function wp_rand( $a, $b ) { return random_int( $a, $b ); }
function wp_cache_delete() { return true; }
$GLOBALS['t']['runs']  = 0;
$GLOBALS['t']['throw'] = true;
$GLOBALS['t']['during'] = null;
function paulus_cleanup_retired_files() {
	++$GLOBALS['t']['runs'];
	if ( $GLOBALS['t']['throw'] ) {
		throw new RuntimeException( 'died' );
	}
	if ( $GLOBALS['t']['during'] ) {
		call_user_func( $GLOBALS['t']['during'] );
	}
	return 0;
}
try {
	paulus_cleanup_on_version_change();
} catch ( RuntimeException $e ) {
	unset( $e );
}
check( 'a run that dies leaves the version unrecorded and its lease in place', ! isset( $GLOBALS['t']['options']['paulus_retired_version'] ) && isset( $GLOBALS['t']['options']['paulus_retired_lock'] ) );
check( 'and takes its tick hook with it', 0 === t_count( 'paulus_cleanup_tick' ) );
$GLOBALS['t']['throw'] = false;
paulus_cleanup_on_version_change();
check( 'a fresh lease keeps a second request out', 1 === $GLOBALS['t']['runs'] );
$stale = (string) ( time() - 600 ) . '.123456';
$GLOBALS['t']['options']['paulus_retired_lock'] = $stale;
check( 'of two requests that read the same stale lease, only one takes it over', paulus_swap_lock( 'paulus_retired_lock', $stale, time() . '.1' ) && ! paulus_swap_lock( 'paulus_retired_lock', $stale, time() . '.2' ) );
// A clean-up that runs long: its lease is aged, a tick renews it, and a
// rival that read the lease before the renewal cannot take it over.
$GLOBALS['t']['options']['paulus_retired_lock'] = $stale;
$GLOBALS['t']['during'] = static function () use ( $stale ) {
	$GLOBALS['t']['options']['paulus_retired_lock'] = (string) ( time() - 600 ) . '.' . substr( $GLOBALS['paulus_cleanup_lease'], strpos( $GLOBALS['paulus_cleanup_lease'], '.' ) + 1 );
	$GLOBALS['paulus_cleanup_lease']                = $GLOBALS['t']['options']['paulus_retired_lock'];
	$rival_read                                     = $GLOBALS['paulus_cleanup_lease'];
	do_action( 'paulus_cleanup_tick' );
	$GLOBALS['t']['renewed'] = time() - (int) $GLOBALS['t']['options']['paulus_retired_lock'] < 60;
	$GLOBALS['t']['rival']   = paulus_swap_lock( 'paulus_retired_lock', $rival_read, time() . '.9' );
};
paulus_cleanup_on_version_change();
check( 'a stale lease is taken over and the version recorded after the run', 2 === $GLOBALS['t']['runs'] && '9.9.9' === $GLOBALS['t']['options']['paulus_retired_version'] && ! isset( $GLOBALS['t']['options']['paulus_retired_lock'] ) );
check( 'the version is recorded while the lease is still held, and the lease freed after', is_string( $GLOBALS['t']['lock_at_version'] ) && '-done' === substr( $GLOBALS['t']['lock_at_version'], -5 ) );
check( 'a running clean-up renews its lease on a tick', ! empty( $GLOBALS['t']['renewed'] ) );
check( 'a rival holding the old lease cannot displace it', false === $GLOBALS['t']['rival'] );
paulus_cleanup_on_version_change();
check( 'once recorded, it does not run again', 2 === $GLOBALS['t']['runs'] );
// A run whose lease is taken over before it finishes leaves the lock and the
// version to the request that took it.
$GLOBALS['t']['options']['paulus_retired_version'] = '9.9.8';
$GLOBALS['t']['during'] = static function () {
	$GLOBALS['t']['options']['paulus_retired_lock'] = time() . '.successor';
};
paulus_cleanup_on_version_change();
check( 'a run that lost its lease frees nothing and records nothing', 3 === $GLOBALS['t']['runs'] && time() . '.successor' === $GLOBALS['t']['options']['paulus_retired_lock'] && '9.9.8' === $GLOBALS['t']['options']['paulus_retired_version'] );
unset( $GLOBALS['t']['options']['paulus_retired_lock'] );
$GLOBALS['t']['options']['paulus_retired_version'] = '9.9.9';

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
