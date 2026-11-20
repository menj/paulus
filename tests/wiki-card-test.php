<?php
/**
 * Wikipedia card built in (inc/wiki-card.php): the module loads,
 * stands aside while the plugin is active, hooks the front end, the editor
 * and the REST routes, and ships its files in assets/js and assets/css.
 * WordPress is replaced by small stubs.
 *
 * Run: php tests/paulus-wiki-card-test.php
 *
 * @package Paulus
 */

if ( 'cli' !== PHP_SAPI ) {
	exit;
}

define( 'ABSPATH', __DIR__ . '/' );
define( 'PAULUS_DIR', dirname( __DIR__ ) );
define( 'PAULUS_URI', 'https://example.org/theme' );

$GLOBALS['t'] = array( 'actions' => array() );
function add_action( $hook, $cb ) {
	$GLOBALS['t']['actions'][ $hook ][] = $cb;
}

$paulus_fail = 0;
function paulus_test_check( $ok, $what ) {
	global $paulus_fail;
	echo ( $ok ? 'PASS ' : 'FAIL ' ) . $what . "\n";
	$paulus_fail += $ok ? 0 : 1;
}

if ( isset( $argv[1] ) && 'native' === $argv[1] ) {
	require dirname( __DIR__ ) . '/inc/wiki-card.php';
	echo json_encode(
		array(
			'version' => defined( 'PAULUS_WIKI_CARD_VERSION' ) ? PAULUS_WIKI_CARD_VERSION : null,
			'hooks'   => array_keys( $GLOBALS['t']['actions'] ),
		)
	);
	exit;
}

$out = json_decode( (string) shell_exec( 'php ' . escapeshellarg( __FILE__ ) . ' native 2>&1' ), true );
paulus_test_check( is_array( $out ) && '1.17.1' === $out['version'], 'the module loads without the plugin' );
paulus_test_check( is_array( $out ) && array( 'wp_enqueue_scripts', 'enqueue_block_editor_assets', 'init', 'rest_api_init' ) === $out['hooks'], 'it hooks the front end, the editor, the meta and the REST routes' );

foreach ( array( 'js/paulus-wiki-card.js', 'js/paulus-wiki-card-init.js', 'js/paulus-wiki-card-editor.js', 'css/paulus-wiki-card.css', 'css/paulus-wiki-card-link.css', 'css/paulus-wiki-card-editor.css' ) as $f ) {
	$ok = is_readable( PAULUS_DIR . '/assets/' . $f ) && false === strpos( (string) file_get_contents( PAULUS_DIR . '/assets/' . $f ), 'sourceMappingURL' );
	paulus_test_check( $ok, 'assets/' . $f . ' ships, without a source-map link' );
}
paulus_test_check( false !== strpos( (string) file_get_contents( PAULUS_DIR . '/inc/wiki-card/license.txt' ), 'MIT License' ), 'the MIT licence is kept' );

// With the plugin loaded the module only says it is ready.
define( 'WIKIPEDIA_PREVIEW_PLUGIN_VERSION', '1.17.1' );
$GLOBALS['t']['actions'] = array();
require PAULUS_DIR . '/inc/wiki-card.php';
paulus_test_check( function_exists( 'paulus_wiki_card_ready' ) && array() === $GLOBALS['t']['actions'], 'it stands aside while the plugin is loaded' );
$entry = file_get_contents( PAULUS_DIR . '/inc/plugin-retire.php' );
paulus_test_check( false !== strpos( $entry, "'wikipedia-preview/wikipediapreview.php'" ) && false !== strpos( $entry, 'paulus_wiki_card_ready' ), 'plugin-retire lists Wikipedia card' );

exit( $paulus_fail ? 1 : 0 );
