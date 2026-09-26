<?php
/**
 * Site search: a search toggle in the header, and the results list for the
 * search template.
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

/**
 * The search form, used in the header panel, on the results page and on the 404 page.
 *
 * @param string $id Unique id prefix.
 * @return string
 */
function paulus_search_form( $id = 'paulus-s' ) {
	return '<form role="search" method="get" class="paulus-searchform" action="' . esc_url( home_url( '/' ) ) . '">'
		// The field's Greek label, ΕΡΑΥΝΑΤΕ ("search", John 5:39); the button's
		// rollover carries a different word, ΖΗΤΕΙΤΕ ("seek", Matthew 7:7).
		. '<span class="paulus-searchform__mark" lang="grc" aria-hidden="true" title="' . esc_attr( paulus_greek_marks()['search-label'][1] ) . '">' . esc_html( paulus_greek_marks()['search-label'][0] ) . '</span>'
		. '<label class="screen-reader-text" for="' . esc_attr( $id ) . '">' . esc_html__( 'Search the site', 'paulus' ) . '</label>'
		. '<input type="search" id="' . esc_attr( $id ) . '" name="s" value="' . esc_attr( get_search_query() ) . '" placeholder="' . esc_attr__( 'Search the case: Damascus, Barnabas, al-Qummī…', 'paulus' ) . '" autocomplete="off">'
		. '<button type="submit" class="paulus-button" title="' . esc_attr( paulus_greek_title( 'search' ) ) . '">' . paulus_greek_label( 'search', esc_html__( 'Search', 'paulus' ) ) . '</button>'
		. '</form>';
}

/**
 * Header toggle: a magnifier that opens a search panel under the header.
 * Built on <details>, so it works without JavaScript.
 *
 * @return string
 */
function paulus_sc_search_toggle() {
	return '<details class="paulus-hsearch"><summary aria-label="' . esc_attr__( 'Search', 'paulus' ) . '" title="' . esc_attr__( 'Search', 'paulus' ) . '">' . paulus_icon( 'search' ) . '</summary>'
		. '<div class="paulus-hsearch__panel">' . paulus_search_form( 'paulus-hs' ) . '</div></details>';
}
add_shortcode( 'paulus_search_toggle', 'paulus_sc_search_toggle' );
add_shortcode( 'paulus_search_form', function () { return paulus_search_form( 'paulus-404s' ); } );

/**
 * Highlight the search terms in a piece of plain text.
 *
 * @param string $text Plain text.
 * @return string Escaped HTML with <mark>.
 */
function paulus_search_highlight( $text ) {
	$text  = esc_html( $text );
	$terms = array_filter(
		preg_split( '/\s+/u', trim( get_search_query( false ) ) ),
		static function ( $t ) {
			return paulus_ulen( $t ) >= 2;
		}
	);
	if ( ! $terms ) {
		return $text;
	}
	// One combined pattern, applied in a single pass over the original
	// text: replacing per term in a loop (the previous approach) reruns
	// each later term's regex over the OUTPUT of the earlier ones, so a
	// term such as "mark" can match the literal word inside the <mark>
	// tags a previous replacement just inserted, corrupting the markup.
	$alternatives = array_map(
		static function ( $t ) {
			return preg_quote( esc_html( $t ), '/' );
		},
		$terms
	);
	$pattern = '/(' . implode( '|', $alternatives ) . ')/iu';
	return preg_replace( $pattern, '<mark>$1</mark>', $text );
}

/**
 * An excerpt around the first match in the content, else the summary.
 *
 * @param WP_Post $post Post.
 * @return string Plain text.
 */
function paulus_search_snippet( $post ) {
	$q     = trim( get_search_query( false ) );
	$body  = preg_replace( '#<sup[^>]*>.*?</sup>#s', '', $post->post_content );
	$plain = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( $body ) ) ) );
	$first = $q ? paulus_ustripos( $plain, strtok( $q, ' ' ) ) : false;
	if ( false !== $first ) {
		$start = max( 0, $first - 120 );
		if ( $start > 0 ) {
			$space = paulus_ustrpos( $plain, ' ', $start );
			$start = false === $space ? $start : $space + 1;
		}
		$snip  = paulus_usubstr( $plain, $start, 300 );
		$cut   = paulus_ustrrpos( $snip, ' ' );
		$snip  = ( false !== $cut && paulus_ulen( $snip ) >= 300 ) ? paulus_usubstr( $snip, 0, $cut ) : $snip;
		return ( $start > 0 ? '… ' : '' ) . $snip . ( paulus_ulen( $plain ) > $start + 300 ? ' …' : '' );
	}
	return has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( $plain, 45 );
}

