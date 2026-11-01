<?php
/**
 * Security: remote-publishing lockdown, spam gate on new content, clean-up
 * tools and 410 Gone for removed spam. Settings live in paulus_options (sec_*
 * keys, all on by default) and appear on the Security tab of Theme Options.
 * Ported from the Abrahamic theme (2.87.0), written after the October 2026
 * attack on abrahamic-religions.com.
 *
 * Application passwords stay with the switch the theme already had, under
 * Theme Options, Login (inc/login.php, off by default); this file adds no
 * second one. The hidden login address (inc/hide-login.php) is unchanged.
 *
 * Emergency switch: define( 'PAULUS_SECURITY_LOCK', false ); in wp-config.php
 * turns the XML-RPC and REST locks off without touching the options.
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/security-detector.php';

define( 'PAULUS_SEC_LOG', 'paulus_security_log' );
define( 'PAULUS_SEC_GONE', 'paulus_security_gone' );
define( 'PAULUS_SEC_LOG_MAX', 200 );
define( 'PAULUS_SEC_GONE_MAX', 5000 );

/**
 * Whether a Security toggle is on.
 *
 * @param string $key Option key.
 * @return bool
 */
function paulus_sec_on( $key ) {
	$locks = array( 'sec_xmlrpc_off', 'sec_rest_lock' );
	if ( in_array( $key, $locks, true ) && defined( 'PAULUS_SECURITY_LOCK' ) && ! PAULUS_SECURITY_LOCK ) {
		return false;
	}
	return (bool) paulus_option( $key );
}

/**
 * Address of the Security tab, with optional query arguments.
 *
 * @param array $args Query arguments.
 * @return string
 */
function paulus_sec_url( $args = array() ) {
	return add_query_arg( $args, admin_url( 'themes.php?page=paulus-options' ) ) . '#security';
}

/* -------------------------------------------------------------------------
 * 1. Remote-publishing lockdown
 * ---------------------------------------------------------------------- */

/**
 * XML-RPC off: no methods, no pingback header, xmlrpc.php answers 403.
 */
function paulus_sec_xmlrpc() {
	if ( ! paulus_sec_on( 'sec_xmlrpc_off' ) ) {
		return;
	}
	add_filter( 'xmlrpc_enabled', '__return_false' );
	add_filter( 'xmlrpc_methods', '__return_empty_array', 999 );
	add_filter( 'pings_open', '__return_false', 999 );
	add_filter(
		'wp_headers',
		function ( $headers ) {
			unset( $headers['X-Pingback'], $headers['x-pingback'] );
			return $headers;
		}
	);
	remove_action( 'wp_head', 'rsd_link' );
	if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
		// Counted, not logged one by one: the October 2026 attack sent over
		// 17,000 requests in twelve days from 701 addresses.
		paulus_sec_count( 'xmlrpc' );
		if ( paulus_sec_fake_jetpack() ) {
			paulus_sec_count( 'fake-jetpack' );
		}
		status_header( 403 );
		header( 'Content-Type: text/plain; charset=utf-8' );
		echo 'XML-RPC is disabled on this site.';
		exit;
	}
}
add_action( 'after_setup_theme', 'paulus_sec_xmlrpc', 1 );

/**
 * Lines that deny xmlrpc.php at the web server, for Apache 2.4 and 2.2. The
 * marker name is shared by all seven sister themes, so switching between
 * them never writes the block twice.
 *
 * @return string[]
 */
function paulus_sec_htaccess_lines() {
	return array(
		'<Files xmlrpc.php>',
		'<IfModule mod_authz_core.c>',
		'Require all denied',
		'</IfModule>',
		'<IfModule !mod_authz_core.c>',
		'Order Deny,Allow',
		'Deny from all',
		'</IfModule>',
		'</Files>',
	);
}

/**
 * Path of the site's .htaccess, or '' when the server is not Apache or the
 * file cannot be written.
 *
 * @return string
 */
