<?php
/**
 * Site architecture: sections, reading order, navigation and the shortcodes
 * that expose them in templates.
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

/**
 * Pages carry a one-line summary used by the Reference index.
 */
function paulus_page_excerpts() {
	add_post_type_support( 'page', 'excerpt' );
}
add_action( 'init', 'paulus_page_excerpts' );

/**
 * Section categories, in site order.
 *
 * @return WP_Term[]
 */
function paulus_parts() {
	static $parts = null;
	if ( null !== $parts ) {
		return $parts;
	}
	$parts = get_terms(
		array(
			'taxonomy'   => 'category',
			'hide_empty' => false,
			'meta_key'   => 'paulus_order', // phpcs:ignore WordPress.DB.SlowDBQuery
			'orderby'    => 'meta_value_num',
			'order'      => 'ASC',
		)
	);
	if ( is_wp_error( $parts ) ) {
		$parts = array();
	}
	return $parts;
}

/**
 * Articles in reading order, optionally limited to one section.
 *
 * @param int $term_id Part term ID, or 0 for all.
 * @return WP_Post[]
 */
function paulus_chapters( $term_id = 0 ) {
	$args = array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'no_found_rows'  => true,
		// Managed articles (with a recorded reading order) come first, in
		// that order; any ordinary post in the same section that was
		// never brought into the reading order follows, newest first. A
		// bare meta_key alone (the previous query) silently excludes
		// posts without that meta from the results entirely.
		'meta_query'     => array(
			'relation'       => 'OR',
			'paulus_ordered' => array( 'key' => '_paulus_order', 'type' => 'NUMERIC' ),
			'paulus_none'    => array( 'key' => '_paulus_order', 'compare' => 'NOT EXISTS' ),
		),
		'orderby'        => array( 'paulus_ordered' => 'ASC', 'date' => 'ASC' ),
	);
	if ( $term_id ) {
		$args['cat'] = (int) $term_id;
	}
	return get_posts( $args );
}

/**
 * The section an article belongs to.
 *
 * @param int $post_id Post ID.
 * @return WP_Term|null
 */
function paulus_part_of( $post_id ) {
	foreach ( paulus_parts() as $part ) {
		if ( has_category( $part->term_id, $post_id ) ) {
			return $part;
		}
	}
	return null;
}

/**
 * Section archives list articles in reading order.
 *
 * @param WP_Query $query Main query.
 */
function paulus_part_order( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_category() ) {
		return;
	}
	$term = get_queried_object();
	if ( ! $term instanceof WP_Term || '' === (string) get_term_meta( $term->term_id, 'paulus_order', true ) ) {
		return;
	}
	$query->set(
		'meta_query',
		array(
			'relation'       => 'OR',
			'paulus_ordered' => array( 'key' => '_paulus_order', 'type' => 'NUMERIC' ),
			'paulus_none'    => array( 'key' => '_paulus_order', 'compare' => 'NOT EXISTS' ),
		)
	);
	$query->set( 'orderby', array( 'paulus_ordered' => 'ASC', 'date' => 'ASC' ) );
}
add_action( 'pre_get_posts', 'paulus_part_order' );

/**
 * Point the header navigation block at the menu the installer created.
 *
 * @param array $block Parsed block.
 * @return array
 */
function paulus_nav_ref( $block ) {
	if ( 'core/navigation' !== $block['blockName'] || ! empty( $block['attrs']['ref'] ) ) {
		return $block;
	}
	if ( false === strpos( $block['attrs']['className'] ?? '', 'paulus-nav' ) ) {
		return $block;
	}
	$nav_id = (int) get_option( 'paulus_nav_id' );
	if ( $nav_id && 'publish' === get_post_status( $nav_id ) ) {
		$block['attrs']['ref'] = $nav_id;
	}
	return $block;
}
add_filter( 'render_block_data', 'paulus_nav_ref' );

/**
 * Menu links take their address from what they link to as the page renders.
 * A navigation link stores the full address it had when the menu was built;
 * when the site's addresses later change (Rank Math's option to strip the
 * category base, a new permalink structure), the stored address goes stale
 * and the menu shows, say, /category/the-charges/ where the site now uses
 * /the-charges/. A link that names its section or page by ID is given that
 * item's current address, which every SEO plugin's filters have shaped.
 *
 * @param array $block Parsed block.
 * @return array
 */
function paulus_nav_link_live_url( $block ) {
	if ( 'core/navigation-link' !== ( $block['blockName'] ?? '' ) ) {
		return $block;
	}
	$a  = $block['attrs'] ?? array();
	$id = isset( $a['id'] ) ? (int) $a['id'] : 0;
	if ( ! $id || empty( $a['kind'] ) ) {
		return $block;
	}
	$url = '';
	if ( 'taxonomy' === $a['kind'] ) {
		$tax  = ! empty( $a['type'] ) && 'tag' !== $a['type'] ? $a['type'] : 'post_tag';
		$link = get_term_link( $id, $tax );
		$url  = is_wp_error( $link ) ? '' : $link;
		// The label follows the section's current name where it is that name
		// in other capitals; a label written differently by hand is kept.
		$term = get_term( $id, $tax );
		if ( $term && ! is_wp_error( $term ) && isset( $a['label'] ) && $a['label'] !== $term->name && 0 === strcasecmp( wp_strip_all_tags( $a['label'] ), $term->name ) ) {
			$block['attrs']['label'] = $term->name;
		}
	} elseif ( 'post-type' === $a['kind'] ) {
		$url = (string) get_permalink( $id );
	}
	if ( $url ) {
		$block['attrs']['url'] = $url;
	}
	return $block;
}
add_filter( 'render_block_data', 'paulus_nav_link_live_url' );

/**
 * A label split into its word and its numeral, so the ornament layer can
 * set the numeral on the coin roundel. "Count·III", "Count III" and the
 * pre-2.4 "Count 3" all give the word "Count" and the numeral "III".
 *
 * @param string $label Stored label.
 * @return string Escaped markup.
 */
