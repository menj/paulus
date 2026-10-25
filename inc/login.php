<?php
/**
 * The login page in the site's own dress: the parchment ground between
 * meander bands, a portrait medallion above the wordmark, a Greek
 * inscription over a card with the accent on its upper edge, and the site's
 * faces, fields and button, in whichever colour scheme the site uses. It
 * covers every screen wp-login.php draws (log in, lost password, reset,
 * messages and errors, and the small login shown when a session expires).
 * Set on Theme Options, Login.
 *
 * The logo follows Login Logo by Mark Jaquith (GPL v2 or later): a file
 * named login-logo.png in the wp-content folder takes the medallion's place,
 * unless a logo address is set on Theme Options, Login. While that plugin
 * is active, the theme leaves the logo to it.
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

/**
 * Application passwords are off unless switched on under Theme Options,
 * Login. With them off WordPress neither shows the section on the profile
 * screen nor accepts an existing application password at the REST API or
 * XML-RPC, so the only way in is the login form.
 *
 * @param bool $available Whether WordPress would offer them.
 * @return bool
 */
function paulus_application_passwords_available( $available ) {
	return paulus_option( 'login_app_passwords' ) ? $available : false;
}
add_filter( 'wp_is_application_passwords_available', 'paulus_application_passwords_available' );

/**
 * Security headers for the site's pages, unless switched off under Theme
 * Options, Login. Each one only narrows what a browser allows and none
 * limits the site's own scripts, so Google's tag and the plugins keep
 * working:
 *
 * - frame-ancestors 'self' and X-Frame-Options: no other site may show the
 *   pages in a frame (clickjacking); the site itself still may, as the
 *   Customizer does.
 * - Cross-Origin-Opener-Policy: a page opened from the site, or that opens
 *   it, gets no handle on its window; pop-ups such as a share dialog still
 *   open.
 * - Strict-Transport-Security, sent only over HTTPS: for a year the browser
 *   reaches the site only over HTTPS. Subdomains are left out.
 * - X-Content-Type-Options and Referrer-Policy: no guessing of file types,
 *   and only the site's address, never the page, sent to other sites.
 *
 * The list can be changed with the paulus_security_headers filter.
 */
function paulus_send_security_headers() {
	if ( ! paulus_option( 'security_headers' ) || headers_sent() || is_admin() ) {
		return;
	}
	$headers = array(
		'Content-Security-Policy'    => "frame-ancestors 'self'",
		'X-Frame-Options'            => 'SAMEORIGIN',
		'Cross-Origin-Opener-Policy' => 'same-origin-allow-popups',
		'X-Content-Type-Options'     => 'nosniff',
		'Referrer-Policy'            => 'strict-origin-when-cross-origin',
	);
	if ( is_ssl() ) {
		$headers['Strict-Transport-Security'] = 'max-age=31536000';
	}
	foreach ( (array) apply_filters( 'paulus_security_headers', $headers ) as $name => $value ) {
		header( $name . ': ' . $value );
	}
}
add_action( 'send_headers', 'paulus_send_security_headers' );

/**
 * The image above the login form: the address set on Theme Options, then
 * wp-content/login-logo.png, then the chosen portrait (a medallion).
 *
 * @return array{url:string,medallion:bool}
 */
function paulus_login_logo() {
	$url = esc_url_raw( (string) paulus_option( 'login_logo_url' ) );
	if ( '' !== $url ) {
		return array( 'url' => $url, 'medallion' => false );
	}
	if ( file_exists( WP_CONTENT_DIR . '/login-logo.png' ) ) {
		return array( 'url' => content_url( 'login-logo.png' ) . '?v=' . filemtime( WP_CONTENT_DIR . '/login-logo.png' ), 'medallion' => false );
	}
	$slug = (string) paulus_option( 'login_image' );
	return array( 'url' => paulus_image_url( '' !== $slug ? $slug : 'paul-portrait-face' ), 'medallion' => true );
}

/**
 * Styles: the site's faces and schemes, the theme.json colour presets
 * (the login page does not load the site's global styles), and the login
 * stylesheet, with the logo passed as a variable.
 */
add_action( 'login_enqueue_scripts', static function () {
	wp_enqueue_style( 'paulus-fonts', PAULUS_URI . '/assets/css/fonts.css', array(), PAULUS_VERSION );
	wp_enqueue_style( 'paulus-schemes', PAULUS_URI . '/assets/css/schemes.css', array( 'paulus-fonts' ), PAULUS_VERSION );
	wp_enqueue_style( 'paulus-login', PAULUS_URI . '/assets/css/login.css', array( 'login', 'paulus-schemes' ), PAULUS_VERSION );
	$presets = function_exists( 'wp_get_global_stylesheet' ) ? wp_get_global_stylesheet( array( 'variables' ) ) : '';
	$logo    = paulus_login_logo();
	$vars    = class_exists( 'CWS_Login_Logo_Plugin' ) ? '' : 'body.login{--paulus-login-logo:url("' . esc_url( $logo['url'] ) . '");}';
	wp_add_inline_style( 'paulus-login', $presets . $vars );
	// The LOG IN button takes the Greek swap (assets/js/login.js).
	$mark = paulus_greek_marks()['login'] ?? null;
	if ( $mark ) {
		wp_enqueue_script( 'paulus-login', PAULUS_URI . '/assets/js/login.js', array(), PAULUS_VERSION, true );
		wp_add_inline_script( 'paulus-login', 'window.paulusLogin = ' . wp_json_encode( array( 'greek' => $mark[0], 'title' => paulus_greek_title( 'login' ) ) ) . ';', 'before' );
	}
} );

add_filter( 'login_body_class', static function ( $classes ) {
	$classes[] = 'paulus-login';
	$classes[] = 'paulus-scheme-' . sanitize_html_class( paulus_option( 'scheme' ) );
	if ( paulus_option( 'ornament' ) ) {
		$classes[] = 'paulus-ornament';
	}
	if ( class_exists( 'CWS_Login_Logo_Plugin' ) ) {
		$classes[] = 'paulus-login--plugin-logo';
	} elseif ( ! paulus_login_logo()['medallion'] ) {
		$classes[] = 'paulus-login--logo';
	}
	return $classes;
} );

// The heading links home and names the site.
add_filter( 'login_headerurl', static function () {
	return home_url( '/' );
} );
add_filter( 'login_headertext', static function () {
	return esc_html( get_bloginfo( 'name' ) );
} );

/**
 * Under the name: the tagline, then the Greek inscription over the card.
 *
 * @param string $message WordPress's own message for the screen.
 * @return string
 */
function paulus_login_message( $message ) {
	$out     = '';
	$tagline = trim( (string) paulus_option( 'login_tagline' ) );
	if ( '' !== $tagline ) {
		$out .= '<p class="paulus-login__tagline">' . esc_html( $tagline ) . '</p>';
	}
	if ( paulus_option( 'login_greek' ) ) {
		$mark = paulus_greek_marks()['login'] ?? null;
		if ( $mark ) {
			$out .= '<p class="paulus-login__greek"><span lang="grc" title="' . esc_attr( $mark[1] ) . '">' . esc_html( $mark[0] ) . '</span></p>';
		}
	}
	return $out . $message;
}
add_filter( 'login_message', 'paulus_login_message' );

// The language menu under the form is not needed on an English site.
add_filter( 'login_display_language_dropdown', '__return_false' );