function paulus_sec_htaccess_file() {
	if ( ! function_exists( 'get_home_path' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	if ( ! function_exists( 'insert_with_markers' ) ) {
		require_once ABSPATH . 'wp-admin/includes/misc.php';
	}
	global $is_apache;
	if ( empty( $is_apache ) ) {
		return '';
	}
	$file = get_home_path() . '.htaccess';
	if ( file_exists( $file ) ? ! wp_is_writable( $file ) : ! wp_is_writable( dirname( $file ) ) ) {
		return '';
	}
	return $file;
}

/**
 * Keep the server-level block in step with the XML-RPC switch: written while
 * the switch is on, removed when it is off. Runs in the admin, at most once an
 * hour, so a lost or overwritten .htaccess is repaired without any action.
 *
 * @param bool $force Skip the hourly throttle.
 */
function paulus_sec_sync_htaccess( $force = false ) {
	if ( ! $force && get_transient( 'paulus_sec_htaccess_checked' ) ) {
		return;
	}
	set_transient( 'paulus_sec_htaccess_checked', 1, HOUR_IN_SECONDS );
	$file = paulus_sec_htaccess_file();
	if ( '' === $file ) {
		update_option( 'paulus_security_htaccess', 'unavailable', false );
		return;
	}
	$want    = paulus_sec_on( 'sec_xmlrpc_off' );
	$current = function_exists( 'extract_from_markers' ) ? extract_from_markers( $file, 'Block xmlrpc.php' ) : array();
	$ok      = true;
	if ( $want && $current !== paulus_sec_htaccess_lines() ) {
		$ok = insert_with_markers( $file, 'Block xmlrpc.php', paulus_sec_htaccess_lines() );
	} elseif ( ! $want && $current ) {
		$ok = insert_with_markers( $file, 'Block xmlrpc.php', array() );
	}
	update_option( 'paulus_security_htaccess', ! $ok ? 'unavailable' : ( $want ? 'written' : 'off' ), false );
}
add_action( 'admin_init', 'paulus_sec_sync_htaccess' );
add_action( 'after_switch_theme', function () { paulus_sec_sync_htaccess( true ); } );
add_action( 'update_option_paulus_options', function () { paulus_sec_sync_htaccess( true ); } );

/**
 * Take the block out when the theme is switched to one outside the sister
 * themes. A sister theme writes the same block back on its first admin load.
 */
function paulus_sec_remove_htaccess() {
	$file = paulus_sec_htaccess_file();
	if ( '' !== $file ) {
		insert_with_markers( $file, 'Block xmlrpc.php', array() );
	}
	delete_transient( 'paulus_sec_htaccess_checked' );
}
add_action( 'switch_theme', 'paulus_sec_remove_htaccess' );

/**
 * Whether this REST request comes from a logged-in browser session with a
 * valid wp_rest nonce. Core sets $wp_rest_auth_cookie when the logged-in cookie
 * validates, and rest_cookie_check_errors() has already logged the user out
 * if the cookie came without a valid nonce.
 *
 * @param WP_REST_Request $request Request.
 * @return bool
 */
function paulus_sec_cookie_session( $request ) {
	if ( true !== ( isset( $GLOBALS['wp_rest_auth_cookie'] ) ? $GLOBALS['wp_rest_auth_cookie'] : null ) ) {
		return false;
	}
	if ( ! empty( $GLOBALS['wp_rest_application_password_uuid'] ) ) {
		return false;
	}
	$nonce = $request->get_header( 'X-WP-Nonce' );
	if ( ! $nonce && isset( $_REQUEST['_wpnonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- the nonce is checked here.
		$nonce = sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
	return $nonce && false !== wp_verify_nonce( $nonce, 'wp_rest' );
}

/**
 * REST lock: a request that writes (POST, PUT, PATCH, DELETE) as a logged-in
 * user must come from a cookie session with a valid nonce. Basic auth,
 * application passwords and any other header-based login are refused for
 * writes in every namespace. Reads and anonymous requests are unchanged, so
 * public GET output, contact forms and comment forms behave as before. The
 * block editor, Rank Math and the media modal send X-WP-Nonce with cookies
 * and pass.
 *
 * @param mixed           $result  Earlier result.
 * @param WP_REST_Server  $server  Server.
 * @param WP_REST_Request $request Request.
 * @return mixed
 */
function paulus_sec_rest_guard( $result, $server, $request ) {
	if ( null !== $result || ! paulus_sec_on( 'sec_rest_lock' ) || ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return $result;
	}
	if ( in_array( strtoupper( $request->get_method() ), array( 'GET', 'HEAD', 'OPTIONS' ), true ) ) {
		return $result;
	}
	$route = (string) $request->get_route();
	/**
	 * Route prefixes that may write with non-cookie authentication, for
	 * example '/jetpack/'. Empty by default.
	 */
	foreach ( (array) apply_filters( 'paulus_sec_rest_allow', array() ) as $prefix ) {
		if ( $prefix && 0 === strpos( $route, $prefix ) ) {
			return $result;
		}
	}
	$core = (bool) preg_match( '#^/(wp/v2|batch/v1)(/|$)#', $route ) && 0 !== strpos( $route, '/wp/v2/comments' );
	if ( ! is_user_logged_in() && ! $core ) {
		return $result;
	}
	if ( ! is_user_logged_in() ) {
		// Core refuses anonymous writes to these routes itself; nothing to add.
		return $result;
	}
	if ( paulus_sec_cookie_session( $request ) ) {
		return $result;
	}
	paulus_sec_log(
		array(
			'event' => 'rest-blocked',
			'title' => $request->get_method() . ' ' . $route,
		)
	);
	return new WP_Error( 'paulus_rest_locked', __( 'Changes through the REST API need a logged-in browser session on this site.', 'paulus' ), array( 'status' => 403 ) );
}
add_filter( 'rest_pre_dispatch', 'paulus_sec_rest_guard', 5, 3 );

/* -------------------------------------------------------------------------
 * 1b. Signals from the October 2026 attack
 *
 * The access logs showed the pattern: password guessing through xmlrpc.php
 * from 15 October, sent with forged "Jetpack by WordPress.com" user agents;
 * the first correct password on 20 October; from 22 October a post every 18
 * to 20 minutes, each one a login, a read of the site and a wp.newPost call,
 * every request from a different address. The login page itself was never
 * reached. These checks answer each step.
 * ---------------------------------------------------------------------- */

define( 'PAULUS_SEC_COUNTS', 'paulus_security_counts' );
define( 'PAULUS_SEC_FAILED', 'paulus_security_failed' );

/**
 * Add one to today's counter for an event. Each hit is one atomic row write
 * (a non-autoloaded option per key and day), so a flood of blocked XML-RPC
 * requests never reads, unserialises or rewrites the whole counts array.
 * paulus_sec_counts() folds past days into PAULUS_SEC_COUNTS when read.
 *
 * @param string $key Event key.
 */
function paulus_sec_count( $key ) {
	global $wpdb;
	$name = 'paulus_security_tally_' . sanitize_key( $key ) . '_' . gmdate( 'Ymd' );
	$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, '1', 'off') ON DUPLICATE KEY UPDATE option_value = option_value + 1", $name ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}

/**
 * Counts for display: PAULUS_SEC_COUNTS plus the tally rows. Rows from past
 * days are added to PAULUS_SEC_COUNTS and deleted; today's row is added to
 * the figures returned but stays in place. Thirty days are kept.
 *
 * @return array date => array key => count, newest first.
 */
function paulus_sec_counts() {
	global $wpdb;
	$counts = get_option( PAULUS_SEC_COUNTS, array() );
	$counts = is_array( $counts ) ? $counts : array();
	$rows   = $wpdb->get_results( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'paulus\\_security\\_tally\\_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$today  = gmdate( 'Ymd' );
	$live   = array();
	$done   = array();
	foreach ( (array) $rows as $row ) {
		if ( ! preg_match( '/^paulus_security_tally_([a-z0-9_-]+)_(\d{8})$/', $row->option_name, $m ) ) {
			continue;
		}
		if ( $m[2] === $today ) {
			$live[ $m[1] ] = (int) $row->option_value;
			continue;
		}
		$day                     = substr( $m[2], 0, 4 ) . '-' . substr( $m[2], 4, 2 ) . '-' . substr( $m[2], 6, 2 );
		$counts[ $day ][ $m[1] ] = ( isset( $counts[ $day ][ $m[1] ] ) ? (int) $counts[ $day ][ $m[1] ] : 0 ) + (int) $row->option_value;
		$done[]                  = $row->option_name;
	}
	krsort( $counts );
	$counts = array_slice( $counts, 0, 30, true );
	if ( $done ) {
		update_option( PAULUS_SEC_COUNTS, $counts, false );
		foreach ( $done as $name ) {
			delete_option( $name );
		}
	}
	$day = gmdate( 'Y-m-d' );
	foreach ( $live as $key => $n ) {
		$counts[ $day ][ $key ] = ( isset( $counts[ $day ][ $key ] ) ? (int) $counts[ $day ][ $key ] : 0 ) + $n;
	}
	krsort( $counts );
	return $counts;
}

/**
 * Whether the request claims to come from Jetpack or WordPress.com while the
 * Jetpack plugin is not active. Real Jetpack traffic only reaches sites that
 * run Jetpack, so on this site the claim is forged.
 *
 * @return bool
 */
function paulus_sec_fake_jetpack() {
	$agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
	if ( ! preg_match( '/jetpack|wordpress\.com/i', $agent ) ) {
		return false;
	}
	return ! class_exists( 'Jetpack' ) && ! defined( 'JETPACK__VERSION' );
}

/**
 * Whether this request is a remote, non-browser call: XML-RPC, or REST with
 * an application password or other header login.
 *
 * @return bool
 */
function paulus_sec_is_remote() {
	if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
		return true;
	}
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST && is_user_logged_in() ) {
		return true !== ( isset( $GLOBALS['wp_rest_auth_cookie'] ) ? $GLOBALS['wp_rest_auth_cookie'] : null );
	}
	return false;
}

/**
 * Password logins through XML-RPC are refused even when XML-RPC is switched
 * back on (for Jetpack, say). Jetpack signs its own requests and needs no
 * password, so it keeps working.
 *
 * @param WP_User|WP_Error|null $user Result so far.
 * @return WP_User|WP_Error|null
 */
function paulus_sec_refuse_remote_password( $user ) {
	if ( ! ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) || ! paulus_sec_on( 'sec_xmlrpc_off' ) && ! paulus_sec_on( 'sec_rest_lock' ) ) {
		return $user;
	}
	if ( $user instanceof WP_User ) {
		paulus_sec_count( 'remote-password' );
		paulus_sec_log(
			array(
				'event' => 'remote-password-refused',
				'title' => $user->user_login . ': ' . __( 'the correct password was sent through XML-RPC. Change it now.', 'paulus' ),
				'user'  => $user->ID,
			)
		);
	}
	return new WP_Error( 'paulus_remote_password', __( 'Password logins through XML-RPC are not accepted on this site.', 'paulus' ) );
}
add_filter( 'authenticate', 'paulus_sec_refuse_remote_password', 100 );

/**
 * Count failed logins per account name over a rolling day. The attack came
 * from a different address every time, so counting by address shows nothing;
 * counting by account name shows which account is under attack.
 *
 * @param string $login Account name or email tried.
 */
function paulus_sec_failed_login( $login ) {
	paulus_sec_count( 'failed-login' );
	$login  = sanitize_user( (string) $login, true );
	$failed = get_option( PAULUS_SEC_FAILED, array() );
	$failed = is_array( $failed ) ? $failed : array();
	$now    = time();
	foreach ( $failed as $name => $row ) {
		if ( $now - (int) $row['last'] > DAY_IN_SECONDS ) {
			unset( $failed[ $name ] );
		}
	}
	if ( '' === $login ) {
		$login = '(empty)';
	}
	if ( ! isset( $failed[ $login ] ) && count( $failed ) >= 50 ) {
		return;
	}
	$row = isset( $failed[ $login ] ) ? $failed[ $login ] : array( 'n' => 0, 'first' => $now, 'last' => $now, 'ips' => array() );
	$row['n']++;
	$row['last'] = $now;
	$ip          = paulus_sec_ip();
	if ( $ip && ! in_array( $ip, $row['ips'], true ) && count( $row['ips'] ) < 1000 ) {
		$row['ips'][] = $ip;
	}
	$failed[ $login ] = $row;
	update_option( PAULUS_SEC_FAILED, $failed, false );
}
add_action( 'wp_login_failed', 'paulus_sec_failed_login' );

/**
 * Admin notice when an existing account is being guessed at: ten or more
 * failures in a day, or a correct password refused on the remote route.
 */
function paulus_sec_attack_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$failed = get_option( PAULUS_SEC_FAILED, array() );
	$hit    = array();
	foreach ( (array) $failed as $name => $row ) {
		if ( (int) $row['n'] >= 10 && ( username_exists( $name ) || email_exists( $name ) ) ) {
			$hit[] = sprintf( '%s (%d, %d)', $name, (int) $row['n'], count( (array) $row['ips'] ) );
		}
	}
	$counts = paulus_sec_counts();
	$today  = isset( $counts[ gmdate( 'Y-m-d' ) ]['remote-password'] ) ? (int) $counts[ gmdate( 'Y-m-d' ) ]['remote-password'] : 0;
	if ( ! $hit && ! $today ) {
		return;
	}
	$url = paulus_sec_url();
	echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'Security:', 'paulus' ) . '</strong> ';
	if ( $today ) {
		esc_html_e( 'Someone sent a correct password through XML-RPC today. The login was refused, but the password is known: change it now.', 'paulus' );
		echo ' ';
	}
	if ( $hit ) {
		/* translators: %s: account names with failure and address counts. */
		echo esc_html( sprintf( __( 'Passwords are being guessed for these accounts in the last day (failures, addresses): %s.', 'paulus' ), implode( ', ', $hit ) ) ) . ' ';
	}
	echo '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Open the Security tab', 'paulus' ) . '</a></p></div>';
}
add_action( 'admin_notices', 'paulus_sec_attack_notice' );

