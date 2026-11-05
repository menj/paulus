<?php
/**
 * The article page: the pieces paulus did not yet have.
 *
 * The title panel ([paulus_page_hero]) already carries the breadcrumb, the
 * title, the excerpt, the byline and the reading time; the text already has
 * its contents tablet, its references and the reading-order band. This file
 * adds what was missing:
 *
 * - the dates in the byline: published, and updated when the article was
 *   changed on a later day;
 * - a "Last reviewed" line, from the date in the Article fields box;
 * - "Key takeaways", the article's points, above the contents tablet;
 * - one line in the middle of the text pointing to the next step: the
 *   article's study questions, or else the next article in reading order;
 * - the study questions as an FAQPage, built from the exact questions, only
 *   on a page that shows them.
 *
 * No new piece sells anything. [paulus_book_teaser] stays where it is, as
 * the owner decided on 2026-11-05, and nothing here adds another.
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

/** Post meta: the key takeaways, one a line. */
const PAULUS_ARTICLE_POINTS_KEY = '_paulus_takeaways';

/** Post meta: the date the article was last checked against its sources, Y-m-d. */
const PAULUS_ARTICLE_REVIEWED_KEY = '_paulus_reviewed';

/* ---------------------------------------------------------------------
 * Helpers
 * ------------------------------------------------------------------ */

/**
 * Whether this is an article of the case file (a post), not a Journal entry.
 *
 * @return bool
 */
function paulus_article_applies() {
	return is_singular( 'post' );
}

/**
 * Lines of text, trimmed, blanks dropped.
 *
 * @param string $text One a line.
 * @return string[]
 */
function paulus_article_lines( $text ) {
	return array_values( array_filter( array_map( 'trim', (array) preg_split( '/\r\n|\r|\n/', (string) $text ) ), 'strlen' ) );
}

/**
 * Whether a string is a real calendar date written Y-m-d.
 *
 * @param string $date Date.
 * @return bool
 */
function paulus_article_valid_date( $date ) {
	return (bool) preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', (string) $date, $m ) && checkdate( (int) $m[2], (int) $m[3], (int) $m[1] );
}

/* ---------------------------------------------------------------------
 * The byline: dates and "Last reviewed"
 * ------------------------------------------------------------------ */

/**
 * The dates for the title panel's byline, on a line of their own under the
 * name: published, and updated when the article was changed on a later day
 * than it went up. Then, on another line, "Last reviewed" when the Key
 * takeaways and review box has a date.
 *
 * @param int $id Post ID.
 * @return string
 */
function paulus_article_dates_html( $id ) {
	$sep = ' <span class="paulus-hero-panel__sep" aria-hidden="true">·</span> ';
	$out = '<span class="paulus-hero-panel__dates"><span class="paulus-hero-panel__date">' . esc_html__( 'Published', 'paulus' ) . ' <time datetime="' . esc_attr( (string) get_the_date( 'c', $id ) ) . '">' . esc_html( (string) get_the_date( 'j F Y', $id ) ) . '</time></span>';
	if ( get_the_modified_date( 'Y-m-d', $id ) !== get_the_date( 'Y-m-d', $id ) && (int) get_post_modified_time( 'U', true, $id ) > (int) get_post_time( 'U', true, $id ) ) {
		$out .= $sep . '<span class="paulus-hero-panel__date">' . esc_html__( 'Updated', 'paulus' ) . ' <time datetime="' . esc_attr( (string) get_the_modified_date( 'c', $id ) ) . '">' . esc_html( (string) get_the_modified_date( 'j F Y', $id ) ) . '</time></span>';
	}
	$out     .= '</span>';
	$reviewed = (string) get_post_meta( $id, PAULUS_ARTICLE_REVIEWED_KEY, true );
	if ( paulus_article_valid_date( $reviewed ) ) {
		// Noon on that day in the site's time zone, so the date shown is the date saved.
		$day  = DateTimeImmutable::createFromFormat( '!Y-m-d H:i', $reviewed . ' 12:00', wp_timezone() );
		$out .= '<span class="paulus-hero-panel__reviewed">' . esc_html__( 'Last reviewed', 'paulus' ) . ' <time datetime="' . esc_attr( $reviewed ) . '">' . esc_html( wp_date( 'j F Y', $day ? $day->getTimestamp() : 0 ) ) . '</time></span>';
	}
	return $out;
}

/* ---------------------------------------------------------------------
 * Key takeaways and the mid-article next step
 * ------------------------------------------------------------------ */

