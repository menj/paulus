<?php
/**
 * The development files clean-up (inc/dev-files.php), run against a temporary
 * theme folder. WordPress is replaced by small stubs; the theme's own file is
 * loaded as it is.
 *
 * Run: php tests/dev-files-test.php
 *
 * @package Paulus
 */

if ( 'cli' !== PHP_SAPI ) {
	exit;
}

define( 'ABSPATH', '/srv/site/' );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'DAY_IN_SECONDS', 86400 );
define( 'PAULUS_VERSION', '9.9.9' );
define( 'PAULUS_DIR', sys_get_temp_dir() . '/paulus-dev-' . getmypid() );
if ( in_array( 'keep-const', $argv, true ) ) {
	define( 'PAULUS_KEEP_DOTFILES', true );
}

$GLOBALS['t'] = array(
	'env'       => 'production',
	'options'   => array(),
	'transient' => array(),
	'hooks'     => array(),
	'cron'      => array(),
	'refuse'    => '',
	'during'    => null,
);
$log = sys_get_temp_dir() . '/paulus-dev-' . getmypid() . '.log';
ini_set( 'error_log', $log );
register_shutdown_function( static function () use ( $log ) {
	$e = error_get_last();
	if ( $e && E_ERROR === $e['type'] ) {
		fwrite( STDERR, $e['message'] . ' ' . $e['file'] . ':' . $e['line'] . "\n" );
	}
} );

function wp_get_environment_type() {
	return $GLOBALS['t']['env'];
}
function add_action( $hook, $fn ) {
	$GLOBALS['t']['hooks'][ $hook ][] = $fn;
}
function remove_action( $hook, $fn ) {
	$GLOBALS['t']['hooks'][ $hook ] = array_values( array_filter( $GLOBALS['t']['hooks'][ $hook ] ?? array(), static function ( $f ) use ( $fn ) { return $f !== $fn; } ) );
}
function do_action( $hook ) {
	foreach ( $GLOBALS['t']['hooks'][ $hook ] ?? array() as $fn ) {
		if ( is_string( $fn ) && function_exists( $fn ) ) {
			$fn();
		}
	}
}
function get_option( $k, $d = false ) {
	return $GLOBALS['t']['options'][ $k ] ?? $d;
}
function add_option( $k, $v ) {
	if ( array_key_exists( $k, $GLOBALS['t']['options'] ) ) {
		return false;
	}
	$GLOBALS['t']['options'][ $k ] = $v;
	return true;
}
function update_option( $k, $v ) {
	$GLOBALS['t']['options'][ $k ] = $v;
	return true;
}
function delete_option( $k ) {
	unset( $GLOBALS['t']['options'][ $k ] );
	return true;
}
function get_transient( $k ) {
	return $GLOBALS['t']['transient'][ $k ] ?? false;
}
function set_transient( $k, $v ) {
	$GLOBALS['t']['transient'][ $k ] = $v;
	return true;
}
function wp_cache_delete() {
	return true;
}
function wp_rand( $a, $b ) {
	return random_int( $a, $b );
}
function wp_next_scheduled( $hook ) {
	return $GLOBALS['t']['cron'][ $hook ] ?? false;
}
function wp_schedule_event( $time, $recurrence, $hook ) {
	$GLOBALS['t']['cron'][ $hook ] = $time;
	return true;
}
function wp_clear_scheduled_hook( $hook ) {
	unset( $GLOBALS['t']['cron'][ $hook ] );
}
// A file named in 'refuse' cannot be deleted, as under a permission the web user lacks.
function wp_delete_file( $p ) {
	if ( '' !== $GLOBALS['t']['refuse'] && false !== strpos( $p, $GLOBALS['t']['refuse'] ) ) {
		return;
	}
	@unlink( $p );
}
// The options table, as far as the lease's compare-and-set UPDATE and DELETE go.
class T_DB {
	public $options = 'wp_options';
	public function prepare( $sql, ...$args ) {
		return $args;
	}
	public function get_var( $args ) {
		return $GLOBALS['t']['options'][ $args[0] ] ?? null;
	}
	public function query( $args ) {
		$opts = &$GLOBALS['t']['options'];
		if ( 2 === count( $args ) ) {
			list( $name, $held ) = $args;
			if ( ! array_key_exists( $name, $opts ) || (string) $opts[ $name ] !== $held ) {
				return 0;
			}
			unset( $opts[ $name ] );
			return 1;
		}
		list( $next, $name, $held ) = $args;
		if ( ! array_key_exists( $name, $opts ) || (string) $opts[ $name ] !== $held ) {
			return 0;
		}
		$opts[ $name ] = $next;
		return 1;
	}
}
$GLOBALS['wpdb'] = new T_DB();

