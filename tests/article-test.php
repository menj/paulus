<?php
/**
 * The article page (inc/article.php) and the author hardening
 * (inc/hardening.php): where the takeaways and the mid-article next step
 * go, the study questions read word for word into an FAQPage, the REST user
 * routes hidden from visitors, and the book teaser left where it is.
 * WordPress is replaced by small stubs.
 *
 * Run: php tests/article-test.php
 *
 * @package Paulus
 */

// Run from the command line only: on a web server this file does nothing.
if ( 'cli' !== PHP_SAPI ) {
	exit;
}

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['t'] = array(
	'options'   => array( 'sec_hide_authors' => 1, 'article_takeaways' => 1, 'article_mid_next' => 1 ),
	'logged_in' => false,
	'meta'      => array(),
);
function add_action() {}
function add_filter() {}
function paulus_option( $key ) {
	return $GLOBALS['t']['options'][ $key ] ?? '';
}
function is_user_logged_in() {
	return $GLOBALS['t']['logged_in'];
}
function get_post_meta( $id, $key ) {
	return $GLOBALS['t']['meta'][ $id ][ $key ] ?? '';
}
function esc_html( $s ) {
	return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' );
}
function esc_attr( $s ) {
	return esc_html( $s );
}
function esc_url( $s ) {
	return (string) $s;
}
function esc_html__( $s ) {
	return $s;
}
function __( $s ) {
	return $s;
}
function wp_strip_all_tags( $s ) {
	return strip_tags( (string) $s );
}
function get_permalink() {
	return 'https://apostleofdoom.org/reference/study-questions/';
}

require __DIR__ . '/../inc/article.php';
require __DIR__ . '/../inc/hardening.php';

$failed = 0;
$check  = static function ( $ok, $what ) use ( &$failed ) {
	if ( ! $ok ) {
		++$failed;
		echo 'FAIL: ' . $what . "\n";
	}
};

// Takeaways go above the contents tablet, else above the first section.
$body = '<p>Intro.</p><nav class="paulus-faq-index paulus-contents" id="contents"></nav><h2 id="a">A</h2><p>1</p>';
$out  = paulus_article_insert_takeaways( $body, '<section class="paulus-takeaways"></section>' );
$at  = strpos( $out, 'paulus-takeaways' );
$check( false !== $at && $at < strpos( $out, 'paulus-contents' ) && 0 === strpos( $out, '<p>Intro.</p>' ), 'takeaways sit above the contents, after the opening paragraph' );
$check( 0 === strpos( paulus_article_insert_takeaways( '<h2>A</h2>', '<x>' ), '<x><h2>' ), 'without contents, above the first section' );

// The mid-article step goes before the third section and its back link.
$text = '<h2>A</h2><p>1</p><p class="paulus-faq-back"><a href="#contents">Back</a></p><h2>B</h2><p>2</p><p class="paulus-faq-back"><a href="#contents">Back</a></p><h2>C</h2><p>3</p>';
$mid  = paulus_article_insert_mid( $text, '<p class="paulus-next-step">x</p>' );
$check( false !== strpos( $mid, '<p>2</p><p class="paulus-next-step">x</p><p class="paulus-faq-back">' ), 'the step goes before the back link that leads into the third section' );
$check( '<h2>A</h2><h2>B</h2>' === paulus_article_insert_mid( '<h2>A</h2><h2>B</h2>', '<x>' ), 'nothing with fewer than three sections' );

// The study questions, word for word, with their set's address.
$html = '<p>Intro</p><h2 id="who-was-paul-of-tarsus">The man: Who was Paul?</h2><ol class="paulus-questions"><li>What gave Paul &amp; his letters such a place?</li><li>Why <em>Rome</em>?</li></ol><h2 id="x">Other</h2><ol><li>Not a question set</li></ol>';
$sets = paulus_article_question_sets( $html );
$check( 1 === count( $sets ) && 2 === count( $sets[0]['questions'] ), 'one set of two questions; a plain list is not a set' );
$check( 'What gave Paul & his letters such a place?' === $sets[0]['questions'][0] && 'Why Rome?' === $sets[0]['questions'][1], 'the questions are read as plain text, word for word' );
$check( 'who-was-paul-of-tarsus' === $sets[0]['anchor'], 'each set keeps the id of its heading' );
$check( array() === paulus_article_question_sets( '<h2>Q?</h2><p>A.</p>' ), 'a page without study questions has no sets, so no FAQPage' );

// Both hooks in inc/seo.php build the FAQPage of the study questions, as
// the owner decision of 2026-11-03 keeps every schema.org type.
$seo = preg_replace( '#/\*.*?\*/|//[^\n]*#s', '', (string) file_get_contents( __DIR__ . '/../inc/seo.php' ) );
$check( 2 === substr_count( $seo, 'paulus_study_questions_schema( get_queried_object() )' ), 'paulus_schema_additions() and paulus_webpage_schema() both call paulus_study_questions_schema()' );

// The REST user routes are hidden from visitors only.
$routes = array( '/wp/v2/users' => 1, '/wp/v2/users/(?P<id>[\d]+)' => 1, '/wp/v2/users/me' => 1, '/wp/v2/posts' => 1 );
$check( array( '/wp/v2/posts' => 1 ) === paulus_hardening_rest_endpoints( $routes ), 'a visitor gets no user routes' );
$GLOBALS['t']['logged_in'] = true;
$check( $routes === paulus_hardening_rest_endpoints( $routes ), 'a logged-in user keeps them' );
$GLOBALS['t']['logged_in']                    = false;
$GLOBALS['t']['options']['sec_hide_authors'] = 0;
$check( $routes === paulus_hardening_rest_endpoints( $routes ), 'the Security switch turns it off' );

// Dates.
$check( paulus_article_valid_date( '2026-11-02' ) && ! paulus_article_valid_date( '2026-02-30' ) && ! paulus_article_valid_date( '2 Nov' ), 'only a real Y-m-d date is a review date' );

// The book teaser stays on every article, and nothing new sells.
$single = (string) file_get_contents( __DIR__ . '/../templates/single.html' );
$check( false !== strpos( $single, '[paulus_book_teaser]' ), 'the book teaser stays on the article template' );
$src = preg_replace( '#/\*.*?\*/|//[^\n]*#s', '', (string) file_get_contents( __DIR__ . '/../inc/article.php' ) );
foreach ( array( 'book_price', 'book_buy_url', 'paulus_order_link', 'Buy', 'Order' ) as $word ) {
	$check( false === strpos( $src, $word ), 'no sales wording or link in inc/article.php: ' . $word );
}

echo $failed ? "Article checks failed: $failed\n" : "Article checks: all passed\n";
exit( $failed ? 1 : 0 );
