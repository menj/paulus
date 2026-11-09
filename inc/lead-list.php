<?php
/**
 * Lead and list, for FEATURED entries only: the newest featured entry large
 * on the left, the next three as a stacked list on the right, above the
 * Journal's ordinary list, which still holds every entry. An entry is
 * featured by the "Featured" box on its edit screen (post meta
 * _paulus_featured). [paulus_journal_list] calls paulus_lead_list() with the
 * featured entries; with fewer than four, or a lead without a featured
 * image, it draws nothing and the Journal shows as the plain list.
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

/** How many entries the list beside the lead holds. */
const PAULUS_LEAD_LIST_MAX = 3;

/** The fewest entries the layout needs, the lead included. */
const PAULUS_LEAD_LIST_MIN = 4;

/**
 * Credit line for a post's featured image. A registered figure (someone
 * else's work, content/images.json "figures") takes its own credit, as
 * [paulus_figure] prints it; one of the theme's own illustrations is
 * credited to the book's author; any other image gives its caption.
 *
 * @param int $post_id Post ID.
 * @return string HTML, or '' when there is nothing to show.
 */
function paulus_lead_list_credit( $post_id ) {
	$thumb = (int) get_post_thumbnail_id( $post_id );
	if ( ! $thumb ) {
		return '';
	}
	$file = (string) get_attached_file( $thumb );
	$key  = preg_replace( '/(-[0-9a-f]{8})?(-\d+x\d+|-scaled)?\.(avif|jpe?g|png|webp)$/i', '', basename( $file ) );
	$figs = function_exists( 'paulus_figures' ) ? paulus_figures() : array();
	$own  = function_exists( 'paulus_images' ) ? paulus_images() : array();
	// WordPress adds -1, -2 ... when a file name is taken; "paul-halo-2023"
	// shows why the exact name is tried first.
	if ( '' !== $key && ! isset( $figs[ $key ] ) && ! array_key_exists( $key, $own ) ) {
		$key = preg_replace( '/-\d{1,2}$/', '', $key );
	}
	if ( '' !== $key && isset( $figs[ $key ] ) && function_exists( 'paulus_figure_credit' ) ) {
		return paulus_figure_credit( $figs[ $key ] );
	}
	$name = function_exists( 'paulus_option' ) ? trim( (string) paulus_option( 'book_author' ) ) : '';
	if ( '' !== $key && '' !== $name && array_key_exists( $key, $own ) ) {
		/* translators: %s: the book's author. */
		return esc_html( sprintf( __( 'Illustration: %s.', 'paulus' ), $name ) );
	}
	$caption = wp_get_attachment_caption( $thumb );
	return $caption ? esc_html( $caption ) : '';
}

/**
 * Draw the lead and list.
 *
 * @param WP_Post[] $posts Entries, newest first.
 * @param array     $args  {
 *     @type string $heading    Section heading.
 *     @type string $heading_id ID of the heading, for aria-labelledby.
 *     @type int    $lead_id    Entry to lead with; the newest leads when it is 0 or not usable.
 *     @type int    $max        Entries in the list (at most three).
 * }
 * @return array { html: string, ids: int[] } html is '' when the layout does not apply.
 */