require dirname( __DIR__ ) . '/inc/dev-files.php';

$fail = 0;
$n    = 0;
function check( $label, $ok ) {
	global $fail, $n;
	++$n;
	echo ( $ok ? 'ok   ' : 'FAIL ' ) . $label . "\n";
	if ( ! $ok ) {
		++$fail;
	}
}
function rrmdir( $d ) {
	if ( ! file_exists( $d ) && ! is_link( $d ) ) {
		return;
	}
	if ( is_link( $d ) || is_file( $d ) ) {
		@chmod( $d, 0644 );
		@unlink( $d );
		return;
	}
	foreach ( (array) scandir( $d ) as $f ) {
		if ( '.' !== $f && '..' !== $f ) {
			rrmdir( "$d/$f" );
		}
	}
	@rmdir( $d );
}
// A theme folder as an FTP upload of the working copy leaves it.
function build() {
	rrmdir( PAULUS_DIR );
	foreach ( array( '.git/objects/ab', '.github/workflows', '.claude', 'tests', 'tools', 'docs', 'bin', 'data', 'inc', 'assets/docs' ) as $d ) {
		mkdir( PAULUS_DIR . '/' . $d, 0755, true );
	}
	foreach ( array( '.git/objects/ab/cd', '.git/config', '.github/workflows/ci.yml', '.claude/CLAUDE.md', '.gitignore', 'tests/a-test.php', 'tools/build.php', 'docs/kw.csv', 'bin/run.php', 'data/x.json', 'inc/x.php', 'functions.php', '.htaccess', '.user.ini', 'README.md', 'assets/docs/n.pdf' ) as $f ) {
		file_put_contents( PAULUS_DIR . '/' . $f, 'x' );
	}
	chmod( PAULUS_DIR . '/.git/objects/ab/cd', 0444 ); // git's objects are read-only.
}
function reset_state() {
	$GLOBALS['t']['options']   = array();
	$GLOBALS['t']['transient'] = array();
	$GLOBALS['t']['during']    = null;
	$GLOBALS['t']['refuse']    = '';
	unset( $GLOBALS['paulus_cleanup_lease'] );
}
function names( $list ) {
	$names = array_map( 'basename', $list );
	sort( $names );
	return $names;
}
$left_after = static function () {
	return array_values( array_filter( names( array_map( static function ( $n ) { return PAULUS_DIR . '/' . $n; }, array_diff( (array) scandir( PAULUS_DIR ), array( '.', '..' ) ) ) ), static function ( $n ) { return in_array( $n, array( '.git', '.github', '.claude', '.gitignore', 'tests', 'tools', 'docs' ), true ); } ) );
};

// The subprocess run for the constant.
if ( in_array( 'keep-const', $argv, true ) ) {
	build();
	echo array() === paulus_hidden_entries() && ! paulus_dev_files_schedule() ? "const-keeps\n" : "const-removes\n";
	rrmdir( PAULUS_DIR );
	exit;
}

// 1. What is listed.
build();
check( 'the hidden entries and tests, tools and docs are listed; .htaccess, .user.ini, bin and data are not', array( '.claude', '.git', '.github', '.gitignore', 'docs', 'tests', 'tools' ) === names( paulus_hidden_entries() ) );

