<?php
/**
 * Dashboard widget: the state of the site after an update. It reads the
 * structure sync's own records, so it reports exactly what the sync will
 * and will not change.
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the widget for users who can change the theme.
 */
function paulus_dashboard_widget() {
	if ( current_user_can( 'edit_theme_options' ) ) {
		wp_add_dashboard_widget( 'paulus_status', get_bloginfo( 'name' ) . ': ' . __( 'site status', 'paulus' ), 'paulus_dashboard_render' );
	}
}
add_action( 'wp_dashboard_setup', 'paulus_dashboard_widget' );

/**
 * The shipped articles and pages whose content on this site differs from
 * the shipped file. The sync refreshes a page only while it still holds
 * text this theme shipped, so each of these is left alone.
 *
 * @return array<int, array{title:string, id:int}>
 */
function paulus_dashboard_edited() {
	$manifest = paulus_manifest();
	$edited   = array();
	$items    = array();
	foreach ( (array) ( $manifest['posts'] ?? array() ) as $item ) {
		$items[] = array( $item, 'post', $item['slug'] );
	}
	foreach ( (array) ( $manifest['pages'] ?? array() ) as $item ) {
		$path    = empty( $item['parent'] ) ? $item['slug'] : $item['parent'] . '/' . $item['slug'];
		$items[] = array( $item, 'page', $path );
	}
	$known = (array) get_option( 'paulus_content_hashes', array() );
	foreach ( $items as list( $item, $type, $path ) ) {
		if ( empty( $item['file'] ) ) {
			continue;
		}
		$post = get_page_by_path( $path, OBJECT, $type );
		if ( ! $post ) {
			continue;
		}
		$shipped = paulus_content_file( $item['file'] );
		if ( '' === $shipped || md5( $post->post_content ) === md5( $shipped ) ) {
			continue;
		}
		$entry = $known[ basename( $item['file'] ) ] ?? array();
		$base  = is_string( $entry ) ? $entry : ( $entry['content'] ?? '' );
		// Matching the recorded baseline means the page is simply awaiting
		// a refresh; anything else is a hand edit the sync will protect.
		if ( md5( $post->post_content ) !== $base ) {
			$edited[] = array( 'title' => get_the_title( $post ), 'id' => (int) $post->ID );
		}
	}
	return $edited;
}

/**
 * Render the widget.
 */