function paulus_label_markup( $label ) {
	if ( preg_match( '/^(.*?)[\s·]+([0-9]+|[IVXLC]+)$/u', trim( $label ), $m ) ) {
		$n = $m[2];
		if ( ctype_digit( $n ) ) {
			$map = array( 1 => 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X' );
			$n   = $map[ (int) $n ] ?? $n;
		}
		return '<span class="paulus-label__word">' . esc_html( $m[1] ) . '</span><span class="paulus-label__sep">·</span><span class="paulus-label__num">' . esc_html( $n ) . '</span>';
	}
	return esc_html( $label );
}

/**
 * Article card markup.
 *
 * @param WP_Post $post  Article.
 * @param string  $style cards, list or counts.
 * @return string
 */
function paulus_chapter_card( $post, $style = 'cards', $series_card = false ) {
	$url   = get_permalink( $post );
	$label = get_post_meta( $post->ID, '_paulus_label', true );
	$parts = (int) get_post_meta( $post->ID, '_paulus_parts', true );
	$no    = (int) get_post_meta( $post->ID, '_paulus_part_no', true );
	$title = get_the_title( $post );
	if ( $series_card && $parts > 1 ) {
		$title = get_post_meta( $post->ID, '_paulus_series_title', true ) ?: $title;
	}
	$out   = '<li class="paulus-chapter' . ( $parts > 1 ? ' paulus-chapter--series' : '' ) . '">';
	if ( 'cards' === $style && has_post_thumbnail( $post ) ) {
		$out .= '<a class="paulus-chapter__image" href="' . esc_url( $url ) . '" tabindex="-1" aria-hidden="true">' . get_the_post_thumbnail( $post, 'medium_large', array( 'alt' => '', 'loading' => 'lazy' ) ) . '</a>';
	}
	if ( $label ) {
		$out .= '<p class="paulus-chapter__label">' . paulus_label_markup( $label ) . '</p>';
	}
	$out .= '<h3 class="paulus-chapter__title"><a href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a></h3>';
	if ( has_excerpt( $post ) ) {
		$out .= '<p class="paulus-chapter__excerpt">' . esc_html( get_the_excerpt( $post ) ) . '</p>';
	}
	if ( $parts > 1 ) {
		/* translators: 1: part number, 2: number of parts. */
		$note = $series_card ? sprintf( _n( 'In %s part', 'In %s parts', $parts, 'paulus' ), number_format_i18n( $parts ) ) : sprintf( __( 'Part %1$s of %2$s', 'paulus' ), number_format_i18n( $no ), number_format_i18n( $parts ) );
		$out .= '<p class="paulus-chapter__parts">' . esc_html( $note ) . '</p>';
	}
	return $out . '</li>';
}

/**
 * Sections with their articles.
 *
 * Attributes: part="the-charges" limits output to one section, and
 * part="current" uses the section being viewed (without its heading);
 * style="cards" (images), "text" (cards without images), "counts"
 * (numbered dossier list) or "list" (compact).
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function paulus_sc_parts( $atts ) {
	$atts  = shortcode_atts( array( 'style' => 'cards', 'part' => '' ), $atts, 'paulus_parts' );
	$style = in_array( $atts['style'], array( 'cards', 'text', 'counts', 'list' ), true ) ? $atts['style'] : 'cards';
	if ( 'current' === $atts['part'] ) {
		$term = get_queried_object();
		if ( ! $term instanceof WP_Term ) {
			return '';
		}
		if ( '' === (string) get_term_meta( $term->term_id, 'paulus_order', true ) ) {
			// A category outside the site structure: plain list, newest first.
			$out = '<div class="paulus-parts paulus-parts--' . esc_attr( $style ) . '"><ol class="paulus-chapters">';
			foreach ( get_posts( array( 'cat' => $term->term_id, 'posts_per_page' => 50 ) ) as $post ) {
				$out .= paulus_chapter_card( $post, $style );
			}
			return $out . '</ol></div>';
		}
		$atts['part'] = $term->slug;
		$bare         = true;
	}
	$out   = '<div class="paulus-parts paulus-parts--' . esc_attr( $style ) . '">';
	foreach ( paulus_parts() as $part ) {
		if ( $atts['part'] && $part->slug !== $atts['part'] ) {
			continue;
		}
		$chapters = paulus_chapters( $part->term_id );
		if ( ! $chapters ) {
			continue;
		}
		if ( empty( $bare ) ) {
			// One card per series on overview pages; section pages list every part.
			$seen  = array();
			$first = array();
			foreach ( $chapters as $chapter ) {
				$series = get_post_meta( $chapter->ID, '_paulus_series', true );
				if ( $series && isset( $seen[ $series ] ) ) {
					continue;
				}
				if ( $series ) {
					$seen[ $series ] = true;
				}
				$first[] = $chapter;
			}
			$chapters = $first;
		}
		$out .= '<section class="paulus-part" id="' . esc_attr( $part->slug ) . '">';
		if ( ! empty( $bare ) ) {
			$out .= '<ol class="paulus-chapters">';
			foreach ( $chapters as $chapter ) {
				$out .= paulus_chapter_card( $chapter, $style );
			}
			$out .= '</ol></section>';
			continue;
		}
		$greek = paulus_greek_mark( $part->slug );
		$out  .= '<header class="paulus-part__head">' . ( $greek ? '<p class="paulus-greek-line">' . $greek . '</p>' : '' ) . '<h2 class="paulus-part__title"><a href="' . esc_url( get_term_link( $part ) ) . '">' . esc_html( $part->name ) . '</a></h2>';
		if ( $part->description ) {
			$out .= '<p class="paulus-part__description">' . esc_html( $part->description ) . '</p>';
		}
		$out .= '</header><ol class="paulus-chapters">';
		foreach ( $chapters as $chapter ) {
			$out .= paulus_chapter_card( $chapter, $style, true );
		}
		$out .= '</ol></section>';
	}
	return $out . '</div>';
}
add_shortcode( 'paulus_parts', 'paulus_sc_parts' );

/**
 * Link to a page or post by slug, resolved at render time.
 * Usage: [paulus_link slug="the-damascus-road"]text[/paulus_link]
 *
 * @param array  $atts    Attributes: slug, optional anchor.
 * @param string $content Link text.
 * @return string
 */
function paulus_sc_link( $atts, $content = '' ) {
	$atts = shortcode_atts( array( 'slug' => '', 'anchor' => '' ), $atts, 'paulus_link' );
	$post = get_page_by_path( $atts['slug'], OBJECT, array( 'post', 'page' ) );
	$text = $content ? $content : ( $post ? get_the_title( $post ) : $atts['slug'] );
	if ( ! $post ) {
		return esc_html( $text );
	}
	$url = get_permalink( $post ) . ( $atts['anchor'] ? '#' . rawurlencode( $atts['anchor'] ) : '' );
	return '<a href="' . esc_url( $url ) . '">' . wp_kses_post( $text ) . '</a>';
}
add_shortcode( 'paulus_link', 'paulus_sc_link' );

/**
 * Questions and short answers parsed from the Answers page.
 *
 * Each question is an <h2 id="…"> heading; its answer is everything up to the
 * next heading or the footnotes. Footnote markers are dropped, since the notes
 * live on the Answers page itself.
 *
 * @return array<int,array{id:string,question:string,answer:string}>
 */
function paulus_answers() {
	$page = get_page_by_path( 'answers' );
	if ( ! $page || 'publish' !== $page->post_status || post_password_required( $page ) ) {
		return array();
	}
	$content = preg_replace( '#<ol class="footnotes">.*$#s', '', $page->post_content );
	$parts   = preg_split( '#(<h2[^>]*id="[^"]+"[^>]*>.*?</h2>)#s', $content, -1, PREG_SPLIT_DELIM_CAPTURE );
	$items   = array();
	for ( $i = 1; $i < count( $parts ); $i += 2 ) {
		if ( ! preg_match( '#<h2[^>]*id="([^"]+)"[^>]*>(.*?)</h2>#s', $parts[ $i ], $h ) ) {
			continue;
		}
		$answer  = preg_replace( '#<sup>.*?</sup>#s', '', $parts[ $i + 1 ] ?? '' );
		$items[] = array(
			'id'       => $h[1],
			'question' => wp_strip_all_tags( $h[2] ),
			'answer'   => trim( $answer ),
		);
	}
	return $items;
}

/**
 * Front-page FAQ: the first questions from the Answers page as an accordion.
 * Built on <details> and <summary>, so it opens and closes without script
 * and works with the keyboard and screen readers.
 *
 * @param array $atts Attributes: count (default 6).
 * @return string
 */
function paulus_sc_answers_teaser( $atts ) {
	$atts  = shortcode_atts( array( 'count' => 6 ), $atts, 'paulus_answers_teaser' );
	$page  = get_page_by_path( 'answers' );
	$items = array_slice( paulus_answers(), 0, max( 1, (int) $atts['count'] ) );
	if ( ! $page || ! $items ) {
		return '';
	}
	$url = get_permalink( $page );
	// Recorded for the front page's FAQPage (paulus_schema_front_faq): in a
	// block theme the page body renders before wp_head, so the markup is
	// built from exactly the questions and answers shown here.
	$GLOBALS['paulus_front_faq'] = array( 'url' => $url, 'items' => $items );
	$greek = paulus_greek_mark( 'answers' );
	$out   = '<section class="paulus-answers" aria-labelledby="paulus-answers-heading"><header class="paulus-part__head">' . ( $greek ? '<p class="paulus-greek-line">' . $greek . '</p>' : '' ) . '<h2 class="paulus-part__title" id="paulus-answers-heading"><a href="' . esc_url( $url ) . '">' . esc_html( get_the_title( $page ) ) . '</a></h2>';
	if ( $page->post_excerpt ) {
		$out .= '<p class="paulus-part__description">' . esc_html( $page->post_excerpt ) . '</p>';
	}
	$out .= '</header><div class="paulus-faq">';
	foreach ( $items as $item ) {
		$out .= '<details class="paulus-faq__item" id="faq-' . esc_attr( $item['id'] ) . '">'
			. '<summary class="paulus-faq__question"><span>' . esc_html( $item['question'] ) . '</span></summary>'
			. '<div class="paulus-faq__answer">' . wp_kses_post( do_shortcode( $item['answer'] ) ) . '</div>'
			. '</details>';
	}
	$out .= '</div><p class="paulus-answers__more"><a class="paulus-link" href="' . esc_url( $url ) . '">' . esc_html__( 'All answers, with references', 'paulus' ) . '</a></p></section>';
	return $out;
}
add_shortcode( 'paulus_answers_teaser', 'paulus_sc_answers_teaser' );

/**
 * Child pages of the current page with their summaries.
 *
 * @return string
 */
function paulus_sc_children() {
	$children = get_pages(
		array(
			'parent'      => get_the_ID(),
			'sort_column' => 'menu_order',
		)
	);
	if ( ! $children ) {
		return '';
	}
	$out = '<ul class="paulus-children">';
	foreach ( $children as $child ) {
		$out .= '<li><a class="paulus-children__title" href="' . esc_url( get_permalink( $child ) ) . '">' . esc_html( get_the_title( $child ) ) . '</a>';
		if ( $child->post_excerpt ) {
			$out .= '<p>' . esc_html( $child->post_excerpt ) . '</p>';
		}
		$out .= '</li>';
	}
	return $out . '</ul>';
}
add_shortcode( 'paulus_children', 'paulus_sc_children' );

/**
 * Breadcrumb and label above an article title.
 *
 * @return string
 */
function paulus_sc_article_meta() {
	$id    = get_the_ID();
	$part  = paulus_part_of( $id );
	$label = get_post_meta( $id, '_paulus_label', true );
	$out   = '<nav class="paulus-crumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'paulus' ) . '"><ol>';
	$out  .= '<li><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'paulus' ) . '</a></li>';
	if ( $part ) {
		$out .= '<li><a href="' . esc_url( get_term_link( $part ) ) . '">' . esc_html( $part->name ) . '</a></li>';
	}
	if ( $label ) {
		$out .= '<li aria-current="page">' . esc_html( $label ) . '</li>';
	}
	return $out . '</ol></nav>';
}
add_shortcode( 'paulus_article_meta', 'paulus_sc_article_meta' );

/**
 * Previous and next article in reading order, plus the article's study questions.
 *
 * @return string
 */
function paulus_sc_article_nav() {
	$id       = get_the_ID();
	$chapters = paulus_chapters();
	$ids      = wp_list_pluck( $chapters, 'ID' );
	$pos      = array_search( $id, $ids, true );
	if ( false === $pos ) {
		return '';
	}
	$out   = '<nav class="paulus-readnav" id="paulus-readnav" aria-label="' . esc_attr__( 'Reading order', 'paulus' ) . '"><div class="paulus-readnav__inner">';
	$qs     = get_page_by_path( 'reference/study-questions' );
	$series = get_post_meta( $id, '_paulus_series', true );
	$anchor = '';
	if ( get_post_meta( $id, '_paulus_questions', true ) ) {
		$anchor = get_post_field( 'post_name', $id );
	} elseif ( $series ) {
		foreach ( $chapters as $c ) {
			if ( get_post_meta( $c->ID, '_paulus_series', true ) === $series && get_post_meta( $c->ID, '_paulus_questions', true ) ) {
				$anchor = $c->post_name;
				break;
			}
		}
	}
	if ( $qs && $anchor ) {
		// How many questions the set holds: the list under its heading.
		$count = 0;
		if ( preg_match( '#<h2 id="' . preg_quote( $anchor, '#' ) . '"[^>]*>.*?</h2>\s*<ol[^>]*>(.*?)</ol>#s', (string) $qs->post_content, $set ) ) {
			$count = substr_count( $set[1], '<li' );
		}
		$out .= '<p class="paulus-readnav__questions"><a href="' . esc_url( get_permalink( $qs ) . '#' . $anchor ) . '">'
			. '<span class="paulus-readnav__q-mark" aria-hidden="true">?</span>'
			. '<span class="paulus-readnav__q-label">' . esc_html__( 'Study questions for this article', 'paulus' ) . '</span>'
			/* translators: %s: number of questions. */
			. ( $count ? '<span class="paulus-readnav__q-count">' . esc_html( sprintf( _n( '%s question', '%s questions', $count, 'paulus' ), number_format_i18n( $count ) ) ) . '</span>' : '' )
			. '</a></p>';
	}
	$out .= '<div class="paulus-readnav__links">';
	if ( $pos > 0 ) {
		$prev = $chapters[ $pos - 1 ];
		$same = $series && get_post_meta( $prev->ID, '_paulus_series', true ) === $series;
		$out .= '<a class="paulus-readnav__prev" href="' . esc_url( get_permalink( $prev ) ) . '"><span>' . esc_html( $same ? __( 'Previous part', 'paulus' ) : __( 'Previous', 'paulus' ) ) . '</span>' . esc_html( get_the_title( $prev ) ) . '</a>';
	}
	if ( $pos < count( $chapters ) - 1 ) {
		$next = $chapters[ $pos + 1 ];
		$same = $series && get_post_meta( $next->ID, '_paulus_series', true ) === $series;
		$out .= '<a class="paulus-readnav__next" href="' . esc_url( get_permalink( $next ) ) . '"><span>' . esc_html( $same ? __( 'Next part', 'paulus' ) : __( 'Next', 'paulus' ) ) . '</span>' . esc_html( get_the_title( $next ) ) . '</a>';
	}
	return $out . '</div></div></nav>';
}
add_shortcode( 'paulus_article_nav', 'paulus_sc_article_nav' );



/**
 * Footer menu: the case in reading order, and the reference pages.
 * Built from the site structure, so it follows new sections and pages
 * without editing a menu.
 *
 * @return string
 */
function paulus_sc_footer_nav() {
	$case = array();
	foreach ( paulus_parts() as $part ) {
		$case[] = array( $part->name, get_term_link( $part ), $part->slug );
	}
	$verdict = get_page_by_path( 'the-verdict' );
	if ( $verdict && 'publish' === $verdict->post_status ) {
		// Named as in the header menu, in the sections' title case.
		$case[] = array( __( 'The Verdict', 'paulus' ), get_permalink( $verdict ), 'the-verdict' );
	}

	// Short labels where a page's full title would crowd its column.
	$short = array(
		'chronology'                           => __( 'Timeline', 'paulus' ),
		'dale-b-martin-luke-versus-paul'       => __( 'Dale B. Martin on Acts', 'paulus' ),
		'why-luke-does-not-know-pauls-letters' => __( 'Why Acts ignores the letters', 'paulus' ),
	);
	$reference = array();
	$parent    = get_page_by_path( 'reference' );
	if ( $parent && 'publish' === $parent->post_status ) {
		foreach ( get_pages( array( 'parent' => $parent->ID, 'sort_column' => 'menu_order' ) ) as $child ) {
			$label       = $short[ $child->post_name ] ?? get_the_title( $child );
			$reference[] = array( $label, get_permalink( $child ) );
		}
	}

	// The appendices: pages set beside the case, under their own heading.
	$appendices = array();
	$app_parent = get_page_by_path( 'appendices' );
	if ( $app_parent && 'publish' === $app_parent->post_status ) {
		foreach ( get_pages( array( 'parent' => $app_parent->ID, 'sort_column' => 'menu_order' ) ) as $child ) {
			$label        = $short[ $child->post_name ] ?? get_the_title( $child );
			$appendices[] = array( $label, get_permalink( $child ) );
		}
	}
	$columns = array(
		array( __( 'The case', 'paulus' ), '', $case ),
		array( __( 'Reference', 'paulus' ), $parent ? get_permalink( $parent ) : '', $reference ),
		array( __( 'Appendices', 'paulus' ), $app_parent ? get_permalink( $app_parent ) : '', $appendices ),
	);
	$out = '<nav class="paulus-footer-nav" aria-label="' . esc_attr__( 'Footer', 'paulus' ) . '">';
	foreach ( $columns as list( $heading, $url, $links ) ) {
		if ( ! $links ) {
			continue;
		}
		$title = $url ? '<a href="' . esc_url( $url ) . '">' . esc_html( $heading ) . '</a>' : esc_html( $heading );
		$out  .= '<div class="paulus-footer-nav__col"><p class="paulus-footer-nav__heading">' . $title . '</p><ul>';
		foreach ( $links as $link ) {
			list( $label, $href ) = $link;
			if ( is_wp_error( $href ) ) {
				continue;
			}
			$key   = $link[2] ?? '';
			$title = $key ? paulus_greek_title( $key ) : '';
			$out  .= '<li><a href="' . esc_url( $href ) . '"' . ( $title ? ' title="' . esc_attr( $title ) . '"' : '' ) . '>' . ( $key ? paulus_greek_label( $key, esc_html( $label ) ) : esc_html( $label ) ) . '</a></li>';
		}
		$out .= '</ul></div>';
	}
	return $out . '</nav>';
}
add_shortcode( 'paulus_footer_nav', 'paulus_sc_footer_nav' );

/**
 * Social profiles from Theme Options: one URL per line, the platform read
 * from the host, so the field stays a single box rather than forty-five.
 *
 * @return array List of [ icon slug, label, url ].
 */
function paulus_social_profiles() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	// Host fragment => [ icon file in assets/icons/social, label ]. Only
	// platforms the bundled icon set covers are listed; anything else is
	// still linked, by name, without a mark.
	$map = array(
		'facebook.com'    => array( 'facebook', 'Facebook' ),
		'instagram.com'   => array( 'instagram', 'Instagram' ),
		'x.com'           => array( 'x', 'X' ),
		'twitter.com'     => array( 'x', 'X' ),
		'linkedin.com'    => array( 'linkedin', 'LinkedIn' ),
		'youtube.com'     => array( 'youtube', 'YouTube' ),
		'youtu.be'        => array( 'youtube', 'YouTube' ),
		'tiktok.com'      => array( 'tiktok', 'TikTok' ),
		'pinterest.'      => array( 'pinterest', 'Pinterest' ),
		'snapchat.com'    => array( 'snapchat', 'Snapchat' ),
		'wa.me'           => array( 'whatsapp', 'WhatsApp' ),
		'whatsapp.com'    => array( 'whatsapp', 'WhatsApp' ),
		't.me'            => array( 'telegram', 'Telegram' ),
		'telegram.'       => array( 'telegram', 'Telegram' ),
		'reddit.com'      => array( 'reddit', 'Reddit' ),
		'discord.'        => array( 'discord', 'Discord' ),
		'threads.'        => array( 'threads', 'Threads' ),
		'wechat.com'      => array( 'wechat', 'WeChat' ),
		'tumblr.com'      => array( 'tumblr', 'Tumblr' ),
		'twitch.tv'       => array( 'twitch', 'Twitch' ),
		'vimeo.com'       => array( 'vimeo', 'Vimeo' ),
		'medium.com'      => array( 'medium', 'Medium' ),
		'behance.net'     => array( 'behance', 'Behance' ),
		'dribbble.com'    => array( 'dribbble', 'Dribbble' ),
		'mastodon.'       => array( 'mastodon', 'Mastodon' ),
		'bsky.app'        => array( 'bluesky', 'Bluesky' ),
		'line.me'         => array( 'line', 'LINE' ),
		'signal.'         => array( 'signal', 'Signal' ),
		'github.com'      => array( 'github', 'GitHub' ),
		'spotify.com'     => array( 'spotify', 'Spotify' ),
		'goodreads.com'   => array( 'goodreads', 'Goodreads' ),
		'wikidata.org'    => array( 'wikidata', 'Wikidata' ),
		'wikipedia.org'   => array( 'wikipedia', 'Wikipedia' ),
		'academia.edu'    => array( 'academia', 'Academia.edu' ),
		'quora.com'       => array( 'quora', 'Quora' ),
		'issuu.com'       => array( 'issuu', 'Issuu' ),
		'substack.com'    => array( 'substack', 'Substack' ),
		'flickr.com'      => array( 'flickr', 'Flickr' ),
		'scribd.com'      => array( 'scribd', 'Scribd' ),
		'gtribe.com'      => array( 'gtribe', 'GTribe' ),
		'profiles.wordpress.org' => array( 'wordpress-profile', 'WordPress.org' ),
		'wordpress.'      => array( 'wordpress', 'WordPress' ),
		'soundcloud.com'  => array( 'soundcloud', 'SoundCloud' ),
		'suno.'           => array( 'suno', 'Suno' ),
		'orcid.org'       => array( 'orcid', 'ORCID' ),
		'worldcat.org'    => array( 'oclc', 'WorldCat' ),
		'oclc.org'        => array( 'oclc', 'OCLC' ),
		'viaf.org'        => array( 'viaf', 'VIAF' ),
		'isni.org'        => array( 'isni', 'ISNI' ),
		'fiverr.com'      => array( 'fiverr', 'Fiverr' ),
	);

	$out = array();
	foreach ( preg_split( '/\R/u', (string) paulus_option( 'social_links' ) ) as $line ) {
		$url = trim( $line );
		if ( '' === $url || ! preg_match( '#^https?://#i', $url ) ) {
			continue;
		}
		$host  = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		$host  = preg_replace( '#^www\.#', '', $host );
		$icon  = '';
		$label = $host;
		foreach ( $map as $needle => $found ) {
			if ( false !== strpos( $host . '/', $needle ) ) {
				list( $icon, $label ) = $found;
				break;
			}
		}
		$out[] = array( $icon, $label, $url );
	}
	return $cache = $out;
}