// 2. A development or local site, or one with the constant, keeps them.
foreach ( array( 'development', 'local' ) as $env ) {
	$GLOBALS['t']['env'] = $env;
	check( "a $env site keeps them all", array() === paulus_hidden_entries() && paulus_keeps_dev_files() );
	paulus_dev_files_schedule();
	check( "and schedules no hourly event on a $env site", ! wp_next_scheduled( 'paulus_dev_files_hourly' ) );
}
$GLOBALS['t']['env'] = 'production';
$out = (string) shell_exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( __FILE__ ) . ' keep-const' );
check( 'a site that defines PAULUS_KEEP_DOTFILES keeps them all', false !== strpos( $out, 'const-keeps' ) );
paulus_dev_files_schedule();
check( 'a production site schedules the hourly event', false !== wp_next_scheduled( 'paulus_dev_files_hourly' ) );

// 3. A fresh lease held by another request keeps this one out.
build();
reset_state();
$GLOBALS['t']['options']['paulus_retired_lock'] = time() . '.111111';
paulus_cleanup_on_version_change();
check( 'a fresh lease held by another request keeps this one out', file_exists( PAULUS_DIR . '/.git' ) && ! isset( $GLOBALS['t']['options']['paulus_retired_version'] ) );

// 4. Two requests read the same stale lease: one wins.
$stale = ( time() - 600 ) . '.222222';
$GLOBALS['t']['options']['paulus_retired_lock'] = $stale;
check( 'of two requests that read the same stale lease, only one takes it over', paulus_swap_lock( 'paulus_retired_lock', $stale, time() . '.1' ) && ! paulus_swap_lock( 'paulus_retired_lock', $stale, time() . '.2' ) );

// 5, 6, 7. A stale lease is taken over; development files go, the rest stays; version recorded, lease freed.
$GLOBALS['t']['options']['paulus_retired_lock'] = $stale;
paulus_cleanup_on_version_change();
check( 'a stale lease is taken over and every development file goes', array() === $left_after() && ! file_exists( PAULUS_DIR . '/.git' ) );
check( 'the code, data, bin, .htaccess, .user.ini and assets/docs stay', file_exists( PAULUS_DIR . '/inc/x.php' ) && file_exists( PAULUS_DIR . '/functions.php' ) && file_exists( PAULUS_DIR . '/data/x.json' ) && file_exists( PAULUS_DIR . '/bin/run.php' ) && file_exists( PAULUS_DIR . '/.htaccess' ) && file_exists( PAULUS_DIR . '/.user.ini' ) && file_exists( PAULUS_DIR . '/assets/docs/n.pdf' ) );
check( 'the version is recorded and the lease freed', '9.9.9' === get_option( 'paulus_retired_version' ) && ! isset( $GLOBALS['t']['options']['paulus_retired_lock'] ) );

// 8. Not twice for one version.
mkdir( PAULUS_DIR . '/.git' );
paulus_cleanup_on_version_change();
check( 'it does not run twice for one version', file_exists( PAULUS_DIR . '/.git' ) );

// 9. The hourly callback removes a .git added after the version was recorded.
paulus_dev_files_hourly();
check( 'the hourly callback removes a .git added after the version was recorded', ! file_exists( PAULUS_DIR . '/.git' ) && '9.9.9' === get_option( 'paulus_retired_version' ) && ! isset( $GLOBALS['t']['options']['paulus_retired_lock'] ) );

// 10. ... and leaves a folder with no development entry alone.
$GLOBALS['t']['options']['paulus_retired_version'] = '9.9.9';
$GLOBALS['t']['options']['paulus_retired_lock']    = time() . '.333333';
paulus_dev_files_hourly();
check( 'the hourly callback leaves a clean folder alone: the version and the lock are as they were', '9.9.9' === get_option( 'paulus_retired_version' ) && time() - (int) $GLOBALS['t']['options']['paulus_retired_lock'] < 5 );
unset( $GLOBALS['t']['options']['paulus_retired_lock'] );