/* -------------------------------------------------------------------------
 * 2. Spam gate
 * ---------------------------------------------------------------------- */

/**
 * How this request reached WordPress.
 *
 * @return string
 */
function paulus_sec_request_type() {
	if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
		return 'XML-RPC';
	}
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return ! empty( $GLOBALS['wp_rest_application_password_uuid'] ) ? 'REST (application password)' : 'REST';
	}
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return 'WP-CLI';
	}
	if ( wp_doing_cron() ) {
		return 'cron';
	}
	if ( wp_doing_ajax() ) {
		return 'admin AJAX';
	}
	return is_admin() ? 'admin' : 'front end';
}

/**
 * Visitor IP address, as the web server saw it.
 *
 * @return string
 */
function paulus_sec_ip() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
}

/**
 * Add a line to the capped log.
 *
 * @param array $entry Fields: event, title, post_id, reasons.
 */
function paulus_sec_log( $entry ) {
	$log   = get_option( PAULUS_SEC_LOG, array() );
	$log   = is_array( $log ) ? $log : array();
	$log[] = array_merge(
		array(
			'time'    => time(),
			'event'   => '',
			'title'   => '',
			'post_id' => 0,
			'reasons' => array(),
			'user'    => get_current_user_id(),
			'type'    => paulus_sec_request_type(),
			'ip'      => paulus_sec_ip(),
		),
		$entry
	);
	update_option( PAULUS_SEC_LOG, array_slice( $log, -PAULUS_SEC_LOG_MAX ), false );
}

/**
 * Slugs of what the theme ships, by post type: the articles, pages and
 * Journal entries of content/manifest.json. The structure sync identifies its
 * items by these slugs (it marks them with no meta key of its own).
 *
 * @return array type => slug => title
 */
function paulus_sec_seed_index() {
	static $index = null;
	if ( null !== $index ) {
		return $index;
	}
	$index    = array( 'post' => array(), 'page' => array(), 'paulus_journal' => array() );
	$manifest = function_exists( 'paulus_manifest' ) ? (array) paulus_manifest() : array();
	foreach ( array( 'posts' => 'post', 'pages' => 'page', 'journal' => 'paulus_journal' ) as $group => $type ) {
		foreach ( (array) ( $manifest[ $group ] ?? array() ) as $item ) {
			if ( ! empty( $item['slug'] ) ) {
				$index[ $type ][ (string) $item['slug'] ] = (string) ( $item['title'] ?? '' );
			}
		}
	}
	return $index;
}

/**
 * Whether a post is content the theme ships: an existing post of the right
 * type whose slug is in the manifest, or a new one carrying both the slug and
 * the title the manifest gives it (as the structure sync inserts them).
 *
 * @param int    $post_id Post ID, 0 for a new post.
 * @param string $title   Title.
 * @param string $slug    Slug.
 * @param string $type    Post type.
 * @return bool
 */
function paulus_sec_is_seeded( $post_id, $title = '', $slug = '', $type = '' ) {
	if ( $post_id && '' === $type ) {
		$type = (string) get_post_type( $post_id );
	}
	$index = paulus_sec_seed_index();
	$slug  = strtolower( rawurldecode( (string) $slug ) );
	if ( ! isset( $index[ $type ][ $slug ] ) ) {
		return false;
	}
	if ( $post_id ) {
		$post = get_post( $post_id );
		if ( $post && $post->post_name === $slug && $post->post_type === $type ) {
			return true;
		}
	}
	return $index[ $type ][ $slug ] === (string) $title;
}

/**
 * Post types the gate and the scan look at. Menus, attachments, templates and
 * blocks are never touched.
 *
 * @return string[]
 */
function paulus_sec_types() {
	// Journal entries are public, open to the REST API and would show spam
	// as readily as posts, so they are covered too.
	return array( 'post', 'page', 'paulus_journal' );
}

/**
 * Hold suspected spam: any post going public whose title, slug or content
 * scores as gambling spam is saved as Pending instead.
 *
 * @param array $data    Sanitised post data.
 * @param array $postarr Raw post data.
 * @return array
 */
