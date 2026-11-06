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
	$panel = static function ( $post, $first ) {
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
		$h     .= '<p class="paulus-lead-list__date"><time datetime="' . esc_attr( get_the_date( 'c', $post ) ) . '">' . esc_html( get_the_date( 'j F Y', $post ) ) . '</time></p>';
		$h     .= '<h3 class="paulus-lead-list__title"><a class="paulus-lead-list__link" href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( get_the_title( $post ) ) . '</a></h3>';
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

	$html  = '<section class="paulus-lead-list alignwide" aria-labelledby="' . esc_attr( $heading_id ) . '">';
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
	foreach ( $list as $item ) {
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
		$html .= '<li class="paulus-lead-list__item"' . ( isset( $swap[ $item->ID ] ) ? ' data-lead="' . (int) $item->ID . '"' : '' ) . '>';
		$html .= '<span class="paulus-lead-list__thumb' . ( '' === $thumb ? ' paulus-lead-list__thumb--empty' : '' ) . '" aria-hidden="true">' . $thumb . '</span>';
		$html .= '<div class="paulus-lead-list__text"><h3 class="paulus-lead-list__item-title"><a class="paulus-lead-list__link" href="' . esc_url( get_permalink( $item ) ) . '">' . esc_html( get_the_title( $item ) ) . '</a></h3>';
		$html .= '<p class="paulus-lead-list__item-date"><time datetime="' . esc_attr( get_the_date( 'c', $item ) ) . '">' . esc_html( get_the_date( 'j F Y', $item ) ) . '</time></p></div>';
		$html .= '</li>';
	}
	$html .= '</ul></div></section>';


	return array(
		'html' => $html,
		'ids'  => array_merge( array( $lead->ID ), wp_list_pluck( $list, 'ID' ) ),
	);
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
	wp_enqueue_script( 'paulus-lead-list', PAULUS_URI . '/assets/js/lead-list.js', array(), PAULUS_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'paulus_lead_list_assets', 21 );
