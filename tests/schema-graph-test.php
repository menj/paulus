<?php
/**
 * The structured-data graph: renders sample pages with stubbed WordPress
 * functions, on the theme's own graph and through Rank Math's filter, and
 * checks that each page prints one graph that parses, that every top-level
 * @id is unique, that the nodes carry their required properties, and that
 * the pieces of the schema plan are there: Bible chapters for the scripture
 * blocks, citations for the footnotes, a series node, Journal entries in the
 * Blog, chart images with their datasets, full image credits (the horned
 * illustrations included) and the page kinds.
 *
 * Run: php tests/schema-graph-test.php
 *
 * @package Paulus
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'PAULUS_DIR', dirname( __DIR__ ) );
define( 'PAULUS_URI', 'https://example.org/wp-content/themes/paulus' );
define( 'HOME', 'https://example.org/' );

// ---- Stubs ---------------------------------------------------------------

class WP_Post {
	public $ID, $post_name, $post_title, $post_type, $post_status = 'publish', $post_content = '', $post_excerpt = '', $post_date = '2026-01-15 10:00:00', $post_parent = 0;
	public function __construct( $a ) {
		foreach ( $a as $k => $v ) {
			$this->$k = $v;
		}
	}
}
class WP_Term {
	public $term_id, $name, $slug;
	public function __construct( $a ) {
		foreach ( $a as $k => $v ) {
			$this->$k = $v;
		}
	}
}

$GLOBALS['T'] = array();
function t( $k, $d = null ) {
	return $GLOBALS['T'][ $k ] ?? $d;
}
function add_action() {}
function add_filter() {}
function add_shortcode() {}
function remove_filter() {}
function apply_filters( $h, $v ) {
	return $v;
}
function do_action() {}
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
function esc_url_raw( $s ) {
	return (string) $s;
}
function wp_kses( $s ) {
	return $s;
}
function wp_kses_post( $s ) {
	return $s;
}
function home_url( $p = '' ) {
	return rtrim( HOME, '/' ) . '/' . ltrim( $p, '/' );
}
function get_bloginfo( $k = '' ) {
	return 'language' === $k ? 'en-GB' : 'Apostle of Doom';
}
function get_locale() {
	return 'en_GB';
}
function get_option( $k, $d = false ) {
	return $d;
}
function get_theme_mod( $k, $d = false ) {
	return $d;
}
function is_admin() {
	return false;
}
function is_feed() {
	return false;
}
function is_404() {
	return '404' === t( 'kind' );
}
function is_search() {
	return 'search' === t( 'kind' );
}
function is_front_page() {
	return 'front' === t( 'kind' );
}
function is_home() {
	return false;
}
function is_category() {
	return 'category' === t( 'kind' );
}
function is_tag() {
	return false;
}
function is_date() {
	return false;
}
function is_author() {
	return false;
}
function is_tax() {
	return false;
}
function is_archive() {
	return in_array( t( 'kind' ), array( 'category', 'journal-archive' ), true );
}
function is_paged() {
	return false;
}
function is_post_type_archive( $type = '' ) {
	return 'journal-archive' === t( 'kind' );
}
function is_singular( $type = '' ) {
	$p = t( 'post' );
	if ( ! $p || ! in_array( t( 'kind' ), array( 'post', 'page', 'journal' ), true ) ) {
		return false;
	}
	return '' === $type || in_array( $p->post_type, (array) $type, true );
}
function is_single( $p = '' ) {
	return is_singular() && 'page' !== t( 'post' )->post_type;
}
function is_page( $which = '' ) {
	if ( ! is_singular( 'page' ) ) {
		return false;
	}
	if ( '' === $which ) {
		return true;
	}
	$p = t( 'post' );
	foreach ( (array) $which as $w ) {
		if ( $w === $p->post_name || (int) $w === $p->ID ) {
			return true;
		}
	}
	return false;
}
function in_the_loop() {
	return false;
}
function is_main_query() {
	return true;
}
function get_queried_object() {
	return 'category' === t( 'kind' ) ? t( 'term' ) : t( 'post' );
}
function get_queried_object_id() {
	$o = get_queried_object();
	return $o instanceof WP_Post ? $o->ID : ( $o ? $o->term_id : 0 );
}
function get_post( $id = null ) {
	if ( null === $id || 0 === $id ) {
		return t( 'post' );
	}
	if ( $id instanceof WP_Post ) {
		return $id;
	}
	return $GLOBALS['POSTS'][ (int) $id ] ?? null;
}
function paulus_test_post( $id ) {
	return $id instanceof WP_Post ? $id : get_post( $id );
}
function get_post_field( $f, $id = null ) {
	$p = paulus_test_post( $id ?: t( 'post' ) );
	return $p ? $p->$f : '';
}
function get_post_type( $id = null ) {
	$p = paulus_test_post( $id ?: t( 'post' ) );
	return $p ? $p->post_type : false;
}
function get_the_ID() {
	return t( 'post' ) ? t( 'post' )->ID : 0;
}
function get_permalink( $id = null ) {
	$p = paulus_test_post( $id ?: t( 'post' ) );
	if ( ! $p ) {
		return '';
	}
	if ( 'paulus_journal' === $p->post_type ) {
		return home_url( 'journal/2026/01/' . $p->post_name . '/' );
	}
	return home_url( $p->post_name . '/' );
}
function get_the_title( $id = null ) {
	$p = paulus_test_post( $id ?: t( 'post' ) );
	return $p ? $p->post_title : '';
}
function get_the_date( $f = '', $id = null ) {
	$p = paulus_test_post( $id ?: t( 'post' ) );
	return 'Y' === $f ? '2026' : '2026-01-15T10:00:00+08:00';
}
function get_the_modified_date( $f = '', $id = null ) {
	return '2026-02-01T10:00:00+08:00';
}
function get_post_modified_time( $f = '', $g = false, $id = null ) {
	return '2026-02-01T10:00:00+08:00';
}
function get_post_time( $f = '', $g = false, $id = null ) {
	return '2026-01-15T10:00:00+08:00';
}
function has_excerpt( $id = null ) {
	$p = paulus_test_post( $id ?: t( 'post' ) );
	return $p && '' !== $p->post_excerpt;
}
function get_the_excerpt( $id = null ) {
	$p = paulus_test_post( $id ?: t( 'post' ) );
	return $p ? $p->post_excerpt : '';
}
function has_post_thumbnail( $id = null ) {
	return (bool) t( 'thumb' );
}
function get_post_thumbnail_id( $id = null ) {
	return t( 'thumb' ) ? 900 : 0;
}
function get_the_post_thumbnail_url( $id = null, $size = '' ) {
	return t( 'thumb' ) ? home_url( 'wp-content/uploads/2026/01/' . t( 'thumb' ) . '.avif' ) : false;
}
function wp_get_attachment_image_src( $id, $size = '' ) {
	return t( 'thumb' ) ? array( get_the_post_thumbnail_url(), 1200, 800 ) : false;
}
function wp_get_attachment_metadata( $id ) {
	return array( 'width' => 1200, 'height' => 800 );
}
function get_post_meta( $id, $k = '', $single = false ) {
	return $GLOBALS['META'][ (int) $id ][ $k ] ?? '';
}
function get_page_by_path( $path ) {
	$slug = basename( $path );
	foreach ( $GLOBALS['POSTS'] as $p ) {
		if ( 'page' === $p->post_type && $p->post_name === $slug ) {
			return $p;
		}
	}
	return null;
}
function get_pages( $a = array() ) {
	$out = array();
	foreach ( $GLOBALS['POSTS'] as $p ) {
		if ( 'page' === $p->post_type && ( ! isset( $a['parent'] ) || $p->post_parent === $a['parent'] ) ) {
			$out[] = $p;
		}
	}
	return $out;
}
function get_posts( $a = array() ) {
	$type = $a['post_type'] ?? 'post';
	return array_values( array_filter( $GLOBALS['POSTS'], static function ( $p ) use ( $type ) {
		return $p->post_type === $type;
	} ) );
}
function get_terms( $a = array() ) {
	return $GLOBALS['TERMS'];
}
function get_the_terms( $id, $tax ) {
	return array( $GLOBALS['TERMS'][0] );
}
function get_the_category( $id = null ) {
	return array( $GLOBALS['TERMS'][0] );
}
function get_term_link( $t ) {
	return home_url( $t->slug . '/' );
}
function get_category_link( $t ) {
	return get_term_link( is_object( $t ) ? $t : $GLOBALS['TERMS'][0] );
}
function single_cat_title( $p = '', $d = true ) {
	return t( 'term' )->name;
}
function get_query_var( $k, $d = '' ) {
	return $d;
}
function get_search_query( $e = true ) {
	return 'grace';
}
function post_type_exists( $t ) {
	return true;
}
function wp_count_posts( $t ) {
	return (object) array( 'publish' => 1 );
}
function is_wp_error( $x ) {
	return false;
}
function get_post_type_archive_link( $t ) {
	return home_url( 'journal/' );
}
function wp_json_encode( $d, $o = 0 ) {
	return json_encode( $d, $o );
}
function wp_parse_url( $u, $c = -1 ) {
	return parse_url( $u, $c );
}
function wp_strip_all_tags( $s ) {
	return trim( strip_tags( preg_replace( '#<(script|style)[^>]*>.*?</\1>#s', '', (string) $s ) ) );
}
function strip_shortcodes( $s ) {
	return preg_replace( '#\[/?[a-z_]+[^\]]*\]#', '', $s );
}
function sanitize_title( $s ) {
	return trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( (string) $s ) ), '-' );
}
function sanitize_email( $s ) {
	return (string) $s;
}
function sanitize_text_field( $s ) {
	return trim( (string) $s );
}
function remove_accents( $s ) {
	return (string) $s;
}
function wptexturize( $s ) {
	return $s;
}
function wp_trim_words( $s, $n = 55 ) {
	return implode( ' ', array_slice( preg_split( '/\s+/', (string) $s ), 0, $n ) );
}
function get_avatar_url() {
	return '';
}
function get_template_directory() {
	return PAULUS_DIR;
}
function get_stylesheet_directory_uri() {
	return PAULUS_URI;
}
function get_site_icon_url() {
	return '';
}
function wp_get_theme() {
	return new class() {
		public function get( $k ) {
			return '2.71.0';
		}
	};
}
function is_plugin_active() {
	return false;
}
function shortcode_atts( $d, $a ) {
	return array_merge( $d, (array) $a );
}
function date_i18n( $f, $t = null ) {
	return gmdate( $f, $t ?? time() );
}
function mysql2date( $f, $d ) {
	return '2026-01-15T10:00:00+08:00';
}
function get_userdata() {
	return null;
}
function trailingslashit( $s ) {
	return rtrim( $s, '/' ) . '/';
}
function untrailingslashit( $s ) {
	return rtrim( $s, "/" );
}
function has_category( $c = '', $p = null ) {
	return 'post' === get_post_type( $p );
}
function sanitize_file_name() {
	return '';
}
function get_term_meta() {
	return '';
}
function term_description() {
	return '';
}
function number_format_i18n( $n, $d = 0 ) {
	return number_format( $n, $d );
}

// The string helpers of functions.php (paulus_ulen to paulus_ustrtolower), without the rest
// of the file, which boots the theme.
$paulus_fn = (string) file_get_contents( PAULUS_DIR . '/functions.php' );
$paulus_a  = strpos( $paulus_fn, 'function paulus_ulen(' );
$paulus_b  = strpos( $paulus_fn, "if ( ! paulus_require( 'defaults' ) )" );
eval( substr( $paulus_fn, $paulus_a, $paulus_b - $paulus_a ) ); // phpcs:ignore Squiz.PHP.Eval
foreach ( array( 'defaults', 'importer', 'charts', 'figures', 'structure', 'journal', 'seo', 'schema-deep' ) as $f ) {
	require_once PAULUS_DIR . '/inc/' . $f . '.php';
}

// ---- Fixtures ------------------------------------------------------------

$manifest = paulus_manifest();
$content  = static function ( $file ) {
	$doc = json_decode( (string) file_get_contents( PAULUS_DIR . '/content/articles/' . $file ), true );
	return (string) ( $doc['content'] ?? '' );
};
$GLOBALS['POSTS'] = array();
$GLOBALS['META']  = array();
$GLOBALS['TERMS'] = array();
$id               = 10;
foreach ( (array) $manifest['parts'] as $i => $part ) {
	$GLOBALS['TERMS'][] = new WP_Term( array( 'term_id' => 100 + $i, 'name' => $part['name'] ?? $part['title'] ?? 'Part', 'slug' => $part['slug'] ?? 'part-' . $i ) );
}
$by_slug = array();
foreach ( array( 'posts' => 'post', 'pages' => 'page', 'journal' => 'paulus_journal' ) as $list => $type ) {
	foreach ( (array) $manifest[ $list ] as $item ) {
		$file = (string) ( $item['file'] ?? '' );
		$p    = new WP_Post(
			array(
				'ID'           => ++$id,
				'post_name'    => $item['slug'],
				'post_title'   => $item['title'] ?? $item['slug'],
				'post_type'    => $type,
				'post_excerpt' => (string) ( $item['excerpt'] ?? '' ),
				'post_content' => '' !== $file && is_readable( PAULUS_DIR . '/content/articles/' . $file ) ? $content( $file ) : '',
			)
		);
		$GLOBALS['POSTS'][ $id ] = $p;
		$by_slug[ $item['slug'] ]  = $p;
		if ( ! empty( $item['series'] ) ) {
			$GLOBALS['META'][ $id ] = array( '_paulus_series' => $item['series'], '_paulus_series_title' => $item['series_title'] ?? '', '_paulus_part_no' => (int) ( $item['part_no'] ?? 1 ) );
		}
	}
}
// Part order and chapter lookups read terms and posts; give them the
// manifest's order.
$GLOBALS['T'] = array();

// ---- Checks --------------------------------------------------------------

$fail  = 0;
$check = static function ( $ok, $msg ) use ( &$fail ) {
	if ( ! $ok ) {
		echo 'FAIL: ' . $msg . "\n";
		++$fail;
	}
};

/**
 * Render a page's graph on the theme's own path and return its nodes.
 */
