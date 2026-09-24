<?php
/**
 * The Journal: dated entries (replies, notes on new sources, news of the
 * book), kept apart from the case articles so they never enter the reading
 * order, the counts or the sections.
 *
 * Addresses carry the date: an entry at /journal/2026/09/slug/, the Journal
 * at /journal/, a year at /journal/2026/ and a month at /journal/2026/09/.
 * The year and month views are Journal archives filtered by date through
 * the theme's own query variables, so WordPress does not treat them as date
 * archives, and an SEO plugin that switches date archives off (Rank Math
 * sends them to the front page) leaves them alone.
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the entry type and its date tags.
 */
function paulus_journal_register() {
	add_rewrite_tag( '%journal_year%', '([0-9]{4})', 'journal_year=' );
	add_rewrite_tag( '%journal_monthnum%', '([0-9]{2})', 'journal_monthnum=' );
	register_post_type(
		'paulus_journal',
		array(
			'labels'       => array(
				'name'               => __( 'Journal', 'paulus' ),
				'singular_name'      => __( 'Journal entry', 'paulus' ),
				'add_new_item'       => __( 'Add journal entry', 'paulus' ),
				'edit_item'          => __( 'Edit journal entry', 'paulus' ),
				'new_item'           => __( 'New journal entry', 'paulus' ),
				'view_item'          => __( 'View journal entry', 'paulus' ),
				'all_items'          => __( 'All entries', 'paulus' ),
				'search_items'       => __( 'Search the Journal', 'paulus' ),
				'not_found'          => __( 'No entries found.', 'paulus' ),
				'not_found_in_trash' => __( 'No entries in the bin.', 'paulus' ),
				'archives'           => __( 'Journal', 'paulus' ),
				'menu_name'          => __( 'Journal', 'paulus' ),
			),
			'public'       => true,
			'show_in_rest' => true,
			'menu_icon'    => 'dashicons-edit-page',
			'menu_position' => 6,
			'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions' ),
			'has_archive'  => 'journal',
			'rewrite'      => array(
				'slug'       => 'journal/%journal_year%/%journal_monthnum%',
				'with_front' => false,
				'feeds'      => true,
				'walk_dirs'  => false,
			),
		)
	);
	// Year and month views of the Journal, above WordPress's own rules.
	add_rewrite_rule( '^journal/([0-9]{4})/([0-9]{2})/page/([0-9]+)/?$', 'index.php?post_type=paulus_journal&journal_year=$matches[1]&journal_monthnum=$matches[2]&paged=$matches[3]', 'top' );
	add_rewrite_rule( '^journal/([0-9]{4})/([0-9]{2})/?$', 'index.php?post_type=paulus_journal&journal_year=$matches[1]&journal_monthnum=$matches[2]', 'top' );
	add_rewrite_rule( '^journal/([0-9]{4})/page/([0-9]+)/?$', 'index.php?post_type=paulus_journal&journal_year=$matches[1]&paged=$matches[2]', 'top' );
	add_rewrite_rule( '^journal/([0-9]{4})/?$', 'index.php?post_type=paulus_journal&journal_year=$matches[1]', 'top' );
}
add_action( 'init', 'paulus_journal_register' );

/**
 * Refresh the address rules once after each theme update, so the Journal's
 * addresses work without a visit to Settings, Permalinks.
 */
function paulus_journal_flush() {
	if ( get_option( 'paulus_journal_rules' ) !== PAULUS_VERSION ) {
		flush_rewrite_rules( false );
		update_option( 'paulus_journal_rules', PAULUS_VERSION, false );
	}
}
add_action( 'init', 'paulus_journal_flush', 99 );

/**
 * An entry's address takes the year and month of its publication date.
 *
 * @param string  $link Address with tags.
 * @param WP_Post $post Entry.
 * @return string
 */
function paulus_journal_link( $link, $post ) {
	if ( 'paulus_journal' !== $post->post_type || false === strpos( $link, '%journal_' ) ) {
		return $link;
	}
	$time = strtotime( $post->post_date ?: current_time( 'mysql' ) );
	return str_replace( array( '%journal_year%', '%journal_monthnum%' ), array( gmdate( 'Y', $time ), gmdate( 'm', $time ) ), $link );
}
add_filter( 'post_type_link', 'paulus_journal_link', 10, 2 );

/**
 * The Journal's year and month views: filter the archive by date, ten
 * entries to a page.
 *
 * @param WP_Query $q Query.
 */
