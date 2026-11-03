<?php
/**
 * Renamed addresses: every old path in the manifest resolves to the new one.
 *
 * Run: php tests/renamed-test.php
 *
 * @package Paulus
 */

define( 'ABSPATH', __DIR__ . '/' );

function add_action() {}

require dirname( __DIR__ ) . '/inc/renamed.php';

$manifest = json_decode( (string) file_get_contents( dirname( __DIR__ ) . '/content/manifest.json' ), true );
$fail     = 0;

$expect = array(
	'/appendices/the-2023-edition/'                    => array( 'page', 'appendices/the-first-edition' ),
	'appendices/the-2023-edition'                      => array( 'page', 'appendices/the-first-edition' ),
	'/site-map/'                                       => array( 'page', 'sitemap' ),
	'/the-witnesses/from-ibn-hazm-to-al-faruqi/'       => array( 'post', 'ibn-hazm-and-al-thalabi' ),
	'/the-2023-edition/'                               => null,
	'/appendices/the-first-edition/'                   => null,
);

foreach ( $expect as $path => $want ) {
	$hit = paulus_renamed_target( $path, $manifest );
	$got = $hit ? array( $hit['type'], $hit['path'] ) : null;
	if ( $got !== $want ) {
		echo 'FAIL: ' . $path . ' gave ' . json_encode( $got ) . ', expected ' . json_encode( $want ) . "\n";
		++$fail;
	}
}

// No live address carries a digit, and every renamed target exists.
$slugs = array();
foreach ( array( 'posts', 'journal', 'pages' ) as $list ) {
	foreach ( $manifest[ $list ] as $item ) {
		$slugs[] = $item['slug'];
		if ( preg_match( '/\d/', $item['slug'] ) ) {
			echo 'FAIL: ' . $item['slug'] . " carries a digit\n";
			++$fail;
		}
	}
}
foreach ( paulus_renamed_map( $manifest ) as $old => $hit ) {
	if ( in_array( $old, $slugs, true ) ) {
		echo "FAIL: $old is renamed but still live\n";
		++$fail;
	}
}

echo $fail ? "$fail failure(s)\n" : "OK: renamed addresses resolve\n";
exit( $fail ? 1 : 0 );
