<?php
/**
 * Upkeep: maintenance mode, the error watch, and the files a theme uploaded
 * by FTP leaves behind.
 *
 * - Maintenance mode is the file .paulus-maintenance in the site's folder
 *   (paulus_maintenance_flag(), in functions.php, which also serves the
 *   page). Theme Options, Maintenance makes and deletes it; an .htaccess
 *   rule written here serves the same static page from the web server, so
 *   maintenance holds while WordPress itself cannot run (WordPress uploaded
 *   by FTP, file by file).
 * - The error watch reads the incidents the PHP error page records
 *   (wp-content/paulus-incidents.log.php) and the server's PHP error log,
 *   shows them on the dashboard and emails the administrator when new fatal
 *   errors appear.
 * - Files a release has retired (the old content sources, the build script)
 *   are removed from the theme folder, which an FTP upload never does.
 * - Requests for the gambling spam addresses that point at the domain from
 *   its earlier life are answered 410 Gone, so search engines drop them.
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

/*
 * Maintenance mode.
 */

/**
 * Turn maintenance on or off by writing or deleting the flag file.
 *
 * @param bool $on Whether maintenance should be on.
 * @return bool Whether the flag now matches.
 */
function paulus_set_maintenance( $on ) {
	$flag = paulus_maintenance_flag();
	if ( $on ) {
		if ( ! file_exists( $flag ) ) {
			$user = wp_get_current_user();
			$text = "Paulus maintenance mode, switched on " . gmdate( 'Y-m-d H:i' ) . " UTC by " . ( $user->exists() ? $user->user_login : 'the theme' ) . ".\nWhile this file exists, visitors see the maintenance page. Delete it to end maintenance.\n";
			file_put_contents( $flag, $text ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
		paulus_install_htaccess();
		return file_exists( $flag );
	}
	if ( file_exists( $flag ) ) {
		wp_delete_file( $flag );
	}
	return ! file_exists( $flag );
}

// The checkbox on Theme Options writes or deletes the file. Only a change of
// the setting does; the theme's own writes to its options leave it be.
add_action( 'update_option_paulus_options', static function ( $old, $new ) {
	$was = ! empty( $old['maintenance_mode'] );
	$now = ! empty( $new['maintenance_mode'] );
	if ( $was !== $now ) {
		paulus_set_maintenance( $now );
	}
}, 10, 2 );

// The file is the truth: one made or deleted by FTP sets the checkbox to
// match, so the screen never claims what the site is not doing.
add_action( 'admin_init', static function () {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	$on   = file_exists( paulus_maintenance_flag() );
	$opts = get_option( 'paulus_options' );
	if ( is_array( $opts ) && (bool) ( $opts['maintenance_mode'] ?? false ) !== $on ) {
		$opts['maintenance_mode'] = $on ? 1 : 0;
		update_option( 'paulus_options', $opts );
	}
} );

// While it is on, anyone signed in sees a reminder at the top of every page.
add_action( 'admin_bar_menu', static function ( $bar ) {
	if ( current_user_can( 'edit_theme_options' ) && file_exists( paulus_maintenance_flag() ) ) {
		$bar->add_node(
			array(
				'id'    => 'paulus-maintenance',
				'title' => '<span style="background:#b32d2e;color:#fff;padding:0 8px;border-radius:2px;">' . esc_html__( 'Maintenance mode is on', 'paulus' ) . '</span>',
				'href'  => admin_url( 'themes.php?page=paulus-options' ),
			)
		);
	}
}, 100 );

/**
 * The .htaccess block: while the flag file exists, every request that is not
 * from someone signed in, not for the login screen, the admin or cron, and
 * not for the maintenance page and its stylesheet, is answered 503 with the
 * static page, by the web server alone. Without the flag it does nothing.
 *
 * @return string[] Lines.
 */
function paulus_htaccess_lines() {
	$base  = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	$base  = '' === $base ? '/' : trailingslashit( $base );
	$quote = static function ( $s ) {
		return preg_quote( $s, '#' );
	};
	$open  = array( 'wp-login\.php', 'wp-admin/', 'wp-cron\.php', 'wp-content/paulus-maintenance\.html', 'wp-content/paulus-error-page\.css', 'wp-content/themes/' . $quote( get_stylesheet() ) . '/assets/fonts/' );
	$slug  = sanitize_title_with_dashes( (string) get_option( 'whl_page', '' ) );
	// A login slug is letters, digits, hyphens and underscores (WordPress's
	// sanitize_title_with_dashes), none of which needs escaping; escaped
	// with preg_quote, the hyphen became "\-", which Apache did not match.
	$query = array();
	if ( preg_match( '/^[a-z0-9_-]+$/', $slug ) ) {
		$open[] = $slug . '/?$';
		// Without pretty permalinks the login address is ?slug, in the query
		// string, which %{REQUEST_URI} does not include.
		$query[] = 'RewriteCond %{QUERY_STRING} !(^|&)' . $slug . '(=|&|$)';
	}
	$content = (string) wp_parse_url( content_url( '/' ), PHP_URL_PATH );
	return array_merge( array(
		'Written by the Paulus theme. Inactive unless the file .paulus-maintenance',
		'exists in the site\'s folder (Theme Options, Maintenance); delete that',
		'file to end maintenance. Visitors then get the maintenance page from the',
		'web server, even while WordPress itself cannot run.',
		'<IfModule mod_rewrite.c>',
		'RewriteEngine On',
		// Only on the request as it arrived: WordPress's own rule rewrites
		// pretty addresses (the hidden login among them) to index.php, which
		// Apache then runs through these rules again, and the 503 page is
		// fetched by an internal redirect too; both carry REDIRECT_STATUS.
		'RewriteCond %{ENV:REDIRECT_STATUS} ^$',
		'RewriteCond "' . paulus_maintenance_flag() . '" -f',
		'RewriteCond %{REQUEST_URI} !^' . $quote( $base ) . '(' . implode( '|', $open ) . ')',
	), $query, array(
		'RewriteCond %{HTTP_COOKIE} !wordpress_logged_in_ [NC]',
		'RewriteRule ^ - [R=503,L]',
		'</IfModule>',
		'ErrorDocument 503 ' . trailingslashit( $content ) . 'paulus-maintenance.html',
	) );
}

/**
 * Whether this server reads .htaccess files (Apache, LiteSpeed).
 *
 * @return bool
 */
function paulus_htaccess_supported() {
	global $is_apache;
	return (bool) $is_apache || false !== stripos( (string) ( $_SERVER['SERVER_SOFTWARE'] ?? '' ), 'litespeed' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
}

/**
 * Write the block at the top of the site's .htaccess, before WordPress's own
 * rules (which would otherwise answer first), or take it out. The file is
 * written to a temporary name and moved into place, and only when the block
 * has changed; WordPress's rules and everything else are kept as they are.
 *
 * @param bool $remove Take the block out instead.
 * @return bool Whether the file now holds what it should.
 */
function paulus_install_htaccess( $remove = false ) {
	if ( ! paulus_htaccess_supported() ) {
		return false;
	}
	if ( ! function_exists( 'get_home_path' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	$file = get_home_path() . '.htaccess';
	if ( ( file_exists( $file ) && ! is_writable( $file ) ) || ( ! file_exists( $file ) && ! is_writable( dirname( $file ) ) ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions
		return false;
	}
	$text  = file_exists( $file ) ? (string) file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions
	$clean = (string) preg_replace( '/^# BEGIN Paulus maintenance\R.*?^# END Paulus maintenance\R*/ms', '', $text );
	$block = '';
	if ( ! $remove ) {
		$lines = paulus_htaccess_lines();
		$block = "# BEGIN Paulus maintenance\n";
		foreach ( $lines as $i => $line ) {
			$block .= ( $i < 4 ? '# ' : '' ) . $line . "\n";
		}
		$block .= "# END Paulus maintenance\n\n";
	}
	$next = $block . ltrim( $clean );
	if ( $next === $text ) {
		return true;
	}
	$tmp = $file . '.' . uniqid( 'paulus-', true ) . '.tmp';
	if ( false === file_put_contents( $tmp, $next ) || ! rename( $tmp, $file ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( file_exists( $tmp ) ) {
			wp_delete_file( $tmp );
		}
		return false;
	}
	return true;
}

/**
 * Whether the .htaccess block is in place.
 *
 * @return bool
 */
function paulus_htaccess_installed() {
	if ( ! function_exists( 'get_home_path' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	$file = get_home_path() . '.htaccess';
	return file_exists( $file ) && false !== strpos( (string) file_get_contents( $file ), '# BEGIN Paulus maintenance' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
}

// The block goes in with the drop-ins (an administrator's admin page load)
// and whenever the login address or the site's address changes it; it comes
// out when the theme is switched off.
add_action( 'admin_init', static function () {
	if ( ! current_user_can( 'manage_options' ) || wp_doing_ajax() || ( is_multisite() && ! is_main_site() ) ) {
		return;
	}
	$stamp = md5( wp_json_encode( paulus_htaccess_lines() ) );
	if ( get_option( 'paulus_htaccess_stamp' ) === $stamp && paulus_htaccess_installed() ) {
		return;
	}
	if ( ! function_exists( 'get_home_path' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	if ( paulus_install_htaccess() ) {
		update_option( 'paulus_htaccess_stamp', $stamp, false );
	}
} );
add_action( 'switch_theme', static function () {
	if ( ! function_exists( 'get_home_path' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	paulus_install_htaccess( true );
	delete_option( 'paulus_htaccess_stamp' );
	if ( file_exists( paulus_maintenance_flag() ) ) {
		wp_delete_file( paulus_maintenance_flag() );
	}
} );

/*
 * The error watch.
 */

/**
 * The incidents the PHP error page has recorded, newest first.
 *
 * @param int $limit At most this many.
 * @return array[] Each: t (time), k ('update' or 'error'), u, m, f, l.
 */
function paulus_incidents( $limit = 200 ) {
	$file = WP_CONTENT_DIR . '/paulus-incidents.log.php';
	if ( ! is_readable( $file ) ) {
		return array();
	}
	$lines = file( $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	$out   = array();
	foreach ( array_reverse( (array) $lines ) as $line ) {
		$row = json_decode( (string) $line, true );
		if ( is_array( $row ) && isset( $row['t'] ) ) {
			$out[] = $row;
			if ( count( $out ) >= $limit ) {
				break;
			}
		}
	}
	return $out;
}

/**
 * Keep the incident log short: past 200 KB it keeps its last 500 lines. The
 * error page stops writing at 256 KB, so it never grows without end.
 */
function paulus_trim_incidents() {
	$file = WP_CONTENT_DIR . '/paulus-incidents.log.php';
	if ( ! is_file( $file ) || filesize( $file ) <= 204800 || ! is_writable( $file ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions
		return;
	}
	// Read and rewritten under one lock on one handle: the error page appends
	// with LOCK_EX, so an incident recorded meanwhile waits and lands after
	// the rewrite instead of being lost with a stale copy.
	$fh = fopen( $file, 'r+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( ! $fh ) {
		return;
	}
	if ( flock( $fh, LOCK_EX ) ) {
		$lines = preg_split( '/\R/', (string) stream_get_contents( $fh ), -1, PREG_SPLIT_NO_EMPTY );
		$lines = array_slice( array_values( array_filter( $lines, static fn( $l ) => 0 !== strpos( $l, '<?php' ) ) ), -500 );
		ftruncate( $fh, 0 );
		rewind( $fh );
		fwrite( $fh, "<?php exit; ?>\n" . implode( "\n", $lines ) . "\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		fflush( $fh );
		flock( $fh, LOCK_UN );
	}
	fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions
}

/**
 * The server's PHP error log, read from its end (the last 1 MB), as entries
 * of the last $days days, grouped by message.
 *
 * Each group: the message with numbers and quoted values blanked, how often,
 * when last, whether fatal, and what raised it: this theme, a named plugin,
 * WordPress itself or the database.
 *
 * @param int $days Days back.
 * @return array{path:string,readable:bool,groups:array,fatal:int,total:int,newest:int}
 */
function paulus_php_error_summary( $days = 7 ) {
	$path = (string) ini_get( 'error_log' );
	if ( '' !== $path && ! preg_match( '#^(/|[A-Za-z]:)#', $path ) ) {
		$path = ABSPATH . $path;
	}
	$out = array( 'path' => $path, 'readable' => false, 'groups' => array(), 'fatal' => 0, 'total' => 0, 'newest' => 0 );
	if ( '' === $path || ! @is_readable( $path ) || ! is_file( $path ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors
		return $out;
	}
	$out['readable'] = true;
	$size            = (int) filesize( $path );
	$fh              = fopen( $path, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( ! $fh ) {
		return $out;
	}
	fseek( $fh, max( 0, $size - 1048576 ) );
	$text = (string) stream_get_contents( $fh );
	fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	$since = time() - $days * DAY_IN_SECONDS;
	$theme = '/themes/' . get_stylesheet() . '/';
	foreach ( preg_split( '/\R/', $text ) as $line ) {
		if ( ! preg_match( '/^\[([^\]]+)\] (.+)$/', $line, $m ) ) {
			continue;
		}
		$time = strtotime( $m[1] );
		if ( ! $time || $time < $since ) {
			continue;
		}
		$msg   = $m[2];
		$fatal = (bool) preg_match( '/PHP (Fatal|Parse) error/', $msg );
		if ( false !== strpos( $msg, $theme ) || 0 === strpos( $msg, 'Paulus:' ) ) {
			$source = __( 'this theme', 'paulus' );
		} elseif ( preg_match( '#/plugins/([^/]+)/#', $msg, $p ) ) {
			$source = $p[1];
		} elseif ( 0 === strpos( $msg, 'WordPress database error' ) ) {
			$source = __( 'database', 'paulus' );
		} elseif ( preg_match( '#/(wp-includes|wp-admin)/|wp-settings\.php|wp-load\.php#', $msg ) ) {
			$source = __( 'WordPress', 'paulus' );
		} else {
			$source = __( 'other', 'paulus' );
		}
		$key = preg_replace( array( '/\d+/', "/'[^']*'/" ), array( 'N', "'…'" ), (string) preg_replace( '/ made by .*$/', '', $msg ) );
		$key = mb_substr( (string) $key, 0, 220 );
		if ( ! isset( $out['groups'][ $key ] ) ) {
			$out['groups'][ $key ] = array( 'message' => $key, 'count' => 0, 'last' => 0, 'fatal' => $fatal, 'source' => $source, 'hint' => paulus_error_hint( $msg ), 'info' => paulus_error_hint_info( $msg ) );
		}
		++$out['groups'][ $key ]['count'];
		$out['groups'][ $key ]['last'] = max( $out['groups'][ $key ]['last'], $time );
		$out['total']                  += 1;
		$out['fatal']                  += $fatal ? 1 : 0;
		$out['newest']                  = max( $out['newest'], $time );
	}
	uasort( $out['groups'], static fn( $a, $b ) => ( $b['fatal'] <=> $a['fatal'] ) ?: ( $b['last'] <=> $a['last'] ) );
	return $out;
}

/**
 * The first line of an error message: PHP appends the stack trace.
 *
 * @param string $msg Message.
 * @return string
 */
function paulus_first_line( $msg ) {
	return trim( (string) strtok( str_replace( 'Stack trace:', "\n", (string) $msg ), "\n" ) );
}

/**
 * Fatal errors newer than a moment: those the PHP error page recorded and
 * those in the PHP error log.
 *
 * @param int $since Unix time.
 * @return array{count:int,lines:string[]}
 */
function paulus_new_fatals( $since ) {
	$lines = array();
	foreach ( paulus_incidents() as $i ) {
		if ( (int) $i['t'] > $since ) {
			$lines[] = gmdate( 'Y-m-d H:i', (int) $i['t'] ) . ' UTC  ' . ( 'update' === $i['k'] ? '[during an upload] ' : '' ) . paulus_first_line( $i['m'] ) . ' (' . $i['f'] . ':' . $i['l'] . ') at ' . $i['u'];
		}
	}
	$log = paulus_php_error_summary( 2 );
	foreach ( $log['groups'] as $g ) {
		if ( $g['fatal'] && $g['last'] > $since ) {
			$lines[] = gmdate( 'Y-m-d H:i', $g['last'] ) . ' UTC  ' . $g['count'] . '× ' . $g['message'] . ' [' . $g['source'] . ']';
		}
	}
	return array( 'count' => count( $lines ), 'lines' => array_slice( array_unique( $lines ), 0, 30 ) );
}

/**
 * Hourly: if fatal errors have appeared since the last look, email the
 * administration address (at most once in six hours, so an outage brings one
 * email, not dozens). The email lists them and links to the dashboard.
 */
function paulus_error_watch() {
	paulus_trim_incidents();
	$state = (array) get_option( 'paulus_error_watch', array() );
	$since = (int) ( $state['checked'] ?? ( time() - HOUR_IN_SECONDS ) );
	$new   = paulus_new_fatals( $since );
	$now   = time();
	// The mark moves on only once the errors since it are dealt with: none
	// came, alerts are off, or the email went. An email held back by the
	// six-hour limit, or one that failed, leaves the mark where it was, so
	// those errors are in the next email.
	if ( ! $new['count'] || ! paulus_option( 'error_alerts' ) ) {
		$state['checked'] = $now;
	} elseif ( $now - (int) ( $state['mailed'] ?? 0 ) > 6 * HOUR_IN_SECONDS ) {
		$site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		/* translators: 1: site name, 2: number of errors. */
		$subject = sprintf( __( '[%1$s] %2$d new fatal errors on the site', 'paulus' ), $site, $new['count'] );
		$body    = sprintf(
			/* translators: %s: site address. */
			__( "The site %s recorded fatal errors since the last check. An error during an upload by FTP clears itself when the upload is done; any other needs a look.\n\n", 'paulus' ),
			home_url( '/' )
		) . implode( "\n", $new['lines'] ) . "\n\n" . __( 'The dashboard shows the details:', 'paulus' ) . ' ' . admin_url() . "\n" . __( 'These emails can be switched off under Appearance, Theme Options, Maintenance.', 'paulus' );
		if ( wp_mail( get_option( 'admin_email' ), $subject, $body ) ) {
			$state['mailed']  = $now;
			$state['checked'] = $now;
		}
	}
	$state['checked'] = $state['checked'] ?? $since;
	update_option( 'paulus_error_watch', $state, false );
}
add_action( 'paulus_error_watch', 'paulus_error_watch' );
add_action( 'init', static function () {
	if ( ! wp_next_scheduled( 'paulus_error_watch' ) ) {
		wp_schedule_event( time() + 300, 'hourly', 'paulus_error_watch' );
	}
} );
add_action( 'switch_theme', static function () {
	wp_clear_scheduled_hook( 'paulus_error_watch' );
} );

/**
 * One finding of the PHP error log as a list item.
 *
 * @param array    $f   Finding from paulus_watch_findings().
 * @param callable $fmt Formats a Unix time.
 * @return string HTML.
 */
function paulus_watch_finding_html( array $f, $fmt ) {
	$sources = array_values( array_unique( array_filter( wp_list_pluck( $f['lines'], 'source' ) ) ) );
	$html    = '<li class="paulus-watch__finding paulus-watch__finding--' . esc_attr( $f['level'] ) . '">';
	$html   .= '<div class="paulus-watch__head"><span class="paulus-watch__count">' . esc_html( number_format_i18n( $f['count'] ) ) . '×</span>';
	$html   .= '<h4 class="paulus-watch__title">' . esc_html( $f['title'] ) . '</h4>';
	$html   .= $f['fatal'] ? '<span class="paulus-watch__tag">' . esc_html__( 'Fatal', 'paulus' ) . '</span>' : '';
	$html   .= '</div>';
	/* translators: 1: where it came from, 2: when it last happened. */
	$html .= '<p class="paulus-watch__meta">' . esc_html( sprintf( __( '%1$s · last %2$s', 'paulus' ), implode( ', ', $sources ), $fmt( $f['last'] ) ) ) . '</p>';
	$html .= $f['text'] ? '<p class="paulus-watch__text">' . esc_html( $f['text'] ) . '</p>' : '';
	$html .= '<details class="paulus-watch__lines"><summary>' . esc_html( sprintf( /* translators: %d: number of log lines. */ _n( 'Show the log line (%d)', 'Show the log lines (%d)', count( $f['lines'] ), 'paulus' ), count( $f['lines'] ) ) ) . '</summary><ul>';
	foreach ( $f['lines'] as $line ) {
		$html .= '<li><code>' . esc_html( mb_substr( $line['message'], 0, 220 ) ) . '</code> <small>' . esc_html( $line['count'] . '× · ' . $fmt( $line['last'] ) ) . '</small></li>';
	}
	return $html . '</ul></details></li>';
}

/**
 * The dashboard panel: maintenance, the safety net, the incidents and the
 * PHP error log of the last seven days, as a status strip, three figures and
 * a short list of findings, most pressing first. Styled by
 * assets/css/watch.css.
 */
function paulus_watch_widget() {
	$on       = file_exists( paulus_maintenance_flag() );
	$supports = paulus_htaccess_supported();
	$net_ok   = $supports && paulus_htaccess_installed();
	$net_note = '';
	if ( ! $supports ) {
		$net_note = __( 'The web-server safety net is not available on this server (not Apache or LiteSpeed); maintenance is served by WordPress only.', 'paulus' );
	} elseif ( ! $net_ok ) {
		$net_note = __( 'The web-server safety net is not written (the .htaccess file is not writable).', 'paulus' );
	}
	$pages = array();
	foreach ( array( 'php-error.php', 'maintenance.php', 'db-error.php', 'paulus-maintenance.html', 'paulus-error-page.css' ) as $f ) {
		$pages[ $f ] = file_exists( WP_CONTENT_DIR . '/' . $f );
	}
	$missing   = array_keys( array_filter( $pages, static fn( $v ) => ! $v ) );
	$incidents = paulus_incidents( 50 );
	$recent    = array_filter( $incidents, static fn( $i ) => (int) $i['t'] > time() - 14 * DAY_IN_SECONDS );
	$log       = paulus_php_error_summary( 7 );
	$fmt       = static fn( $t ) => wp_date( 'j M, H:i', (int) $t );
	$scheme    = array_key_exists( paulus_option( 'scheme' ), paulus_schemes() ) ? paulus_option( 'scheme' ) : 'ochre';
	$chips     = array(
		array( __( 'Maintenance mode', 'paulus' ), $on ? __( 'On', 'paulus' ) : __( 'Off', 'paulus' ), $on ? 'bad' : 'ok' ),
		array( __( 'Web-server safety net', 'paulus' ), $net_ok ? __( 'In place', 'paulus' ) : ( $supports ? __( 'Not written', 'paulus' ) : __( 'Not available', 'paulus' ) ), $net_ok ? 'ok' : 'warn' ),
		array( __( 'Error pages', 'paulus' ), $missing ? sprintf( /* translators: %d: number of missing files. */ _n( '%d missing', '%d missing', count( $missing ), 'paulus' ), count( $missing ) ) : __( 'All in place', 'paulus' ), $missing ? 'warn' : 'ok' ),
	);
	echo '<div class="paulus-watch paulus-watch--' . esc_attr( $scheme ) . '">';
	echo '<ul class="paulus-watch__status">';
	foreach ( $chips as $c ) {
		echo '<li class="is-' . esc_attr( $c[2] ) . '"><span class="paulus-watch__dot" aria-hidden="true"></span><span class="paulus-watch__label">' . esc_html( $c[0] ) . '</span><strong>' . esc_html( $c[1] ) . '</strong></li>';
	}
	echo '</ul>';
	if ( $net_note ) {
		echo '<p class="paulus-watch__note">' . esc_html( $net_note ) . '</p>';
	}
	if ( $missing ) {
		/* translators: %s: file names. */
		echo '<p class="paulus-watch__note">' . esc_html( sprintf( __( 'Missing in wp-content: %s. They are written on the next admin page load if the folder is writable.', 'paulus' ), implode( ', ', $missing ) ) ) . '</p>';
	}
	echo '<p class="paulus-watch__link"><a href="' . esc_url( admin_url( 'themes.php?page=paulus-options' ) ) . '">' . esc_html__( 'Theme Options, Maintenance', 'paulus' ) . '</a></p>';

	echo '<section class="paulus-watch__block"><h3>' . esc_html__( 'Visitors, last 14 days', 'paulus' ) . '</h3>';
	if ( ! $recent ) {
		echo '<p class="paulus-watch__calm">' . esc_html__( 'No visitor met an error.', 'paulus' ) . '</p>';
	} else {
		$updates = count( array_filter( $recent, static fn( $i ) => 'update' === $i['k'] ) );
		/* translators: 1: total, 2: during uploads. */
		echo '<p>' . esc_html( sprintf( __( '%1$d errors, %2$d of them during an upload (answered as maintenance).', 'paulus' ), count( $recent ), $updates ) ) . '</p><ul class="paulus-watch__plain">';
		foreach ( array_slice( $recent, 0, 5 ) as $i ) {
			echo '<li><code>' . esc_html( $fmt( $i['t'] ) ) . '</code> ' . esc_html( mb_substr( paulus_first_line( $i['m'] ), 0, 160 ) ) . ' <small>' . esc_html( $i['f'] . ':' . $i['l'] ) . '</small></li>';
		}
		echo '</ul>';
	}
	echo '</section>';

	echo '<section class="paulus-watch__block"><h3>' . esc_html__( 'Server log, last 7 days', 'paulus' ) . '</h3>';
	if ( ! $log['readable'] ) {
		echo '<p class="paulus-watch__note">' . esc_html( '' === $log['path'] ? __( 'PHP is not set to write an error log the site can read.', 'paulus' ) : sprintf( /* translators: %s: path. */ __( 'The log at %s cannot be read by the site.', 'paulus' ), $log['path'] ) ) . '</p>';
	} elseif ( ! $log['total'] ) {
		echo '<p class="paulus-watch__calm">' . esc_html__( 'Nothing logged.', 'paulus' ) . '</p>';
	} else {
		$findings = paulus_watch_findings( $log['groups'] );
		$look     = array_filter( $findings, static fn( $f ) => in_array( $f['level'], array( 'action', 'unknown' ), true ) );
		$quiet    = array_filter( $findings, static fn( $f ) => in_array( $f['level'], array( 'noise', 'upload' ), true ) );
		echo '<dl class="paulus-watch__stats">';
		echo '<div><dt>' . esc_html__( 'Entries', 'paulus' ) . '</dt><dd>' . esc_html( number_format_i18n( $log['total'] ) ) . '</dd></div>';
		echo '<div><dt>' . esc_html__( 'Fatal', 'paulus' ) . '</dt><dd>' . esc_html( number_format_i18n( $log['fatal'] ) ) . '</dd></div>';
		echo '<div class="' . ( $look ? 'is-attention' : 'is-calm' ) . '"><dt>' . esc_html__( 'Need a look', 'paulus' ) . '</dt><dd>' . esc_html( number_format_i18n( count( $look ) ) ) . '</dd></div>';
		echo '</dl>';
		if ( $look ) {
			echo '<ol class="paulus-watch__findings">';
			foreach ( $look as $f ) {
				echo paulus_watch_finding_html( $f, $fmt ); // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts.
			}
			echo '</ol>';
		} else {
			echo '<p class="paulus-watch__calm">' . esc_html__( 'Nothing in the log needs your attention.', 'paulus' ) . '</p>';
		}
		if ( $quiet ) {
			$entries = array_sum( wp_list_pluck( $quiet, 'count' ) );
			echo '<details class="paulus-watch__quiet"><summary>' . esc_html( sprintf( /* translators: 1: kinds, 2: entries. */ _n( 'Background noise: %1$d kind, %2$s entries', 'Background noise: %1$d kinds, %2$s entries', count( $quiet ), 'paulus' ), count( $quiet ), number_format_i18n( $entries ) ) ) . '</summary><ol class="paulus-watch__findings">';
			foreach ( $quiet as $f ) {
				echo paulus_watch_finding_html( $f, $fmt ); // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts.
			}
			echo '</ol></details>';
		}
	}
	echo '</section></div>';
}
add_action( 'admin_enqueue_scripts', static function ( $hook ) {
	if ( 'index.php' === $hook && current_user_can( 'manage_options' ) ) {
		wp_enqueue_style( 'paulus-watch', PAULUS_URI . '/assets/css/watch.css', array(), PAULUS_VERSION );
	}
} );
add_action( 'wp_dashboard_setup', static function () {
	if ( current_user_can( 'manage_options' ) ) {
		wp_add_dashboard_widget( 'paulus_watch', __( 'Errors and maintenance', 'paulus' ), 'paulus_watch_widget' );
	}
} );

/*
 * Files a release has retired.
 */

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
// Removes a folder inside the theme and everything in it. Links are removed,
// never followed, and nothing outside the theme folder is touched.
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
		if ( $f->isDir() && ! $f->isLink() ) {
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

// Hidden files and folders at the top of the theme folder: the repository's
// `.git`, `.github`, `.gitignore` and `.gitattributes`, and editor folders
// (`.vscode`, `.idea`, `.claude`), which an upload of a working copy carries
// and the web server may serve. Server files that may belong to the host
// stay. A local or development site keeps them, so a theme run from a
// working copy keeps its repository; so does `define( 'PAULUS_KEEP_DOTFILES', true );`.
function paulus_hidden_entries() {
	if ( ( defined( 'PAULUS_KEEP_DOTFILES' ) && PAULUS_KEEP_DOTFILES )
		|| in_array( wp_get_environment_type(), array( 'local', 'development' ), true ) ) {
		return array();
	}
	$keep = array( '.', '..', '.htaccess', '.user.ini', '.well-known' );
	$list = array();
	foreach ( (array) scandir( PAULUS_DIR ) as $name ) {
		if ( is_string( $name ) && '.' === substr( $name, 0, 1 ) && ! in_array( $name, $keep, true ) ) {
			$list[] = PAULUS_DIR . '/' . $name;
		}
	}
	return $list;
}

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
		$gone += paulus_remove_tree( $dir );
	}
	foreach ( $paths as $path ) {
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

// Once for each version, on the first admin page an administrator loads, and
// with the weekly image housekeeping.
add_action( 'admin_init', static function () {
	if ( get_option( 'paulus_retired_version' ) !== PAULUS_VERSION && current_user_can( 'manage_options' ) ) {
		update_option( 'paulus_retired_version', PAULUS_VERSION, false );
		$gone = paulus_cleanup_retired_files();
		if ( $gone ) {
			error_log( 'Paulus: removed ' . $gone . ' retired files from the theme folder.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
		}
	}
} );
add_action( 'paulus_image_housekeeping', 'paulus_cleanup_retired_files', 20 );
add_action( 'after_switch_theme', static function () {
	$gone = paulus_cleanup_retired_files();
	if ( $gone ) {
		error_log( 'Paulus: removed ' . $gone . ' retired and hidden files from the theme folder.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
	}
} );

/*
 * Addresses that should not be answered with a page.
 */

/**
 * The home page asked for with nothing but a number in "j" (/?j=0581482253980)
 * is the same page under an endless run of addresses. From late September
 * Googlebot asked for nearly half a million of them, up to 128,000 a day,
 * each a full render of the home page (the theme never made such links). It
 * is sent to the home page itself, permanently, before WordPress queries the
 * database, so search engines fold them into one and stop asking.
 */
function paulus_fold_query_trap() {
	$uri = (string) ( $_SERVER['REQUEST_URI'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$home = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	if ( 'GET' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && preg_match( '#^' . preg_quote( '' === $home ? '/' : $home, '#' ) . '\?j=\d*$#', $uri ) ) {
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
}
add_action( 'init', 'paulus_fold_query_trap', 0 );

/**
 * An image the theme no longer ships under that name (the WebP copies before
 * 2.62.40, the JPEG copies before that) but still has as AVIF: search
 * engines and pages saved elsewhere still ask for the old address, which
 * WordPress's rules hand to WordPress as a missing page. It is sent to the
 * AVIF, permanently, so the image keeps its place in image search.
 */
function paulus_redirect_old_images() {
	if ( ! is_404() ) {
		return;
	}
	$path  = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$theme = (string) wp_parse_url( PAULUS_URI, PHP_URL_PATH );
	if ( preg_match( '#^' . preg_quote( $theme, '#' ) . '/assets/images/((?:church/)?[a-z0-9-]+)\.(?:webp|jpe?g|png)$#', $path, $m ) && file_exists( PAULUS_DIR . '/assets/images/' . $m[1] . '.avif' ) ) {
		wp_safe_redirect( PAULUS_URI . '/assets/images/' . $m[1] . '.avif', 301 );
		exit;
	}
}
add_action( 'template_redirect', 'paulus_redirect_old_images', 4 );

/**
 * Before this site, the domain carried gambling spam and a fresh WordPress,
 * and crawlers still ask for those addresses: gambling pages in several
 * languages, posts under dated addresses (this site has never used dates in
 * its addresses), WordPress's sample page and first post. A request that
 * WordPress finds nothing for, and that is one of these, is answered 410
 * Gone, which tells search engines to drop it for good; any address
 * WordPress does find is untouched.
 */
function paulus_gone_spam() {
	if ( ! is_404() ) {
		return;
	}
	$path = strtolower( rawurldecode( (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( preg_match( '/casino|pokies|slots?\b|slot-|bingo|jackpot|roulette|blackjack|baccarat|betting|free-spins|no-deposit|kasino|hrajte|hraci|automat|gokkast|speelautomat|spielbank|tragamonedas|sportsbook|poker/', $path )
		|| preg_match( '#^/\d{4}/\d{2}/\d{2}/[^/]+/?$#', $path )
		|| in_array( trim( $path, '/' ), array( 'sample-page', 'hello-world' ), true ) ) {
		status_header( 410 );
		nocache_headers();
	}
}
add_action( 'template_redirect', 'paulus_gone_spam', 5 );