function paulus_journal_query( $q ) {
	if ( is_admin() || ! $q->is_main_query() || ! $q->is_post_type_archive( 'paulus_journal' ) ) {
		return;
	}
	$q->set( 'posts_per_page', 10 );
	$year  = (int) $q->get( 'journal_year' );
	$month = (int) $q->get( 'journal_monthnum' );
	if ( $year ) {
		$date = array( 'year' => $year );
		if ( $month >= 1 && $month <= 12 ) {
			$date['month'] = $month;
		}
		$q->set( 'date_query', array( $date ) );
	}
}
add_action( 'pre_get_posts', 'paulus_journal_query' );

/**
 * The period shown by the current Journal view, as a label: "September
 * 2026", "2026", or an empty string for the whole Journal.
 *
 * @return string
 */
function paulus_journal_period() {
	$year  = (int) get_query_var( 'journal_year' );
	$month = (int) get_query_var( 'journal_monthnum' );
	if ( ! $year ) {
		return '';
	}
	return $month ? date_i18n( 'F Y', mktime( 0, 0, 0, $month, 1, $year ) ) : (string) $year;
}

/**
 * The address of a Journal view.
 *
 * @param int $year  Year, or 0 for the whole Journal.
 * @param int $month Month, or 0.
 * @return string
 */
function paulus_journal_url( $year = 0, $month = 0 ) {
	$base = (string) get_post_type_archive_link( 'paulus_journal' );
	if ( ! $year ) {
		return $base;
	}
	return trailingslashit( $base ) . $year . '/' . ( $month ? sprintf( '%02d', $month ) . '/' : '' );
}

/**
 * [paulus_journal_list] The entries of the current Journal view: date,
 * title, excerpt and a link, with pagination, an index of the months that
 * have entries, and the feed.
 *
 * @return string
 */
