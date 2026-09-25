<?php
/**
 * A private login address: the login screen moves from wp-login.php to an
 * address of the owner's choosing, and wp-login.php and wp-admin answer
 * visitors who are not logged in with a page not found (or a chosen page).
 * A native form of the plugin WPS Hide Login by WPServeur, Nicolas Kulka
 * and wpformation (GPL v2 or later), following its request handling step by
 * step and reading its own options, `whl_page` (the login address) and
 * `whl_redirect_admin` (where blocked requests go), so a site that used the
 * plugin keeps its login address.
 *
 * A theme loads later than a plugin: the plugin's first step runs on
 * plugins_loaded, before any theme exists. It only recognises the request
 * and adjusts $pagenow and the request address, which WordPress does not
 * read again until later, so here it runs on after_setup_theme, the
 * earliest point a theme has. The rest runs on wp_loaded, as in the plugin.
 *
 * Safety: nothing changes until a login address is saved (Settings,
 * Permalinks). define( 'PAULUS_HIDE_LOGIN', false ); in wp-config.php turns
 * the whole module off, restoring wp-login.php; left out, or set to true,
 * the module runs. The module stands
 * down while the plugin itself is active.
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

if ( defined( 'WPS_HIDE_LOGIN_BASENAME' ) || class_exists( 'WPS\\WPS_Hide_Login\\Plugin' ) ) {
	return;
}

/**
 * The login address's slug, or an empty string when none is set.
 *
 * @return string
 */
function paulus_login_slug() {
	// define( 'PAULUS_HIDE_LOGIN', false ); in wp-config.php switches it off.
	if ( defined( 'PAULUS_HIDE_LOGIN' ) && ! PAULUS_HIDE_LOGIN ) {
		return '';
	}
	$slug = sanitize_title_with_dashes( (string) get_option( 'whl_page', '' ) );
	return ( '' === $slug || false !== strpos( $slug, 'wp-login' ) ) ? '' : $slug;
}

/**
 * Where blocked requests go: a slug on the site, 404 by default.
 *
 * @return string
 */
function paulus_login_redirect_slug() {
	$slug = sanitize_title_with_dashes( (string) get_option( 'whl_redirect_admin', '404' ) );
	return '' !== $slug ? $slug : '404';
}

/**
 * With or without a closing slash, as the permalink structure has it.
 *
 * @param string $url Address.
 * @return string
 */
function paulus_login_slashed( $url ) {
	return '/' === substr( (string) get_option( 'permalink_structure' ), -1 ) ? trailingslashit( $url ) : untrailingslashit( $url );
}

/**
 * The private login address.
 *
 * @param string|null $scheme Scheme.
 * @return string
 */
function paulus_login_url( $scheme = null ) {
	$home = home_url( '/', $scheme );
	return get_option( 'permalink_structure' ) ? paulus_login_slashed( $home . paulus_login_slug() ) : $home . '?' . paulus_login_slug();
}

/**
 * Where blocked requests are sent.
 *
 * @param string|null $scheme Scheme.
 * @return string
 */
function paulus_login_redirect_url( $scheme = null ) {
	$home = home_url( '/', $scheme );
	return get_option( 'permalink_structure' ) ? paulus_login_slashed( $home . paulus_login_redirect_slug() ) : $home . '?' . paulus_login_redirect_slug();
}