function paulus_dashboard_render() {
	$manifest = paulus_manifest();
	$shipped  = (string) ( $manifest['content_version'] ?? '' );
	$applied  = (string) get_option( 'paulus_content_version', '' );
	$edited   = paulus_dashboard_edited();
	$options  = admin_url( 'themes.php?page=paulus-options' );
	?>
	<table class="widefat striped" style="border:0">
		<tbody>
			<tr><th scope="row"><?php esc_html_e( 'Theme', 'paulus' ); ?></th><td>Paulus <?php echo esc_html( PAULUS_VERSION ); ?></td></tr>
			<tr><th scope="row"><?php esc_html_e( 'Content', 'paulus' ); ?></th><td>
				<?php
				if ( $applied === $shipped ) {
					/* translators: %s: content version. */
					echo esc_html( sprintf( __( 'Up to date (version %s).', 'paulus' ), $shipped ) );
				} else {
					/* translators: 1: version on the site, 2: version shipped. */
					echo esc_html( sprintf( __( 'Sync pending: the site holds %1$s, the theme ships %2$s. It runs on the next page load by an administrator.', 'paulus' ), $applied ? $applied : __( 'none', 'paulus' ), $shipped ) );
				}
				?>
			</td></tr>
			<tr><th scope="row"><?php esc_html_e( 'Journal', 'paulus' ); ?></th><td>
				<?php
				$entries = (int) ( wp_count_posts( 'paulus_journal' )->publish ?? 0 );
				/* translators: %d: number of published entries. */
				echo esc_html( sprintf( _n( '%d published entry.', '%d published entries.', $entries, 'paulus' ), $entries ) );
				?>
				<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=paulus_journal' ) ); ?>"><?php esc_html_e( 'Write an entry', 'paulus' ); ?></a>
			</td></tr>
			<?php
			$drafts = array();
			foreach ( (array) ( $manifest['pages'] ?? array() ) as $item ) {
				if ( 'draft' === ( $item['status'] ?? '' ) ) {
					$page = get_page_by_path( $item['slug'] );
					if ( $page && 'draft' === $page->post_status ) {
						$drafts[] = '<a href="' . esc_url( get_edit_post_link( $page->ID ) ) . '">' . esc_html( get_the_title( $page ) ) . '</a>';
					}
				}
			}
			if ( $drafts ) :
				?>
			<tr><th scope="row"><?php esc_html_e( 'To review', 'paulus' ); ?></th><td>
				<?php echo wp_kses_post( implode( ', ', $drafts ) ); ?><br>
				<?php esc_html_e( 'Drafted by the theme. Each appears in the footer bar once you review and publish it.', 'paulus' ); ?>
			</td></tr>
			<?php endif; ?>
			<?php if ( function_exists( 'paulus_login_slug' ) && '' !== paulus_login_slug() ) : ?>
			<tr><th scope="row"><?php esc_html_e( 'Login address', 'paulus' ); ?></th><td><code><?php echo esc_html( paulus_login_url() ); ?></code></td></tr>
			<?php endif; ?>
			<?php if ( function_exists( 'paulus_unlisted_ids' ) && paulus_unlisted_ids() ) : ?>
			<tr><th scope="row"><?php esc_html_e( 'Unlisted', 'paulus' ); ?></th><td>
				<?php
				$links = array();
				foreach ( paulus_unlisted_ids() as $uid ) {
					if ( get_post( $uid ) ) {
						$links[] = '<a href="' . esc_url( get_edit_post_link( $uid ) ) . '">' . esc_html( get_the_title( $uid ) ) . '</a>';
					}
				}
				echo wp_kses_post( implode( ', ', $links ) );
				?>
			</td></tr>
			<?php endif; ?>
			<?php
			$paulus_sync = get_option( 'paulus_sync_status' );
			$paulus_cv   = (string) get_option( 'paulus_content_version' );
			$paulus_tv   = (string) ( paulus_manifest()['content_version'] ?? '' );
			?>
			<tr><th scope="row"><?php esc_html_e( 'Content sync', 'paulus' ); ?></th><td>
				<?php
				if ( $paulus_cv === $paulus_tv ) {
					/* translators: %s: content version. */
					echo esc_html( sprintf( __( 'Up to date (content %s).', 'paulus' ), $paulus_tv ) );
				} elseif ( is_array( $paulus_sync ) && 'pending' === $paulus_sync['state'] ) {
					$paulus_left = 0;
					$paulus_map  = get_option( 'paulus_attachments', array() );
					foreach ( array_merge( array_keys( paulus_images() ), array( 'book-cover' ) ) as $paulus_slug ) {
						$paulus_id = (int) ( $paulus_map[ $paulus_slug ] ?? 0 );
						if ( ! $paulus_id || ! preg_match( '/\.avif$/i', (string) get_attached_file( $paulus_id ) ) ) {
							++$paulus_left;
						}
					}
					/* translators: %d: images still to import or convert. */
					echo esc_html( sprintf( _n( 'In progress: %d image still to import or convert. Each admin page you open continues it.', 'In progress: %d images still to import or convert. Each admin page you open continues it.', $paulus_left, 'paulus' ), $paulus_left ) );
				} elseif ( is_array( $paulus_sync ) && 'failed' === $paulus_sync['state'] ) {
					echo '<strong>' . esc_html__( 'Stopped.', 'paulus' ) . '</strong> ' . esc_html( sprintf( /* translators: 1: stage, 2: time, 3: message. */ __( 'At %1$s, %2$s: %3$s', 'paulus' ), $paulus_sync['stage'], wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $paulus_sync['time'] ), $paulus_sync['message'] ) );
				} else {
					/* translators: 1: installed content version, 2: this theme's. */
					echo esc_html( sprintf( __( 'Waiting: content %1$s installed, %2$s to come. It runs on the next page you open while logged in.', 'paulus' ), '' !== $paulus_cv ? $paulus_cv : '–', $paulus_tv ) );
				}
				?>
			</td></tr>
			<?php $paulus_hk = get_option( 'paulus_housekeeping' ); ?>
			<tr><th scope="row"><?php esc_html_e( 'Image housekeeping', 'paulus' ); ?></th><td>
				<?php
				if ( is_array( $paulus_hk ) && ! empty( $paulus_hk['time'] ) ) {
					/* translators: 1: date and time, 2: files removed then, 3: files removed in all. */
					echo esc_html( sprintf( __( 'Last run %1$s: %2$d unused files removed (%3$d in all).', 'paulus' ), wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $paulus_hk['time'] ), (int) $paulus_hk['uploads'] + (int) $paulus_hk['theme'], (int) $paulus_hk['total'] ) );
				} else {
					esc_html_e( 'Not yet run.', 'paulus' );
				}
				$paulus_next = wp_next_scheduled( 'paulus_image_housekeeping' );
				if ( $paulus_next ) {
					/* translators: %s: date and time. */
					echo ' ' . esc_html( sprintf( __( 'Next run %s.', 'paulus' ), wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $paulus_next ) ) );
				}
				?>
			</td></tr>
			<tr><th scope="row"><?php esc_html_e( 'Articles and pages', 'paulus' ); ?></th><td>
				<?php
				/* translators: 1: number of articles, 2: number of pages. */
				echo esc_html( sprintf( __( '%1$d articles, %2$d pages shipped with the theme.', 'paulus' ), count( (array) ( $manifest['posts'] ?? array() ) ), count( (array) ( $manifest['pages'] ?? array() ) ) ) );
				?>
			</td></tr>
		</tbody>
	</table>
	<h3 style="margin:1em 0 .4em"><?php esc_html_e( 'Edited by hand', 'paulus' ); ?></h3>
	<?php if ( $edited ) : ?>
		<p><?php esc_html_e( 'These differ from the shipped text, so updates leave them as they are:', 'paulus' ); ?></p>
		<ul style="margin:0 0 1em 1.2em;list-style:disc">
			<?php foreach ( $edited as $e ) : ?>
				<li><a href="<?php echo esc_url( get_edit_post_link( $e['id'] ) ); ?>"><?php echo esc_html( $e['title'] ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<p><?php esc_html_e( 'None. Every shipped article and page follows theme updates.', 'paulus' ); ?></p>
	<?php endif; ?>
	<p>
		<a class="button" href="<?php echo esc_url( $options ); ?>"><?php esc_html_e( 'Theme Options', 'paulus' ); ?></a>
		<a class="button" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View site', 'paulus' ); ?></a>
	</p>
	<?php
}

/**
 * While one of the three plugins now built into the theme is still active,
 * say so: the theme's version stands by, with the same settings, until the
 * plugin is deactivated.
 */
add_action( 'admin_notices', static function () {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	$active = array();
	if ( class_exists( 'Unlist_Posts' ) ) {
		$active[] = 'Unlist Posts &amp; Pages';
	}
	if ( function_exists( 'wpseosearch_base' ) ) {
		$active[] = 'Pretty Search Permalinks';
	}
	if ( defined( 'WPS_HIDE_LOGIN_BASENAME' ) ) {
		$active[] = 'WPS Hide Login';
	}
	if ( $active ) {
		echo '<div class="notice notice-info"><p><strong>Paulus:</strong> ' . wp_kses_post( sprintf(
			/* translators: %s: plugin names. */
			__( 'these plugins are now built into the theme and use the same settings: %s. The theme\'s version waits until each plugin is deactivated. You can then deactivate and delete them.', 'paulus' ),
			implode( ', ', $active )
		) ) . ' <a href="' . esc_url( admin_url( 'plugins.php' ) ) . '">' . esc_html__( 'Plugins', 'paulus' ) . '</a></p></div>';
	}
} );
