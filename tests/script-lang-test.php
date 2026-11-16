<?php
/**
 * Language of parts (inc/script-lang.php): unmarked Arabic and Greek runs get a
 * lang span, marked ones and code are left alone.
 *
 * Run: php tests/script-lang-test.php
 *
 * @package Paulus
 */

if ( 'cli' !== PHP_SAPI ) {
	exit;
}
define( 'ABSPATH', __DIR__ . '/' );
function add_filter() {}
require dirname( __DIR__ ) . '/inc/script-lang.php';

$fail = 0;
function paulus_t( $name, $got, $want ) {
	global $fail;
	if ( $got !== $want ) {
		++$fail;
		echo "FAIL $name\n  got:  $got\n  want: $want\n";
	}
}

paulus_t( 'arabic', paulus_script_lang_filter( '<p>word (الحواريون, “x”)</p>' ), '<p>word (<span lang="ar">الحواريون</span>, “x”)</p>' );
paulus_t( 'greek', paulus_script_lang_filter( '<p>ἀπόστολος here</p>' ), '<p><span lang="grc">ἀπόστολος</span> here</p>' );
paulus_t( 'marked', paulus_script_lang_filter( '<p><span lang="grc">λόγος</span></p>' ), '<p><span lang="grc">λόγος</span></p>' );
paulus_t( 'nested', paulus_script_lang_filter( '<p class="greek" lang="grc"><em>λόγος</em> ἦν</p>' ), '<p class="greek" lang="grc"><em>λόγος</em> ἦν</p>' );
paulus_t( 'code', paulus_script_lang_filter( '<code>ب</code>' ), '<code>ب</code>' );
paulus_t( 'plain', paulus_script_lang_filter( '<p>English only <br> text</p>' ), '<p>English only <br> text</p>' );
paulus_t( 'attribute', paulus_script_lang_filter( '<p title="الله">x</p>' ), '<p title="الله">x</p>' );

echo $fail ? "$fail failed\n" : "script-lang-test: all passed\n";
exit( $fail ? 1 : 0 );