/**
 * Results list for the search template.
 *
 * @return string
 */
function paulus_sc_search_results() {
	global $wp_query;
	$q     = get_search_query();
	$count = (int) $wp_query->found_posts;
	$out   = '<div class="paulus-results">' . paulus_search_form( 'paulus-rs' );
	/* translators: 1: number of results, 2: search terms. */
	$out  .= '<p class="paulus-results__count">' . esc_html( sprintf( _n( '%1$s result for “%2$s”', '%1$s results for “%2$s”', $count, 'paulus' ), number_format_i18n( $count ), $q ) ) . '</p>';
	if ( ! have_posts() ) {
		$out .= '<p class="paulus-results__none">' . esc_html__( 'Nothing on the site matches. Try a single name or term, or browse the sections below.', 'paulus' ) . '</p>' . do_shortcode( '[paulus_404_links]' );
		return $out . '</div>';
	}
	$out .= '<ol class="paulus-results__list">';
	$paged = max( 1, (int) get_query_var( 'paged' ) );
	$i     = ( $paged - 1 ) * 20;
	while ( have_posts() ) {
		the_post();
		$i++;
		$post  = get_post();
		$label = get_post_meta( $post->ID, '_paulus_label', true );
		$part  = 'post' === $post->post_type ? paulus_part_of( $post->ID ) : null;
		$kind  = 'page' === $post->post_type ? __( 'Page', 'paulus' ) : ( 'paulus_journal' === $post->post_type ? paulus_option( 'journal_title' ) . ', ' . get_the_date( 'j F Y', $post ) : '' );
		$where = array_filter( array( $part ? $part->name : $kind, $label ) );
		$out  .= '<li class="paulus-result">'
			. '<span class="paulus-result__n" aria-hidden="true">' . esc_html( paulus_roman( $i ) ) . '</span>'
			. ( $where ? '<p class="paulus-result__where">' . esc_html( implode( ' · ', $where ) ) . '</p>' : '' )
			. '<h2 class="paulus-result__title"><a href="' . esc_url( get_permalink() ) . '">' . paulus_search_highlight( get_the_title() ) . '</a></h2>'
			. '<p class="paulus-result__snippet">' . paulus_search_highlight( paulus_search_snippet( $post ) ) . '</p>'
			. '</li>';
	}
	wp_reset_postdata();
	$out .= '</ol>';
	$links = paginate_links( array( 'type' => 'list', 'prev_text' => __( 'Previous', 'paulus' ), 'next_text' => __( 'Next', 'paulus' ) ) );
	if ( $links ) {
		$out .= '<nav class="paulus-results__pages" aria-label="' . esc_attr__( 'Result pages', 'paulus' ) . '">' . $links . '</nav>';
	}
	return $out . '</div>';
}
add_shortcode( 'paulus_search_results', 'paulus_sc_search_results' );

/**
 * Search posts and pages only (never attachments), twenty to a page.
 *
 * @param WP_Query $q Query.
 */
function paulus_search_scope( $q ) {
	if ( ! is_admin() && $q->is_main_query() && $q->is_search() ) {
		$q->set( 'post_type', array( 'post', 'page', 'paulus_journal' ) );
		$q->set( 'posts_per_page', 20 );
	}
}
add_action( 'pre_get_posts', 'paulus_search_scope' );

/**
 * A Roman numeral, for the result marks.
 *
 * @param int $n Number, 1 to 3999.
 * @return string
 */
function paulus_roman( $n ) {
	$map = array( 1000 => 'M', 900 => 'CM', 500 => 'D', 400 => 'CD', 100 => 'C', 90 => 'XC', 50 => 'L', 40 => 'XL', 10 => 'X', 9 => 'IX', 5 => 'V', 4 => 'IV', 1 => 'I' );
	$out = '';
	foreach ( $map as $v => $r ) {
		while ( $n >= $v ) {
			$out .= $r;
			$n   -= $v;
		}
	}
	return $out;
}