/**
 * The key takeaways of an article.
 *
 * @param int $id Post ID.
 * @return string[]
 */
function paulus_article_points( $id ) {
	return paulus_article_lines( (string) get_post_meta( $id, PAULUS_ARTICLE_POINTS_KEY, true ) );
}

/**
 * The key takeaways box, or ''.
 *
 * @param int $id Post ID.
 * @return string
 */
function paulus_article_takeaways_html( $id ) {
	if ( ! paulus_option( 'article_takeaways' ) ) {
		return '';
	}
	$points = paulus_article_points( $id );
	if ( ! $points ) {
		return '';
	}
	$out = '<section class="paulus-takeaways" aria-labelledby="paulus-takeaways-label"><p class="paulus-takeaways__label" id="paulus-takeaways-label">' . esc_html__( 'Key takeaways', 'paulus' ) . '</p><ul>';
	foreach ( $points as $point ) {
		$out .= '<li>' . esc_html( $point ) . '</li>';
	}
	return $out . '</ul></section>';
}

/**
 * The slug of the study-question set an article points to: its own, or for
 * a part of a series without its own, the nearest earlier part that has one,
 * else the first later one. The same rule the reading-order band uses.
 *
 * @param int            $id       Post ID.
 * @param WP_Post[]|null $chapters The reading order; paulus_chapters() when null.
 * @return string
 */
function paulus_article_questions_anchor( $id, $chapters = null ) {
	if ( get_post_meta( $id, '_paulus_questions', true ) ) {
		return (string) get_post_field( 'post_name', $id );
	}
	$series = get_post_meta( $id, '_paulus_series', true );
	if ( ! $series ) {
		return '';
	}
	$before = '';
	$after  = '';
	$passed = false;
	foreach ( null === $chapters ? paulus_chapters() : $chapters as $c ) {
		if ( (int) $c->ID === (int) $id ) {
			$passed = true;
			continue;
		}
		if ( get_post_meta( $c->ID, '_paulus_series', true ) !== $series || ! get_post_meta( $c->ID, '_paulus_questions', true ) ) {
			continue;
		}
		if ( ! $passed ) {
			$before = $c->post_name;
		} elseif ( '' === $after ) {
			$after = $c->post_name;
		}
	}
	return '' !== $before ? $before : $after;
}

/**
 * The next article in reading order, or null.
 *
 * @param int $id Post ID.
 * @return WP_Post|null
 */
function paulus_article_next( $id ) {
	$chapters = paulus_chapters();
	$ids      = array_map( 'intval', wp_list_pluck( $chapters, 'ID' ) );
	$pos      = array_search( (int) $id, $ids, true );
	return ( false !== $pos && isset( $chapters[ $pos + 1 ] ) ) ? $chapters[ $pos + 1 ] : null;
}

/**
 * The one-line next step in the middle of the text: the article's study
 * questions, or the next article in reading order. Never a sale.
 *
 * @param int $id Post ID.
 * @return string
 */
function paulus_article_mid_next_html( $id ) {
	if ( ! paulus_option( 'article_mid_next' ) ) {
		return '';
	}
	$qs     = get_page_by_path( 'reference/study-questions' );
	$anchor = $qs && 'publish' === $qs->post_status ? paulus_article_questions_anchor( $id ) : '';
	if ( '' !== $anchor ) {
		$line = sprintf(
			/* translators: %s: link to the study questions for this article. */
			esc_html__( 'Test the case as you read: %s ask what the evidence shows, one step at a time.', 'paulus' ),
			'<a href="' . esc_url( get_permalink( $qs ) . '#' . $anchor ) . '">' . esc_html__( 'the study questions for this article', 'paulus' ) . '</a>'
		);
	} else {
		$next = paulus_article_next( $id );
		if ( ! $next ) {
			return '';
		}
		$line = sprintf(
			/* translators: %s: link to the next article. */
			esc_html__( 'Next in the reading order: %s.', 'paulus' ),
			'<a href="' . esc_url( get_permalink( $next ) ) . '">' . esc_html( get_the_title( $next ) ) . '</a>'
		);
	}
	return '<p class="paulus-next-step"><span class="paulus-next-step__mark" aria-hidden="true">?</span> ' . $line . '</p>';
}

/**
 * Put a piece of markup before the third section of the text, and before the
 * "Back to top" link that leads into it. Nothing when the text has fewer than
 * three sections.
 *
 * @param string $html  Rendered text.
 * @param string $piece Markup.
 * @return string
 */
