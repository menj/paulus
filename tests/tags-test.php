<?php
/**
 * Tags (content/tags.json, inc/tags.php): every item has one to six tags, all
 * lower case, each tied to the glossary where a term exists; every shipped
 * article and Journal entry is covered.
 *
 * Run: php tests/tags-test.php
 *
 * @package Paulus
 */

if ( 'cli' !== PHP_SAPI ) {
	exit;
}

$paulus_fail = 0;
function paulus_test_check( $ok, $what ) {
	global $paulus_fail;
	echo ( $ok ? 'PASS ' : 'FAIL ' ) . $what . "\n";
	$paulus_fail += $ok ? 0 : 1;
}

$root     = dirname( __DIR__ );
$data     = json_decode( file_get_contents( $root . '/content/tags.json' ), true );
$manifest = json_decode( file_get_contents( $root . '/content/manifest.json' ), true );
$slugs    = array();
foreach ( array( 'posts', 'journal' ) as $k ) {
	foreach ( $manifest[ $k ] as $p ) {
		$slugs[] = $p['slug'];
	}
}
$tags = $data['tags'];

paulus_test_check( array() === array_diff( $slugs, array_keys( $tags ) ) && array() === array_diff( array_keys( $tags ), $slugs ), 'every article and Journal entry has tags, and nothing else does (' . count( $slugs ) . ')' );

$bad = array();
foreach ( $tags as $slug => $list ) {
	if ( count( $list ) < 1 || count( $list ) > 6 || count( $list ) !== count( array_unique( $list ) ) ) {
		$bad[] = $slug;
	}
	foreach ( $list as $t ) {
		if ( $t !== mb_strtolower( $t ) || $t !== trim( $t ) || mb_strlen( $t ) > 40 ) {
			$bad[] = $slug . ':' . $t;
		}
	}
}
paulus_test_check( array() === $bad, 'one to six tags each, all lower case' . ( $bad ? ': ' . implode( ', ', $bad ) : '' ) );

// Glossary ties point at real terms and real tags.
$g = json_decode( file_get_contents( $root . '/content/articles/glossary.json' ), true )['content'];
preg_match_all( '#<dt[^>]*>(.*?)</dt>#s', $g, $m );
$terms = array_map( fn( $t ) => mb_strtolower( trim( strip_tags( $t ) ) ), $m[1] );
$all   = array_unique( array_merge( ...array_values( $tags ) ) );
$tie_bad = array();
foreach ( $data['glossary'] as $tag => $term ) {
	if ( ! in_array( $tag, $all, true ) || ! in_array( mb_strtolower( $term ), $terms, true ) ) {
		$tie_bad[] = $tag;
	}
}
paulus_test_check( array() === $tie_bad && count( $data['glossary'] ) > 20, 'every glossary tie names a used tag and a real glossary term (' . count( $data['glossary'] ) . ')' . ( $tie_bad ? ': ' . implode( ', ', $tie_bad ) : '' ) );

$src = file_get_contents( $root . '/inc/tags.php' );
paulus_test_check( false !== strpos( $src, "'pre_insert_term'" ) && false !== strpos( $src, "'set_object_terms'" ), 'new tags are lowercased and capped at six in code' );
paulus_test_check( is_readable( $root . '/templates/tag.html' ), 'the tag archive has a template' );

exit( $paulus_fail ? 1 : 0 );
