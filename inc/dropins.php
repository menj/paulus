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
 * written by the Paulus theme, or by any sister theme whose files say
 * "Written by the ... theme; safe to delete", is replaced; switching themes
 * removes the theme's own.
 *
 * The static pages in errors/ assume a site at the domain root with the
 * theme in /wp-content/themes/paulus/. The theme also copies each into
 * wp-content/paulus-errors/ with this site's home address and theme folder,
 * so a site installed in a subfolder gets links that work. The .htaccess
 * lines on the Content tab point at the copies.
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

/** The folder in wp-content that holds this site's copies of the static pages. */
const PAULUS_STATIC_ERRORS = 'paulus-errors';

/**
 * The files written beside the drop-ins: the stylesheet the error pages use,
 * copied out of the theme so they keep their look while the theme folder is
 * being uploaded, and the maintenance page as static HTML, which the web
 * server and the theme's maintenance mode serve without running WordPress,
 * and a copy of each static page in errors/ with this site's addresses.
 *
 * @return array<string,string> Target file name, relative to wp-content => source file in the theme.
 */
function paulus_dropin_extras() {
	$files = array(
		'paulus-error-page.css'   => 'assets/css/error-page.css',
		'paulus-maintenance.html' => 'dropins/maintenance.php',
	);
	foreach ( array( '403', '500', '502', '503', '504' ) as $code ) {
		$files[ PAULUS_STATIC_ERRORS . '/' . $code . '.html' ] = 'errors/' . $code . '.html';
	}
	return $files;
}

/**
 * Whether a file in wp-content was written by this theme (or is a copy the
 * Abrahamic theme wrote and marked safe to delete).
 *
 * @param string $path File path.
 * @return bool
 */
