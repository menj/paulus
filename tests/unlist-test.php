<?php
/**
 * Unlisted items (inc/unlist.php): which queries and links leave them out,
 * and the robots and sitemap filters. WordPress is replaced by small stubs.
 *
 * Run: php tests/unlist-test.php
 *
 * @package Paulus
 */

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['t'] = array(
	'options'  => array( 'unlist_posts' => array( 7, '9', 0 ) ),
	'admin'    => false,
	'ajax'     => false,
	'singular' => false,
	'queried'  => 0,
	'enabled'  => true,
	'can'      => false,
	'referer'  => '',
	'filters'  => array(),
);
function add_action() {}
function add_filter( $hook, $cb ) {
	$GLOBALS['t']['filters'][ $hook ][] = $cb;
}
function apply_filters( $hook, $value ) {
	return 'unlist_posts_enabled' === $hook ? $GLOBALS['t']['enabled'] : $value;
}
function get_option( $key, $default = false ) {
	return $GLOBALS['t']['options'][ $key ] ?? $default;
}
function update_option( $key, $value ) {
	$GLOBALS['t']['options'][ $key ] = $value;
	return true;
}
$GLOBALS['t']['meta'] = array();
function update_post_meta( $id, $key, $value ) {
	$GLOBALS['t']['meta'][ $id ][ $key ] = $value;
}
function wp_cache_delete() {}
/** Stand-in for $wpdb: answers the two flag queries from the meta stub. */
class Fake_WPDB {
	public $postmeta = 'wp_postmeta';
	public function prepare( $sql, ...$args ) {
		return $args;
	}
	public function get_col( $args ) {
		$out = array();
		foreach ( $GLOBALS['t']['meta'] as $id => $meta ) {
			if ( isset( $meta[ $args[0] ] ) && $meta[ $args[0] ] === $args[1] ) {
				$out[] = (string) $id;
			}
		}
		return $out;
	}
}
$GLOBALS['wpdb'] = new Fake_WPDB();
function absint( $v ) {
	return abs( (int) $v );
}
function is_admin() {
	return $GLOBALS['t']['admin'];
}
function wp_doing_ajax() {
	return $GLOBALS['t']['ajax'];
}
function is_singular() {
	return $GLOBALS['t']['singular'];
}
function get_queried_object_id() {
	return $GLOBALS['t']['queried'];
}
function current_user_can() {
	return $GLOBALS['t']['can'];
}
function wp_get_referer() {
	return $GLOBALS['t']['referer'];
}
function admin_url() {
	return 'https://example.org/wp-admin/';
}
function wp_robots_no_robots( $robots ) {
	$robots['noindex'] = true;
	return $robots;
}

/** Minimal WP_Query. */
class WP_Query {
	public $vars;
	public $singular;
	public function __construct( $vars = array(), $singular = false ) {
		$this->vars     = $vars;
		$this->singular = $singular;
	}
	public function get( $key ) {
		return $this->vars[ $key ] ?? '';
	}
	public function set( $key, $value ) {
		$this->vars[ $key ] = $value;
	}
	public function is_singular() {
		return $this->singular;
	}
}
/** Minimal WP_Post. */
class WP_Post {
	public $ID;
	public function __construct( $id ) {
		$this->ID = $id;
	}
}

require dirname( __DIR__ ) . '/inc/unlist.php';

$fail  = 0;
$count = 0;
function check( $ok, $label ) {
	global $fail, $count;
	++$count;
	if ( ! $ok ) {
		++$fail;
		echo "FAILED: $label\n";
	}
}
function not_in( $q ) {
	return array_values( array_filter( (array) $q->get( 'post__not_in' ) ) );
}
function reset_state() {
	$GLOBALS['t'] = array_merge( $GLOBALS['t'], array( 'admin' => false, 'ajax' => false, 'singular' => false, 'queried' => 0, 'enabled' => true, 'can' => false, 'referer' => '' ) );
}
function run_filter( $hook, ...$args ) {
	foreach ( $GLOBALS['t']['filters'][ $hook ] ?? array() as $cb ) {
		$args[0] = $cb( ...$args );
	}
	return $args[0];
}

check( array( 7, 9 ) === paulus_unlisted_ids(), 'stored IDs are cleaned' );

// Lists (home page, archives, feeds, search, REST lists) leave unlisted items out.
$q = new WP_Query( array( 'post__not_in' => array( 3 ) ) );
paulus_unlist_query( $q );
check( array( 3, 7, 9 ) === not_in( $q ), 'a list keeps its exclusions and adds the unlisted ones' );

// One item opened by its address is left alone.
foreach ( array( array( array(), true ), array( array( 'p' => 7 ), false ), array( array( 'name' => 'x' ), false ), array( array( 'pagename' => 'x' ), false ) ) as $case ) {
	$q = new WP_Query( $case[0], $case[1] );
	paulus_unlist_query( $q );
	check( array() === not_in( $q ), 'a single-item query is left alone: ' . json_encode( $case[0] ) );
}