function paulus_lead_list( array $posts, array $args = array() ) {
	$args  = wp_parse_args(
		$args,
		array(
			'heading'    => __( 'Featured', 'paulus' ),
			'heading_id' => 'paulus-lead-list-heading',
			'lead_id'    => 0,
			'max'        => PAULUS_LEAD_LIST_MAX,
		)
	);
	$empty = array(
		'html' => '',
		'ids'  => array(),
	);

	$usable = static function ( $p ) {
		return $p instanceof WP_Post && 'publish' === $p->post_status && ! ( function_exists( 'paulus_is_unlisted' ) && paulus_is_unlisted( $p->ID ) );
	};
	$posts  = array_values( array_filter( array_map( 'get_post', $posts ), $usable ) );

	$lead    = null;
	$lead_id = (int) $args['lead_id'];
	if ( $lead_id ) {
		foreach ( $posts as $i => $p ) {
			if ( $p->ID === $lead_id ) {
				$lead = $p;
				unset( $posts[ $i ] );
				break;
			}
		}
		if ( ! $lead && $usable( get_post( $lead_id ) ) ) {
			$lead = get_post( $lead_id );
		}
	}
	if ( ! $lead ) {
		$lead = array_shift( $posts );
	}
	$list = array_slice( array_values( $posts ), 0, max( 1, min( PAULUS_LEAD_LIST_MAX, (int) $args['max'] ) ) );

	if ( ! $lead || 1 + count( $list ) < PAULUS_LEAD_LIST_MIN || ! has_post_thumbnail( $lead ) ) {
		return $empty;
	}

	// One panel per entry: the lead shows first, and hovering or focusing an
	// entry in the list brings its panel forward (assets/js/lead-list.js).
	$set   = paulus_lead_list_settings();
	$panel = static function ( $post, $first ) use ( $set ) {
		$img = get_the_post_thumbnail(
			$post,
			'large',
			array(
				'class'         => 'paulus-lead-list__img',
				'loading'       => $first ? 'eager' : 'lazy',
				'fetchpriority' => $first ? 'high' : 'low',
				'sizes'         => '(max-width: 781px) 100vw, 700px',
			)
		);
		if ( '' === $img ) {
			return '';
		}
		$credit = paulus_lead_list_credit( $post->ID );
		$h      = '<article class="paulus-lead-list__lead" data-lead="' . (int) $post->ID . '"' . ( $first ? '' : ' hidden' ) . '><figure class="paulus-lead-list__figure">';
		$h     .= '<div class="paulus-lead-list__media">' . $img;
		$h     .= '<div class="paulus-lead-list__overlay"><span class="paulus-lead-list__rule" aria-hidden="true"></span>';
		$h     .= $set['tabs'] ? paulus_lead_list_tab( $post ) : '';
		$h     .= '<h3 class="paulus-lead-list__title"><a class="paulus-lead-list__link" href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( get_the_title( $post ) ) . '</a></h3>';
		$h     .= paulus_lead_list_meta( $post );
		$h     .= '<div class="paulus-lead-list__read" aria-hidden="true">' . esc_html__( 'Read', 'paulus' ) . ' <span class="paulus-lead-list__arrow">&rarr;</span></div>';
		$h     .= '</div></div>';
		if ( '' !== $credit ) {
			$h .= '<figcaption class="paulus-lead-list__credit">' . $credit . '</figcaption>';
		}
		return $h . '</figure></article>';
	};
	$lead_panel = $panel( $lead, true );
	if ( '' === $lead_panel ) {
		return $empty;
	}
	$heading_id = sanitize_html_class( $args['heading_id'] );

	$classes = 'paulus-lead-list alignwide' . ( $set['motion'] ? '' : ' paulus-lead-list--calm' );
	$style   = '' !== $set['accent'] ? ' style="--paulus-ll-accent:' . esc_attr( $set['accent'] ) . '"' : '';

	$html  = '<section class="' . esc_attr( $classes ) . '" aria-labelledby="' . esc_attr( $heading_id ) . '"' . $style . '>';
	$html .= '<h2 class="paulus-lead-list__heading" id="' . esc_attr( $heading_id ) . '">' . esc_html( $args['heading'] ) . '</h2>';
	$html .= '<div class="paulus-lead-list__grid">';
	$html .= '<div class="paulus-lead-list__stage">' . $lead_panel;
	$swap  = array();
	foreach ( $list as $item ) {
		$extra = $panel( $item, false );
		if ( '' !== $extra ) {
			$html .= $extra;
			$swap[ $item->ID ] = true;
		}
	}
	$html .= '</div>';

	$html .= '<ul class="paulus-lead-list__list">';
	foreach ( $list as $n => $item ) {
		$thumb = has_post_thumbnail( $item ) ? get_the_post_thumbnail(
			$item,
			'medium',
			array(
				'class'   => 'paulus-lead-list__thumb-img',
				'alt'     => '',
				'loading' => 'lazy',
				'sizes'   => '(max-width: 781px) 40vw, 12rem',
			)
		) : '';
		$html .= '<li class="paulus-lead-list__item' . ( 0 === $n ? ' is-current' : '' ) . '" style="--paulus-ll-i:' . (int) $n . '"' . ( isset( $swap[ $item->ID ] ) ? ' data-lead="' . (int) $item->ID . '"' : '' ) . '>';
		$html .= '<span class="paulus-lead-list__thumb' . ( '' === $thumb ? ' paulus-lead-list__thumb--empty' : '' ) . '" aria-hidden="true">' . $thumb . '</span>';
		$html .= '<div class="paulus-lead-list__text">';
		$html .= $set['tabs'] ? paulus_lead_list_tab( $item ) : '';
		$html .= '<h3 class="paulus-lead-list__item-title"><a class="paulus-lead-list__link" href="' . esc_url( get_permalink( $item ) ) . '">' . esc_html( get_the_title( $item ) ) . '</a></h3>';
		$html .= paulus_lead_list_meta( $item );
		$html .= '</div>';
		$html .= '<div class="paulus-lead-list__arrow" aria-hidden="true">&rarr;</div>';
		$html .= '</li>';
	}
	$html .= '</ul></div></section>';


	return array(
		'html' => $html,
		'ids'  => array_merge( array( $lead->ID ), wp_list_pluck( $list, 'ID' ) ),
	);
}

