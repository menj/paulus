<?php
/**
 * Clean search addresses (inc/search-permalinks.php): the address a
 * query-string search is sent to, page numbers, empty searches and the
 * rewrite flush marker. WordPress is replaced by small stubs.
 *
 * Run: php tests/search-rewrite-test.php
 *
 * @package Paulus
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'PAULUS_VERSION', '9.9.9' );

$GLOBALS['t'] = array( 'options' => array( 'permalink_structure' => '/%postname%/' ), 'flushed' => 0, 'actions' => array() );
function add_action( $hook, $cb ) {
	$GLOBALS['t']['actions'][ $hook ][] = $cb;
}
function get_option( $key, $default = false ) {
	return $GLOBALS['t']['options'][ $key ] ?? $default;
}
function update_option( $key, $value ) {
	$GLOBALS['t']['options'][ $key ] = $value;
	return true;
}
function sanitize_key( $s ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $s ) );
}
function flush_rewrite_rules() {
	++$GLOBALS['t']['flushed'];
}
function run_init() {
	foreach ( $GLOBALS['t']['actions']['init'] as $cb ) {
		$cb();
	}
}

require dirname( __DIR__ ) . '/inc/search-permalinks.php';

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

$h = 'https://example.org/';
check( 'https://example.org/search/paul/' === paulus_search_pretty_url( $h, 'search', 'paul' ), 'a term becomes /search/term/' );
check( 'https://example.org/search/paul/' === paulus_search_pretty_url( 'https://example.org', 'search', ' paul ' ), 'home without a slash, term trimmed' );
check( 'https://example.org/search/road%20to%20damascus/' === paulus_search_pretty_url( $h, 'search', 'road to damascus' ), 'spaces are encoded' );
check( 'https://example.org/search/a%2Fb/' === paulus_search_pretty_url( $h, 'search', 'a/b' ), 'a slash cannot add a path segment' );
check( 'https://example.org/cari/paul/' === paulus_search_pretty_url( $h, 'cari', 'paul' ), 'a custom base' );
check( 'https://example.org/search/paul/page/2/' === paulus_search_pretty_url( $h, 'search', 'paul', 2 ), 'page 2 goes into the path' );
check( 'https://example.org/search/paul/' === paulus_search_pretty_url( $h, 'search', 'paul', 1 ), 'page 1 has no page segment' );
check( 'https://example.org/search/paul/page/3/?orderby=date' === paulus_search_pretty_url( $h, 'search', 'paul', 3, array( 'orderby' => 'date' ) ), 'other query variables travel along' );
check( '' === paulus_search_pretty_url( $h, 'search', '' ), 'an empty search gets no address, so no redirect' );
check( '' === paulus_search_pretty_url( $h, 'search', '   ' ), 'a blank search gets no address' );

check( 'search' === paulus_search_base(), 'the default base is search' );
$GLOBALS['t']['options']['wpseosearch_base'] = 'Cari!';
check( 'cari' === paulus_search_base(), 'the plugin option is read and cleaned' );
$GLOBALS['t']['options']['wpseosearch_base'] = '';
check( 'search' === paulus_search_base(), 'an empty option falls back to search' );

// Flush once per version and base, never on an ordinary load.
$GLOBALS['wp_rewrite'] = null;
run_init();
run_init();
check( 1 === $GLOBALS['t']['flushed'], 'the first load after an update flushes once, the next does not' );
check( '9.9.9|search' === get_option( 'paulus_search_rewrite_built' ), 'the marker holds version and base' );
$GLOBALS['t']['options']['wpseosearch_base'] = 'cari';
run_init();
check( 2 === $GLOBALS['t']['flushed'], 'a new base flushes once' );
$GLOBALS['t']['options']['paulus_search_rewrite_built'] = '9.9.8|cari';
run_init();
run_init();
check( 3 === $GLOBALS['t']['flushed'], 'a new theme version flushes once' );
$GLOBALS['t']['options']['permalink_structure'] = '';
$GLOBALS['t']['options']['paulus_search_rewrite_built'] = 'old';
run_init();
check( 3 === $GLOBALS['t']['flushed'], 'plain permalinks: nothing to flush' );

echo "Search address checks: $count, failed: $fail\n";
exit( $fail ? 1 : 0 );
