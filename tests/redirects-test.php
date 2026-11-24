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
if ( in_array( 'rank-math', $argv, true ) ) {
	eval( 'namespace RankMath; class Helper { public static function is_module_active( $id ) { return "redirections" === $id; } }
	namespace RankMath\\Redirections; class DB { public static $rows = array(); public static function match_redirections( $uri ) { return self::$rows[ $uri ] ?? false; } }
	class Redirection { public $data; public static function from( $d ) { $o = new self(); $o->data = $d; return $o; } public function is_infinite_loop() { return false; } public function save() { DB::$rows[ $this->data["sources"][0]["pattern"] ] = $this->data; return 1; } }' );
	$GLOBALS['wpdb'] = new class() {
		public $prefix = 'wp_';
		public function prepare( $q, ...$a ) {
			return $a[0];
		}
		public function get_var( $t ) {
			return $t;
		}
	};
	function wp_safe_redirect() {
		throw new RuntimeException( 'redirected' );
	}
}
require dirname( __DIR__ ) . '/inc/redirects.php';
if ( in_array( 'rank-math', $argv, true ) ) {
	$ok = paulus_rm_redirections_ready();
	$_SERVER['QUERY_STRING'] = '';
	$ok = $ok && paulus_rm_store_redirect( 'old/post', 'https://x.test/new/' ) && isset( \RankMath\Redirections\DB::$rows['old/post'] ) && 'https://x.test/new/' === \RankMath\Redirections\DB::$rows['old/post']['url_to'];
	$GLOBALS['t_search'] = 'https://x.test/search/paul/';
	$pre = paulus_rm_pre_search( null, '', '' );
	$ok  = $ok && is_array( $pre ) && 'https://x.test/search/paul/' === $pre['url_to'] && 301 === $pre['header_code'] && false === paulus_rm_no_query_string( true, $pre );
	$before = count( \RankMath\Redirections\DB::$rows );
	$_SERVER['QUERY_STRING'] = '0';
	// A query of "0" is a query: the rule is not stored.
	try {
		paulus_redirect( 'https://x.test/b/', 301, 'with/query' );
	} catch ( Throwable $e ) {
		unset( $e );
	}
	echo $ok && $before === count( \RankMath\Redirections\DB::$rows ) && ! isset( \RankMath\Redirections\DB::$rows['with/query'] ) ? "rm-ok\n" : "rm-fail\n";
	exit;
}
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

// No site file sends a 301 on its own any more, on one line or several, with wp_safe_redirect or wp_redirect.
$left = array();
foreach ( glob( dirname( __DIR__ ) . '/inc/*.php' ) as $file ) {
	$code = (string) file_get_contents( $file );
	if ( 'redirects.php' !== basename( $file ) && ( preg_match( '/wp_safe_redirect\s*\([^;]*\b301\b[^;]*\)\s*;/s', $code ) || preg_match( '/\bwp_redirect\s*\([^;]*\b301\b/s', $code ) ) ) {
		$left[] = basename( $file );
	}
}
$check( 'no 301 sent outside paulus_redirect()', $left, array() );

// The hand-over to Rank Math, with Rank Math's classes stood in for, in a process of its own
// (the "ready" answer is cached for the request).
$out = (string) shell_exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( __FILE__ ) . ' rank-math 2>&1' );
$check( 'with Rank Math ready: rules stored, the filter answers, no rule for a query address', false !== strpos( $out, 'rm-ok' ), true );
if ( false === strpos( $out, 'rm-ok' ) ) {
	echo $out;
}

// The gone-spam block.
$src = (string) file_get_contents( dirname( __DIR__ ) . '/inc/security.php' );
preg_match( '/function paulus_sec_spam_lines\(\) \{(.*?)\n\}/s', $src, $m );
preg_match( '/\$words = \'(.*?)\';/', $m[1], $w );
preg_match_all( '/RedirectMatch 410 "(.*?)"\'[,;]/', $m[1], $rules );
$rules[1] = array_map( static function ( $r ) use ( $w ) { return str_replace( "' . \$words . '", $w[1], $r ); }, $rules[1] );
$check( 'three gone rules in the source', count( $rules[1] ), 3 );
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