function paulus_test_graph( $state ) {
	$GLOBALS['T'] = $state;
	ob_start();
	paulus_print_schema_graph();
	$html = (string) ob_get_clean();
	return $html;
}

/**
 * The same page through Rank Math's filter, with a typical Rank Math graph.
 */
function paulus_test_rank_math( $state ) {
	$GLOBALS['T'] = $state;
	unset( $GLOBALS['paulus_rank_math_json_ld_ran'] );
	$url  = get_permalink();
	$data = array(
		'publisher'   => array( '@type' => 'Organization', '@id' => HOME . '#organization', 'name' => (string) paulus_option( 'pub_name' ) ),
		'WebSite'     => array( '@type' => 'WebSite', '@id' => HOME . '#website', 'url' => HOME, 'name' => 'Apostle of Doom' ),
		'WebPage'     => array( '@type' => 'WebPage', '@id' => $url . '#webpage', 'url' => $url, 'name' => get_the_title() ),
		'richSnippet' => array( '@type' => 'BlogPosting', '@id' => $url . '#richSnippet', 'headline' => get_the_title() ),
	);
	if ( has_post_thumbnail() ) {
		$data['richSnippet']['image'] = array( '@type' => 'ImageObject', 'url' => get_the_post_thumbnail_url() );
	}
	$out = paulus_rank_math_json_ld( $data );
	return '<script type="application/ld+json">' . json_encode( array( '@context' => 'https://schema.org', '@graph' => array_values( $out ) ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>';
}

$required = array(
	'Article'            => array( 'headline' ),
	'ScholarlyArticle'   => array( 'headline' ),
	'BlogPosting'        => array( 'headline' ),
	'WebPage'            => array( 'url' ),
	'ImageObject'        => array(),
	'Quotation'          => array( 'text' ),
	'Chapter'            => array( 'name', 'isPartOf' ),
	'Book'               => array( 'name' ),
	'CreativeWorkSeries' => array( 'name', 'hasPart' ),
	'Dataset'            => array( 'name', 'description' ),
	'ListItem'           => array( 'position' ),
	'Blog'               => array( 'name', 'url' ),
);

/**
 * Parse and check one page's output; return its top-level nodes by @id.
 */
function paulus_test_parse( $html, $label, $check, $required ) {
	$count = preg_match_all( '#<script type="application/ld\+json">(.*?)</script>#s', $html, $m );
	$check( 1 === $count, $label . ': ' . $count . ' JSON-LD blocks, expected one' );
	if ( ! $count ) {
		return array();
	}
	$doc = json_decode( $m[1][0], true );
	$check( is_array( $doc ) && isset( $doc['@graph'] ), $label . ': JSON does not parse (' . json_last_error_msg() . ')' );
	$byid = array();
	foreach ( (array) ( $doc['@graph'] ?? array() ) as $node ) {
		$check( ! empty( $node['@type'] ), $label . ': a top-level node without @type' );
		if ( ! empty( $node['@id'] ) ) {
			$check( ! isset( $byid[ $node['@id'] ] ), $label . ': duplicate @id ' . $node['@id'] );
			$byid[ $node['@id'] ] = $node;
		}
	}
	// Required properties, at any depth; every @id is an absolute address
	// with a fragment or path of its own.
	$walk = static function ( $n ) use ( &$walk, $check, $label, $required ) {
		if ( ! is_array( $n ) ) {
			return;
		}
		if ( isset( $n['@type'] ) ) {
			foreach ( (array) $n['@type'] as $t ) {
				foreach ( $required[ $t ] ?? array() as $prop ) {
					$check( isset( $n[ $prop ] ) && '' !== $n[ $prop ], $label . ': ' . $t . ' ' . ( $n['@id'] ?? '' ) . ' lacks ' . $prop );
				}
			}
			if ( in_array( 'ImageObject', (array) $n['@type'], true ) ) {
				$check( ! empty( $n['url'] ) || ! empty( $n['contentUrl'] ) || ! empty( $n['@id'] ), $label . ': ImageObject without an address' );
			}
		}
		if ( isset( $n['@id'] ) ) {
			$check( 0 === strpos( (string) $n['@id'], 'http' ), $label . ': relative @id ' . $n['@id'] );
		}
		foreach ( $n as $v ) {
			$walk( $v );
		}
	};
	$walk( $doc['@graph'] );
	// Every reference that names a node of this site resolves to a node in
	// the graph, or to a page address (another page's node).
	$json = $m[1][0];
	preg_match_all( '#\{"@id":"([^"]+)"\}#', $json, $refs );
	foreach ( array_unique( $refs[1] ) as $ref ) {
		if ( 0 !== strpos( $ref, HOME ) || false === strpos( $ref, '#' ) ) {
			continue;
		}
		$local = 0 === strpos( $ref, HOME . '#' );
		if ( $local ) {
			$check( isset( $byid[ $ref ] ) || false !== strpos( $json, '"@id":"' . $ref . '","' ), $label . ': reference to ' . $ref . ' has no node' );
		}
	}
	return $byid;
}

$types = static function ( $byid, $type ) {
	return array_filter( $byid, static function ( $n ) use ( $type ) {
		return in_array( $type, (array) $n['@type'], true );
	} );
};

// 1. A page with scripture blocks and footnotes.
$p = $by_slug['apostle-to-the-gentiles'];
foreach ( array( 'theme' => paulus_test_graph( array( 'kind' => 'page', 'post' => $p ) ), 'rank math' => paulus_test_rank_math( array( 'kind' => 'page', 'post' => $p ) ) ) as $path => $html ) {
	$g = paulus_test_parse( $html, 'apostle-to-the-gentiles (' . $path . ')', $check, $required );
	$check( isset( $g[ HOME . '#bible-romans-11' ] ), $path . ': Romans 11 chapter node missing' );
	$check( isset( $g[ HOME . '#romans' ] ) && isset( $g[ HOME . '#bible' ] ) && ! isset( $g[ HOME . '#bible-romans' ] ), $path . ': Romans should be the site entity, with the Bible node' );
	$check( false !== strpos( $html, '"isPartOf":{"@id":"' . HOME . '#bible-romans-11"}' ), $path . ': nothing points to Romans 11' );
	$check( false !== strpos( $html, '"translationOfWork":{"@id":"' ), $path . ': English quotation not linked to its Greek' );
	$check( false !== strpos( $html, '"citation":[' ), $path . ': footnotes are not citations' );
}

// 2. An article in a series, headed by a horned illustration.
$series = null;
foreach ( $manifest['posts'] as $item ) {
	if ( ! empty( $item['series'] ) && ! empty( $item['file'] ) ) {
		$series = $by_slug[ $item['slug'] ];
		break;
	}
}
$state = array( 'kind' => 'post', 'post' => $series, 'thumb' => 'paul-horned-stone' );
foreach ( array( 'theme' => paulus_test_graph( $state ), 'rank math' => paulus_test_rank_math( $state ) ) as $path => $html ) {
	$g = paulus_test_parse( $html, $series->post_name . ' (' . $path . ')', $check, $required );
	$check( (bool) $types( $g, 'CreativeWorkSeries' ), $path . ': series node missing' );
	$check( false !== strpos( $html, 'paul-horned-stone.avif' ), $path . ': the horned illustration is not in the graph' );
	$check( (bool) preg_match( '#\{[^{}]*"url":"[^"]*paul-horned-stone\.avif"[^{}]*"creditText"#', $html ) || (bool) preg_match( '#"creditText"[^{}]*paul-horned-stone\.avif#', $html ) || false !== strpos( $html, '"encodingFormat":"image/avif"' ), $path . ': the horned illustration has no credit' );
	$check( false !== strpos( $html, '"accessMode":' ), $path . ': article access modes missing' );
	$check( false !== strpos( $html, '"Chapter"' ), $path . ': chapter of the book missing' );
}

// 3. A page with a chart: a dataset and the chart's image.
$chart_page = null;
foreach ( $by_slug as $p ) {
	if ( false !== strpos( $p->post_content, '[paulus_chart id="' ) ) {
		$chart_page = $p;
		break;
	}
}
if ( $chart_page ) {
	$html = paulus_test_graph( array( 'kind' => $chart_page->post_type === 'page' ? 'page' : 'post', 'post' => $chart_page ) );
	$g    = paulus_test_parse( $html, $chart_page->post_name, $check, $required );
	$check( (bool) $types( $g, 'Dataset' ), 'chart: Dataset missing' );
	$imgs = array_filter( array_keys( $g ), static function ( $k ) {
		return (bool) preg_match( '/#chart-[a-z0-9-]+-image$/', $k );
	} );
	$check( (bool) $imgs, 'chart: ImageObject missing' );
	foreach ( $imgs as $k ) {
		foreach ( array( 'creator', 'creditText', 'copyrightHolder', 'about' ) as $prop ) {
			$check( isset( $g[ $k ][ $prop ] ), 'chart image lacks ' . $prop );
		}
	}
} else {
	$check( false, 'no page with a chart found' );
}

// 4. A page with figures: each figure fully credited.
$fig_page = $by_slug['paul-in-the-churches'] ?? null;
if ( $fig_page ) {
	$g = paulus_test_parse( paulus_test_graph( array( 'kind' => 'page', 'post' => $fig_page ) ), 'paul-in-the-churches', $check, $required );
	$figs = array_filter( $g, static function ( $n, $k ) {
		return false !== strpos( $k, '/assets/images/church/' ) && in_array( 'ImageObject', (array) $n['@type'], true );
	}, ARRAY_FILTER_USE_BOTH );
	$check( count( $figs ) > 5, 'figures: too few ImageObjects' );
	foreach ( $figs as $k => $n ) {
		foreach ( array( 'contentUrl', 'creditText', 'license', 'encodingFormat', 'width', 'height', 'caption' ) as $prop ) {
			$check( isset( $n[ $prop ] ), 'figure ' . basename( $k ) . ' lacks ' . $prop );
		}
	}
}

// 5. A Journal entry and the Journal.
$entries = array_filter( $by_slug, static function ( $p ) {
	return 'paulus_journal' === $p->post_type && '' !== $p->post_content;
} );
$entry   = reset( $entries );
if ( $entry ) {
	$html = paulus_test_graph( array( 'kind' => 'journal', 'post' => $entry ) );
	$g    = paulus_test_parse( $html, 'journal entry', $check, $required );
	$check( (bool) $types( $g, 'BlogPosting' ), 'journal: entry is not a BlogPosting' );
	$check( (bool) preg_match( '#"isPartOf":\[?(\{"@id":"[^"]+"\},)*\{"@id":"' . preg_quote( paulus_journal_url() . '#blog', '#' ) . '"\}#', $html ), 'journal: entry not in the Blog' );
	$check( isset( $g[ paulus_journal_url() . '#blog' ] ), 'journal: Blog node missing' );
}
$g = paulus_test_parse( paulus_test_graph( array( 'kind' => 'journal-archive' ) ), 'journal archive', $check, $required );
$check( (bool) $types( $g, 'Blog' ), 'journal archive: Blog missing' );
$check( (bool) $types( $g, 'CollectionPage' ), 'journal archive: not a CollectionPage' );

// 6. Page kinds: contact, sitemap, the book, a category.
$kinds = array( 'contact' => 'ContactPage', 'sitemap' => 'CollectionPage', 'about-the-author' => 'ProfilePage' );
foreach ( $kinds as $slug => $kind ) {
	$g = paulus_test_parse( paulus_test_graph( array( 'kind' => 'page', 'post' => $by_slug[ $slug ] ) ), $slug, $check, $required );
	$check( (bool) $types( $g, $kind ), $slug . ': not a ' . $kind );
}
$g = paulus_test_parse( paulus_test_graph( array( 'kind' => 'page', 'post' => $by_slug['sitemap'] ) ), 'sitemap', $check, $required );
$check( isset( $g[ get_permalink( $by_slug['sitemap'] ) . '#contents' ] ), 'sitemap: ItemList missing' );
$g = paulus_test_parse( paulus_test_graph( array( 'kind' => 'page', 'post' => $by_slug['the-book'] ) ), 'the-book', $check, $required );
$check( count( $types( $g, 'Chapter' ) ) > 3, 'the-book: chapters missing' );
$GLOBALS['T'] = array( 'kind' => 'category', 'term' => $GLOBALS['TERMS'][0] );
$g = paulus_test_parse( paulus_test_graph( $GLOBALS['T'] ), 'category', $check, $required );
$check( (bool) $types( $g, 'CollectionPage' ), 'category: not a CollectionPage' );

// 7. Every type the graphs use exists in schema.org (when the vocabulary
// file is given: php tests/schema-graph-test.php path/to/schemaorg.jsonld).
if ( ! empty( $argv[1] ) && is_readable( $argv[1] ) ) {
	$vocab = (string) file_get_contents( $argv[1] );
	foreach ( array( 'Chapter', 'CreativeWorkSeries', 'Dataset', 'ImageObject', 'Quotation', 'ContactPage', 'CollectionPage', 'ProfilePage', 'BlogPosting', 'Blog' ) as $t ) {
		$check( false !== strpos( $vocab, '"schema:' . $t . '"' ), 'not in schema.org: ' . $t );
	}
	foreach ( array( 'translationOfWork', 'accessMode', 'accessModeSufficient', 'accessibilityFeature', 'associatedMedia', 'primaryImageOfPage', 'representativeOfPage', 'encodingFormat', 'copyrightNotice', 'acquireLicensePage', 'creditText', 'abstract', 'dateCreated', 'variableMeasured', 'speakable' ) as $prop ) {
		$check( false !== strpos( $vocab, '"schema:' . $prop . '"' ), 'not in schema.org: ' . $prop );
	}
}

echo $fail ? $fail . " failure(s)\n" : "schema graph: all checks passed\n";
exit( $fail ? 1 : 0 );