/**
 * The social profiles as a row of marks, for the footer.
 *
 * @return string
 */
function paulus_sc_footer_social() {
	$profiles = paulus_social_profiles();
	if ( ! $profiles ) {
		return '';
	}
	$out = '<ul class="paulus-social" aria-label="' . esc_attr__( 'Elsewhere', 'paulus' ) . '">';
	foreach ( $profiles as list( $icon, $label, $url ) ) {
		$mark = $icon ? paulus_icon( 'social/' . $icon ) : '';
		$out .= '<li><a href="' . esc_url( $url ) . '" rel="me noopener"' . ( $mark ? '' : ' class="paulus-social__text"' )
			. ( $mark ? ' title="' . esc_attr( $label ) . '"' : '' ) . '>'
			. ( $mark ? $mark . '<span class="screen-reader-text">' . esc_html( $label ) . '</span>' : esc_html( $label ) )
			. '</a></li>';
	}
	return $out . '</ul>';
}
add_shortcode( 'paulus_footer_social', 'paulus_sc_footer_social' );

/**
 * The HTML sitemap page, at /sitemap/ (before 2.25.4, /site-map/: the old
 * slug is also tried, for the moment between installing the update and
 * the structure sync that renames the page).
 *
 * @return WP_Post|null
 */
function paulus_sitemap_page() {
	return get_page_by_path( 'sitemap' ) ?: get_page_by_path( 'site-map' );
}