/**
 * The lead and list settings: the accent colour override, the category tabs
 * and the arrows and motion, saved on the Journal tab. Each has a filter. The accent colours the bar, the tint, the arrows and the underline; the hovered title keeps the scheme's own text colour.
 *
 * @return array { accent: string, tabs: bool, motion: bool }
 */
function paulus_lead_list_settings() {
	$accent = function_exists( 'paulus_option' ) ? (string) paulus_option( 'll_accent' ) : '';
	$tabs   = function_exists( 'paulus_option' ) ? paulus_option( 'll_tabs' ) : 1;
	$motion = function_exists( 'paulus_option' ) ? paulus_option( 'll_motion' ) : 1;
	$accent = (string) apply_filters( 'paulus_lead_list_accent', $accent );

	return array(
		'accent' => preg_match( '/^#[0-9a-f]{6}$/i', $accent ) ? strtolower( $accent ) : '',
		'tabs'   => (bool) apply_filters( 'paulus_lead_list_show_tabs', ! empty( $tabs ) ),
		'motion' => (bool) apply_filters( 'paulus_lead_list_motion', ! empty( $motion ) ),
	);
}

/**
 * Relative luminance of a hex colour.
 *
 * @param string $hex Colour, #rrggbb.
 * @return float
 */
function paulus_lead_list_luminance( $hex ) {
	$hex = ltrim( $hex, '#' );
	$out = 0.0;
	foreach ( array( array( 0.2126, 0 ), array( 0.7152, 2 ), array( 0.0722, 4 ) ) as list( $weight, $at ) ) {
		$c    = hexdec( substr( $hex, $at, 2 ) ) / 255;
		$out += $weight * ( $c <= 0.03928 ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 ) );
	}
	return $out;
}

/**
 * Mix two hex colours, the first at $share percent.
 *
 * @param string $a     First colour.
 * @param string $b     Second colour.
 * @param int    $share Percent of the first.
 * @return string
 */
function paulus_lead_list_mix( $a, $b, $share ) {
	$a   = ltrim( $a, '#' );
	$b   = ltrim( $b, '#' );
	$out = '#';
	foreach ( array( 0, 2, 4 ) as $at ) {
		$out .= str_pad( dechex( (int) round( hexdec( substr( $a, $at, 2 ) ) * $share / 100 + hexdec( substr( $b, $at, 2 ) ) * ( 100 - $share ) / 100 ) ), 2, '0', STR_PAD_LEFT );
	}
	return $out;
}

/**
 * The contrast ratio of two hex colours.
 *
 * @param string $a First colour.
 * @param string $b Second colour.
 * @return float
 */
function paulus_lead_list_ratio( $a, $b ) {
	$la = paulus_lead_list_luminance( $a );
	$lb = paulus_lead_list_luminance( $b );
	return ( max( $la, $lb ) + 0.05 ) / ( min( $la, $lb ) + 0.05 );
}

/**
 * The page colour of each colour scheme, for the accent check.
 *
 * @return array scheme => #rrggbb
 */
function paulus_lead_list_scheme_bases() {
	return array(
		'ochre'    => '#b8955f',
		'vellum'   => '#fbf7eb',
		'oxblood'  => '#4a1a10',
		'ink'      => '#1b1714',
		'paper'    => '#f3eee4',
		'graphite' => '#2e3033',
	);
}

/**
 * Whether an accent override is visible on a scheme's page: the bar, the
 * arrows and the underline need 3:1 against it (WCAG 1.4.11). The hovered
 * title keeps the scheme's own text colour, so an override never has to
 * carry text.
 *
 * @param string $hex    Colour, #rrggbb.
 * @param string $scheme Scheme slug.
 * @return bool
 */
function paulus_lead_list_accent_ok( $hex, $scheme = 'ochre' ) {
	$bases = paulus_lead_list_scheme_bases();
	$base  = isset( $bases[ $scheme ] ) ? $bases[ $scheme ] : $bases['ochre'];
	return paulus_lead_list_ratio( $hex, $base ) >= 3;
}

/** How many colours the category tabs rotate through (see lead-list.css). */
const PAULUS_LEAD_LIST_TAB_COLOURS = 5;

/**
 * The category tab of a piece: a pill that names its first category and
 * links to that category's archive, else (Journal entries have no category)
 * names the Journal and the year and links to the year's view. The colour is
 * the slug's or the year's hash, so it stays the same everywhere.
 *
 * @param WP_Post $post Article.
 * @return string Markup, or '' when the article has no category.
 */