function paulus_sec_gate( $data, $postarr ) {
	if ( ! paulus_sec_on( 'sec_spam_gate' ) || 'trash' === $data['post_status'] || ! in_array( $data['post_type'], paulus_sec_types(), true ) ) {
		return $data;
	}
	$public = in_array( $data['post_status'], array( 'publish', 'future', 'private' ), true );
	$id     = isset( $postarr['ID'] ) ? (int) $postarr['ID'] : 0;
	$title  = wp_unslash( $data['post_title'] );
	$slug   = $data['post_name'];
	if ( paulus_sec_is_seeded( $id, $title, $slug, $data['post_type'] ) ) {
		return $data;
	}
	$verdict = paulus_spam_score( $title, $slug, wp_unslash( $data['post_content'] ) );
	if ( ! $verdict['spam'] && $public && paulus_sec_is_remote() ) {
		// The spam arrived through wp.newPost: anything published from
		// outside the editor waits for review, whatever it says.
		$verdict = array( 'spam' => true, 'reasons' => array( __( 'published from outside the editor', 'paulus' ) . ' (' . paulus_sec_request_type() . ')' ) );
	}
	if ( ! $verdict['spam'] ) {
		return $data;
	}
	if ( $public ) {
		$data['post_status'] = 'pending';
	}
	$GLOBALS['paulus_sec_held'] = array(
		'event'   => $public ? 'held' : 'flagged-draft',
		'title'   => $title,
		'reasons' => $verdict['reasons'],
	);
	return $data;
}
add_filter( 'wp_insert_post_data', 'paulus_sec_gate', 99, 2 );

/**
 * Log what the gate held, now that the post has an ID.
 *
 * @param int $post_id Post ID.
 */
function paulus_sec_gate_log( $post_id ) {
	if ( empty( $GLOBALS['paulus_sec_held'] ) ) {
		return;
	}
	$entry                   = $GLOBALS['paulus_sec_held'];
	$GLOBALS['paulus_sec_held'] = null;
	$entry['post_id']        = (int) $post_id;
	update_post_meta( $post_id, '_paulus_spam_held', time() );
	paulus_sec_log( $entry );
}
add_action( 'wp_insert_post', 'paulus_sec_gate_log', 1 );

/**
 * Keep held posts away from search engines while they wait: Rank Math and the
 * core sitemap only list published items, and a held post is never published,
 * so Instant Indexing has nothing to submit.
 */

/* -------------------------------------------------------------------------
 * 3. 410 Gone for removed spam
 * ---------------------------------------------------------------------- */

/**
 * Path of a URL, without slashes at either end.
 *
 * @param string $url URL.
 * @return string
 */
function paulus_sec_path( $url ) {
	$path = (string) wp_parse_url( $url, PHP_URL_PATH );
	return trim( strtolower( rawurldecode( $path ) ), '/' );
}

/**
 * Remember the address of a post moved to the trash as spam.
 *
 * @param WP_Post $post Post.
 */
function paulus_sec_remember_gone( $post ) {
	$gone = get_option( PAULUS_SEC_GONE, array() );
	$gone = is_array( $gone ) ? $gone : array();
	foreach ( array( get_permalink( $post ), home_url( '/?p=' . $post->ID ) ) as $url ) {
		$path = paulus_sec_path( $url );
		if ( '' !== $path ) {
			$gone[ $path ] = (int) $post->ID;
		}
	}
	if ( $post->post_name ) {
		$gone[ strtolower( rawurldecode( $post->post_name ) ) ] = (int) $post->ID;
	}
	update_option( PAULUS_SEC_GONE, array_slice( $gone, -PAULUS_SEC_GONE_MAX, null, true ), false );
}

/**
 * Answer 410 Gone, in place of 404, on the address of removed spam. A post
 * restored from the trash and published again is served as normal, since the
 * request is then no longer a 404.
 */
function paulus_sec_send_gone() {
	if ( ! is_404() || ! paulus_sec_on( 'sec_gone' ) ) {
		return;
	}
	$gone = get_option( PAULUS_SEC_GONE, array() );
	if ( ! $gone || ! is_array( $gone ) ) {
		return;
	}
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
	$path = paulus_sec_path( $uri );
	$last = basename( $path );
	if ( isset( $gone[ $path ] ) || ( '' !== $last && isset( $gone[ $last ] ) && false === strpos( $path, '/' ) ) ) {
		status_header( 410 );
		nocache_headers();
		header( 'X-Robots-Tag: noindex', true );
	}
}
add_action( 'template_redirect', 'paulus_sec_send_gone', 0 );

/* -------------------------------------------------------------------------
 * 4. Login record for the report
 * ---------------------------------------------------------------------- */

/**
 * Last login time and IP, kept from this version on.
 *
 * @param string  $login Login name.
 * @param WP_User $user  User.
 */
function paulus_sec_record_login( $login, $user ) {
	update_user_meta( $user->ID, '_paulus_last_login', array( 'time' => time(), 'ip' => paulus_sec_ip() ) );
}
add_action( 'wp_login', 'paulus_sec_record_login', 10, 2 );

/* -------------------------------------------------------------------------
 * 5. Admin actions
 * ---------------------------------------------------------------------- */

/**
 * Stop unless the user may manage options and the nonce is valid.
 *
 * @param string $action Nonce action.
 */
function paulus_sec_check( $action ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to do that.', 'paulus' ), 403 );
	}
	check_admin_referer( $action );
}

/**
 * Back to the Security tab with a notice.
 *
 * @param string $notice Notice code.
 * @param int    $count  Count.
 */
function paulus_sec_back( $notice, $count = 0 ) {
	wp_safe_redirect( paulus_sec_url( array( 'paulus-sec' => $notice, 'paulus-count' => (int) $count ) ) );
	exit;
}

/**
 * Revoke every application password of every user.
 */
function paulus_sec_handle_revoke() {
	paulus_sec_check( 'paulus_sec_revoke' );
	$count = 0;
	if ( class_exists( 'WP_Application_Passwords' ) ) {
		foreach ( get_users( array( 'fields' => 'ID' ) ) as $user_id ) {
			$deleted = WP_Application_Passwords::delete_all_application_passwords( (int) $user_id );
			if ( ! is_wp_error( $deleted ) ) {
				$count += (int) $deleted;
			}
		}
	}
	paulus_sec_log( array( 'event' => 'app-passwords-revoked', 'title' => (string) $count ) );
	paulus_sec_back( 'revoked', $count );
}
add_action( 'admin_post_paulus_sec_revoke', 'paulus_sec_handle_revoke' );

/**
 * End every login session of every user, this one included.
 */
function paulus_sec_handle_logout() {
	paulus_sec_check( 'paulus_sec_logout' );
	paulus_sec_log( array( 'event' => 'sessions-ended', 'title' => '' ) );
	foreach ( get_users( array( 'fields' => 'ID' ) ) as $user_id ) {
		WP_Session_Tokens::get_instance( (int) $user_id )->destroy_all();
	}
	wp_clear_auth_cookie();
	wp_safe_redirect( wp_login_url( paulus_sec_url() ) );
	exit;
}
add_action( 'admin_post_paulus_sec_logout', 'paulus_sec_handle_logout' );

/**
 * Category slugs the theme seeds: the sections of content/manifest.json.
 *
 * @return string[]
 */
function paulus_sec_seeded_terms() {
	$manifest = function_exists( 'paulus_manifest' ) ? (array) paulus_manifest() : array();
	$slugs    = array();
	foreach ( (array) ( $manifest['parts'] ?? array() ) as $part ) {
		if ( ! empty( $part['slug'] ) ) {
			$slugs[] = (string) $part['slug'];
		}
	}
	return $slugs;
}

