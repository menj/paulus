<?php
/**
 * Self-test for inc/lead-list.php: when the lead and list draws, which
 * entry leads, and that the lead stays out of the list. WordPress is
 * replaced by small stubs. Run: php tests/lead-list-test.php. Exit code 0
 * when every check passes.
 *
 * @package Paulus
 */

// Run from the command line only: on a web server this file does nothing.
if ( 'cli' !== PHP_SAPI ) {
	exit;
}

define( 'ABSPATH', __DIR__ . '/' );

class WP_Post {
	public $ID;
	public $post_content = 'one two three four five six seven eight nine ten';
	public $post_status = 'publish';
	public $post_title  = '';
	public function __construct( $id, $img = true ) {
		$this->ID         = $id;
		$this->post_title = 'Entry ' . $id;
		$this->img        = $img;
	}
	public $img;
}
class WP_Term {
	public $slug;
	public $name;
	public $term_id;
}
class WP_Query {}
class WP_Block {}

$GLOBALS['paulus_t'] = array(
	'posts'    => array(),
	'unlisted' => array(),
	'filters'  => array(),
);
function paulus_t_posts( array $spec ) {
	$GLOBALS['paulus_t']['posts'] = array();
	foreach ( $spec as $id => $img ) {
		$GLOBALS['paulus_t']['posts'][ $id ] = new WP_Post( $id, $img );
	}
	return array_values( $GLOBALS['paulus_t']['posts'] );
}
function add_action() {}
function add_filter() {}
function apply_filters( $tag, $value ) {
	return $GLOBALS['paulus_t']['filters'][ $tag ] ?? $value;
}
function strip_shortcodes( $s ) {
	return $s;
}
function wp_strip_all_tags( $s ) {
	return strip_tags( (string) $s );
}
function _n( $a ) {
	return $a;
}
function get_the_category( $id ) {
	$cats = array(
		1 => array( 'history' ),
		2 => array( 'uncategorized', 'scripture' ),
		3 => array( 'uncategorized' ),
	);
	$out  = array();
	foreach ( $cats[ $id ] ?? array( 'theology' ) as $slug ) {
		$c          = new WP_Term();
		$c->slug    = $slug;
		$c->name    = ucfirst( $slug );
		$c->term_id = crc32( $slug ) % 1000;
		$out[]      = $c;
	}
	return $out;
}
function get_post_type_archive_link() {
	return '/journal/';
}
function trailingslashit( $s ) {
	return rtrim( $s, '/' ) . '/';
}
function get_category_link( $id ) {
	return '/category/' . $id . '/';
}

function __( $s ) {
	return $s;
}
function esc_html__( $s ) {
	return $s;
}
function esc_html( $s ) {
	return htmlspecialchars( (string) $s, ENT_QUOTES );
}
function esc_attr( $s ) {
	return htmlspecialchars( (string) $s, ENT_QUOTES );
}
function esc_url( $s ) {
	return (string) $s;
}
function sanitize_html_class( $s ) {
	return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $s );
}
function wp_parse_args( $a, $d ) {
	return array_merge( $d, $a );
}
function wp_enqueue_style() {}
function wp_list_pluck( $list, $f ) {
	return array_map(
		static function ( $o ) use ( $f ) {
			return $o->$f;
		},
		$list
	);
}
function get_post( $p ) {
	if ( $p instanceof WP_Post ) {
		return $p;
	}
	return $GLOBALS['paulus_t']['posts'][ (int) $p ] ?? null;
}
function paulus_is_unlisted( $id ) {
	return in_array( (int) $id, $GLOBALS['paulus_t']['unlisted'], true );
}
function has_post_thumbnail( $p ) {
	return get_post( $p )->img;
}
function get_the_post_thumbnail( $p, $size, $attr ) {
	return get_post( $p )->img ? '<img class="' . $attr['class'] . '" alt="' . ( $attr['alt'] ?? 'photo' ) . '" src="/' . get_post( $p )->ID . '.avif">' : '';
}
function get_post_thumbnail_id( $p ) {
	return get_post( $p )->img ? 900 + get_post( $p )->ID : 0;
}
function get_post_meta() {
	return '';
}
function get_option( $k, $d = false ) {
	return $d;
}
function get_attached_file( $id ) {
	return '/uploads/' . ( 905 === $id ? 'met-papyrus-letter-1' : 'paul-horned-ink' ) . '.avif';
}
function paulus_figures() {
	return array( 'met-papyrus-letter' => array( 'author' => 'Papyrus letter', 'license' => 'CC0', 'license_url' => '', 'source' => '' ) );
}
function paulus_figure_credit( $f ) {
	return 'Image: ' . $f['author'] . ', ' . $f['license'] . '.';
}
function paulus_images() {
	return array( 'paul-horned-ink' => 'Illustration', 'met-papyrus-letter' => 'Papyrus' );
}
function paulus_option() {
	return 'The Author';
}
function get_the_date( $f, $p ) {
	return '2026-11-0' . get_post( $p )->ID;
}
function wp_get_attachment_caption() {
	return 'Caption line';
}
function wp_kses( $s ) {
	return $s;
}
function get_permalink( $p ) {
	return '/entry-' . get_post( $p )->ID . '/';
}
function get_the_title( $p ) {
	return get_post( $p )->post_title;
}

require dirname( __DIR__ ) . '/inc/lead-list.php';

$paulus_fail = 0;
function paulus_test_check( $ok, $what ) {
	global $paulus_fail;
	echo ( $ok ? 'PASS ' : 'FAIL ' ) . $what . "\n";
	$paulus_fail += $ok ? 0 : 1;
}

