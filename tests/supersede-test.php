<?php
/**
 * Superseded content (inc/supersede.php): the map cleaning and the guards that stop removals.
 * WordPress is replaced by small stubs.
 *
 * Run: php tests/supersede-test.php
 *
 * @package Paulus
 */

if ( 'cli' !== PHP_SAPI ) {
	exit;
}
define( 'ABSPATH', __DIR__ . '/' );
function add_action() {}
function wp_json_encode( $d ) {
	return json_encode( $d );
}
$GLOBALS['t_admin']   = false;
$GLOBALS['t_ajax']    = false;
$GLOBALS['t_editor']  = false;
$GLOBALS['t_referer'] = '';
function is_admin() {
	return $GLOBALS['t_admin'];
}
function wp_doing_ajax() {
	return $GLOBALS['t_ajax'];
}
function current_user_can() {
	return $GLOBALS['t_editor'];
}
function wp_get_referer() {
	return $GLOBALS['t_referer'];
}
function admin_url() {
	return 'https://example.test/wp-admin/';
}
function sanitize_title( $s ) {
	return trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( (string) $s ) ), '-' );
}
require dirname( __DIR__ ) . '/inc/supersede.php';

$fail  = 0;
$check = static function ( $name, $got, $want ) use ( &$fail ) {
	if ( $got !== $want ) {
		++$fail;
		echo "FAIL $name\n  got:  " . var_export( $got, true ) . "\n  want: " . var_export( $want, true ) . "\n";
	}
};

$check( 'the map is cleaned', paulus_supersedes_map( array( 'supersedes' => array( 'Old Post' => 'New Post', 'same' => 'same', '' => 'x', 'a' => '' ) ) ), array( 'old-post' => 'new-post' ) );
$check( 'no map, no pairs', paulus_supersedes_map( array() ), array() );
$check( 'a map that is not an array is ignored', paulus_supersedes_map( array( 'supersedes' => 'nope' ) ), array() );

$check( 'removals run when everything is in order', paulus_removal_guard( false, true, 100, 100 ), '' );
$check( 'a first sync has no earlier count', paulus_removal_guard( false, true, 5, 0 ), '' );
$check( 'the kill switch stops removals', '' !== paulus_removal_guard( true, true, 100, 100 ), true );
$check( 'a bad checksum stops removals', '' !== paulus_removal_guard( false, false, 100, 100 ), true );
$check( 'a drop of exactly 10 percent still runs', paulus_removal_guard( false, true, 90, 100 ), '' );
$check( 'a drop of more than 10 percent stops removals', '' !== paulus_removal_guard( false, true, 89, 100 ), true );

$check( 'no include list, nothing to cut', paulus_supersede_cut_includes( array(), array( 5 ) ), null );
$check( 'a superseded ID leaves the include list', paulus_supersede_cut_includes( array( 3, 5, 7 ), array( 5 ) ), array( 3, 7 ) );
$check( 'an emptied include list asks for a post that cannot exist', paulus_supersede_cut_includes( array( 5 ), array( 5 ) ), array( 0 ) );
$check( 'IDs given as strings are cut too', paulus_supersede_cut_includes( array( '5', '8' ), array( 5 ) ), array( 8 ) );

$check( 'the state key changes when the guard clears', paulus_supersede_state_key( array( 'a' => 'b' ), 'blocked' ) !== paulus_supersede_state_key( array( 'a' => 'b' ), '' ), true );
$check( 'the state key changes when the map changes', paulus_supersede_state_key( array( 'a' => 'b' ), '' ) !== paulus_supersede_state_key( array( 'a' => 'c' ), '' ), true );
$check( 'the same map and state give the same key', paulus_supersede_state_key( array( 'a' => 'b' ), 'x' ), paulus_supersede_state_key( array( 'a' => 'b' ), 'x' ) );

$src = (string) file_get_contents( dirname( __DIR__ ) . '/inc/supersede.php' );
$check( 'the redirect runs at priority 1', false !== strpos( $src, "'template_redirect', 'paulus_supersede_redirect', 1" ), true );
$check( 'an item with no file fails the integrity check', false !== strpos( $src, 'empty( $item[\'file\'] ) || ! is_string( $item[\'file\'] )' ), true );
$check( 'only shipped text is retired', false !== strpos( $src, 'paulus_is_shipped_text( $old_post )' ), true );
$check( 'the count baseline is not written by the retire step', 1 === substr_count( $src, "update_option( 'paulus_manifest_count'" ), true );
$check( 'the fallback covers Rank Math robots and sitemap', 3 === substr_count( $src, "'rank_math/" ), true );
// paulus_supersede_hides(): the public site hides, an editor at work does not.
$check( 'a front-end request hides', paulus_supersede_hides(), true );
$GLOBALS['t_admin'] = true;
$check( 'an admin screen does not hide', paulus_supersede_hides(), false );
$GLOBALS['t_ajax']   = true;
$GLOBALS['t_editor'] = true;
$GLOBALS['t_referer'] = 'https://example.test/wp-admin/edit.php';
$check( 'an editor\'s AJAX call from a wp-admin screen does not hide', paulus_supersede_hides(), false );
$GLOBALS['t_admin']   = false;
$GLOBALS['t_referer'] = 'https://example.test/some-article/';
$check( 'an editor\'s AJAX call from the public site hides', paulus_supersede_hides(), true );
$GLOBALS['t_editor']  = false;
$GLOBALS['t_referer'] = 'https://example.test/wp-admin/edit.php';
$check( 'a visitor\'s AJAX call with an admin referer hides', paulus_supersede_hides(), true );
$GLOBALS['t_ajax'] = false;
$check( 'the module deletes nothing for good', false === strpos( $src, 'wp_delete_post' ), true );
$check( 'the module uses the Trash', false !== strpos( $src, 'wp_trash_post' ), true );

echo $fail ? "$fail failed\n" : "supersede-test: all passed\n";
exit( $fail ? 1 : 0 );