/**
 * Categories spam created: a spam-scoring name or one of the names seen in the
 * incident, never a seeded or default category, and only when nothing but
 * trashed posts uses it.
 *
 * @return WP_Term[]
 */
function paulus_sec_spam_categories() {
	$known   = array( 'casino', 'casinos', 'review', 'reviews', 'betting', 'gambling', 'bonus', 'slots', 'sportsbook', 'kasyno', 'kazino' );
	$seeded  = paulus_sec_seeded_terms();
	$default = (int) get_option( 'default_category' );
	$out     = array();
	foreach ( get_terms( array( 'taxonomy' => 'category', 'hide_empty' => false ) ) as $term ) {
		if ( (int) $term->term_id === $default || in_array( $term->slug, $seeded, true ) || 'uncategorized' === $term->slug ) {
			continue;
		}
		$verdict = paulus_spam_score( $term->name, $term->slug, '' );
		if ( ! in_array( $term->slug, $known, true ) && ! $verdict['spam'] ) {
			continue;
		}
		$used = get_posts(
			array(
				'post_type'      => 'any',
				'post_status'    => array( 'publish', 'pending', 'draft', 'future', 'private' ),
				'category'       => $term->term_id,
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		$term->paulus_in_use = ! empty( $used );
		$out[]            = $term;
	}
	return $out;
}

/**
 * Delete spam categories that are empty.
 */
function paulus_sec_handle_delete_categories() {
	paulus_sec_check( 'paulus_sec_categories' );
	$count = 0;
	foreach ( paulus_sec_spam_categories() as $term ) {
		if ( ! $term->paulus_in_use && ! is_wp_error( wp_delete_term( $term->term_id, 'category' ) ) ) {
			++$count;
		}
	}
	paulus_sec_log( array( 'event' => 'categories-deleted', 'title' => (string) $count ) );
	paulus_sec_back( 'categories', $count );
}
add_action( 'admin_post_paulus_sec_categories', 'paulus_sec_handle_delete_categories' );

/**
 * Clear the gate log.
 */
function paulus_sec_handle_clear_log() {
	paulus_sec_check( 'paulus_sec_clear_log' );
	delete_option( PAULUS_SEC_LOG );
	paulus_sec_back( 'log-cleared' );
}
add_action( 'admin_post_paulus_sec_clear_log', 'paulus_sec_handle_clear_log' );

/**
 * AJAX: one batch of the scan.
 */
function paulus_sec_ajax_scan() {
	check_ajax_referer( 'paulus_sec_ajax', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'You do not have permission to do that.', 'paulus' ) ), 403 );
	}
	$offset = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
	$batch  = 100;
	$query  = new WP_Query(
		array(
			'post_type'              => paulus_sec_types(),
			'post_status'            => array( 'publish', 'pending', 'draft', 'future', 'private' ),
			'posts_per_page'         => $batch,
			'offset'                 => $offset,
			'orderby'                => 'ID',
			'order'                  => 'DESC',
			'no_found_rows'          => false,
			'update_post_term_cache' => false,
			'suppress_filters'       => true,
		)
	);
	$rows = array();
	foreach ( $query->posts as $post ) {
		if ( paulus_sec_is_seeded( $post->ID, $post->post_title, $post->post_name, $post->post_type ) ) {
			continue;
		}
		$verdict = paulus_spam_score( $post->post_title, $post->post_name, $post->post_content );
		if ( ! $verdict['spam'] ) {
			continue;
		}
		$author = get_userdata( (int) $post->post_author );
		$rows[] = array(
			'id'      => (int) $post->ID,
			'title'   => '' !== $post->post_title ? wp_strip_all_tags( $post->post_title ) : __( '(no title)', 'paulus' ),
			'date'    => get_the_date( 'Y-m-d H:i', $post ),
			'status'  => $post->post_status,
			'author'  => $author ? $author->user_login : '',
			'reasons' => $verdict['reasons'],
			'edit'    => (string) get_edit_post_link( $post->ID, 'raw' ),
			'view'    => 'publish' === $post->post_status ? (string) get_permalink( $post ) : '',
		);
	}
	$next = $offset + count( $query->posts );
	wp_send_json_success(
		array(
			'rows'  => $rows,
			'next'  => $next,
			'total' => (int) $query->found_posts,
			'done'  => count( $query->posts ) < $batch,
		)
	);
}
add_action( 'wp_ajax_paulus_sec_scan', 'paulus_sec_ajax_scan' );

/**
 * AJAX: move a batch of posts to the trash. Each one is scored again here, so
 * only posts that still read as spam and are not starter content go.
 */
function paulus_sec_ajax_trash() {
	check_ajax_referer( 'paulus_sec_ajax', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'You do not have permission to do that.', 'paulus' ) ), 403 );
	}
	$ids     = isset( $_POST['ids'] ) ? array_slice( array_map( 'absint', (array) wp_unslash( $_POST['ids'] ) ), 0, 100 ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- absint on each.
	$trashed = array();
	$skipped = array();
	foreach ( array_filter( $ids ) as $id ) {
		$post = get_post( $id );
		if ( ! $post || ! in_array( $post->post_type, paulus_sec_types(), true ) || 'trash' === $post->post_status || ! current_user_can( 'delete_post', $id ) || paulus_sec_is_seeded( $id, $post->post_title, $post->post_name, $post->post_type ) ) {
			$skipped[] = $id;
			continue;
		}
		$verdict = paulus_spam_score( $post->post_title, $post->post_name, $post->post_content );
		if ( ! $verdict['spam'] ) {
			$skipped[] = $id;
			continue;
		}
		if ( 'publish' === $post->post_status ) {
			paulus_sec_remember_gone( $post );
		}
		if ( wp_trash_post( $id ) ) {
			$trashed[] = $id;
		} else {
			$skipped[] = $id;
		}
	}
	if ( $trashed ) {
		paulus_sec_log( array( 'event' => 'trashed', 'title' => (string) count( $trashed ) ) );
	}
	wp_send_json_success( array( 'trashed' => $trashed, 'skipped' => $skipped ) );
}
add_action( 'wp_ajax_paulus_sec_trash', 'paulus_sec_ajax_trash' );

/* -------------------------------------------------------------------------
 * 6. Screen
 * ---------------------------------------------------------------------- */

/**
 * Assets for the Security tab.
 *
 * @param string $hook Screen hook.
 */
