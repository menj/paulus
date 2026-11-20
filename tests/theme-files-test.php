<?php
/**
 * Every file the theme loads (paulus_theme_files() in functions.php) exists
 * in inc/. A missing one puts the whole site behind the maintenance page.
 *
 * Run: php tests/theme-files-test.php
 *
 * @package Paulus
 */

if ( 'cli' !== PHP_SAPI ) {
	exit;
}

$src = file_get_contents( dirname( __DIR__ ) . '/functions.php' );
preg_match( '/function paulus_theme_files\(\)\s*\{\s*return array\(([^;]*)\);/', $src, $m );
preg_match_all( "/'([^']+)'/", $m[1] ?? '', $names );
$missing = array();
foreach ( $names[1] as $n ) {
	if ( ! is_readable( dirname( __DIR__ ) . '/inc/' . $n . '.php' ) ) {
		$missing[] = $n;
	}
}
echo ( count( $names[1] ) > 30 && array() === $missing ? 'PASS ' : 'FAIL ' ) . 'every file the theme loads exists (' . count( $names[1] ) . ')' . ( $missing ? ': missing ' . implode( ', ', $missing ) : '' ) . "\n";
exit( array() === $missing ? 0 : 1 );
