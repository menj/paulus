<?php
/**
 * Redirects (inc/redirects.php): every 301 goes through paulus_redirect(),
 * Rank Math answers the query-dependent ones, and the gone-spam Apache block
 * holds no address the site has. WordPress is replaced by small stubs.
 *
 * Run: php tests/redirects-test.php
 *
 * @package Paulus
 */

if ( 'cli' !== PHP_SAPI ) {
	exit;
}
define( 'ABSPATH', __DIR__ . '/' );
$GLOBALS['t_filters'] = array();
function add_action() {}
function add_filter( $name, $fn ) {
	$GLOBALS['t_filters'][ $name ] = $fn;
}
function is_admin() {
	return false;
}
function wp_doing_ajax() {
	return false;
}
function paulus_author_redirect_url() {
	return $GLOBALS['t_author'];
}
function paulus_search_redirect_url() {
	return $GLOBALS['t_search'];
}
$GLOBALS['t_author'] = '';
$GLOBALS['t_search'] = '';
require dirname( __DIR__ ) . '/inc/redirects.php';
define( 'PAULUS_SEC_SPAM_MARKER', 'x' );
$fails = 0;
$check = function ( $label, $got, $want ) use ( &$fails ) {
	if ( $got !== $want ) {
		++$fails;
		echo "FAIL $label\n";
	}
};

// Without Rank Math the theme answers itself: nothing is handed over.
$check( 'no Rank Math, not ready', paulus_rm_redirections_ready(), false );
$check( 'no Rank Math, no rule stored', paulus_rm_store_redirect( 'old', 'https://x.test/new/' ), false );
$check( 'pre_search leaves a null answer', paulus_rm_pre_search( null, '', '' ), null );
$check( 'pre_search keeps an earlier answer', paulus_rm_pre_search( array( 'url_to' => 'a' ), '', '' ), array( 'url_to' => 'a' ) );

// With Rank Math ready, a query-dependent redirect is answered through the filter.
$GLOBALS['t_search'] = 'https://x.test/search/paul/';
$check( 'dynamic url from the search', paulus_dynamic_redirect_url(), 'https://x.test/search/paul/' );
$GLOBALS['t_author'] = 'https://x.test/about/';
$check( 'dynamic url, author first', paulus_dynamic_redirect_url(), 'https://x.test/about/' );
$check( 'Rank Math adds no query string to the theme\'s own', paulus_rm_no_query_string( true, array( 'paulus' => true ) ), false );
$check( 'Rank Math adds it to its own rules', paulus_rm_no_query_string( true, array( 'id' => 3 ) ), true );
$check( 'hooks registered', array_keys( $GLOBALS['t_filters'] ), array( 'rank_math/redirection/pre_search', 'rank_math/redirection/add_query_string' ) );

// No site file sends a 301 on its own any more.
$left = array();
foreach ( glob( dirname( __DIR__ ) . '/inc/*.php' ) as $file ) {
	if ( preg_match( '/wp_safe_redirect\(.*, 301 \)/', (string) file_get_contents( $file ) ) && 'redirects.php' !== basename( $file ) ) {
		$left[] = basename( $file );
	}
}
$check( 'no 301 sent outside paulus_redirect()', $left, array() );

// The gone-spam block.
$src = (string) file_get_contents( dirname( __DIR__ ) . '/inc/security.php' );
preg_match( '/function paulus_sec_spam_lines\(\) \{(.*?)\n\}/s', $src, $m );
preg_match_all( '/RedirectMatch 410 "(.*?)"\',/', $m[1], $rules );
$check( 'three gone rules', count( $rules[1] ), 3 );
$gone = function ( $path ) use ( $rules ) {
	foreach ( $rules[1] as $rule ) {
		$ci = 0 === strpos( $rule, '(?i)' );
		if ( preg_match( '~' . str_replace( '~', '\~', $ci ? substr( $rule, 4 ) : $rule ) . '~' . ( $ci ? 'i' : '' ), $path ) ) {
			return true;
		}
	}
	return false;
};
foreach ( array( '/royal-winner-casino/', '/pokies-open-now-near-me/', '/2025/12/19/hebben-mobiele-gokkasten-kaarttellers/', '/sample-page/', '/hello-world/' ) as $path ) {
	$check( "gone: $path", $gone( $path ), true );
}
$manifest = json_decode( (string) file_get_contents( dirname( __DIR__ ) . '/content/manifest.json' ), true );
foreach ( array( 'posts', 'journal', 'pages' ) as $list ) {
	foreach ( $manifest[ $list ] as $item ) {
		$check( 'kept: ' . $item['slug'], $gone( '/' . $item['slug'] . '/' ) || $gone( '/journal/2026/11/' . $item['slug'] . '/' ), false );
	}
}
foreach ( array( '/', '/the-verdict/', '/journal/2026/11/', '/wp-content/themes/paulus/assets/images/x.avif' ) as $path ) {
	$check( "kept: $path", $gone( $path ), false );
}

echo $fails ? "redirects-test: $fails failed\n" : "redirects-test: all passed\n";
exit( $fails ? 1 : 0 );