function paulus_sec_assets( $hook ) {
	if ( 'appearance_page_paulus-options' !== $hook || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	wp_enqueue_style( 'paulus-security', PAULUS_URI . '/assets/css/security.css', array( 'paulus-admin' ), PAULUS_VERSION );
	wp_enqueue_script( 'paulus-security', PAULUS_URI . '/assets/js/security.js', array(), PAULUS_VERSION, true );
	wp_localize_script(
		'paulus-security',
		'paulusSecurity',
		array(
			'ajax'    => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'paulus_sec_ajax' ),
			'i18n'    => array(
				'scanning'     => __( 'Scanning: %1$d of %2$d checked, %3$d found.', 'paulus' ),
				'done'         => __( 'Scan finished: %1$d checked, %2$d found.', 'paulus' ),
				'none'         => __( 'No suspected spam found.', 'paulus' ),
				'trashing'     => __( 'Moving to the trash: %1$d of %2$d.', 'paulus' ),
				'trashed'      => __( '%1$d moved to the trash, %2$d skipped. Posts > Trash can restore them.', 'paulus' ),
				'confirmTrash' => __( 'Move the selected posts to the trash? They can be restored from Posts > Trash.', 'paulus' ),
				'error'        => __( 'The request failed. Reload the page and try again.', 'paulus' ),
				'edit'         => __( 'Edit', 'paulus' ),
				'view'         => __( 'View', 'paulus' ),
				'select'       => __( 'Select', 'paulus' ),
			),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'paulus_sec_assets', 20 );

/**
 * Notices after Security actions.
 */
function paulus_sec_notice() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
	$code = isset( $_GET['paulus-sec'] ) ? sanitize_key( wp_unslash( $_GET['paulus-sec'] ) ) : '';
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
	$n    = isset( $_GET['paulus-count'] ) ? absint( $_GET['paulus-count'] ) : 0;
	$map  = array(
		/* translators: %d: number of application passwords. */
		'revoked'     => sprintf( __( 'Application passwords revoked: %d.', 'paulus' ), $n ),
		/* translators: %d: number of categories. */
		'categories'  => sprintf( __( 'Empty spam categories deleted: %d.', 'paulus' ), $n ),
		'log-cleared' => __( 'Security log cleared.', 'paulus' ),
	);
	if ( isset( $map[ $code ] ) ) {
		printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html( $map[ $code ] ) );
	}
}

/**
 * Notes under the Security tab's switches, which are ordinary Theme Options
 * fields (inc/options-fields.php): the state of the .htaccess block, where
 * application passwords are set, and the emergency constant.
 *
 * @param string $tab Tab being drawn.
 */
function paulus_sec_render_notes( $tab ) {
	if ( 'security' !== $tab ) {
		return;
	}
	echo '<div class="paulus-sec-notes">';
	$state = get_option( 'paulus_security_htaccess', '' );
	if ( 'written' === $state ) {
		echo '<p class="paulus-help">' . esc_html__( 'The web server also refuses xmlrpc.php: the theme keeps a "Block xmlrpc.php" section in .htaccess and repairs it if it goes missing.', 'paulus' ) . '</p>';
	} elseif ( 'unavailable' === $state ) {
		echo '<p class="paulus-help paulus-help--key">' . esc_html__( 'The theme could not write .htaccess (the server is not Apache, or the file is read-only). WordPress still refuses xmlrpc.php; ask the host to deny it at the server as well.', 'paulus' ) . '</p>';
	}
	echo '<p class="paulus-help">' . esc_html(
		paulus_option( 'login_app_passwords' )
			? __( 'Application passwords are on, under the Login tab. Switch them off there unless a tool needs them, and revoke the stored ones below.', 'paulus' )
			: __( 'Application passwords are off, under the Login tab, so none can be made or used.', 'paulus' )
	) . '</p>';
	if ( defined( 'PAULUS_SECURITY_LOCK' ) && ! PAULUS_SECURITY_LOCK ) {
		echo '<p class="paulus-help paulus-help--key">' . esc_html__( 'PAULUS_SECURITY_LOCK is false in wp-config.php, so the XML-RPC and REST switches are off whatever they show.', 'paulus' ) . '</p>';
	}
	echo '</div>';
}
add_action( 'paulus_options_panel_after', 'paulus_sec_render_notes' );

/**
 * Small action form posting to admin-post.php.
 *
 * @param string $action  Action and nonce name.
 * @param string $label   Button label.
 * @param string $confirm Confirmation text.
 * @param string $class   Button class.
 */
function paulus_sec_button( $action, $label, $confirm, $class = 'secondary' ) {
	?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="paulus-tools-form" data-paulus-confirm="<?php echo esc_attr( $confirm ); ?>">
		<input type="hidden" name="action" value="<?php echo esc_attr( $action ); ?>">
		<?php wp_nonce_field( $action ); ?>
		<?php submit_button( $label, $class, 'submit', false ); ?>
	</form>
	<?php
}

/**
 * Administrators with last login and application passwords.
 *
 * @return array
 */
function paulus_sec_admin_rows() {
	$rows = array();
	foreach ( get_users( array( 'role' => 'administrator' ) ) as $user ) {
		$last     = get_user_meta( $user->ID, '_paulus_last_login', true );
		$sessions = get_user_meta( $user->ID, 'session_tokens', true );
		$time     = is_array( $last ) && ! empty( $last['time'] ) ? (int) $last['time'] : 0;
		$ip       = is_array( $last ) && ! empty( $last['ip'] ) ? $last['ip'] : '';
		if ( is_array( $sessions ) ) {
			foreach ( $sessions as $session ) {
				if ( ! empty( $session['login'] ) && (int) $session['login'] > $time ) {
					$time = (int) $session['login'];
					$ip   = isset( $session['ip'] ) ? $session['ip'] : $ip;
				}
			}
		}
		$apps = class_exists( 'WP_Application_Passwords' ) ? WP_Application_Passwords::get_user_application_passwords( $user->ID ) : array();
		$used = 0;
		foreach ( $apps as $app ) {
			$used = max( $used, (int) $app['last_used'] );
		}
		$rows[] = array(
			'user'     => $user,
			'login'    => $time,
			'ip'       => $ip,
			'sessions' => is_array( $sessions ) ? count( $sessions ) : 0,
			'apps'     => $apps,
			'app_used' => $used,
		);
	}
	return $rows;
}

/**
 * Posts per day and author over the last 30 days, and hours with bursts.
 *
 * @return array { days: array date => array login => count, bursts: array }
 */
function paulus_sec_activity() {
	global $wpdb;
	$since = gmdate( 'Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- report query, read only.
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT post_author, DATE(post_date) AS d, HOUR(post_date) AS h, COUNT(*) AS n FROM {$wpdb->posts} WHERE post_type IN ('post','paulus_journal') AND post_status NOT IN ('auto-draft','inherit') AND post_date_gmt >= %s GROUP BY post_author, d, h",
			$since
		)
	);
	$days   = array();
	$bursts = array();
	foreach ( (array) $rows as $row ) {
		$user  = get_userdata( (int) $row->post_author );
		$login = $user ? $user->user_login : '#' . (int) $row->post_author;
		$days[ $row->d ][ $login ] = ( isset( $days[ $row->d ][ $login ] ) ? $days[ $row->d ][ $login ] : 0 ) + (int) $row->n;
		if ( (int) $row->n > 2 ) {
			$bursts[] = array( 'login' => $login, 'when' => sprintf( '%s %02d:00', $row->d, (int) $row->h ), 'n' => (int) $row->n );
		}
	}
	krsort( $days );
	return array( 'days' => $days, 'bursts' => $bursts );
}

/**
 * Starter items whose content now carries gambling links.
 *
 * @return WP_Post[]
 */
function paulus_sec_tainted_seeds() {
	$out   = array();
	$index = paulus_sec_seed_index();
	$slugs = array();
	foreach ( $index as $type_slugs ) {
		$slugs = array_merge( $slugs, array_map( 'strval', array_keys( $type_slugs ) ) );
	}
	if ( ! $slugs ) {
		return $out;
	}
	$posts = get_posts(
		array(
			'post_type'      => array_keys( $index ),
			'post_status'    => 'any',
			'posts_per_page' => 500,
			'post_name__in'  => array_values( array_unique( $slugs ) ),
			'no_found_rows'  => true,
		)
	);
	foreach ( $posts as $post ) {
		if ( ! paulus_sec_is_seeded( $post->ID, $post->post_title, $post->post_name, $post->post_type ) ) {
			continue;
		}
		$hosts = paulus_spam_links( $post->post_content );
		if ( $hosts ) {
			$post->paulus_hosts = $hosts;
			$out[]           = $post;
		}
	}
	return $out;
}

