<?php
/**
 * The error pages WordPress shows when it cannot run the theme: the
 * maintenance page (maintenance.php), the database error page (db-error.php)
 * and the PHP fatal error page (php-error.php). WordPress looks for them in
 * the wp-content folder, so the theme copies them there from its dropins
 * folder, filling in the site name, the home address and the address of the
 * theme's stylesheets. They hold no PHP beyond the status headers and need
 * nothing from the database, so they work when the site does not. Their
 * stylesheet is assets/css/error-page.css.
 *
 * A file in wp-content that the theme did not write is left alone. A copy
 * written by the Paulus theme, or by the Abrahamic theme (whose files say
 * they are safe to delete), is replaced; switching themes removes them.
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

/**
 * The drop-in file names.
 *
 * @return string[]
 */
function paulus_dropin_files() {
	return array( 'maintenance.php', 'db-error.php', 'php-error.php' );
}

/**
 * The files written beside the drop-ins: the stylesheet the error pages use,
 * copied out of the theme so they keep their look while the theme folder is
 * being uploaded, and the maintenance page as static HTML, which the web
 * server and the theme's maintenance mode serve without running WordPress.
 *
 * @return array<string,string> Target file name => source file in the theme.
 */
function paulus_dropin_extras() {
	return array(
		'paulus-error-page.css'   => 'assets/css/error-page.css',
		'paulus-maintenance.html' => 'dropins/maintenance.php',
	);
}

/**
 * Whether a file in wp-content was written by this theme (or is a copy the
 * Abrahamic theme wrote and marked safe to delete).
 *
 * @param string $path File path.
 * @return bool
 */
function paulus_dropin_is_ours( $path ) {
	$head = (string) file_get_contents( $path, false, null, 0, 300 );
	return false !== strpos( $head, 'Paulus error page drop-in' ) || false !== strpos( $head, 'Paulus: the standalone error pages' ) || false !== strpos( $head, 'Written by the Abrahamic theme' );
}

/**
 * The values filled into the templates.
 *
 * @return array<string,string> Token => value.
 */
function paulus_dropin_tokens() {
	$theme_url = (string) wp_parse_url( PAULUS_URI, PHP_URL_PATH );
	$home_url  = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	return array(
		'{{VERSION}}'     => PAULUS_VERSION,
		'{{SITE_NAME}}'   => esc_html( wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) ),
		'{{THEME_URL}}'   => esc_attr( $theme_url ),
		'{{HOME_URL}}'    => esc_attr( '' !== $home_url ? $home_url : '/' ),
		'{{CONTENT_URL}}' => esc_attr( untrailingslashit( (string) wp_parse_url( content_url( '/' ), PHP_URL_PATH ) ) ),
	);
}

/**
 * Every file the theme writes into wp-content, with its source in the theme.
 *
 * @return array<string,string> Target file name => source file in the theme.
 */
function paulus_dropin_sources() {
	$files = array();
	foreach ( paulus_dropin_files() as $file ) {
		$files[ $file ] = 'dropins/' . $file;
	}
	return $files + paulus_dropin_extras();
}

/**
 * The text the theme writes for one file, with the placeholders filled. Each
 * drop-in comes from its own source in dropins/.
 *
 * @param string $file Target file name.
 * @return string|null Null when the source is missing.
 */