// 11. A running clean-up renews its lease.
build();
reset_state();
$GLOBALS['t']['during'] = null;
$renewed = null;
$stale   = ( time() - 600 ) . '.444444';
$GLOBALS['t']['options']['paulus_retired_lock'] = $stale;
// A tick that finds its lease a minute old renews it. Age the lease inside the run, then tick.
function paulus_probe_tick() {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;
	$held = (string) $GLOBALS['paulus_cleanup_lease'];
	$aged = ( time() - 120 ) . '.' . substr( $held, strpos( $held, '.' ) + 1 );
	$GLOBALS['t']['options']['paulus_retired_lock'] = $aged;
	$GLOBALS['paulus_cleanup_lease']                = $aged;
	paulus_renew_cleanup_lease();
	$GLOBALS['t']['renewed'] = time() - (int) $GLOBALS['t']['options']['paulus_retired_lock'] < 60 && $aged !== $GLOBALS['t']['options']['paulus_retired_lock'];
}
add_action( 'paulus_cleanup_tick', 'paulus_probe_tick' );
paulus_cleanup_on_version_change();
check( 'a running clean-up renews its lease, at most once a minute', ! empty( $GLOBALS['t']['renewed'] ) && '9.9.9' === get_option( 'paulus_retired_version' ) );

// 12. A symlink is removed, its target is not.
build();
reset_state();
$outside = sys_get_temp_dir() . '/paulus-dev-target-' . getmypid();
mkdir( $outside );
file_put_contents( "$outside/keep.txt", 'keep' );
symlink( $outside, PAULUS_DIR . '/.vscode' );         // a link at the top: removed itself.
symlink( $outside, PAULUS_DIR . '/tests/link' );      // a link inside a folder to remove.
paulus_cleanup_on_version_change();
check( 'a symlink is removed and its target is not followed', ! is_link( PAULUS_DIR . '/.vscode' ) && ! file_exists( PAULUS_DIR . '/tests' ) && file_exists( "$outside/keep.txt" ) );
rrmdir( $outside );

// 13. Something that cannot be removed: no version, the lease kept, one log line a day.
build();
reset_state();
file_put_contents( $log, '' );
$GLOBALS['t']['refuse'] = '.git/config';
paulus_cleanup_on_version_change();
$lease = $GLOBALS['t']['options']['paulus_retired_lock'] ?? null;
check( 'a file that cannot be removed leaves the version unrecorded and the lease kept', ! isset( $GLOBALS['t']['options']['paulus_retired_version'] ) && is_string( $lease ) && '-done' !== substr( $lease, -5 ) );
paulus_cleanup_on_version_change();
paulus_cleanup_on_version_change();
$lines = array_filter( explode( "\n", (string) file_get_contents( $log ) ), static function ( $l ) { return false !== strpos( $l, 'could not be removed' ); } );
check( 'the retry does not come on every request: one run, one log line a day', 1 === count( $lines ) && false !== strpos( reset( $lines ), 'delete them by FTP' ) );
$GLOBALS['t']['options']['paulus_retired_lock'] = ( time() - 600 ) . '.555555';
paulus_cleanup_on_version_change();
$lines = array_filter( explode( "\n", (string) file_get_contents( $log ) ), static function ( $l ) { return false !== strpos( $l, 'could not be removed' ); } );
check( 'once the lease is five minutes stale it retries, and logs no second line the same day', 1 === count( $lines ) && ! isset( $GLOBALS['t']['options']['paulus_retired_version'] ) );
$GLOBALS['t']['refuse'] = '';
$GLOBALS['t']['options']['paulus_retired_lock'] = ( time() - 600 ) . '.666666';
paulus_cleanup_on_version_change();
check( 'when the cause is gone the retry succeeds and records the version', '9.9.9' === get_option( 'paulus_retired_version' ) && array() === $left_after() );

// The event leaves with the theme.
paulus_dev_files_schedule();
foreach ( $GLOBALS['t']['hooks']['switch_theme'] as $fn ) {
	$fn();
}
check( 'the hourly event is cleared when the theme is switched', ! wp_next_scheduled( 'paulus_dev_files_hourly' ) );

rrmdir( PAULUS_DIR );
@unlink( $log );
echo "\n$n checks, $fail failed\n";
exit( $fail ? 1 : 0 );
