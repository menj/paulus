<?php
/**
 * The development files clean-up.
 *
 * An upload of the working copy by FTP puts the repository (.git, .gitignore
 * and the other hidden entries), tools/, tests/ and docs/ on the live server,
 * and an FTP upload never deletes what an earlier release shipped and this
 * one does not. On the first request of any kind after the theme version
 * changes the theme removes them, once, under a lease held in an option, and
 * again every hour through WP-Cron, since an upload can bring .git back at any
 * time. The theme's .htaccess refuses the same paths until then.
 *
 * - Kept: .htaccess, .user.ini, .well-known, and every other file of the
 *   theme. A local or development site keeps all of it, and so does a site
 *   that defines PAULUS_KEEP_DOTFILES as true.
 * - A link is removed itself and never followed. A read-only file (git keeps
 *   its objects read-only) is made writable first.
 * - The version is recorded only when nothing is left. Otherwise the lease
 *   stays, so the retry comes every five minutes and never on every request,
 *   and one log line a day says how many files remain.
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

/**
 * Remove a folder inside the theme and everything in it. Links are removed,
 * never followed, and nothing outside the theme folder is touched.
 *
 * @param string $root The folder.
 * @return int Files removed.
 */
function paulus_remove_tree( $root ) {
	$theme = realpath( PAULUS_DIR ) . DIRECTORY_SEPARATOR;
	if ( ! is_dir( $root ) || is_link( $root ) || 0 !== strpos( realpath( $root ), $theme ) ) {
		return 0;
	}
	$gone = 0;
	// A folder that cannot be opened is skipped (CATCH_GET_CHILD), and one
	// that cannot be listed at all ends the walk, never the admin page.
	try {
		$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST, RecursiveIteratorIterator::CATCH_GET_CHILD );
	} catch ( UnexpectedValueException $e ) {
		return 0;
	}
	foreach ( $it as $f ) {
		// A long walk keeps the version clean-up's lease fresh: the tick
		// fires for every entry, and the renewal itself goes by the clock
		// (once a minute), so a slow entry delays it by that entry alone.
		do_action( 'paulus_cleanup_tick' );
		if ( $f->isDir() && ! $f->isLink() ) {
			@chmod( $f->getPathname(), 0755 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors, WordPress.WP.AlternativeFunctions
			@rmdir( $f->getPathname() ); // phpcs:ignore WordPress.PHP.NoSilencedErrors, WordPress.WP.AlternativeFunctions
		} else {
			if ( ! $f->isLink() ) {
				// git's pack files are read-only. chmod follows links, so a link is left as it is.
				@chmod( $f->getPathname(), 0644 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors, WordPress.WP.AlternativeFunctions
			}
			wp_delete_file( $f->getPathname() );
			$gone += file_exists( $f->getPathname() ) || is_link( $f->getPathname() ) ? 0 : 1;
		}
	}
	@rmdir( $root ); // phpcs:ignore WordPress.PHP.NoSilencedErrors, WordPress.WP.AlternativeFunctions
	return $gone;
}

// Development files at the top of the theme folder, which an FTP upload of a
// working copy carries and the web server may serve: the hidden entries (the
// repository's `.git`, `.github`, `.gitignore` and `.gitattributes`, and
// editor folders such as `.vscode`, `.idea` and `.claude`), and the folders a
// release zip leaves out (`tests`, `tools`, `docs`). Server files that may
// belong to the host (`.htaccess`, `.user.ini`, `.well-known`) stay. A local
// or development site keeps all of them, so a theme run from a working copy
// keeps its repository; so does `define( 'PAULUS_KEEP_DOTFILES', true );`.
function paulus_keeps_dev_files() {
	return ( defined( 'PAULUS_KEEP_DOTFILES' ) && PAULUS_KEEP_DOTFILES )
		|| in_array( wp_get_environment_type(), array( 'local', 'development' ), true );
}

function paulus_hidden_entries() {
	if ( paulus_keeps_dev_files() ) {
		return array();
	}
	$keep = array( '.', '..', '.htaccess', '.user.ini', '.well-known' );
	$list = array();
	foreach ( (array) scandir( PAULUS_DIR ) as $name ) {
		if ( is_string( $name ) && '.' === substr( $name, 0, 1 ) && ! in_array( $name, $keep, true ) ) {
			$list[] = PAULUS_DIR . '/' . $name;
		}
	}
	foreach ( array( 'tests', 'tools', 'docs' ) as $name ) {
		if ( file_exists( PAULUS_DIR . '/' . $name ) || is_link( PAULUS_DIR . '/' . $name ) ) {
			$list[] = PAULUS_DIR . '/' . $name;
		}
	}
	return $list;
}

/**
 * Remove from the theme folder what earlier releases shipped and this one no
 * longer does: the build script and the drafts it read, the Greek text, the
 * Python cache, and the content texts as HTML or plain text (the texts are
 * JSON since 2.62.38). An FTP upload copies the new files but deletes none,
 * so without this they would stay on the server. Only these named paths,
 * inside the theme folder, are touched.
 *
 * @return int Files removed.
 */
function paulus_cleanup_retired_files() {
	$gone  = 0;
	$paths = array_merge(
		array( PAULUS_DIR . '/build.py' ),
		(array) glob( PAULUS_DIR . '/content/articles/*.{html,htm,txt}', GLOB_BRACE )
	);
	$dirs = array( PAULUS_DIR . '/content/src', PAULUS_DIR . '/sblgnt', PAULUS_DIR . '/__pycache__' );
	foreach ( paulus_hidden_entries() as $path ) {
		if ( is_dir( $path ) && ! is_link( $path ) ) {
			$dirs[] = $path;
		} else {
			$paths[] = $path;
		}
	}
	foreach ( $dirs as $dir ) {
		do_action( 'paulus_cleanup_tick' );
		$gone += paulus_remove_tree( $dir );
	}
	foreach ( $paths as $path ) {
		do_action( 'paulus_cleanup_tick' );
		if ( is_link( $path ) ) {
			wp_delete_file( $path );
			$gone += is_link( $path ) ? 0 : 1;
		} elseif ( is_file( $path ) && 0 === strpos( realpath( $path ), realpath( PAULUS_DIR ) . DIRECTORY_SEPARATOR ) ) {
			wp_delete_file( $path );
			$gone += file_exists( $path ) ? 0 : 1;
		}
	}
	return $gone;
}

/**
 * Once for each version, on the first request of any kind after the version
 * changes (an FTP upload of a working copy puts `.git` and the tests on the
 * server at once, and the site may see no administrator for days), and with
 * the weekly image housekeeping.
 *
 * One request runs it, under a lease held in an option: add_option() takes
 * a free lease (it fails when the option exists), and the running clean-up
 * renews it every minute while it works (paulus_renew_cleanup_lease(), on
 * the "paulus_cleanup_tick" action fired for every entry it removes), so a
 * long clean-up is never displaced, and releases it only while it still
 * holds it (paulus_release_lock()). A lease left unrenewed for five minutes
 * belongs to a request that died, and is taken over with one compare-and-set
 * UPDATE that only one request can win (paulus_swap_lock()). The version is
 * recorded after the clean-up returns, so a request that dies is retried.
 */
function paulus_cleanup_on_version_change() {
	if ( get_option( 'paulus_retired_version' ) === PAULUS_VERSION ) {
		return;
	}
	$lease = time() . '.' . wp_rand( 100000, 999999 );
	if ( ! add_option( 'paulus_retired_lock', $lease, '', false ) ) {
		$held = (string) get_option( 'paulus_retired_lock' );
		if ( time() - (int) $held < 5 * MINUTE_IN_SECONDS || ! paulus_swap_lock( 'paulus_retired_lock', $held, $lease ) ) {
			return;
		}
	}
	// Read the version again, from the database, now the lease is held: a
	// request that passed the check above may have paused while another ran
	// the clean-up, recorded the version and released the lease.
	global $wpdb;
	wp_cache_delete( 'paulus_retired_version', 'options' );
	if ( PAULUS_VERSION === (string) $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", 'paulus_retired_version' ) ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		paulus_release_lock( 'paulus_retired_lock', $lease );
		return;
	}
	$GLOBALS['paulus_cleanup_lease'] = $lease;
	add_action( 'paulus_cleanup_tick', 'paulus_renew_cleanup_lease' );
	try {
		$gone = paulus_cleanup_retired_files();
	} finally {
		remove_action( 'paulus_cleanup_tick', 'paulus_renew_cleanup_lease' );
	}
	// Something that could not be removed (a permission, a folder that cannot
	// be read): the version stays unrecorded and the lease stays as it is, so
	// the next try comes when the lease is five minutes stale, not on every
	// request, and the log gets one line a day until the files are deleted by FTP.
	$left = paulus_dev_files_left();
	if ( $left > 0 ) {
		unset( $GLOBALS['paulus_cleanup_lease'] );
		paulus_dev_files_log_left( $left );
		return;
	}
	// Recorded only once the clean-up has returned, and while this request
	// still holds the lease: it renews the lease one last time (which fails
	// if another request took it over, and then leaves everything to that
	// one), records the version, and only then releases the lease. A request
	// arriving in between finds the lease fresh or the version recorded, so
	// it never starts a second clean-up. A request that dies part-way leaves
	// the version unrecorded and its lease unrenewed, so a later request
	// takes it over and tries again.
	$held  = (string) $GLOBALS['paulus_cleanup_lease'];
	$final = time() . '.' . substr( $held, strpos( $held, '.' ) + 1 ) . '-done';
	unset( $GLOBALS['paulus_cleanup_lease'] );
	if ( ! paulus_swap_lock( 'paulus_retired_lock', $held, $final ) ) {
		return;
	}
	update_option( 'paulus_retired_version', PAULUS_VERSION, false );
	paulus_release_lock( 'paulus_retired_lock', $final );
	if ( $gone ) {
		error_log( 'Paulus: removed ' . $gone . ' retired and development files from the theme folder.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
	}
}
add_action( 'init', 'paulus_cleanup_on_version_change', 1 );

/**
 * Renew the running clean-up's lease, at most once a minute, with the same
 * compare-and-set, so it never looks stale while it works.
 */
function paulus_renew_cleanup_lease() {
	$held = (string) ( $GLOBALS['paulus_cleanup_lease'] ?? '' );
	if ( '' === $held || time() - (int) $held < MINUTE_IN_SECONDS ) {
		return;
	}
	$next = time() . '.' . substr( $held, strpos( $held, '.' ) + 1 );
	if ( paulus_swap_lock( 'paulus_retired_lock', $held, $next ) ) {
		$GLOBALS['paulus_cleanup_lease'] = $next;
	}
}

/**
 * Release a lock only while it still holds this request's value, in one
 * DELETE, so a request that lost its lease never frees its successor's.
 *
 * @param string $name The lock's option name.
 * @param string $held This request's value.
 * @return bool Whether this request still held it and released it.
 */
function paulus_release_lock( $name, $held ) {
	global $wpdb;
	$done = (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", $name, $held ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	wp_cache_delete( $name, 'options' );
	wp_cache_delete( 'alloptions', 'options' );
	wp_cache_delete( 'notoptions', 'options' );
	return 1 === $done;
}

/**
 * Replace a lock's value, for one request only: the new value is written
 * only where the row still holds the value read before, in a single UPDATE,
 * so of two requests that read the same value exactly one changes the row.
 *
 * @param string $name The lock's option name.
 * @param string $held The value read from it.
 * @param string $next The value to write.
 * @return bool Whether this request changed it.
 */
function paulus_swap_lock( $name, $held, $next ) {
	global $wpdb;
	$won = (int) $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND option_value = %s", $next, $name, $held ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	// The cached list of autoloaded options is cleared too, for a lock
	// option that was saved with autoload on.
	wp_cache_delete( $name, 'options' );
	wp_cache_delete( 'alloptions', 'options' );
	return 1 === $won;
}
add_action( 'paulus_image_housekeeping', 'paulus_cleanup_retired_files', 20 );
add_action( 'after_switch_theme', static function () {
	$gone = paulus_cleanup_retired_files();
	if ( $gone ) {
		error_log( 'Paulus: removed ' . $gone . ' retired and development files from the theme folder.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
	}
} );

/**
 * How many files the clean-up has left: the development entries at the top
 * of the theme folder and the retired files, counted file by file (a link
 * counts as one, and is never followed).
 *
 * @return int
 */
function paulus_dev_files_left() {
	$left  = 0;
	$paths = array_merge(
		array( PAULUS_DIR . '/build.py', PAULUS_DIR . '/content/src', PAULUS_DIR . '/sblgnt', PAULUS_DIR . '/__pycache__' ),
		(array) glob( PAULUS_DIR . '/content/articles/*.{html,htm,txt}', GLOB_BRACE ),
		paulus_hidden_entries()
	);
	foreach ( $paths as $path ) {
		if ( ! file_exists( $path ) && ! is_link( $path ) ) {
			continue;
		}
		++$left;
		if ( is_dir( $path ) && ! is_link( $path ) ) {
			try {
				foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $path, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::SELF_FIRST, RecursiveIteratorIterator::CATCH_GET_CHILD ) as $unused ) {
					++$left;
				}
			} catch ( UnexpectedValueException $e ) {
				unset( $e ); // A folder that cannot be listed counts as the one entry.
			}
		}
	}
	return $left;
}

/**
 * One log line a day about files the clean-up cannot remove.
 *
 * @param int $left How many are left.
 */
function paulus_dev_files_log_left( $left ) {
	if ( get_transient( 'paulus_dev_files_logged' ) ) {
		return;
	}
	set_transient( 'paulus_dev_files_logged', 1, DAY_IN_SECONDS );
	error_log( 'Paulus: ' . (int) $left . ' development files could not be removed from the theme folder; delete them by FTP.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
}

/*
 * Every hour. An upload of the working copy can put .git back at any time
 * after the clean-up has run, so WP-Cron looks again.
 */

/**
 * The hourly look: nothing to do while the folder holds no development
 * entry; when it holds one, forget the recorded version and run the same
 * clean-up, under the same lease.
 */
function paulus_dev_files_hourly() {
	if ( paulus_keeps_dev_files() || ! paulus_hidden_entries() ) {
		return;
	}
	delete_option( 'paulus_retired_version' );
	wp_cache_delete( 'paulus_retired_version', 'options' );
	paulus_cleanup_on_version_change();
}
add_action( 'paulus_dev_files_hourly', 'paulus_dev_files_hourly' );

/**
 * Schedule the hourly event, except on a site that keeps its development files.
 */
function paulus_dev_files_schedule() {
	if ( paulus_keeps_dev_files() ) {
		wp_clear_scheduled_hook( 'paulus_dev_files_hourly' );
		return;
	}
	if ( ! wp_next_scheduled( 'paulus_dev_files_hourly' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'paulus_dev_files_hourly' );
	}
}
add_action( 'init', 'paulus_dev_files_schedule' );
add_action(
	'switch_theme',
	static function () {
		wp_clear_scheduled_hook( 'paulus_dev_files_hourly' );
	}
);