function paulus_dropin_text( $file ) {
	$files  = paulus_dropin_sources();
	$source = isset( $files[ $file ] ) ? PAULUS_DIR . '/' . $files[ $file ] : '';
	if ( '' === $source || ! is_readable( $source ) ) {
		return null;
	}
	$text = strtr( (string) file_get_contents( $source ), paulus_dropin_tokens() ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( 'paulus-maintenance.html' === $file ) {
		// The static page is the maintenance drop-in without its PHP: the
		// status and headers come from whatever serves it.
		$text = '<!-- Paulus error page drop-in ' . PAULUS_VERSION . ". Written by the Paulus theme; safe to delete. -->\n" . ltrim( (string) preg_replace( '/^<\?php.*?\?>/s', '', $text ) );
	}
	return $text;
}

/**
 * The state of one file in wp-content: missing, installed (the theme's own,
 * current), outdated (the theme's own, from another version or with other
 * addresses) or foreign (another file, left alone).
 *
 * @param string $file Target file name.
 * @param string $dir  Directory, wp-content by default.
 * @return string
 */
function paulus_dropin_state( $file, $dir = '' ) {
	$path = rtrim( '' !== $dir ? $dir : WP_CONTENT_DIR, '/' ) . '/' . $file;
	if ( ! file_exists( $path ) ) {
		return 'missing';
	}
	if ( ! paulus_dropin_is_ours( $path ) ) {
		return 'foreign';
	}
	return (string) file_get_contents( $path ) === paulus_dropin_text( $file ) ? 'installed' : 'outdated'; // phpcs:ignore WordPress.WP.AlternativeFunctions
}

/**
 * Copy the drop-ins into wp-content, when the theme, site name or addresses
 * have changed since the last copy, or at once when $force is set (the
 * Install error pages button). Runs when an administrator loads an admin
 * page, never for a visitor: a write into wp-content belongs to the owner,
 * and two anonymous requests must not write the same file at once. On a
 * network the wp-content folder is shared, so only the main site writes the
 * pages, with its own name and address.
 *
 * @param bool $force Write even when nothing has changed.
 */
function paulus_install_dropins( $force = false ) {
	if ( ! is_admin() || wp_doing_ajax() || ! current_user_can( 'edit_theme_options' ) || ( is_multisite() && ! is_main_site() ) ) {
		return;
	}
	$stamp = md5( wp_json_encode( paulus_dropin_tokens() ) );
	$files = paulus_dropin_sources();
	// Written again when anything they carry has changed, and whenever one
	// of them has gone missing (deleted by hand, or never written because
	// wp-content was not writable at the time).
	$all_there = true;
	foreach ( array_keys( $files ) as $file ) {
		$all_there = $all_there && file_exists( WP_CONTENT_DIR . '/' . $file );
	}
	if ( ! $force && get_option( 'paulus_dropins_stamp' ) === $stamp && $all_there ) {
		return;
	}
	$done = true;
	foreach ( array_keys( $files ) as $file ) {
		$target = WP_CONTENT_DIR . '/' . $file;
		$text   = paulus_dropin_text( $file );
		if ( null === $text ) {
			// The source could not be read: not done, so the next admin page tries again.
			$done = false;
			continue;
		}
		if ( file_exists( $target ) && ! paulus_dropin_is_ours( $target ) ) {
			continue;
		}
		// Written to a temporary name first and moved into place, so a page
		// WordPress reads mid-write is never half a file.
		$tmp = $target . '.' . uniqid( 'paulus-', true ) . '.tmp';
		if ( false === @file_put_contents( $tmp, $text ) || ! @rename( $tmp, $target ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions, WordPress.PHP.NoSilencedErrors
			$done = false;
			if ( file_exists( $tmp ) ) {
				unlink( $tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			}
		}
	}
	// A failed write (wp-content not writable) is tried again on the next
	// admin page load rather than recorded as done.
	if ( $done ) {
		update_option( 'paulus_dropins_stamp', $stamp, false );
	}
}
add_action( 'init', 'paulus_install_dropins', 20 );

/**
 * Remove the drop-ins when the theme is switched off: their stylesheet goes
 * with it.
 */
function paulus_remove_dropins() {
	if ( is_multisite() && ! is_main_site() ) {
		delete_option( 'paulus_dropins_stamp' );
		return;
	}
	foreach ( array_merge( paulus_dropin_files(), array_keys( paulus_dropin_extras() ) ) as $file ) {
		$target = WP_CONTENT_DIR . '/' . $file;
		if ( file_exists( $target ) && paulus_dropin_is_ours( $target ) ) {
			unlink( $target ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
	}
	delete_option( 'paulus_dropins_stamp' );
}
add_action( 'switch_theme', 'paulus_remove_dropins' );

/**
 * "Install error pages" on the Content tab: nonce and edit_theme_options,
 * the capability the installer itself asks for.
 */
function paulus_handle_install_dropins() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to do that.', 'paulus' ), 403 );
	}
	check_admin_referer( 'paulus_install_dropins' );
	paulus_install_dropins( true );
	wp_safe_redirect( admin_url( 'themes.php?page=paulus-options&tab=content&paulus_dropins=1#paulus-error-pages' ) );
	exit;
}
add_action( 'admin_post_paulus_install_dropins', 'paulus_handle_install_dropins' );

/**
 * The lines for .htaccess that point the web server at the static pages in
 * errors/.
 *
 * @return string
 */
function paulus_error_htaccess_snippet() {
	$base  = untrailingslashit( (string) wp_parse_url( PAULUS_URI, PHP_URL_PATH ) ) . '/errors/';
	$lines = array( '# Paulus: pages for the errors the web server answers before WordPress runs' );
	foreach ( array( 403, 500, 502, 503 ) as $code ) {
		$lines[] = 'ErrorDocument ' . $code . ' ' . $base . $code . '.html';
	}
	return implode( "\n", $lines );
}

/**
 * The error pages card: each file's state, the button and the .htaccess
 * lines. Its own form, so it sits outside the options form.
 */
function paulus_render_dropins_status() {
	$labels = array(
		'missing'   => __( 'Not installed', 'paulus' ),
		'installed' => __( 'Installed, current', 'paulus' ),
		'outdated'  => __( 'Installed by the theme, outdated', 'paulus' ),
		'foreign'   => __( 'Another file is there; left alone', 'paulus' ),
	);
	?>
	<div class="paulus-error-pages" id="paulus-error-pages">
		<h3><?php esc_html_e( 'Error pages', 'paulus' ); ?></h3>
		<?php if ( isset( $_GET['paulus_dropins'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only. ?>
			<div class="notice notice-success inline"><p><?php esc_html_e( 'The error pages were written again where the theme may write them.', 'paulus' ); ?></p></div>
		<?php endif; ?>
		<p><?php esc_html_e( 'WordPress shows these files from wp-content when it cannot run the theme: maintenance.php during an update (503), php-error.php after a fatal PHP error (500) and db-error.php when the database cannot be reached (503). The theme refreshes them after each update; the button writes them now. A file the theme did not write is left alone.', 'paulus' ); ?></p>
		<table class="widefat striped">
			<thead><tr><th scope="col"><?php esc_html_e( 'File', 'paulus' ); ?></th><th scope="col"><?php esc_html_e( 'Status', 'paulus' ); ?></th></tr></thead>
			<tbody>
			<?php foreach ( array_keys( paulus_dropin_sources() ) as $paulus_file ) : ?>
				<tr><td><code>wp-content/<?php echo esc_html( $paulus_file ); ?></code></td><td><?php echo esc_html( $labels[ paulus_dropin_state( $paulus_file ) ] ); ?></td></tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="paulus_install_dropins">
			<?php wp_nonce_field( 'paulus_install_dropins' ); ?>
			<p><?php submit_button( __( 'Install error pages', 'paulus' ), 'secondary', 'submit', false ); ?></p>
		</form>
		<p><?php esc_html_e( 'For the errors the web server answers before WordPress starts, static pages are in the errors folder of the theme. Add these lines to .htaccess to use them:', 'paulus' ); ?></p>
		<textarea readonly rows="5" class="large-text code" aria-label="<?php esc_attr_e( '.htaccess lines', 'paulus' ); ?>"><?php echo esc_textarea( paulus_error_htaccess_snippet() ); ?></textarea>
	</div>
	<?php
}