/**
 * Whether Rank Math Instant Indexing is on.
 *
 * @return bool
 */
function paulus_sec_instant_indexing() {
	return class_exists( '\RankMath\Helper' ) && method_exists( '\RankMath\Helper', 'is_module_active' ) && \RankMath\Helper::is_module_active( 'instant-indexing' );
}

/**
 * Tools and reports, shown under the form while the Security tab is open.
 */
function paulus_sec_render_tools() {
	if ( ! current_user_can( 'manage_options' ) || ! paulus_is_options_screen() ) {
		return;
	}
	$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
	$admins = paulus_sec_admin_rows();
	$total  = 0;
	foreach ( $admins as $a ) {
		$total += count( $a['apps'] );
	}
	?>
	<section class="paulus-sec" id="paulus-security-tools" aria-label="<?php esc_attr_e( 'Security tools', 'paulus' ); ?>" hidden>
		<?php paulus_sec_notice(); ?>

		<h2><?php esc_html_e( 'Clean up spam', 'paulus' ); ?></h2>
		<p class="paulus-help"><?php esc_html_e( 'The scan reads every article, page and Journal entry that is published, pending, scheduled, private or a draft, 100 at a time, and lists those that read as gambling spam. Content the theme ships is never listed. Nothing changes until you move posts to the trash, and the trash can restore them.', 'paulus' ); ?></p>
		<div class="paulus-sec-bar">
			<button type="button" class="button button-primary" id="paulus-sec-scan"><?php esc_html_e( 'Scan for spam', 'paulus' ); ?></button>
			<button type="button" class="button" id="paulus-sec-trash" disabled><?php esc_html_e( 'Move selected to Trash', 'paulus' ); ?></button>
			<span class="paulus-sec-status" id="paulus-sec-status" role="status" aria-live="polite"></span>
		</div>
		<div class="paulus-sec-progress" hidden><span id="paulus-sec-progress"></span></div>
		<div class="paulus-sec-scroll">
			<table class="widefat striped paulus-sec-table" id="paulus-sec-results" hidden>
				<thead>
					<tr>
						<td class="check-column"><input type="checkbox" id="paulus-sec-all" aria-label="<?php esc_attr_e( 'Select all', 'paulus' ); ?>"></td>
						<th scope="col"><?php esc_html_e( 'Title', 'paulus' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Date', 'paulus' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Status', 'paulus' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Author', 'paulus' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Reasons', 'paulus' ); ?></th>
					</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
		<p class="paulus-help">
			<?php
			esc_html_e( 'A trashed post answers 404, or 410 Gone when that switch is on. Resubmit the sitemap in Google Search Console afterwards.', 'paulus' );
			echo ' ';
			echo paulus_sec_instant_indexing()
				? esc_html__( 'Rank Math Instant Indexing is on. Held spam is never published, so it is never submitted.', 'paulus' )
				: esc_html__( 'Rank Math Instant Indexing is off.', 'paulus' );
			?>
		</p>

		<h2><?php esc_html_e( 'Spam categories', 'paulus' ); ?></h2>
		<?php $cats = paulus_sec_spam_categories(); ?>
		<?php if ( $cats ) : ?>
			<ul class="paulus-sec-list">
				<?php foreach ( $cats as $term ) : ?>
					<li>
						<?php
						echo esc_html( $term->name . ' (' . $term->slug . ')' );
						echo ' ';
						echo $term->paulus_in_use ? esc_html__( 'still used by posts outside the trash, kept', 'paulus' ) : esc_html__( 'empty, can be deleted', 'paulus' );
						?>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php paulus_sec_button( 'paulus_sec_categories', __( 'Delete empty spam categories', 'paulus' ), __( 'Delete the empty spam categories listed? Categories the theme seeds are never deleted.', 'paulus' ) ); ?>
		<?php else : ?>
			<p class="paulus-help"><?php esc_html_e( 'No spam categories found.', 'paulus' ); ?></p>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Passwords and sessions', 'paulus' ); ?></h2>
		<p class="paulus-help">
			<?php
			/* translators: %d: number of application passwords. */
			echo esc_html( sprintf( __( 'Application passwords stored on administrator accounts: %d.', 'paulus' ), $total ) );
			?>
		</p>
		<div class="paulus-sec-bar">
			<?php
			paulus_sec_button( 'paulus_sec_revoke', __( 'Revoke all application passwords', 'paulus' ), __( 'Revoke every application password of every user? Tools that use them stop working.', 'paulus' ), 'delete' );
			paulus_sec_button( 'paulus_sec_logout', __( 'Log out all sessions', 'paulus' ), __( 'End every login session of every user, yours included? You will need to log in again.', 'paulus' ), 'delete' );
			?>
		</div>

		<h3><?php esc_html_e( 'Administrator accounts', 'paulus' ); ?></h3>
		<div class="paulus-sec-scroll">
			<table class="widefat striped paulus-sec-table">
				<thead><tr><th scope="col"><?php esc_html_e( 'Account', 'paulus' ); ?></th><th scope="col"><?php esc_html_e( 'Last login', 'paulus' ); ?></th><th scope="col"><?php esc_html_e( 'Sessions', 'paulus' ); ?></th><th scope="col"><?php esc_html_e( 'Application passwords', 'paulus' ); ?></th></tr></thead>
				<tbody>
					<?php foreach ( $admins as $a ) : ?>
						<tr>
							<td><a href="<?php echo esc_url( get_edit_user_link( $a['user']->ID ) ); ?>"><?php echo esc_html( $a['user']->user_login ); ?></a><br><span class="paulus-help"><?php echo esc_html( $a['user']->user_email ); ?></span></td>
							<td><?php echo $a['login'] ? esc_html( wp_date( $format, $a['login'] ) . ( $a['ip'] ? ' (' . $a['ip'] . ')' : '' ) ) : esc_html__( 'Not recorded', 'paulus' ); ?></td>
							<td><?php echo esc_html( (string) $a['sessions'] ); ?></td>
							<td>
								<?php
								echo esc_html( (string) count( $a['apps'] ) );
								foreach ( $a['apps'] as $app ) {
									echo '<br><span class="paulus-help">' . esc_html( $app['name'] . ': ' . ( $app['last_used'] ? wp_date( $format, (int) $app['last_used'] ) . ( $app['last_ip'] ? ' (' . $app['last_ip'] . ')' : '' ) : __( 'never used', 'paulus' ) ) ) . '</span>';
								}
								?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<h2><?php esc_html_e( 'Attacks seen', 'paulus' ); ?></h2>
		<?php
		$counts = paulus_sec_counts();
		$labels = array(
			'xmlrpc'          => __( 'XML-RPC requests refused', 'paulus' ),
			'fake-jetpack'    => __( 'of them posing as Jetpack', 'paulus' ),
			'remote-password' => __( 'correct passwords refused on XML-RPC', 'paulus' ),
			'failed-login'    => __( 'failed logins', 'paulus' ),
		);
		?>
		<p class="paulus-help"><?php esc_html_e( 'Counted per day, in UTC, for the last 30 days. A correct password refused on XML-RPC means the password is known to someone else: change it.', 'paulus' ); ?></p>
		<?php if ( $counts ) : ?>
			<div class="paulus-sec-scroll">
				<table class="widefat striped paulus-sec-table">
					<thead><tr><th scope="col"><?php esc_html_e( 'Day', 'paulus' ); ?></th><?php foreach ( $labels as $label ) : ?><th scope="col"><?php echo esc_html( $label ); ?></th><?php endforeach; ?></tr></thead>
					<tbody>
						<?php foreach ( $counts as $day => $row ) : ?>
							<tr><td><?php echo esc_html( $day ); ?></td><?php foreach ( array_keys( $labels ) as $key ) : ?><td><?php echo esc_html( (string) ( isset( $row[ $key ] ) ? (int) $row[ $key ] : 0 ) ); ?></td><?php endforeach; ?></tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php else : ?>
			<p class="paulus-help"><?php esc_html_e( 'Nothing counted yet.', 'paulus' ); ?></p>
		<?php endif; ?>
		<h3><?php esc_html_e( 'Accounts being guessed, last 24 hours', 'paulus' ); ?></h3>
		<p class="paulus-help"><?php esc_html_e( 'Attackers change address on every try, so failures are counted by account name. An account of yours in this list should get a new, long password, and ideally a new name that is not the site name.', 'paulus' ); ?></p>
		<?php
		$failed = get_option( PAULUS_SEC_FAILED, array() );
		$failed = is_array( $failed ) ? $failed : array();
		uasort(
			$failed,
			function ( $x, $y ) {
				return (int) $y['n'] - (int) $x['n'];
			}
		);
		?>
		<?php if ( $failed ) : ?>
			<ul class="paulus-sec-list">
				<?php foreach ( array_slice( $failed, 0, 20, true ) as $name => $row ) : ?>
					<li>
						<?php
						$real = username_exists( $name ) || email_exists( $name );
						/* translators: 1: account name, 2: failures, 3: addresses, 4: whether the account exists. */
						echo esc_html( sprintf( __( '%1$s: %2$d failures from %3$d addresses%4$s', 'paulus' ), $name, (int) $row['n'], count( (array) $row['ips'] ), $real ? __( ', a real account on this site', 'paulus' ) : '' ) );
						?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p class="paulus-help"><?php esc_html_e( 'None.', 'paulus' ); ?></p>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Activity', 'paulus' ); ?></h2>
		<?php $activity = paulus_sec_activity(); ?>
		<h3><?php esc_html_e( 'Posts per day, last 30 days', 'paulus' ); ?></h3>
		<?php if ( $activity['days'] ) : ?>
			<div class="paulus-sec-scroll">
				<table class="widefat striped paulus-sec-table paulus-sec-days">
					<thead><tr><th scope="col"><?php esc_html_e( 'Day', 'paulus' ); ?></th><th scope="col"><?php esc_html_e( 'Account and count', 'paulus' ); ?></th></tr></thead>
					<tbody>
						<?php foreach ( $activity['days'] as $day => $counts ) : ?>
							<tr>
								<td><?php echo esc_html( $day ); ?></td>
								<td>
									<?php
									$parts = array();
									foreach ( $counts as $login => $n ) {
										$parts[] = $login . ': ' . $n;
									}
									echo esc_html( implode( ', ', $parts ) );
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php else : ?>
			<p class="paulus-help"><?php esc_html_e( 'No posts in the last 30 days.', 'paulus' ); ?></p>
		<?php endif; ?>
		<h3><?php esc_html_e( 'Burst publishing', 'paulus' ); ?></h3>
		<p class="paulus-help"><?php esc_html_e( 'Hours in which one account added more than two posts. A report only: nothing is held or trashed for this.', 'paulus' ); ?></p>
		<?php if ( $activity['bursts'] ) : ?>
			<ul class="paulus-sec-list">
				<?php foreach ( array_slice( $activity['bursts'], 0, 50 ) as $b ) : ?>
					<li><?php echo esc_html( sprintf( '%s, %s: %d', $b['login'], $b['when'], $b['n'] ) ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p class="paulus-help"><?php esc_html_e( 'None.', 'paulus' ); ?></p>
		<?php endif; ?>

		<h3><?php esc_html_e( 'Starter content with gambling links', 'paulus' ); ?></h3>
		<?php $tainted = paulus_sec_tainted_seeds(); ?>
		<?php if ( $tainted ) : ?>
			<p class="paulus-help"><?php esc_html_e( 'These starter items link to gambling sites. Nothing is changed here: open each one and remove the links by hand.', 'paulus' ); ?></p>
			<ul class="paulus-sec-list">
				<?php foreach ( $tainted as $post ) : ?>
					<li><a href="<?php echo esc_url( (string) get_edit_post_link( $post->ID ) ); ?>"><?php echo esc_html( $post->post_title ); ?></a> <span class="paulus-help"><?php echo esc_html( implode( ', ', $post->paulus_hosts ) ); ?></span></li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p class="paulus-help"><?php esc_html_e( 'None: no starter item links to a gambling site.', 'paulus' ); ?></p>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Security log', 'paulus' ); ?></h2>
		<?php
		$log = get_option( PAULUS_SEC_LOG, array() );
		$log = is_array( $log ) ? array_reverse( $log ) : array();
		?>
		<?php if ( $log ) : ?>
			<div class="paulus-sec-scroll paulus-sec-scroll--tall">
				<table class="widefat striped paulus-sec-table">
					<thead><tr><th scope="col"><?php esc_html_e( 'Time', 'paulus' ); ?></th><th scope="col"><?php esc_html_e( 'Event', 'paulus' ); ?></th><th scope="col"><?php esc_html_e( 'Detail', 'paulus' ); ?></th><th scope="col"><?php esc_html_e( 'Account', 'paulus' ); ?></th><th scope="col"><?php esc_html_e( 'Route', 'paulus' ); ?></th><th scope="col"><?php esc_html_e( 'IP', 'paulus' ); ?></th></tr></thead>
					<tbody>
						<?php foreach ( $log as $e ) : ?>
							<?php $u = ! empty( $e['user'] ) ? get_userdata( (int) $e['user'] ) : false; ?>
							<tr>
								<td><?php echo esc_html( wp_date( $format, (int) $e['time'] ) ); ?></td>
								<td><?php echo esc_html( $e['event'] ); ?></td>
								<td>
									<?php
									if ( ! empty( $e['post_id'] ) && get_post( (int) $e['post_id'] ) ) {
										echo '<a href="' . esc_url( (string) get_edit_post_link( (int) $e['post_id'] ) ) . '">' . esc_html( $e['title'] ) . '</a>';
									} else {
										echo esc_html( $e['title'] );
									}
									if ( ! empty( $e['reasons'] ) ) {
										echo '<br><span class="paulus-help">' . esc_html( implode( '; ', (array) $e['reasons'] ) ) . '</span>';
									}
									?>
								</td>
								<td><?php echo esc_html( $u ? $u->user_login : '' ); ?></td>
								<td><?php echo esc_html( $e['type'] ); ?></td>
								<td><?php echo esc_html( $e['ip'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php paulus_sec_button( 'paulus_sec_clear_log', __( 'Clear the log', 'paulus' ), __( 'Clear the security log?', 'paulus' ) ); ?>
		<?php else : ?>
			<p class="paulus-help"><?php esc_html_e( 'Nothing logged yet.', 'paulus' ); ?></p>
		<?php endif; ?>
	</section>
	<?php
}
add_action( 'paulus_options_after_form', 'paulus_sec_render_tools' );