// post__in (REST ?include=, theme lists) loses the unlisted IDs; an emptied list matches nothing.
$q = new WP_Query( array( 'post__in' => array( 7, 8 ) ) );
paulus_unlist_query( $q );
check( array( 8 ) === $q->get( 'post__in' ), 'post__in drops unlisted IDs' );
$q = new WP_Query( array( 'post__in' => array( 7, 9 ) ) );
paulus_unlist_query( $q );
check( array( 0 ) === $q->get( 'post__in' ), 'an emptied post__in asks for nothing' );

// Editors at work see everything.
$GLOBALS['t']['admin'] = true;
check( ! paulus_unlist_applies(), 'admin screens see unlisted items' );
$GLOBALS['t']['ajax'] = true;
check( paulus_unlist_applies(), 'AJAX from the front end hides them' );
$GLOBALS['t']['can']     = true;
$GLOBALS['t']['referer'] = 'https://example.org/wp-admin/edit.php';
check( ! paulus_unlist_applies(), 'admin-sent AJAX by an editor sees them' );
reset_state();

// The plugin's switch.
$GLOBALS['t']['enabled'] = false;
check( ! paulus_unlist_applies(), 'unlist_posts_enabled false switches the rules off' );
reset_state();

// Adjacent links and page lists.
check( 'W AND p.ID NOT IN (7,9)' === paulus_unlist_adjacent( 'W' ), 'previous and next links skip them' );
$pages = paulus_unlist_pages( array( new WP_Post( 7 ), new WP_Post( 8 ) ) );
check( 1 === count( $pages ) && 8 === $pages[0]->ID, 'get_pages() drops them' );
check( array( 2, 7, 9 ) === run_filter( 'wp_list_pages_excludes', array( 2 ) ), 'wp_list_pages excludes them' );

// Sitemaps.
$args = run_filter( 'wp_sitemaps_posts_query_args', array() );
check( array( 7, 9 ) === $args['post__not_in'], 'the core sitemap leaves them out' );
check( array( 5, 7, 9 ) === run_filter( 'rank_math/sitemap/posts_to_exclude', array( 5 ) ), 'Rank Math exclusion list (array)' );
check( array( 5, 6, 7, 9 ) === paulus_unlist_rank_math_exclude( '5, 6' ), 'Rank Math exclusion list (string)' );
check( false === paulus_unlist_rank_math_entry( array( 'loc' => 'x' ), 'post', new WP_Post( 9 ) ), 'Rank Math drops an unlisted entry' );
check( array( 'loc' => 'x' ) === paulus_unlist_rank_math_entry( array( 'loc' => 'x' ), 'post', new WP_Post( 8 ) ), 'Rank Math keeps a listed entry' );

// Robots.
$GLOBALS['t']['singular'] = true;
$GLOBALS['t']['queried']  = 7;
$r                        = run_filter( 'wp_robots', array() );
check( ! empty( $r['noindex'] ), 'wp_robots: noindex' );
$r = run_filter( 'rank_math/frontend/robots', array( 'index' => 'index' ) );
check( 'noindex' === $r['index'], 'Rank Math robots: noindex' );
$GLOBALS['t']['queried'] = 8;
$r                       = run_filter( 'rank_math/frontend/robots', array( 'index' => 'index' ) );
check( 'index' === $r['index'], 'a listed item keeps index' );
reset_state();

// Saving.
paulus_unlist_set( 11, true );
paulus_unlist_set( 7, false );
check( array( 9, 11 ) === get_option( 'unlist_posts' ), 'paulus_unlist_set adds and removes IDs, keeping the plugin legacy ID 9' );
check( '1' === $GLOBALS['t']['meta'][11]['_paulus_unlisted'] && '0' === $GLOBALS['t']['meta'][7]['_paulus_unlisted'], 'each change is stored as the item flag' );
// Two saves at once: the second request read the option before the first wrote it.
$GLOBALS['t']['options']['unlist_posts'] = array( 9, 11 );
update_post_meta( 20, '_paulus_unlisted', '1' );   // request A wrote its flag...
paulus_unlist_set( 21, true );                     // ...request B rebuilds and still sees A.
check( array( 9, 11, 20, 21 ) === get_option( 'unlist_posts' ), 'a concurrent change to another item is not lost' );
// A stale option missing an item is repaired from the flags.
$GLOBALS['t']['options']['unlist_posts'] = array();
check( array( 11, 20, 21 ) === paulus_unlist_rebuild(), 'the option is rebuilt from the flags' );

echo "Unlist checks: $count, failed: $fail\n";
exit( $fail ? 1 : 0 );