function paulus_article_insert_mid( $html, $piece ) {
	if ( '' === $piece || ! preg_match_all( '#(?:<p class="paulus-faq-back">.*?</p>\s*)?<h2[\s>]#s', (string) $html, $found, PREG_OFFSET_CAPTURE ) || count( $found[0] ) < 3 ) {
		return (string) $html;
	}
	$at = (int) $found[0][2][1];
	return substr( $html, 0, $at ) . $piece . substr( $html, $at );
}

/**
 * Put the takeaways where the text's own apparatus begins: above the
 * contents tablet, else above the first section, else at the end of the text.
 *
 * @param string $html  Rendered text.
 * @param string $piece Markup.
 * @return string
 */
function paulus_article_insert_takeaways( $html, $piece ) {
	if ( '' === $piece ) {
		return (string) $html;
	}
	foreach ( array( '<nav class="paulus-faq-index paulus-contents"', '<h2' ) as $needle ) {
		$at = strpos( (string) $html, $needle );
		if ( false !== $at ) {
			return substr( $html, 0, $at ) . $piece . substr( $html, $at );
		}
	}
	return $html . $piece;
}

/**
 * The text of an article: the takeaways and the mid-article next step.
 * Runs after the contents tablet (20), so both find it in place.
 *
 * @param string $content Rendered text.
 * @return string
 */