function paulus_dropin_is_ours( $path ) {
	$head = is_readable( $path ) ? (string) file_get_contents( $path, false, null, 0, 300 ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions
	return false !== strpos( $head, 'Paulus error page drop-in' ) || false !== strpos( $head, 'Paulus: the standalone error pages' ) || false !== strpos( $head, 'Written by the Abrahamic theme' );
}

/**
 * Whether a file in wp-content may be written over at install time: the
 * theme's own, or a leftover of one of the owner's sister themes, which mark
 * every drop-in "Written by the ... theme; safe to delete". A file with no
 * such mark is never touched.
 *
 * @param string $path File path.
 * @return bool
 */
function paulus_dropin_is_replaceable( $path ) {
	if ( paulus_dropin_is_ours( $path ) ) {
		return true;
	}
	$head = is_readable( $path ) ? (string) file_get_contents( $path, false, null, 0, 400 ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions
	return (bool) preg_match( '/Written by the [A-Za-z0-9 .\'-]{1,40} theme; safe to delete/', $head );
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
	if ( 0 === strpos( $file, PAULUS_STATIC_ERRORS . '/' ) ) {
		// errors/ is built for a site at the root with the theme in
		// /wp-content/themes/paulus/; the copy carries this site's addresses.
		$tokens = paulus_dropin_tokens();
		return '<!-- Paulus error page drop-in ' . PAULUS_VERSION . ". Written by the Paulus theme; safe to delete. -->\n" . strtr(
			$text,
			array(
				'href="/wp-content/themes/paulus/' => 'href="' . $tokens['{{THEME_URL}}'] . '/',
				'href="/"'                         => 'href="' . $tokens['{{HOME_URL}}'] . '"',
			)
		);
	}
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
 * Whether this site writes the shared files in wp-content: any site off a
 * network, the main site of a network, or a subsite that runs the theme
 * while the main site runs another. Without the last case a network whose
 * main site had switched away kept the files of the old version for good,
 * with no site left to refresh them.
 *
 * @return bool
 */
function paulus_dropins_writer() {
	if ( ! is_multisite() || is_main_site() ) {
		return true;
	}
	$slug = basename( dirname( __DIR__ ) );
	$main = get_main_site_id();
	return ! in_array( $slug, array( get_blog_option( $main, 'stylesheet' ), get_blog_option( $main, 'template' ) ), true );
}

/**
 * Copy the drop-ins into wp-content, when the theme, site name or addresses
 * have changed since the last copy, or at once when $force is set (the
 * Install error pages button). Runs when an administrator loads an admin
 * page, never for a visitor: a write into wp-content belongs to the owner,
 * and two anonymous requests must not write the same file at once. On a
 * network the wp-content folder is shared, so the main site writes the
 * pages, with its own name and address, or a subsite that runs the theme
 * while the main site runs another (paulus_dropins_writer()).
 *
 * A failed attempt (wp-content not writable, or a source file unreadable)
 * is tried again at most once an hour, so a file that cannot be written does
 * not cost a rewrite on every admin page. The button always tries.
 *
 * @param bool $force Write even when nothing has changed.
 * @return array<string,string>|null Target file name => written, kept or
 *                                   failed; null when nothing was tried.
 */
function paulus_install_dropins( $force = false ) {
	if ( ! is_admin() || wp_doing_ajax() || ! current_user_can( 'edit_theme_options' ) || ! paulus_dropins_writer() ) {
		return null;
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
		return null;
	}
	if ( ! $force && get_transient( 'paulus_dropins_retry' ) ) {
		return null;
	}
	$result = array();
	foreach ( array_keys( $files ) as $file ) {
		$target = WP_CONTENT_DIR . '/' . $file;
		if ( ! is_dir( dirname( $target ) ) ) {
			wp_mkdir_p( dirname( $target ) );
		}
		if ( file_exists( $target ) && ! paulus_dropin_is_replaceable( $target ) ) {
			$result[ $file ] = 'kept';
			continue;
		}
		$text = paulus_dropin_text( $file );
		if ( null === $text ) {
			$result[ $file ] = 'failed';
			continue;
		}
		// Written to a temporary name first and moved into place, so a page
		// WordPress reads mid-write is never half a file.
		$tmp = $target . '.' . uniqid( 'paulus-', true ) . '.tmp';
		if ( strlen( $text ) !== @file_put_contents( $tmp, $text ) || ! @rename( $tmp, $target ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions, WordPress.PHP.NoSilencedErrors
			$result[ $file ] = 'failed';
			if ( file_exists( $tmp ) ) {
				unlink( $tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			}
			continue;
		}
		$result[ $file ] = 'written';
	}
	update_option( 'paulus_dropins_status', $result, false );
	if ( in_array( 'failed', $result, true ) ) {
		set_transient( 'paulus_dropins_retry', 1, HOUR_IN_SECONDS );
	} else {
		delete_transient( 'paulus_dropins_retry' );
		update_option( 'paulus_dropins_stamp', $stamp, false );
	}
	return $result;
}
add_action( 'init', 'paulus_install_dropins', 20 );

/**
 * Whether another site of the network still runs this theme, as its
 * stylesheet or its template. The drop-ins in the shared wp-content then
 * stay. Sites are read 500 at a time, so a large network is checked in
 * full without loading every site at once.
 *
 * @return bool
 */
function paulus_dropins_in_use_elsewhere() {
	if ( ! is_multisite() ) {
		return false;
	}
	$slug   = basename( dirname( __DIR__ ) );
	$offset = 0;
	do {
		$ids = get_sites(
			array(
				'fields'       => 'ids',
				'number'       => 500,
				'offset'       => $offset,
				'site__not_in' => array( get_current_blog_id() ),
			)
		);
		foreach ( $ids as $id ) {
			if ( in_array( $slug, array( get_blog_option( $id, 'stylesheet' ), get_blog_option( $id, 'template' ) ), true ) ) {
				return true;
			}
		}
		$offset += 500;
	} while ( 500 === count( $ids ) );
	return false;
}

/**
 * Remove the drop-ins when the theme is switched off: their stylesheet goes
 * with it.
 */
function paulus_remove_dropins() {
	// On a network the files are shared: they stay while any other site,
	// the main site included, still runs the theme. The last site to switch
	// away removes them, even a subsite.
	if ( paulus_dropins_in_use_elsewhere() ) {
		delete_option( 'paulus_dropins_stamp' );
		return;
	}
	foreach ( array_merge( paulus_dropin_files(), array_keys( paulus_dropin_extras() ) ) as $file ) {
		$target = WP_CONTENT_DIR . '/' . $file;
		if ( file_exists( $target ) && paulus_dropin_is_ours( $target ) ) {
			unlink( $target ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
	}
	// The folder of static copies goes too, but only when it is empty.
	@rmdir( WP_CONTENT_DIR . '/' . PAULUS_STATIC_ERRORS ); // phpcs:ignore WordPress.WP.AlternativeFunctions, WordPress.PHP.NoSilencedErrors
	delete_option( 'paulus_dropins_stamp' );
	delete_option( 'paulus_dropins_status' );
	delete_transient( 'paulus_dropins_retry' );
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
	$result = paulus_install_dropins( true );
	if ( null === $result ) {
		// A subsite: the network's pages are written from the main site.
		wp_safe_redirect( admin_url( 'themes.php?page=paulus-options&tab=content#paulus-error-pages' ) );
		exit;
	}
	$flag = in_array( 'failed', $result, true ) ? 'failed' : '1';
	wp_safe_redirect( admin_url( 'themes.php?page=paulus-options&tab=content&paulus_dropins=' . $flag . '#paulus-error-pages' ) );
	exit;
}
add_action( 'admin_post_paulus_install_dropins', 'paulus_handle_install_dropins' );

/**
 * The lines for .htaccess that point the web server at this site's copies
 * of the static pages, in wp-content/paulus-errors/.
 *
 * @return string
 */
function paulus_error_htaccess_snippet() {
	$base  = untrailingslashit( (string) wp_parse_url( content_url( '/' ), PHP_URL_PATH ) ) . '/' . PAULUS_STATIC_ERRORS . '/';
	$lines = array( '# Paulus: pages for the errors the web server answers before WordPress runs' );
	foreach ( array( 403, 500, 502, 503, 504 ) as $code ) {
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
		'failed'    => __( 'Could not be written (wp-content not writable or source file unreadable)', 'paulus' ),
	);
	$paulus_subsite = ! paulus_dropins_writer();
	$paulus_saved   = (array) get_option( 'paulus_dropins_status', array() );
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
	$paulus_flag = $paulus_subsite || ! isset( $_GET['paulus_dropins'] ) ? '' : sanitize_key( wp_unslash( $_GET['paulus_dropins'] ) );
	?>
	<div class="paulus-error-pages" id="paulus-error-pages">
		<h3><?php esc_html_e( 'Error pages', 'paulus' ); ?></h3>
		<?php if ( 'failed' === $paulus_flag ) : ?>
			<div class="notice notice-error inline"><p><?php esc_html_e( 'Some error pages could not be written: wp-content is not writable, or a source file in the theme is unreadable. The table shows which.', 'paulus' ); ?></p></div>
		<?php elseif ( '' !== $paulus_flag ) : ?>
			<div class="notice notice-success inline"><p><?php esc_html_e( 'The error pages were written again where the theme may write them.', 'paulus' ); ?></p></div>
		<?php endif; ?>
		<p><?php esc_html_e( 'WordPress shows these files from wp-content when it cannot run the theme: maintenance.php during an update (503), php-error.php after a fatal PHP error (500) and db-error.php when the database cannot be reached (503). The theme refreshes them after each update; the button writes them now. A file the theme did not write is left alone.', 'paulus' ); ?></p>
		<table class="widefat striped">
			<thead><tr><th scope="col"><?php esc_html_e( 'File', 'paulus' ); ?></th><th scope="col"><?php esc_html_e( 'Status', 'paulus' ); ?></th></tr></thead>
			<tbody>
			<?php foreach ( array_keys( paulus_dropin_sources() ) as $paulus_file ) : ?>
				<?php
				$paulus_state = paulus_dropin_state( $paulus_file );
				// A failed write leaves the old state on disk; the saved result says
				// why. A sister theme's file the theme may replace counts too.
				$paulus_left = 'foreign' === $paulus_state && ! paulus_dropin_is_replaceable( WP_CONTENT_DIR . '/' . $paulus_file );
				if ( 'installed' !== $paulus_state && ! $paulus_left && 'failed' === ( $paulus_saved[ $paulus_file ] ?? '' ) ) {
					$paulus_state = 'failed';
				}
				?>
				<tr><td><code>wp-content/<?php echo esc_html( $paulus_file ); ?></code></td><td><?php echo esc_html( $labels[ $paulus_state ] ); ?></td></tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php if ( $paulus_subsite ) : ?>
			<p class="description"><?php esc_html_e( 'The whole network shares wp-content, so the error pages are installed from the main site.', 'paulus' ); ?></p>
		<?php else : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="paulus_install_dropins">
				<?php wp_nonce_field( 'paulus_install_dropins' ); ?>
				<p><?php submit_button( __( 'Install error pages', 'paulus' ), 'secondary', 'submit', false ); ?></p>
			</form>
		<?php endif; ?>
		<p><?php esc_html_e( 'For the errors the web server answers before WordPress starts, the theme writes static pages into wp-content/paulus-errors, with this site\'s addresses. Add these lines to .htaccess to use them:', 'paulus' ); ?></p>
		<textarea readonly rows="6" class="large-text code" aria-label="<?php esc_attr_e( '.htaccess lines', 'paulus' ); ?>"><?php echo esc_textarea( paulus_error_htaccess_snippet() ); ?></textarea>
	</div>
	<?php
}