if ( '' !== paulus_login_slug() ) {

	/**
	 * Recognise the request, as the plugin does on plugins_loaded: a request
	 * for wp-login.php (or wp-register.php) is marked and disguised so it will
	 * resolve to a page not found; a request for the private address becomes
	 * the login screen.
	 */
	add_action( 'after_setup_theme', static function () {
		global $pagenow;
		$uri     = rawurldecode( (string) ( $_SERVER['REQUEST_URI'] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$request = wp_parse_url( $uri );
		$path    = isset( $request['path'] ) ? untrailingslashit( $request['path'] ) : '';
		$slug    = paulus_login_slug();
		if ( ( false !== strpos( $uri, 'wp-login.php' ) || site_url( 'wp-login', 'relative' ) === $path ) && ! is_admin() ) {
			$GLOBALS['paulus_login_blocked'] = true;
			$_SERVER['REQUEST_URI']          = paulus_login_slashed( '/' . str_repeat( '-/', 10 ) );
			$pagenow                         = 'index.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		} elseif ( home_url( $slug, 'relative' ) === $path || ( ! get_option( 'permalink_structure' ) && isset( $_GET[ $slug ] ) && empty( $_GET[ $slug ] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$_SERVER['SCRIPT_NAME'] = $slug;
			$pagenow                = 'wp-login.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		} elseif ( ( false !== strpos( $uri, 'wp-register.php' ) || site_url( 'wp-register', 'relative' ) === $path ) && ! is_admin() ) {
			$GLOBALS['paulus_login_blocked'] = true;
			$_SERVER['REQUEST_URI']          = paulus_login_slashed( '/' . str_repeat( '-/', 10 ) );
			$pagenow                         = 'index.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		}
		// The Customizer is closed to visitors who are not logged in.
		if ( 'customize.php' === $pagenow && ! is_user_logged_in() ) {
			wp_die( esc_html__( 'This has been disabled.', 'paulus' ), 403 );
		}
	}, 1 );

	/**
	 * Act on the request, as the plugin does on wp_loaded.
	 */
	add_action( 'wp_loaded', static function () {
		global $pagenow;
		$request = wp_parse_url( rawurldecode( (string) ( $_SERVER['REQUEST_URI'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$path    = $request['path'] ?? '';
		// Posting a password for a protected post goes through untouched.
		if ( isset( $_GET['action'] ) && 'postpass' === $_GET['action'] && isset( $_POST['post_password'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		$admin_path = wp_parse_url( admin_url( 'options.php' ), PHP_URL_PATH );
		// The admin, to a visitor not logged in: sent away.
		if ( is_admin() && ! is_user_logged_in() && ! defined( 'WP_CLI' ) && ! wp_doing_ajax() && ! wp_doing_cron() && 'admin-post.php' !== $pagenow && $path !== $admin_path ) {
			wp_safe_redirect( paulus_login_redirect_url() );
			exit;
		}
		if ( ! is_user_logged_in() && $path === $admin_path ) {
			wp_safe_redirect( paulus_login_redirect_url() );
			exit;
		}
		// The private address without its closing slash: add it.
		if ( 'wp-login.php' === $pagenow && '' !== $path && paulus_login_slashed( $path ) !== $path && get_option( 'permalink_structure' ) ) {
			wp_safe_redirect( paulus_login_slashed( paulus_login_url() ) . ( ! empty( $_SERVER['QUERY_STRING'] ) ? '?' . sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ) : '' ) );
			exit;
		}
		// wp-login.php itself: the site's page not found, drawn by the theme.
		if ( ! empty( $GLOBALS['paulus_login_blocked'] ) ) {
			$pagenow = 'index.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
			if ( ! defined( 'WP_USE_THEMES' ) ) {
				define( 'WP_USE_THEMES', true );
			}
			wp();
			require_once ABSPATH . WPINC . '/template-loader.php';
			exit;
		}
		// The private address: the login screen.
		if ( 'wp-login.php' === $pagenow ) {
			global $error, $interim_login, $action, $user_login;
			if ( is_user_logged_in() && ! isset( $_REQUEST['action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				wp_safe_redirect( admin_url() );
				exit;
			}
			require_once ABSPATH . 'wp-login.php';
			exit;
		}
	} );

	/**
	 * Every address WordPress builds for wp-login.php points to the private
	 * address instead (links, redirects, emails), as in the plugin.
	 *
	 * @param string      $url    Address.
	 * @param string|null $scheme Scheme.
	 * @return string
	 */
	function paulus_login_filter_url( $url, $scheme = null ) {
		if ( false !== strpos( $url, 'wp-login.php?action=postpass' ) || false === strpos( $url, 'wp-login.php' ) || false !== strpos( (string) wp_get_referer(), 'wp-login.php' ) ) {
			return $url;
		}
		if ( is_ssl() ) {
			$scheme = 'https';
		}
		$parts = explode( '?', $url );
		if ( isset( $parts[1] ) ) {
			parse_str( $parts[1], $args );
			if ( isset( $args['login'] ) ) {
				$args['login'] = rawurlencode( $args['login'] );
			}
			return add_query_arg( $args, paulus_login_url( $scheme ) );
		}
		return paulus_login_url( $scheme );
	}
	add_filter( 'site_url', static function ( $url, $path, $scheme ) {
		return paulus_login_filter_url( $url, $scheme );
	}, 10, 3 );
	add_filter( 'network_site_url', static function ( $url, $path, $scheme ) {
		return paulus_login_filter_url( $url, $scheme );
	}, 10, 3 );
	add_filter( 'wp_redirect', static function ( $location ) {
		return false !== strpos( $location, 'https://wordpress.com/wp-login.php' ) ? $location : paulus_login_filter_url( $location );
	} );
	// A page not found never reveals the login address.
	add_filter( 'login_url', static function ( $login_url ) {
		return is_404() ? '#' : $login_url;
	} );
	// Confirmation emails for personal-data requests carry a working address.
	add_filter( 'user_request_action_email_content', static function ( $text, $data ) {
		return str_replace( '###CONFIRM_URL###', esc_url_raw( str_replace( paulus_login_slug() . '/', 'wp-login.php', $data['confirm_url'] ) ), $text );
	}, 999, 2 );
	add_action( 'template_redirect', static function () {
		if ( isset( $_GET['action'], $_GET['request_id'], $_GET['confirm_key'] ) && 'confirmaction' === $_GET['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			$id  = (int) $_GET['request_id']; // phpcs:ignore WordPress.Security.NonceVerification
			$key = sanitize_text_field( wp_unslash( $_GET['confirm_key'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			if ( ! is_wp_error( wp_validate_user_request_key( $id, $key ) ) ) {
				wp_safe_redirect( add_query_arg( array( 'action' => 'confirmaction', 'request_id' => $id, 'confirm_key' => $key ), paulus_login_url() ) );
				exit;
			}
		}
	} );
	// Sign-up and activation are closed on a single site.
	add_action( 'init', static function () {
		$uri = rawurldecode( (string) ( $_SERVER['REQUEST_URI'] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( ! is_multisite() && ( false !== strpos( $uri, 'wp-signup' ) || false !== strpos( $uri, 'wp-activate' ) ) && false === apply_filters( 'wps_hide_login_signup_enable', false ) ) {
			wp_die( esc_html__( 'This feature is not enabled.', 'paulus' ) );
		}
	} );
}

/**
 * The settings, on Settings, Permalinks, with the site's other addresses.
 */
add_action( 'load-options-permalink.php', static function () {
	if ( isset( $_POST['whl_page'] ) ) {
		check_admin_referer( 'update-permalink' );
		$page     = sanitize_title_with_dashes( wp_unslash( $_POST['whl_page'] ) );
		$redirect = sanitize_title_with_dashes( wp_unslash( $_POST['whl_redirect_admin'] ?? '' ) );
		$reserved = array( 'wp-login', 'wp-login-php', 'wp-admin', 'admin', 'dashboard', 'wp-content', 'wp-includes' );
		if ( '' === $page ) {
			delete_option( 'whl_page' );
		} elseif ( ! in_array( $page, $reserved, true ) && false === strpos( $page, 'wp-login' ) && ! get_page_by_path( $page ) ) {
			update_option( 'whl_page', $page );
		} else {
			add_settings_error( 'permalink', 'paulus-login', __( 'That login address is reserved, or already used by a page. It was not saved.', 'paulus' ) );
		}
		update_option( 'whl_redirect_admin', '' !== $redirect ? $redirect : '404' );
	}
	add_settings_section(
		'paulus-login',
		__( 'Login address', 'paulus' ),
		static function () {
			echo '<p>' . esc_html__( 'Move the login screen to a private address. wp-login.php and the admin then show a page not found to visitors who are not logged in. Leave the address empty to use wp-login.php.', 'paulus' ) . '</p>';
			if ( '' !== paulus_login_slug() ) {
				/* translators: %s: login address. */
				echo '<p><strong>' . sprintf( esc_html__( 'Your login address: %s', 'paulus' ), '<code>' . esc_html( paulus_login_url() ) . '</code>' ) . '</strong> ' . esc_html__( 'Keep a note of it.', 'paulus' ) . '</p>';
			}
		},
		'permalink'
	);
	add_settings_field( 'whl_page', __( 'Login address', 'paulus' ), static function () {
		printf( '<code>%s</code> <input type="text" name="whl_page" id="whl_page" value="%s" class="regular-text code" placeholder="%s">', esc_html( home_url( '/' ) ), esc_attr( (string) get_option( 'whl_page', '' ) ), esc_attr__( 'e.g. masuk', 'paulus' ) );
	}, 'permalink', 'paulus-login' );
	add_settings_field( 'whl_redirect_admin', __( 'Blocked requests go to', 'paulus' ), static function () {
		printf( '<code>%s</code> <input type="text" name="whl_redirect_admin" id="whl_redirect_admin" value="%s" class="regular-text code"><p class="description">%s</p>', esc_html( home_url( '/' ) ), esc_attr( paulus_login_redirect_slug() ), esc_html__( 'Visitors who are not logged in and open the admin are sent here. 404 shows the page not found.', 'paulus' ) );
	}, 'permalink', 'paulus-login' );
} );