function paulus_article_filter_content( $content ) {
	if ( ! paulus_article_applies() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$id      = (int) get_the_ID();
	$content = paulus_article_insert_mid( (string) $content, paulus_article_mid_next_html( $id ) );
	return paulus_article_insert_takeaways( $content, paulus_article_takeaways_html( $id ) );
}
add_filter( 'the_content', 'paulus_article_filter_content', 21 );

/* ---------------------------------------------------------------------
 * The study questions as an FAQPage
 * ------------------------------------------------------------------ */

/**
 * The study questions a text shows: each `<ol class="paulus-questions">`
 * and its items, under the heading that introduces the set.
 *
 * @param string $html Stored text.
 * @return array<int,array{anchor:string,set:string,questions:string[]}>
 */
function paulus_article_question_sets( $html ) {
	if ( false === strpos( (string) $html, 'paulus-questions' ) ) {
		return array();
	}
	$parts = preg_split( '#(<h2\b[^>]*>.*?</h2>)#s', (string) $html, -1, PREG_SPLIT_DELIM_CAPTURE );
	$sets  = array();
	$head  = array( '', '' );
	foreach ( (array) $parts as $part ) {
		if ( preg_match( '#^<h2\b([^>]*)>(.*?)</h2>$#s', $part, $h ) ) {
			$head = array( preg_match( '#\bid=["\']([^"\']+)["\']#', $h[1], $idm ) ? $idm[1] : '', paulus_article_plain( $h[2] ) );
			continue;
		}
		if ( ! preg_match_all( '#<ol\b[^>]*\bclass=["\'][^"\']*\bpaulus-questions\b[^"\']*["\'][^>]*>(.*?)</ol>#s', $part, $lists ) ) {
			continue;
		}
		foreach ( $lists[1] as $list ) {
			preg_match_all( '#<li\b[^>]*>(.*?)</li>#s', $list, $items );
			$questions = array_values( array_filter( array_map( 'paulus_article_plain', $items[1] ), 'strlen' ) );
			if ( $questions ) {
				$sets[] = array(
					'anchor'    => $head[0],
					'set'       => $head[1],
					'questions' => $questions,
				);
			}
		}
	}
	return $sets;
}

/**
 * Plain text of some markup, entities decoded, on one line.
 *
 * @param string $html Markup.
 * @return string
 */
function paulus_article_plain( $html ) {
	return trim( (string) preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( (string) $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
}

/**
 * The FAQPage of the study questions a page shows, or an empty array. Each
 * Question is one of the questions word for word, with the address of its
 * set. The page asks them and leaves the answering to the reader and the
 * article each set accompanies, so no Question carries an answer.
 *
 * @param WP_Post|null $post The page; the queried one when null.
 * @return array
 */
function paulus_study_questions_schema( $post = null ) {
	$post = $post ? $post : get_queried_object();
	if ( ! $post instanceof WP_Post ) {
		return array();
	}
	$sets = paulus_article_question_sets( (string) $post->post_content );
	if ( ! $sets ) {
		return array();
	}
	$url = (string) get_permalink( $post );
	$qs  = array();
	foreach ( $sets as $set ) {
		foreach ( $set['questions'] as $question ) {
			$q = array(
				'@type'       => 'Question',
				'name'        => $question,
				'answerCount' => 0,
			);
			if ( '' !== $set['anchor'] ) {
				$q['url'] = $url . '#' . $set['anchor'];
			}
			$qs[] = $q;
		}
	}
	return array(
		'@type'      => 'FAQPage',
		'@id'        => $url . '#faq',
		'mainEntity' => $qs,
	);
}

/* ---------------------------------------------------------------------
 * The Article fields box and the settings
 * ------------------------------------------------------------------ */

/**
 * The two fields, kept with the post and shown to the REST API for editors.
 *
 * @return void
 */
function paulus_article_register_meta() {
	foreach ( array( PAULUS_ARTICLE_POINTS_KEY, PAULUS_ARTICLE_REVIEWED_KEY ) as $key ) {
		register_post_meta(
			'post',
			$key,
			array(
				'type'          => 'string',
				'single'        => true,
				'show_in_rest'  => true,
				'auth_callback' => static function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}
}
add_action( 'init', 'paulus_article_register_meta' );

/**
 * The Article fields box on the post screen.
 *
 * @return void
 */
function paulus_article_add_box() {
	add_meta_box( 'paulus_article', __( 'Key takeaways and review', 'paulus' ), 'paulus_article_box', 'post', 'side', 'default' );
}
add_action( 'add_meta_boxes', 'paulus_article_add_box' );

/**
 * The box: key takeaways, one a line, and the date last reviewed.
 *
 * @param WP_Post $post Post.
 * @return void
 */
function paulus_article_box( $post ) {
	wp_nonce_field( 'paulus_article', 'paulus_article_nonce' );
	?>
	<p>
		<label for="paulus-takeaways"><?php esc_html_e( 'Key takeaways, one a line', 'paulus' ); ?></label>
		<textarea id="paulus-takeaways" name="paulus_takeaways" rows="5" class="widefat" aria-describedby="paulus-takeaways-desc"><?php echo esc_textarea( (string) get_post_meta( $post->ID, PAULUS_ARTICLE_POINTS_KEY, true ) ); ?></textarea>
	</p>
	<p class="description" id="paulus-takeaways-desc"><?php esc_html_e( 'Shown above the contents. Leave empty for no box.', 'paulus' ); ?></p>
	<p>
		<label for="paulus-reviewed"><?php esc_html_e( 'Last reviewed', 'paulus' ); ?></label>
		<input type="date" id="paulus-reviewed" name="paulus_reviewed" class="widefat" aria-describedby="paulus-reviewed-desc" value="<?php echo esc_attr( (string) get_post_meta( $post->ID, PAULUS_ARTICLE_REVIEWED_KEY, true ) ); ?>">
	</p>
	<p class="description" id="paulus-reviewed-desc"><?php esc_html_e( 'Optional. The day you last checked the article against its sources, shown under the byline.', 'paulus' ); ?></p>
	<?php
}

/**
 * Save the box.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function paulus_article_save_box( $post_id ) {
	if ( ! isset( $_POST['paulus_article_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['paulus_article_nonce'] ) ), 'paulus_article' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$points = implode( "\n", paulus_article_lines( sanitize_textarea_field( wp_unslash( $_POST['paulus_takeaways'] ?? '' ) ) ) );
	$date   = sanitize_text_field( wp_unslash( $_POST['paulus_reviewed'] ?? '' ) );
	foreach ( array( PAULUS_ARTICLE_POINTS_KEY => $points, PAULUS_ARTICLE_REVIEWED_KEY => paulus_article_valid_date( $date ) ? $date : '' ) as $key => $value ) {
		if ( '' !== $value ) {
			update_post_meta( $post_id, $key, $value );
		} else {
			delete_post_meta( $post_id, $key );
		}
	}
}
add_action( 'save_post_post', 'paulus_article_save_box' );

/**
 * The article styles, on articles only.
 *
 * @return void
 */
function paulus_article_assets() {
	if ( paulus_article_applies() ) {
		wp_enqueue_style( 'paulus-article', PAULUS_URI . '/assets/css/article.css', array( 'paulus-theme' ), PAULUS_VERSION );
	}
}
add_action( 'wp_enqueue_scripts', 'paulus_article_assets', 21 ); // After paulus_enqueue_assets() (20), so the order of its stylesheets stays as it was.