/**
 * Permanent redirects from page addresses that changed in a release,
 * listed in the manifest as 'was_slug'. WordPress keeps old slugs and
 * redirects them for posts, but not for pages, so without this any link
 * or search listing pointing at the old address would find a 404.
 */
function paulus_redirect_old_page_slugs() {
	if ( ! is_404() ) {
		return;
	}
	$request = trim( (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ), '/' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$base    = trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
	if ( '' !== $base && 0 === strpos( $request, $base . '/' ) ) {
		$request = substr( $request, strlen( $base ) + 1 );
	}
	foreach ( paulus_manifest()['pages'] as $item ) {
		// A page moved to a new parent: its old address under the old parent.
		if ( ! empty( $item['was_parent'] ) && $request === $item['was_parent'] . '/' . $item['slug'] ) {
			$page = get_page_by_path( ( $item['parent'] ?? '' ) . '/' . $item['slug'] );
			if ( $page && 'publish' === $page->post_status ) {
				wp_safe_redirect( get_permalink( $page ), 301 );
				exit;
			}
		}
		if ( empty( $item['was_slug'] ) ) {
			continue;
		}
		$prefix = ! empty( $item['parent'] ) ? $item['parent'] . '/' : '';
		if ( $request !== $prefix . $item['was_slug'] ) {
			continue;
		}
		$page = get_page_by_path( $prefix . $item['slug'] );
		if ( $page && 'publish' === $page->post_status ) {
			wp_safe_redirect( get_permalink( $page ), 301 );
			exit;
		}
	}
}
add_action( 'template_redirect', 'paulus_redirect_old_page_slugs', 1 );

/**
 * Footer brand block: the wordmark and a line about the site.
 *
 * The wordmark is the site title, so it follows Settings → General; the
 * description is its own Theme Options field rather than the WordPress
 * tagline, which many sites leave at the default or use for something else.
 *
 * @return string
 */
function paulus_sc_footer_brand() {
	$out  = '<div class="paulus-footer-brand">';
	// The site icon beside the name: the one set in the Customizer, else
	// the bundled icon. Decorative, and outside the link: only the wordmark
	// is linked.
	$icon  = has_site_icon() ? get_site_icon_url( 96 ) : PAULUS_URI . '/assets/images/site-icon-96.webp';
	$out .= '<p class="paulus-footer-brand__mark"><img class="paulus-footer-brand__icon" src="' . esc_url( $icon ) . '" alt="" width="48" height="48" loading="lazy" decoding="async"><a href="' . esc_url( home_url( '/' ) ) . '" rel="home">' . esc_html( get_bloginfo( 'name' ) ) . '</a></p>';
	// The site description: the Theme Options footer description, or, when
	// that is empty, the tagline from Settings, General. The publisher
	// credit follows in the same paragraph: "Published by Langgam Fikir
	// (Seri Kembangan, Selangor: 2025).", the place taken from the imprint
	// (Book tab) and the year from "First published".
	$blurb = trim( (string) paulus_option( 'footer_blurb' ) );
	if ( '' === $blurb ) {
		$blurb = trim( (string) get_bloginfo( 'description' ) );
	}
	$pub  = trim( (string) paulus_option( 'pub_name' ) );
	$name = '';
	if ( $pub ) {
		$name = paulus_option( 'pub_url' ) ? '<a href="' . esc_url( paulus_option( 'pub_url' ) ) . '" rel="noopener">' . esc_html( $pub ) . '</a>' : esc_html( $pub );
		// Place from the imprint ("Seri Kembangan, Selangor : Langgam Fikir,
		// 2025"), year from "First published" ("August 2025").
		$place = trim( (string) strtok( (string) paulus_option( 'cat_imprint' ), ':' ) );
		$year  = preg_match( '/\b(1[5-9]|20)\d{2}\b/', (string) paulus_option( 'book_first_pub' ), $y ) ? $y[0] : '';
		$where = implode( ': ', array_filter( array( $place, $year ) ) );
		if ( $where ) {
			$name .= ' (' . esc_html( $where ) . ')';
		}
	}
	$text = '' !== $blurb ? esc_html( rtrim( $blurb, " .\t\n" ) . '.' ) : '';
	if ( $name ) {
		/* translators: %s: publisher name, with place and year. */
		$text .= ( $text ? ' ' : '' ) . sprintf( esc_html__( 'Published by %s.', 'paulus' ), $name );
	}
	if ( $text ) {
		$out .= '<p class="paulus-footer-brand__blurb">' . $text . '</p>';
	}
	$out .= paulus_sc_footer_social();
	return $out . '</div>';
}
add_shortcode( 'paulus_footer_brand', 'paulus_sc_footer_brand' );

/**
 * Title panel for articles, pages and section archives.
 *
 * With an illustration, the image fills the left half and a dark panel the
 * right; without one, the panel runs full width. The panel carries a meta
 * line (breadcrumb), the title, the standfirst, a gold rule and the byline.
 *
 * @return string
 */
/**
 * Koine Greek inscriptions: one New Testament word or phrase above the
 * English heading of each section, the Answers and Verdict pages and the
 * 404 page. Ornament only: the English heading stays, the Greek is set in
 * uncials without accents, and a tooltip gives the meaning and the verse.
 *
 * @return array<string, array{0:string,1:string}> Key => Greek, gloss.
 */
function paulus_greek_marks() {
	// The one register of the site's Koine Greek. Each section or page has one
	// form, used wherever it is named: its title-panel inscription, the front
	// page, the header menu, the footer (The case column and the bar), the
	// breadcrumbs and the Sitemap. Each word is checked against the SBL Greek
	// New Testament.
	return array(
		'the-man'        => array( 'ΣΑΥΛΟΣ', __( 'Saul (Acts 13:9)', 'paulus' ) ),
		'the-charges'    => array( 'ΚΑΤΗΓΟΡΙΑ', __( 'Accusation (John 18:29)', 'paulus' ) ),
		'the-witnesses'  => array( 'ΟΙ ΜΑΡΤΥΡΕΣ', __( 'The witnesses (Acts 7:58)', 'paulus' ) ),
		'the-verdict'    => array( 'ΚΡΙΣΙΣ', __( 'Judgment (John 5:22)', 'paulus' ) ),
		'answers'        => array( 'ΑΠΟΛΟΓΙΑ', __( 'Defence (Acts 22:1)', 'paulus' ) ),
		'the-book'       => array( 'ΒΙΒΛΙΟΝ', __( 'The book (Luke 4:17)', 'paulus' ) ),
		'journal'        => array( 'ΓΡΑΦΩ', __( 'I write (1 John 2:1)', 'paulus' ) ),
		'privacy-policy' => array( 'ΚΑΤʼ ΙΔΙΑΝ', __( 'Privately (Mark 4:34)', 'paulus' ) ),
		'terms-of-use'   => array( 'ΟΡΟΘΕΣΙΑΙ', __( 'The bounds set (Acts 17:26)', 'paulus' ) ),
		'dmca'           => array( 'ΑΠΟΔΟΤΕ', __( 'Render to each his own (Matthew 22:21)', 'paulus' ) ),
		'contact'        => array( 'ΕΠΙΣΤΟΛΗ', __( 'A letter (Acts 15:30)', 'paulus' ) ),
		'sitemap'        => array( 'ΟΔΗΓΟΣ', __( 'A guide (Romans 2:19)', 'paulus' ) ),
		'404'            => array( 'ΑΠΟΛΩΛΩΣ', __( 'Lost (Luke 15:24)', 'paulus' ) ),
	);
}

/**
 * The inscription for a key, as markup, or an empty string.
 *
 * @param string $key   Section or page slug, or "404".
 * @param string $class Extra class.
 * @return string
 */
function paulus_greek_mark( $key, $class = '' ) {
	$marks = paulus_greek_marks();
	if ( ! isset( $marks[ $key ] ) ) {
		return '';
	}
	list( $greek, $gloss ) = $marks[ $key ];
	return '<span class="paulus-greek' . ( $class ? ' ' . esc_attr( $class ) : '' ) . '" lang="grc" title="' . esc_attr( $gloss ) . '">' . esc_html( $greek ) . '</span>';
}

function paulus_sc_page_hero() {
	$crumbs   = array( array( __( 'Home', 'paulus' ), home_url( '/' ) ) );
	$title    = '';
	$standfirst = '';
	$image    = '';
	$byline   = '';

	if ( is_search() ) {
		$title      = __( 'Search the case', 'paulus' );
		$standfirst = __( 'Every article, reference page and answer on the site, searched in full, footnotes included.', 'paulus' );
		$crumbs[]   = array( __( 'Search', 'paulus' ), '' );
	} elseif ( is_post_type_archive( 'paulus_journal' ) ) {
		$period = paulus_journal_period();
		$name   = (string) paulus_option( 'journal_title' );
		if ( $period ) {
			/* translators: 1: Journal title, 2: month and year, or year. */
			$title      = sprintf( __( '%1$s: %2$s', 'paulus' ), $name, $period );
			$crumbs[]   = array( $name, paulus_journal_url() );
			$month      = (int) get_query_var( 'journal_monthnum' );
			if ( $month ) {
				$year     = (int) get_query_var( 'journal_year' );
				$crumbs[] = array( (string) $year, paulus_journal_url( $year ) );
				$crumbs[] = array( date_i18n( 'F', mktime( 0, 0, 0, $month, 1, $year ) ), '' );
			} else {
				$crumbs[] = array( $period, '' );
			}
		} else {
			$title      = $name;
			$standfirst = (string) paulus_option( 'journal_intro' );
			$crumbs[]   = array( $name, '' );
		}
	} elseif ( is_category() ) {
		$term       = get_queried_object();
		$title      = $term->name;
		// The section's featured image: a bundled illustration named in the
		// manifest, with its alt text from the illustration list.
		foreach ( (array) ( paulus_manifest()['parts'] ?? array() ) as $p ) {
			if ( ( $p['slug'] ?? '' ) === $term->slug && ! empty( $p['image'] ) && array_key_exists( $p['image'], paulus_images() ) ) {
				$alts  = paulus_image_alts();
				$image = '<img src="' . esc_url( paulus_image_url( $p['image'] ) ) . '" alt="' . esc_attr( $alts[ $p['image'] ] ?? '' ) . '" width="1024" height="1024" fetchpriority="high">';
				break;
			}
		}
		$standfirst = wp_strip_all_tags( term_description( $term ) );
		$crumbs[]   = array( $term->name, '' );
	} elseif ( is_singular() ) {
		$post       = get_post();
		$title      = get_the_title( $post );
		$standfirst = has_excerpt( $post ) && ! is_page() ? get_the_excerpt( $post ) : '';
		if ( is_single() && (int) get_post_meta( $post->ID, '_paulus_parts', true ) > 1 ) {
			$series_title = get_post_meta( $post->ID, '_paulus_series_title', true );
			if ( $series_title ) {
				$standfirst = $series_title . '. ' . $standfirst;
			}
		}
		if ( is_singular( 'paulus_journal' ) ) {
			$year     = (int) get_the_date( 'Y', $post );
			$month    = (int) get_the_date( 'n', $post );
			$crumbs[] = array( (string) paulus_option( 'journal_title' ), paulus_journal_url() );
			$crumbs[] = array( get_the_date( 'F Y', $post ), paulus_journal_url( $year, $month ) );
			$byline   = paulus_option( 'book_author' );
		} elseif ( is_single() ) {
			$part  = paulus_part_of( $post->ID );
			$label = get_post_meta( $post->ID, '_paulus_label', true );
			if ( $part ) {
				$crumbs[] = array( $part->name, get_term_link( $part ) );
			}
			if ( $label ) {
				$crumbs[] = array( $label, '' );
			}
			$parts = (int) get_post_meta( $post->ID, '_paulus_parts', true );
			if ( $parts > 1 ) {
				/* translators: 1: part number, 2: number of parts. */
				$crumbs[] = array( sprintf( __( 'Part %1$s of %2$s', 'paulus' ), number_format_i18n( (int) get_post_meta( $post->ID, '_paulus_part_no', true ) ), number_format_i18n( $parts ) ), '' );
			}
			$byline = paulus_option( 'book_author' );
		} else {
			if ( $post->post_parent ) {
				$crumbs[] = array( get_the_title( $post->post_parent ), get_permalink( $post->post_parent ) );
			}
			// A page names itself as the last crumb, so the trail always
			// ends where the reader is.
			$crumbs[] = array( get_the_title( $post ), '' );
		}
		if ( has_post_thumbnail( $post ) ) {
			$image = get_the_post_thumbnail( $post, 'large', array( 'alt' => '', 'fetchpriority' => 'high' ) );
		}
	}
	if ( '' === $title ) {
		return '';
	}

	$meta = '<nav class="paulus-hero-panel__meta" aria-label="' . esc_attr__( 'Breadcrumb', 'paulus' ) . '"><ol>';
	$keyed = paulus_greek_urls();
	foreach ( $crumbs as $i => list( $label, $url ) ) {
		$last  = ( count( $crumbs ) - 1 === $i );
		$text  = esc_html( $label );
		$link  = $url && ! is_wp_error( $url ) ? (string) $url : '';
		$key   = $link ? ( $keyed[ untrailingslashit( $link ) ] ?? '' ) : '';
		if ( $link ) {
			// $tip, never $title: $title holds the page's own heading.
			$tip  = $key ? paulus_greek_title( $key ) : '';
			$text = '<a href="' . esc_url( $link ) . '"' . ( $tip ? ' title="' . esc_attr( $tip ) . '"' : '' ) . '>' . ( $key ? paulus_greek_label( $key, $text ) : $text ) . '</a>';
		}
		$meta .= '<li' . ( $last ? ' aria-current="page"' : '' ) . '>' . $text . '</li>';
	}
	$meta .= '</ol></nav>';

	$out  = '<header class="paulus-hero-panel' . ( $image ? ' has-image' : '' ) . '">';
	if ( $image ) {
		$out .= '<figure class="paulus-hero-panel__image">' . $image . '</figure>';
	}
	$out .= '<div class="paulus-hero-panel__body">' . $meta;
	$greek_key = is_category() ? get_queried_object()->slug : ( is_page() ? get_post_field( 'post_name', get_queried_object_id() ) : ( is_post_type_archive( 'paulus_journal' ) && ! paulus_journal_period() ? 'journal' : '' ) );
	$greek     = $greek_key ? paulus_greek_mark( $greek_key, 'paulus-greek--panel' ) : '';
	$out      .= ( $greek ? '<p class="paulus-greek-line">' . $greek . '</p>' : '' ) . '<h1 class="paulus-hero-panel__title">' . esc_html( $title ) . '</h1>';
	if ( $standfirst ) {
		$out .= '<p class="paulus-hero-panel__standfirst">' . esc_html( $standfirst ) . '</p>';
	}
	$out .= '<span class="paulus-hero-panel__rule" aria-hidden="true"></span>';
	if ( $byline ) {
		$out .= '<p class="paulus-hero-panel__byline"><span class="paulus-hero-panel__author">' . esc_html( $byline ) . '</span>';
		if ( is_singular( 'paulus_journal' ) ) {
			// An entry's date, with its reading time, on a line of its own under the name.
			$out .= ' <span class="paulus-hero-panel__time paulus-hero-panel__when"><time datetime="' . esc_attr( get_the_date( 'c' ) ) . '">' . esc_html( get_the_date( 'j F Y' ) ) . '</time> <span class="paulus-hero-panel__sep">·</span> ' . esc_html( paulus_reading_time( get_the_ID() ) ) . '</span>';
		} elseif ( is_single() ) {
			$out .= ' <span class="paulus-hero-panel__time"><span class="paulus-hero-panel__sep">·</span> ' . esc_html( paulus_reading_time( get_the_ID() ) ) . '</span>';
		}
		$out .= '</p>';
	}
	if ( is_single() ) {
		$out .= paulus_share_links( get_the_ID() );
	}
	$series = is_single() ? paulus_series_parts( get_the_ID() ) : null;
	if ( $series ) {
		$out .= '<nav class="paulus-hero-panel__series" aria-label="' . esc_attr__( 'Parts of this series', 'paulus' ) . '"><ol>';
		foreach ( $series['parts'] as $i => $p ) {
			$n    = number_format_i18n( $i + 1 );
			$out .= (int) $p->ID === get_the_ID()
				? '<li aria-current="page"><span class="paulus-hero-panel__part-n">' . esc_html( $n ) . '</span> ' . esc_html( get_the_title( $p ) ) . '</li>'
				: '<li><a href="' . esc_url( get_permalink( $p ) ) . '"><span class="paulus-hero-panel__part-n">' . esc_html( $n ) . '</span> ' . esc_html( get_the_title( $p ) ) . '</a></li>';
		}
		$out .= '</ol></nav>';
	}
	return $out . '</div></header>';
}
add_shortcode( 'paulus_page_hero', 'paulus_sc_page_hero' );

/**
 * About the author: one card, built only from Theme Options (Book tab) and
 * shown above the footer on every page, so the bio can never drift out of
 * step between pages the way a copy pasted into page content does.
 *
 * @return string
 */
function paulus_author_card( $placement = 'footer' ) {
	$bio  = trim( (string) paulus_option( 'author_bio' ) );
	$name = trim( (string) paulus_option( 'book_author' ) );
	if ( '' === $bio ) {
		return '';
	}

	// Monogram from the initials of every word of the name, at most four:
	// "Mohd Elfie Nieshaem Juferi" gives MENJ, the author's own mark.
	$mono = '';
	foreach ( preg_split( '/\s+/u', $name ) as $word ) {
		if ( '' !== $word ) {
			$mono .= paulus_ustrtoupper( paulus_usubstr( $word, 0, 1 ) );
		}
	}
	$mono = paulus_usubstr( $mono, 0, 4 );

	// Other books: one per line, "Title (Publisher, Year)". The part in the
	// closing brackets is set roman, the title in italics.
	$books = array();
	foreach ( preg_split( '/\R/u', (string) paulus_option( 'author_books' ) ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		if ( preg_match( '/^(.*?)\s*\(([^()]*)\)$/u', $line, $m ) ) {
			$books[] = '<li><cite>' . esc_html( $m[1] ) . '</cite> <span>(' . esc_html( $m[2] ) . ')</span></li>';
		} else {
			$books[] = '<li><cite>' . esc_html( $line ) . '</cite></li>';
		}
	}

	$out  = '<section class="paulus-author' . ( 'inline' === $placement ? ' paulus-author--inline' : '' ) . '" aria-labelledby="paulus-author-name"><div class="paulus-author__inner">';
	// The author's portrait in the medallion; the monogram only stands in
	// if the portrait file is missing.
	$portrait = paulus_author_portrait_url();
	if ( $portrait ) {
		$out .= '<div class="paulus-author__seal paulus-author__seal--photo"><img src="' . esc_url( $portrait ) . '" alt="' . esc_attr( $name ) . '" width="240" height="240" loading="lazy" decoding="async"></div>';
	} elseif ( $mono ) {
		$out .= '<div class="paulus-author__seal" aria-hidden="true"><span>' . esc_html( $mono ) . '</span></div>';
	}
	$out .= '<div class="paulus-author__body">';
	$out .= '<p class="paulus-author__label">' . esc_html__( 'About the author', 'paulus' ) . '</p>';
	// The name is a plain heading: the bio beneath opens with the same name,
	// linked to the author's site, so a second link to the same address
	// directly above it would only repeat it.
	$out .= '<h2 class="paulus-author__name" id="paulus-author-name">' . esc_html( $name ) . '</h2>';
	// The bio may carry links and light emphasis (see paulus_bio_kses()).
	$out .= '<p class="paulus-author__bio">' . paulus_author_bio_html( $bio ) . '</p>';
	if ( $books ) {
		$out .= '<details class="paulus-author__books"><summary>'
			/* translators: %d: number of books. */
			. esc_html( sprintf( _n( 'Other book by the author (%d)', 'Other books by the author (%d)', count( $books ), 'paulus' ), count( $books ) ) )
			. '</summary><ul>' . implode( '', $books ) . '</ul></details>';
	}
	return $out . '</div></div></section>';
}

/**
 * URL of the bundled author portrait, or '' when the file is absent.
 *
 * @return string
 */
function paulus_author_portrait_url() {
	return file_exists( PAULUS_DIR . '/assets/images/author-portrait.webp' ) ? PAULUS_URI . '/assets/images/author-portrait.webp' : '';
}

/**
 * The author bio as HTML, with its links inside the text.
 *
 * A bio that already contains links is used as written (filtered through
 * paulus_bio_kses()). A plain-text bio, as saved by earlier versions, gets
 * its links added in place: the first mention of the author's name, his
 * apologetics site, his YouTube channel and the publisher each become a
 * link to the address set for it in Theme Options. A bare
 * "bismikaallahuma.org" is shown by its name, "Bismika Allahuma".
 *
 * @param string $bio Bio from Theme Options.
 * @return string
 */
function paulus_author_bio_html( $bio ) {
	if ( false !== stripos( $bio, '<a ' ) ) {
		return wp_kses( $bio, paulus_bio_kses() );
	}
	$html  = esc_html( $bio );
	$links = array(
		array( array( (string) paulus_option( 'book_author' ) ), (string) paulus_option( 'author_home' ), '' ),
		array( array( 'bismikaallahuma.org', 'Bismika Allahuma' ), (string) paulus_option( 'author_url' ), 'Bismika Allahuma' ),
		array( array( 'The Muslim Apologist' ), (string) paulus_option( 'author_youtube' ), '' ),
		array( array( (string) paulus_option( 'pub_name' ) ), (string) paulus_option( 'pub_url' ), '' ),
	);
	foreach ( $links as list( $phrases, $url, $label ) ) {
		if ( '' === $url ) {
			continue;
		}
		foreach ( $phrases as $phrase ) {
			if ( '' === $phrase ) {
				continue;
			}
			$pattern = '/(?<![\w.\/>])' . preg_quote( esc_html( $phrase ), '/' ) . '(?![\w\/<])/u';
			$count   = 0;
			$html    = preg_replace_callback(
				$pattern,
				static function ( $m ) use ( $url, $label ) {
					return '<a href="' . esc_url( $url ) . '">' . ( '' !== $label ? esc_html( $label ) : $m[0] ) . '</a>';
				},
				$html,
				1,
				$count
			);
			if ( $count ) {
				break;
			}
		}
	}
	return $html;
}

/**
 * Whether the author card may still be placed on this request. The first
 * caller claims it, so the card appears once however many placements
 * are tried.
 *
 * @return bool
 */
function paulus_author_card_claim() {
	static $claimed = false;
	if ( $claimed || is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}
	$claimed = true;
	return true;
}

/**
 * On articles and the book page, the author card follows the text directly —
 * after its references, before the series list, the reading-order band,
 * related links and the book panel — which is where readers look for
 * who wrote what they have just read.
 *
 * It is attached to the post-content block as that renders, rather than
 * placed in each template, so templates customised in the Site Editor
 * (stored in the database, never seeing the theme's files) get it too.
 * Only the page's own post counts: post content rendered for some other
 * post inside a query loop is left alone.
 *
 * @param string   $content  Rendered block.
 * @param array    $block    Block data.
 * @param WP_Block $instance Block instance.
 * @return string
 */
function paulus_author_card_after_content( $content, $block, $instance = null ) {
	// Articles and the book page only: those are the author's own writing.
	// Listings, reference pages, search and 404 have nothing to attribute.
	if ( ! is_singular( 'post' ) && ! is_page( 'the-book' ) ) {
		return $content;
	}
	$post_id = ( $instance instanceof WP_Block ) ? (int) ( $instance->context['postId'] ?? 0 ) : 0;
	if ( $post_id && get_queried_object_id() !== $post_id ) {
		return $content;
	}
	if ( ! paulus_author_card_claim() ) {
		return $content;
	}
	return $content . paulus_author_card( 'inline' );
}
add_filter( 'render_block_core/post-content', 'paulus_author_card_after_content', 10, 3 );


/**
 * The old per-article shortcode. The card now appears on every page on its
 * own, so this renders nothing; it stays registered so a template that
 * still contains it (one customised in the Site Editor, say) neither shows
 * the card twice nor prints the bare shortcode text.
 *
 * @return string
 */
function paulus_sc_about_author() {
	return '';
}
add_shortcode( 'paulus_about_author', 'paulus_sc_about_author' );

/**
 * The other articles in the same section, as a plain list.
 *
 * @return string
 */
function paulus_sc_related() {
	$id   = get_the_ID();
	$part = paulus_part_of( $id );
	if ( ! $part ) {
		return '';
	}
	$items  = '';
	$own    = get_post_meta( $id, '_paulus_series', true );
	$seen   = array();
	foreach ( paulus_chapters( $part->term_id ) as $post ) {
		if ( (int) $post->ID === (int) $id ) {
			continue;
		}
		// Parts of this article's own chapter are already listed in the rail
		// (wide screens) or the title panel (narrower), with the next part in
		// the reading-order band; other chapters appear once, by their first
		// part and the chapter's title, as on the section page.
		$series = get_post_meta( $post->ID, '_paulus_series', true );
		if ( $series && ( $series === $own || isset( $seen[ $series ] ) ) ) {
			continue;
		}
		$title = get_the_title( $post );
		if ( $series ) {
			$seen[ $series ] = true;
			$title           = get_post_meta( $post->ID, '_paulus_series_title', true ) ?: $title;
		}
		$label  = get_post_meta( $post->ID, '_paulus_label', true );
		$items .= '<li><a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( $title ) . '</a>' . ( $label ? ' <span>' . esc_html( $label ) . '</span>' : '' ) . '</li>';
	}
	if ( '' === $items ) {
		return '';
	}
	/* translators: %s: section name. */
	$heading = sprintf( __( 'More from %s', 'paulus' ), $part->name );
	return '<section class="paulus-related"><h2 class="paulus-related__label">' . esc_html( $heading ) . '</h2><ul>' . $items . '</ul></section>';
}
add_shortcode( 'paulus_related', 'paulus_sc_related' );

/**
 * The parts of the series an article belongs to, in order.
 *
 * @param int $id Post ID.
 * @return array{title:string,parts:WP_Post[]}|null
 */
function paulus_series_parts( $id ) {
	$series = get_post_meta( $id, '_paulus_series', true );
	if ( ! $series ) {
		return null;
	}
	$parts = array();
	foreach ( paulus_chapters() as $c ) {
		if ( get_post_meta( $c->ID, '_paulus_series', true ) === $series ) {
			$parts[] = $c;
		}
	}
	return count( $parts ) > 1 ? array( 'title' => get_post_meta( $id, '_paulus_series_title', true ), 'parts' => $parts ) : null;
}

/**
 * Series list in the same form as "More from" below an article, so the
 * parts sit with the other reading links.
 *
 * @return string
 */
function paulus_sc_series_nav() {
	$id     = get_the_ID();
	$series = is_single() ? paulus_series_parts( $id ) : null;
	if ( ! $series ) {
		return '';
	}
	$items = '';
	foreach ( $series['parts'] as $i => $p ) {
		$n       = '<span>' . esc_html( sprintf( /* translators: %s: part number. */ __( 'Part %s', 'paulus' ), number_format_i18n( $i + 1 ) ) ) . '</span>';
		$items  .= (int) $p->ID === (int) $id
			? '<li class="is-current" aria-current="page">' . esc_html( get_the_title( $p ) ) . ' ' . $n . '</li>'
			: '<li><a href="' . esc_url( get_permalink( $p ) ) . '">' . esc_html( get_the_title( $p ) ) . '</a> ' . $n . '</li>';
	}
	/* translators: %s: series title. */
	$heading = sprintf( __( 'This series: %s', 'paulus' ), $series['title'] );
	return '<section class="paulus-related paulus-related--series"><h2 class="paulus-related__label">' . esc_html( $heading ) . '</h2><ul>' . $items . '</ul></section>';
}
add_shortcode( 'paulus_series_nav', 'paulus_sc_series_nav' );

/**
 * Side rail on wide screens: the parts of the series and jump links to the
 * apparatus (references, study questions, more from the section). The title
 * panel already carries the parts for narrower screens.
 *
 * @return string
 */
function paulus_sc_article_rail() {
	if ( ! is_single() ) {
		return '';
	}
	$id     = get_the_ID();
	$series = paulus_series_parts( $id );
	$roman  = array( 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X' );
	$out    = '<aside class="paulus-rail" aria-label="' . esc_attr__( 'In this chapter', 'paulus' ) . '"><div class="paulus-rail__inner">';
	$sections = paulus_article_sections( $id );
	if ( $sections ) {
		$out .= '<p class="paulus-rail__label">' . esc_html__( 'In this article', 'paulus' ) . '</p><ol class="paulus-rail__sections">';
		foreach ( $sections as $sec ) {
			$out .= '<li><a href="#' . esc_attr( $sec['id'] ) . '">' . esc_html( $sec['text'] ) . '</a></li>';
		}
		$out .= '</ol>';
	}
	if ( $series ) {
		$out .= '<p class="paulus-rail__label">' . esc_html__( 'In this chapter', 'paulus' ) . '</p><ol class="paulus-rail__parts">';
		foreach ( $series['parts'] as $i => $p ) {
			$n    = '<span class="paulus-rail__n">' . esc_html( $roman[ $i ] ?? (string) ( $i + 1 ) ) . '</span>';
			$out .= (int) $p->ID === (int) $id
				? '<li class="is-current" aria-current="page">' . $n . esc_html( get_the_title( $p ) ) . '</li>'
				: '<li><a href="' . esc_url( get_permalink( $p ) ) . '">' . $n . esc_html( get_the_title( $p ) ) . '</a></li>';
		}
		$out .= '</ol>';
	}
	$jumps = array();
	if ( false !== strpos( get_post_field( 'post_content', $id ), 'class="footnotes"' ) ) {
		$jumps[] = array( __( 'References', 'paulus' ), '#paulus-references' );
	}
	$jumps[] = array( __( 'Reading order', 'paulus' ), '#paulus-readnav' );
	$part    = paulus_part_of( $id );
	if ( $part ) {
		$jumps[] = array( $part->name, get_term_link( $part ) );
	}
	$out .= '<p class="paulus-rail__label">' . esc_html__( 'Apparatus', 'paulus' ) . '</p><ul class="paulus-rail__jumps">';
	foreach ( $jumps as list( $label, $href ) ) {
		if ( is_wp_error( $href ) ) {
			continue;
		}
		$out .= '<li><a href="' . esc_url( $href ) . '">' . esc_html( $label ) . '</a></li>';
	}
	return $out . '</ul></div></aside>';
}
add_shortcode( 'paulus_article_rail', 'paulus_sc_article_rail' );

/**
 * Anchors for the rail: an id on the footnotes list and on the reading nav.
 *
 * @param string $content Post content.
 * @return string
 */
function paulus_apparatus_anchors( $content ) {
	if ( is_single() ) {
		$content = str_replace( '<ol class="footnotes">', '<ol class="footnotes" id="paulus-references">', $content );
	}
	return $content;
}
add_filter( 'the_content', 'paulus_apparatus_anchors', 8 );

/**
 * Glossary: a letter index above the list, and an id on every term, so a
 * reader can jump by initial. Applied to the Reference glossary page only.
 *
 * @param string $content Post content.
 * @return string
 */
function paulus_glossary_index( $content ) {
	if ( ! is_page() || 'glossary' !== get_post_field( 'post_name' ) || false === strpos( $content, 'paulus-glossary' ) ) {
		return $content;
	}
	$letters = array();
	$content = preg_replace_callback(
		'#<dt>(.*?)</dt>#s',
		function ( $m ) use ( &$letters ) {
			$plain  = wp_strip_all_tags( $m[1] );
			$plain  = preg_replace( '/^[ʿʾ\s]+/u', '', $plain );
			$letter = paulus_ustrtoupper( paulus_usubstr( remove_accents( $plain ), 0, 1 ) );
			$id     = 'g-' . $letter;
			if ( ! isset( $letters[ $letter ] ) ) {
				$letters[ $letter ] = true;
				return '<dt id="' . esc_attr( $id ) . '">' . $m[1] . '</dt>';
			}
			return $m[0];
		},
		$content
	);
	$nav = '<nav class="paulus-alpha" aria-label="' . esc_attr__( 'Jump to letter', 'paulus' ) . '">';
	foreach ( range( 'A', 'Z' ) as $l ) {
		$nav .= isset( $letters[ $l ] ) ? '<a href="#g-' . $l . '">' . $l . '</a>' : '<span>' . $l . '</span>';
	}
	$nav .= '</nav>';
	return str_replace( '<dl class="paulus-glossary">', $nav . '<dl class="paulus-glossary">', $content );
}
add_filter( 'the_content', 'paulus_glossary_index', 9 );

/**
 * Reading time for a post, at 220 words a minute, as a sentence.
 *
 * @param int $id Post ID.
 * @return string
 */
function paulus_reading_time( $id ) {
	$words = str_word_count( wp_strip_all_tags( get_post_field( 'post_content', $id ) ) );
	$mins  = max( 1, (int) round( $words / 220 ) );
	/* translators: %s: number of minutes. */
	return sprintf( _n( 'About %s minute', 'About %s minutes', $mins, 'paulus' ), number_format_i18n( $mins ) );
}

/**
 * Share links: Facebook, X, WhatsApp and email, as plain links with inline
 * line-drawn marks. No scripts, no tracking.
 *
 * @param int $id Post ID.
 * @return string
 */
function paulus_share_links( $id ) {
	$url   = rawurlencode( get_permalink( $id ) );
	$title = rawurlencode( get_the_title( $id ) );
	$links = array(
		'facebook' => array( __( 'Share on Facebook', 'paulus' ), 'https://www.facebook.com/sharer/sharer.php?u=' . $url ),
		'x'        => array( __( 'Share on X', 'paulus' ), 'https://twitter.com/intent/tweet?url=' . $url . '&text=' . $title ),
		'whatsapp' => array( __( 'Share on WhatsApp', 'paulus' ), 'https://wa.me/?text=' . $title . '%20' . $url ),
		'telegram' => array( __( 'Share on Telegram', 'paulus' ), 'https://t.me/share/url?url=' . $url . '&text=' . $title ),
		'email'    => array( __( 'Share by email', 'paulus' ), 'mailto:?subject=' . $title . '&body=' . $url ),
	);
	$out = '<div class="paulus-share"><span class="paulus-share__label">' . esc_html__( 'Share', 'paulus' ) . '</span>';
	// The social links below skip themselves when their icon can't be
	// read; these two buttons always render, so they fall back to a short
	// visible label rather than an empty circle.
	$print_icon = paulus_icon( 'print' ) ?: '<span class="paulus-share__text">' . esc_html__( 'Print', 'paulus' ) . '</span>';
	$pdf_icon   = paulus_icon( 'pdf' ) ?: '<span class="paulus-share__text">PDF</span>';
	$out .= '<button type="button" class="paulus-share__link paulus-share__link--print" aria-label="' . esc_attr__( 'Print this article', 'paulus' ) . '" title="' . esc_attr__( 'Print', 'paulus' ) . '" data-paulus-print>' . $print_icon . '</button>';
	$out .= '<button type="button" class="paulus-share__link paulus-share__link--pdf" aria-label="' . esc_attr__( 'Save this article as a PDF', 'paulus' ) . '" title="' . esc_attr__( 'Save as PDF', 'paulus' ) . '" data-paulus-pdf data-filename="' . esc_attr( get_post_field( 'post_name', $id ) . '.pdf' ) . '" data-src="' . esc_url( PAULUS_URI . '/assets/js/vendor/html2pdf.bundle.min.js?ver=' . PAULUS_VERSION ) . '">' . $pdf_icon . '</button>';
	if ( paulus_option( 'read_copy' ) ) {
		$link_icon = paulus_icon( 'link' ) ?: '<span class="paulus-share__text">' . esc_html__( 'Link', 'paulus' ) . '</span>';
		$out      .= '<button type="button" class="paulus-share__link paulus-share__link--copy" aria-label="' . esc_attr__( 'Copy the link to this page', 'paulus' ) . '" title="' . esc_attr__( 'Copy link', 'paulus' ) . '" data-paulus-copy data-url="' . esc_url( get_permalink( $id ) ) . '">' . $link_icon . '<span class="paulus-share__live screen-reader-text" aria-live="polite"></span></button>';
	}
	foreach ( $links as $key => $l ) {
		$icon = paulus_icon( $key );
		if ( ! $icon ) {
			continue;
		}
		$out .= '<a class="paulus-share__link paulus-share__link--' . esc_attr( $key ) . '" href="' . esc_url( $l[1] ) . '" target="_blank" rel="noopener" aria-label="' . esc_attr( $l[0] ) . '" title="' . esc_attr( $l[0] ) . '">' . $icon . '</a>';
	}
	return $out . '</div>';
}

/**
 * An icon from assets/icons (the Minimalist Social Icons pack, plus the
 * theme's own email mark), inlined with its title removed and its fill set
 * to the current colour.
 *
 * @param string $name File name without extension.
 * @return string SVG markup, or empty.
 */
function paulus_icon( $name ) {
	static $cache = array();
	if ( isset( $cache[ $name ] ) ) {
		return $cache[ $name ];
	}
	// "social/mastodon" reaches the social set. Each segment must be a plain
	// slug, so no path can escape the icon folder. sanitize_file_name() is
	// not used here: it rewrites a bare name that is also a file extension
	// ("pdf") to "unnamed-file.pdf", which hid the PDF mark.
	$parts = explode( '/', (string) $name );
	foreach ( $parts as $part ) {
		if ( ! preg_match( '/^[a-z0-9_-]+$/i', $part ) ) {
			return $cache[ $name ] = '';
		}
	}
	$file  = PAULUS_DIR . '/assets/icons/' . implode( '/', array_filter( $parts ) ) . '.svg';
	if ( ! file_exists( $file ) ) {
		return $cache[ $name ] = '';
	}
	$svg = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	$svg = preg_replace( '#<title>.*?</title>#s', '', $svg );
	// Some marks in the icon pack hard-code black; drawn in the text colour
	// instead, like the rest, they follow the active scheme.
	$svg = preg_replace( '#\bfill=(["\'])(?:\#000000|\#000|black)\1#i', 'fill="currentColor"', $svg );
	$svg = preg_replace( '#<\?xml.*?\?>#s', '', $svg );
	// Drop any size the file declares on its root (the PDF mark ships at
	// 75mm): a browser keeps the first of two duplicate attributes, so the
	// 16px set below would otherwise be ignored.
	$svg = preg_replace_callback(
		'#<svg\b([^>]*)>#',
		static function ( $m ) {
			$attrs = preg_replace( '#\s(?:width|height)=(["\'])[^"\']*\1#i', '', $m[1] );
			return '<svg' . $attrs . ' width="16" height="16" fill="currentColor" aria-hidden="true" focusable="false">';
		},
		$svg,
		1
	);
	return $cache[ $name ] = trim( $svg );
}

/**
 * Section headings of an article, for the rail, with the ids the content
 * filter below gives them.
 *
 * @param int $id Post ID.
 * @return array<int, array{id:string,text:string}>
 */
function paulus_article_sections( $id ) {
	$content = get_post_field( 'post_content', $id );
	if ( ! preg_match_all( '#<h2(\s[^>]*)?>(.*?)</h2>#s', $content, $m, PREG_SET_ORDER ) ) {
		return array();
	}
	$out  = array();
	$seen = array();
	foreach ( $m as $match ) {
		$attr = $match[1] ?? '';
		$text = wp_strip_all_tags( $match[2] );
		// A heading that already carries an id (a manual anchor added in
		// the editor, say) keeps it: paulus_section_ids() below never
		// overwrites an existing id, so computing a different one here
		// would give the rail a link to an id nothing on the page has.
		if ( preg_match( '#\bid=["\']([^"\']+)["\']#', $attr, $existing ) ) {
			$out[] = array( 'id' => $existing[1], 'text' => $text );
			continue;
		}
		$slug  = sanitize_title( remove_accents( $text ) ) ?: 'section';
		$n     = $seen[ $slug ] = ( $seen[ $slug ] ?? 0 ) + 1;
		$out[] = array( 'id' => 's-' . $slug . ( $n > 1 ? '-' . $n : '' ), 'text' => $text );
	}
	return $out;
}

/**
 * Give every h2 in an article the id the rail links to.
 *
 * @param string $content Post content.
 * @return string
 */
function paulus_section_ids( $content ) {
	if ( ! is_single() || ! in_the_loop() ) {
		return $content;
	}
	$sections = paulus_article_sections( get_the_ID() );
	if ( ! $sections ) {
		return $content;
	}
	$i = 0;
	return preg_replace_callback(
		'#<h2(\s[^>]*)?>#',
		function ( $m ) use ( &$i, $sections ) {
			$attr = $m[1] ?? '';
			if ( false !== strpos( $attr, 'id=' ) || ! isset( $sections[ $i ] ) ) {
				$i++;
				return $m[0];
			}
			return '<h2 id="' . esc_attr( $sections[ $i++ ]['id'] ) . '"' . $attr . '>';
		},
		$content
	);
}
add_filter( 'the_content', 'paulus_section_ids', 7 );

/**
 * The Answers page: an index of its questions under the introduction, a
 * link mark on each question heading for readers to share, and a way back
 * to the index after each answer. Built from the headings as the page
 * renders, so an edited copy of the page gets them too.
 *
 * @param string $content Rendered content.
 * @return string
 */
function paulus_answers_navigation( $content ) {
	if ( ! is_page( 'answers' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	if ( ! preg_match_all( '#<h2 id="([^"]+)"[^>]*>(.*?)</h2>#s', $content, $m, PREG_SET_ORDER ) || count( $m ) < 2 ) {
		return $content;
	}
	$items = '';
	foreach ( $m as $h ) {
		$items .= '<li><a href="#' . esc_attr( $h[1] ) . '">' . wp_kses( $h[2], array( 'em' => array() ) ) . '</a></li>';
	}
	/* translators: %s: number of questions. */
	$index = '<nav class="paulus-faq-index" id="questions" aria-labelledby="paulus-faq-index-label"><p class="paulus-faq-index__label" id="paulus-faq-index-label">' . esc_html( sprintf( _n( '%s question', '%s questions', count( $m ), 'paulus' ), number_format_i18n( count( $m ) ) ) ) . '</p><ol>' . $items . '</ol></nav>';
	$back  = '<p class="paulus-faq-back"><a href="#questions">' . esc_html__( 'All questions', 'paulus' ) . ' <span aria-hidden="true">↑</span></a></p>';
	$first = true;
	$content = preg_replace_callback(
		'#<h2 id="([^"]+)"([^>]*)>(.*?)</h2>#s',
		static function ( $h ) use ( &$first, $back ) {
			$mark  = '<a class="paulus-anchor" href="#' . esc_attr( $h[1] ) . '" aria-label="' . esc_attr__( 'Link to this question', 'paulus' ) . '">#</a>';
			$lead  = $first ? '' : $back;
			$first = false;
			return $lead . '<h2 id="' . $h[1] . '"' . $h[2] . '>' . $h[3] . ' ' . $mark . '</h2>';
		},
		$content
	);
	// The last answer's way back sits before the references.
	$content = preg_match( '#<ol class="footnotes"#', $content ) ? preg_replace( '#<ol class="footnotes"#', $back . '<ol class="footnotes"', $content, 1 ) : $content . $back;
	// The index follows the introduction, which is the first paragraph.
	return preg_replace( '#</p>#', '</p>' . $index, $content, 1 );
}
add_filter( 'the_content', 'paulus_answers_navigation', 20 );

/**
 * [paulus_footer_legal] The footer's secondary bar, at its foot: Privacy
 * Policy, Terms of Use, DMCA, Contact and Sitemap. Each link appears once
 * its page is published, so a page still in draft is never linked.
 *
 * @return string
 */
function paulus_sc_footer_legal() {
	// Each term is replaced on hover by its Koine Greek from the register.
	$items = array();
	foreach ( array( 'privacy-policy', 'terms-of-use', 'dmca', 'contact', 'sitemap' ) as $slug ) {
		if ( 'privacy-policy' === $slug && (int) get_option( 'wp_page_for_privacy_policy' ) ) {
			$page = get_post( (int) get_option( 'wp_page_for_privacy_policy' ) );
		} elseif ( 'sitemap' === $slug ) {
			$page = paulus_sitemap_page();
		} else {
			$page = get_page_by_path( $slug );
		}
		if ( $page && 'publish' === $page->post_status ) {
			$current = is_page( $page->ID ) ? ' aria-current="page"' : '';
			$title   = paulus_greek_title( $slug );
			$items[] = '<li><a href="' . esc_url( get_permalink( $page ) ) . '"' . $current . ( $title ? ' title="' . esc_attr( $title ) . '"' : '' ) . '>'
				. paulus_greek_label( $slug, esc_html( get_the_title( $page ) ) ) . '</a></li>';
		}
	}
	if ( ! $items ) {
		return '';
	}
	return '<nav class="paulus-footer-legal" aria-label="' . esc_attr__( 'Site information', 'paulus' ) . '"><ul>' . implode( '', $items ) . '</ul></nav>';
}
add_shortcode( 'paulus_footer_legal', 'paulus_sc_footer_legal' );

/**
 * The Koine Greek that replaces each main-menu term on hover, by the section
 * or page the link points to (so a renamed label keeps its Greek). The
 * sections and the verdict take the Greek inscribed on their own pages;
 * each word is checked against the SBL Greek New Testament.
 *
 * @return array<string, array{0:string,1:string}>
 */
function paulus_nav_greek_words() {
	return paulus_greek_marks();
}

/**
 * A label and its Greek in one cell: the English shows, and on hover or
 * focus the Greek replaces it in the same place. Screen readers hear the
 * English only.
 *
 * @param string $label Label markup.
 * @param string $greek Greek.
 * @return string
 */
function paulus_greek_label( $key, $label ) {
	$marks = paulus_greek_marks();
	return isset( $marks[ $key ] ) ? paulus_greekswap( $label, $marks[ $key ][0] ) : $label;
}

/**
 * The tooltip for a key: the Greek, its sense and verse.
 *
 * @param string $key Register key.
 * @return string
 */
function paulus_greek_title( $key ) {
	$marks = paulus_greek_marks();
	return isset( $marks[ $key ] ) ? $marks[ $key ][0] . ': ' . $marks[ $key ][1] : '';
}

/**
 * A label and its Greek in one cell (see paulus_greek_label()).
 *
 * @param string $label Label markup.
 * @param string $greek Greek.
 * @return string
 */
function paulus_greekswap( $label, $greek ) {
	return '<span class="paulus-greekswap"><span class="paulus-greekswap__en">' . $label . '</span><span class="paulus-greekswap__gr" lang="grc" aria-hidden="true">' . esc_html( $greek ) . '</span></span>';
}

/**
 * Main-menu links: the Koine Greek replaces the term on hover.
 *
 * @param string $html  Rendered link.
 * @param array  $block Block.
 * @return string
 */
function paulus_nav_greek( $html, $block ) {
	$a    = $block['attrs'] ?? array();
	$id   = (int) ( $a['id'] ?? 0 );
	$slug = '';
	if ( $id && 'taxonomy' === ( $a['kind'] ?? '' ) ) {
		$term = get_term( $id, ! empty( $a['type'] ) && 'tag' !== $a['type'] ? $a['type'] : 'post_tag' );
		$slug = $term && ! is_wp_error( $term ) ? $term->slug : '';
	} elseif ( $id && 'post-type' === ( $a['kind'] ?? '' ) ) {
		$slug = (string) get_post_field( 'post_name', $id );
	}
	$words = paulus_nav_greek_words();
	if ( '' === $slug || empty( $words[ $slug ] ) || false !== strpos( $html, 'paulus-greekswap' ) ) {
		return $html;
	}
	list( $greek, $sense ) = $words[ $slug ];
	$html = preg_replace_callback(
		'#(<span class="wp-block-navigation-item__label">)(.*?)(</span>)#s',
		static function ( $m ) use ( $greek ) {
			return $m[1] . paulus_greekswap( $m[2], $greek ) . $m[3];
		},
		$html,
		1
	);
	return preg_replace( '#<a (?![^>]*\btitle=)#', '<a title="' . esc_attr( $greek . ': ' . $sense ) . '" ', $html, 1 );
}
add_filter( 'render_block_core/navigation-link', 'paulus_nav_greek', 10, 2 );

/**
 * The addresses of the sections and pages in the Greek register, so a link
 * to one (a breadcrumb, say) can carry its Greek.
 *
 * @return array<string, string> Address without trailing slash => key.
 */
function paulus_greek_urls() {
	static $map = null;
	if ( null !== $map ) {
		return $map;
	}
	$map = array();
	foreach ( paulus_parts() as $part ) {
		$link = get_term_link( $part );
		if ( ! is_wp_error( $link ) ) {
			$map[ untrailingslashit( $link ) ] = $part->slug;
		}
	}
	foreach ( array( 'the-verdict', 'answers', 'the-book', 'privacy-policy', 'terms-of-use', 'dmca', 'contact', 'sitemap' ) as $slug ) {
		$page = get_page_by_path( $slug );
		if ( $page && 'publish' === $page->post_status ) {
			$map[ untrailingslashit( get_permalink( $page ) ) ] = $slug;
		}
	}
	if ( function_exists( 'paulus_journal_url' ) && post_type_exists( 'paulus_journal' ) ) {
		$map[ untrailingslashit( paulus_journal_url() ) ] = 'journal';
	}
	return $map;
}
