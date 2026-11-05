<?php
/**
 * Author enumeration, closed for visitors who are not logged in.
 *
 * Ported from the murtadd theme's inc/hardening.php. WordPress answers two
 * public questions that give away the site's login names: the REST API's
 * user routes (/wp-json/wp/v2/users and every route under it) list every
 * author with their slug, and ?author=N sends a visitor on to
 * /author/<login>/. With the Security tab's switch "Hide the user list"
 * on (the default), a visitor who is not logged in gets neither: the user
 * routes are not registered for that request, so they answer 404, and any
 * ?author= address goes, permanently, where the author archive already
 * goes (paulus_author_archive_redirect(), inc/seo.php): the About the
 * author page, else the front page. oEmbed answers name the book's author
 * and the site instead of the WordPress account.
 *
 * A logged-in user keeps both, so the block editor's author picker and the
 * users screen work as before. The byline on the site (Theme Options, Book,
 * Author) is unchanged.
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether the user list is hidden from this request.
 *
 * @return bool
 */
function paulus_hide_authors() {
	return (bool) paulus_option( 'sec_hide_authors' ) && ! is_user_logged_in();
}

/**
 * Drop the REST API's user routes for a visitor who is not logged in.
 *
 * @param array $endpoints Routes.
 * @return array
 */
function paulus_hardening_rest_endpoints( $endpoints ) {
	if ( ! paulus_hide_authors() ) {
		return $endpoints;
	}
	foreach ( array_keys( (array) $endpoints ) as $route ) {
		if ( 0 === strpos( (string) $route, '/wp/v2/users' ) ) {
			unset( $endpoints[ $route ] );
		}
	}
	return $endpoints;
}
add_filter( 'rest_endpoints', 'paulus_hardening_rest_endpoints' );

/**
 * Send any ?author= address on, for a visitor who is not logged in, before
 * redirect_canonical (priority 10) can name the login in /author/<login>/,
 * and also when no such user exists, so the answer is the same for every N.
 *
 * @return void
 */
function paulus_hardening_author_query() {
	if ( ! paulus_hide_authors() || is_admin() || ! isset( $_GET['author'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read only to refuse it.
		return;
	}
	$about = get_page_by_path( 'about-the-author' );
	wp_safe_redirect( $about && 'publish' === $about->post_status ? get_permalink( $about ) : home_url( '/' ), 301 );
	exit;
}
add_action( 'template_redirect', 'paulus_hardening_author_query', 0 );

/**
 * oEmbed answers name the book's author and link the site, never the account.
 *
 * @param array $data oEmbed data.
 * @return array
 */
function paulus_hardening_oembed( $data ) {
	if ( ! paulus_option( 'sec_hide_authors' ) || ! is_array( $data ) ) {
		return $data;
	}
	$name                = (string) paulus_option( 'book_author' );
	$data['author_name'] = '' !== $name ? $name : (string) get_bloginfo( 'name' );
	$data['author_url']  = home_url( '/' );
	return $data;
}
add_filter( 'oembed_response_data', 'paulus_hardening_oembed' );