function paulus_lead_list_tab( $post ) {
	$cats = get_the_category( $post->ID );
	$cats = is_array( $cats ) ? $cats : array();
	$cat  = null;
	foreach ( $cats as $c ) {
		if ( 'uncategorized' !== $c->slug ) {
			$cat = $c;
			break;
		}
	}
	if ( $cat ) {
		$colour = abs( crc32( (string) $cat->slug ) ) % PAULUS_LEAD_LIST_TAB_COLOURS;
		return '<p class="paulus-lead-list__tabrow"><a class="paulus-lead-list__tab paulus-lead-list__tab--c' . (int) $colour . '" href="' . esc_url( get_category_link( $cat->term_id ) ) . '">' . esc_html( $cat->name ) . '</a></p>';
	}

	// Journal entries carry no category, so the tab names the Journal and the
	// entry's year and links to that year's view; the colour follows the year.
	$year    = get_the_date( 'Y', $post );
	$archive = get_post_type_archive_link( 'paulus_journal' );
	if ( ! $archive ) {
		return '';
	}
	$colour = abs( crc32( (string) $year ) ) % PAULUS_LEAD_LIST_TAB_COLOURS;
	/* translators: %s: year. */
	$label = sprintf( __( 'Journal %s', 'paulus' ), $year );

	return '<p class="paulus-lead-list__tabrow"><a class="paulus-lead-list__tab paulus-lead-list__tab--c' . (int) $colour . '" href="' . esc_url( trailingslashit( $archive ) . $year . '/' ) . '">' . esc_html( $label ) . '</a></p>';
}

/**
 * The line under a title: the date and the reading time at 220 words a minute.
 *
 * @param WP_Post $post Article.
 * @return string
 */
function paulus_lead_list_meta( $post ) {
	$words   = str_word_count( wp_strip_all_tags( strip_shortcodes( (string) $post->post_content ) ) );
	$minutes = max( 1, (int) round( $words / 220 ) );

	return '<p class="paulus-lead-list__meta"><time datetime="' . esc_attr( get_the_date( 'c', $post ) ) . '">' . esc_html( get_the_date( 'j M Y', $post ) ) . '</time>'
		. '<span class="paulus-lead-list__sep" aria-hidden="true">&middot;</span>'
		/* translators: %d: minutes. */
		. '<span>' . esc_html( sprintf( _n( '%d min read', '%d min read', $minutes, 'paulus' ), $minutes ) ) . '</span></p>';
}


/**
 * The featured entries, newest first: the lead and the three beside it.
 *
 * @return WP_Post[]
 */
function paulus_lead_featured() {
	return get_posts(
		array(
			'post_type'        => 'paulus_journal',
			'post_status'      => 'publish',
			'numberposts'      => 1 + PAULUS_LEAD_LIST_MAX,
			'meta_key'         => '_paulus_featured', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'       => '1', // phpcs:ignore WordPress.DB.SlowDBQuery
			'orderby'          => 'date',
			'order'            => 'DESC',
			'suppress_filters' => false,
		)
	);
}

/** The "Featured" box on a Journal entry's edit screen. */
function paulus_featured_box() {
	add_meta_box(
		'paulus-featured',
		__( 'Featured', 'paulus' ),
		static function ( $post ) {
			wp_nonce_field( 'paulus_featured', 'paulus_featured_nonce' );
			echo '<label><input type="checkbox" name="paulus_featured" value="1"' . checked( '1', get_post_meta( $post->ID, '_paulus_featured', true ), false ) . '> ' . esc_html__( 'Show in the featured block at the head of the Journal (a featured image is needed; the block shows once four entries are featured).', 'paulus' ) . '</label>';
		},
		'paulus_journal',
		'side'
	);
}
add_action( 'add_meta_boxes', 'paulus_featured_box' );

/** Save the box. */
function paulus_featured_save( $post_id ) {
	if ( ! isset( $_POST['paulus_featured_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['paulus_featured_nonce'] ) ), 'paulus_featured' ) || ! current_user_can( 'edit_post', $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
		return;
	}
	if ( empty( $_POST['paulus_featured'] ) ) {
		delete_post_meta( $post_id, '_paulus_featured' );
	} else {
		update_post_meta( $post_id, '_paulus_featured', '1' );
	}
}
add_action( 'save_post_paulus_journal', 'paulus_featured_save' );

/**
 * The stylesheet, on the first page of a Journal view. It is
 * enqueued with the theme's own styles rather than while the block or list
 * renders: an enqueue made during rendering prints the theme stylesheet it
 * depends on above the core block styles, and they then win over it.
 */
function paulus_lead_list_assets() {
	if ( is_paged() || ! is_post_type_archive( 'paulus_journal' ) ) {
		return;
	}
	wp_enqueue_style( 'paulus-lead-list', PAULUS_URI . '/assets/css/lead-list.css', array( 'paulus-theme' ), PAULUS_VERSION );
	wp_enqueue_script( 'paulus-lead-list-enter', PAULUS_URI . '/assets/js/lead-list-enter.js', array(), PAULUS_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	wp_enqueue_script( 'paulus-lead-list', PAULUS_URI . '/assets/js/lead-list.js', array(), PAULUS_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'paulus_lead_list_assets', 21 );