function paulus_sc_journal_list() {
	global $wpdb;
	$out = '';
	if ( have_posts() ) {
		$out .= '<ol class="paulus-journal__list">';
		while ( have_posts() ) {
			the_post();
			$out .= '<li class="paulus-journal__item"><article>'
				. '<p class="paulus-journal__date"><time datetime="' . esc_attr( get_the_date( 'c' ) ) . '">' . esc_html( get_the_date( 'j F Y' ) ) . '</time></p>'
				. '<h2 class="paulus-journal__title"><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h2>'
				. ( has_excerpt() || get_the_content() ? '<p class="paulus-journal__excerpt">' . esc_html( wp_strip_all_tags( get_the_excerpt() ) ) . '</p>' : '' )
				. '<p class="paulus-journal__more"><a href="' . esc_url( get_permalink() ) . '">' . esc_html__( 'Read the entry', 'paulus' ) . ' <span aria-hidden="true">→</span></a></p>'
				. '</article></li>';
		}
		$out .= '</ol>';
		wp_reset_postdata();
		$pages = paginate_links( array( 'type' => 'list', 'prev_text' => __( 'Newer', 'paulus' ), 'next_text' => __( 'Older', 'paulus' ) ) );
		if ( $pages ) {
			$out .= '<nav class="paulus-journal__pages" aria-label="' . esc_attr__( 'Journal pages', 'paulus' ) . '">' . $pages . '</nav>';
		}
	} else {
		$out .= '<p class="paulus-journal__empty">' . esc_html( paulus_journal_period() ? __( 'No entries for this period.', 'paulus' ) : __( 'The first entries are on their way.', 'paulus' ) ) . '</p>';
	}
	// The months that have entries, newest first.
	$months = $wpdb->get_results( $wpdb->prepare( "SELECT YEAR(post_date) AS y, MONTH(post_date) AS m, COUNT(*) AS n FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish' GROUP BY y, m ORDER BY y DESC, m DESC", 'paulus_journal' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	if ( $months ) {
		$years = array();
		foreach ( $months as $row ) {
			$years[ (int) $row->y ][] = $row;
		}
		$out .= '<nav class="paulus-journal__archive" aria-label="' . esc_attr__( 'Journal archive', 'paulus' ) . '"><p class="paulus-journal__archive-label">' . esc_html__( 'Archive', 'paulus' ) . '</p><ul>';
		foreach ( $years as $y => $rows ) {
			$out .= '<li><a class="paulus-journal__year" href="' . esc_url( paulus_journal_url( $y ) ) . '">' . esc_html( $y ) . '</a> ';
			$links = array();
			foreach ( $rows as $row ) {
				$links[] = '<a href="' . esc_url( paulus_journal_url( $y, (int) $row->m ) ) . '">' . esc_html( date_i18n( 'F', mktime( 0, 0, 0, (int) $row->m, 1, $y ) ) ) . '</a> <span class="paulus-journal__count">(' . esc_html( number_format_i18n( (int) $row->n ) ) . ')</span>';
			}
			$out .= implode( ' <span aria-hidden="true">·</span> ', $links ) . '</li>';
		}
		$out .= '</ul></nav>';
	}
	$out .= '<p class="paulus-journal__feed"><a href="' . esc_url( get_post_type_archive_feed_link( 'paulus_journal' ) ) . '">' . esc_html__( 'Follow the Journal by RSS', 'paulus' ) . '</a></p>';
	return '<div class="paulus-journal">' . $out . '</div>';
}
add_shortcode( 'paulus_journal_list', 'paulus_sc_journal_list' );

/**
 * [paulus_journal_nav] Under an entry: the newer and older entries, which
 * the arrow keys also follow, and the way back to the Journal.
 *
 * @return string
 */
function paulus_sc_journal_nav() {
	if ( ! is_singular( 'paulus_journal' ) ) {
		return '';
	}
	$older = get_previous_post();
	$newer = get_next_post();
	// The same band as the reading order under an article.
	$out  = '<nav class="paulus-readnav paulus-journal__nav" aria-label="' . esc_attr__( 'Journal', 'paulus' ) . '"><div class="paulus-readnav__inner">';
	$out .= '<p class="paulus-journal__back"><a href="' . esc_url( paulus_journal_url() ) . '">' . esc_html__( 'All journal entries', 'paulus' ) . '</a></p><div class="paulus-readnav__links">';
	if ( $older ) {
		$out .= '<a class="paulus-readnav__prev" rel="prev" href="' . esc_url( get_permalink( $older ) ) . '"><span>' . esc_html__( 'Older entry', 'paulus' ) . '</span>' . esc_html( get_the_title( $older ) ) . '</a>';
	}
	if ( $newer ) {
		$out .= '<a class="paulus-readnav__next" rel="next" href="' . esc_url( get_permalink( $newer ) ) . '"><span>' . esc_html__( 'Newer entry', 'paulus' ) . '</span>' . esc_html( get_the_title( $newer ) ) . '</a>';
	}
	return $out . '</div></div></nav>';
}
add_shortcode( 'paulus_journal_nav', 'paulus_sc_journal_nav' );

/**
 * [paulus_journal_canton] The Journal's own block in the header, set apart
 * from the menu: an outlined companion to the book button, with a quill and
 * the word Journal, and a dot while the latest entry is under a fortnight
 * old. On phones it becomes a round quill button beside the search. It
 * appears once the Journal has a published entry.
 *
 * @return string
 */
function paulus_sc_journal_canton() {
	if ( ! post_type_exists( 'paulus_journal' ) ) {
		return '';
	}
	$latest = get_posts( array( 'post_type' => 'paulus_journal', 'post_status' => 'publish', 'numberposts' => 1, 'orderby' => 'date', 'order' => 'DESC' ) );
	if ( ! $latest ) {
		return '';
	}
	$name    = (string) paulus_option( 'journal_title' );
	$fresh   = ( time() - (int) get_post_time( 'U', true, $latest[0] ) ) < 14 * DAY_IN_SECONDS;
	$current = is_post_type_archive( 'paulus_journal' ) || is_singular( 'paulus_journal' );
	/* translators: %s: date of the latest entry. */
	$title = sprintf( __( 'Latest entry: %s', 'paulus' ), get_the_date( 'j F Y', $latest[0] ) );
	$label = $fresh ? sprintf( /* translators: %s: Journal title. */ __( '%s, new entry', 'paulus' ), $name ) : $name;
	return '<a class="paulus-journal-canton' . ( $current ? ' is-current' : '' ) . '" href="' . esc_url( paulus_journal_url() ) . '" title="' . esc_attr( $title ) . '" aria-label="' . esc_attr( $label ) . '"' . ( $current ? ' aria-current="page"' : '' ) . '>'
		. paulus_icon( 'quill' )
		. '<span class="paulus-journal-canton__label">' . esc_html( $name ) . '</span>'
		. ( $fresh ? '<span class="paulus-journal-canton__new" aria-hidden="true"></span>' : '' )
		. '</a>';
}
add_shortcode( 'paulus_journal_canton', 'paulus_sc_journal_canton' );