$r = paulus_lead_list( paulus_t_posts( array( 1 => true, 2 => true, 3 => true, 4 => true, 5 => true ) ) );
paulus_test_check( array( 1, 2, 3, 4 ) === $r['ids'], 'one lead and three in the list, no more' );
paulus_test_check( 4 === substr_count( $r['html'], '<article' ) && 1 === substr_count( $r['html'], 'data-lead="1" hidden' ) + 1 && 3 === substr_count( $r['html'], '<li ' ), 'a panel for the lead and one for each list entry, three list items' );
paulus_test_check( false !== strpos( $r['html'], 'aria-labelledby="paulus-lead-list-heading"' ), 'the section is labelled by its heading' );
paulus_test_check( 3 === substr_count( $r['html'], 'alt=""' ), 'list thumbnails carry empty alt text' );
paulus_test_check( false !== strpos( $r['html'], '<figcaption class="paulus-lead-list__credit">Illustration: The Author.</figcaption>' ), 'the credit sits in the figcaption' );

$r = paulus_lead_list( paulus_t_posts( array( 1 => true, 2 => true, 3 => true, 4 => true, 5 => true ) ), array( 'lead_id' => 4 ) );
paulus_test_check( array( 4, 1, 2, 3 ) === $r['ids'], 'lead_id leads and is left out of the list' );

$GLOBALS['paulus_t']['unlisted'] = array( 2 );
$r = paulus_lead_list( paulus_t_posts( array( 1 => true, 2 => true, 3 => true, 4 => true, 5 => true ) ) );
paulus_test_check( array( 1, 3, 4, 5 ) === $r['ids'], 'unlisted entries do not appear' );
$GLOBALS['paulus_t']['unlisted'] = array();

$r = paulus_lead_list( paulus_t_posts( array( 1 => true, 2 => true, 3 => true ) ) );
paulus_test_check( '' === $r['html'], 'three featured: nothing is drawn' );

$r = paulus_lead_list( paulus_t_posts( array( 1 => false, 2 => true, 3 => true, 4 => true ) ) );
paulus_test_check( '' === $r['html'], 'a lead without a featured image: nothing is drawn' );

$r = paulus_lead_list( paulus_t_posts( array( 5 => true, 2 => true, 3 => true, 4 => true ) ) );
paulus_test_check( false !== strpos( $r['html'], 'Image: Papyrus letter, CC0.' ), 'a registered figure keeps its own credit' );

$r = paulus_lead_list( paulus_t_posts( array( 1 => true, 2 => false, 3 => true, 4 => true ) ) );
paulus_test_check( 1 === substr_count( $r['html'], 'paulus-lead-list__thumb--empty' ), 'a list entry without a photograph keeps an empty panel' );

// Category tabs, meta line, current row, settings.
$r = paulus_lead_list( paulus_t_posts( array( 1 => true, 2 => true, 3 => true, 4 => true, 5 => true, 6 => true ) ) );
paulus_test_check( false !== strpos( $r['html'], 'class="paulus-lead-list__meta"' ) && false !== strpos( $r['html'], '1 min read' ), 'the panels and the list items carry a date and reading-time line' );
paulus_test_check( 1 === preg_match( '#<a class="paulus-lead-list__tab paulus-lead-list__tab--c\d" href="/category/\d+/">History</a>#', $r['html'] ) && false === strpos( $r['html'], '>Uncategorized<' ), 'a tab is a link that names the category; the default category is skipped' );
paulus_test_check( false !== strpos( $r['html'], 'paulus-lead-list__tab--c' ) && false !== strpos( $r['html'], '>Journal 2026' ) && false !== strpos( $r['html'], 'href="/journal/2026' ), 'an entry without a category gets a Journal and year tab that links to that year' );
paulus_test_check( 1 === substr_count( $r['html'], 'paulus-lead-list__item is-current' ), 'only the first list item is the current row' );
paulus_test_check( false === strpos( $r['html'], 'paulus-lead-list--calm' ) && false === strpos( $r['html'], 'style="--paulus-ll-accent' ), 'by default motion is on and the scheme accent is used' );
$GLOBALS['paulus_t']['filters'] = array(
	'paulus_lead_list_show_tabs' => false,
	'paulus_lead_list_motion'    => false,
	'paulus_lead_list_accent'    => '#7a2e1a',
);
$r = paulus_lead_list( paulus_t_posts( array( 1 => true, 2 => true, 3 => true, 4 => true, 5 => true, 6 => true ) ) );
paulus_test_check( false === strpos( $r['html'], '<a class="paulus-lead-list__tab' ) && false !== strpos( $r['html'], 'paulus-lead-list--calm' ) && false !== strpos( $r['html'], '--paulus-ll-accent:#7a2e1a' ), 'the switches remove the tabs and motion, and the accent override is applied' );
$GLOBALS['paulus_t']['filters'] = array( 'paulus_lead_list_accent' => 'red; background:url(x)' );
$r = paulus_lead_list( paulus_t_posts( array( 1 => true, 2 => true, 3 => true, 4 => true, 5 => true, 6 => true ) ) );
paulus_test_check( false === strpos( $r['html'], 'style="--paulus-ll-accent' ), 'an accent that is not a hex colour is dropped' );
$GLOBALS['paulus_t']['filters'] = array();
paulus_test_check( paulus_lead_list_accent_ok( '#7a2e1a', 'paper' ) && ! paulus_lead_list_accent_ok( '#f3eee4', 'paper' ) && ! paulus_lead_list_accent_ok( '#b8955f', 'ochre' ), 'an accent that is hard to see on the scheme page is refused' );

exit( $paulus_fail ? 1 : 0 );
