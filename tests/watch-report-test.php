<?php
/**
 * Checks how the error-log report gathers log groups into findings, using
 * the messages the live site's dashboard showed on 1 November 2026.
 *
 * Run: php tests/watch-report-test.php
 */

define( 'ABSPATH', __DIR__ . '/' );
if ( ! function_exists( '__' ) ) {
	function __( $s ) {
		return $s;
	}
}
if ( ! function_exists( 'get_stylesheet' ) ) {
	function get_stylesheet() {
		return 'paulus';
	}
}
require dirname( __DIR__ ) . '/inc/watch-report.php';

$fail = 0;
$n    = 0;
function check( $label, $ok ) {
	global $fail, $n;
	++$n;
	if ( ! $ok ) {
		++$fail;
		echo "FAIL: $label\n";
	}
}

$bot  = static fn( $m, $last ) => array( 'message' => $m, 'count' => 4, 'last' => $last, 'fatal' => true, 'source' => 'WordPress' );
$groups = array(
	$bot( 'PHP Fatal error:  Uncaught Error: Call to undefined function add_action() in /home/x/public_html/wp-includes/blocks/rss.php:N', 100 ),
	$bot( 'PHP Fatal error:  Uncaught Error: Call to undefined function add_action() in /home/x/public_html/wp-includes/blocks/search.php:N', 100 ),
	$bot( 'PHP Fatal error:  Uncaught Error: Call to undefined function add_action() in /home/x/public_html/wp-includes/blocks/latest-posts.php:N', 100 ),
	$bot( 'PHP Fatal error:  Uncaught Error: Undefined constant "ABSPATH" in /home/x/public_html/wp-settings.php:N', 100 ),
	array( 'message' => 'WordPress database error Table \'…\' doesn\'t exist for query SHOW FULL COLUMNS FROM `EcN_rank_math_analytics_objects`', 'count' => 92, 'last' => 900, 'fatal' => false, 'source' => 'database' ),
	// As the live log showed it: the table name was blanked out of the grouped message, so only the meaning recorded from the raw line identifies it.
	array( 'message' => 'WordPress database error Table \'…\' doesn\'…\'post\' AND object_id = N', 'count' => 83, 'last' => 800, 'fatal' => false, 'source' => 'database', 'info' => paulus_error_hint_info( 'WordPress database error Table \'db.x_rank_math_analytics_objects\' doesn\'t exist for query SELECT 1 WHERE object_type = \'post\'' ) ),
	array( 'message' => 'Something nobody has seen before in the log', 'count' => 2, 'last' => 500, 'fatal' => false, 'source' => 'other' ),
);

$f = paulus_watch_findings( $groups );
check( 'three findings: Rank Math, the unknown line, the bots', 3 === count( $f ) );
check( 'the blanked-out group is still recognised as Rank Math', 'rank-math-analytics' === paulus_watch_findings( array( $groups[5] ) )[0]['id'] );
check( 'Rank Math first, both its groups merged into one finding of 175', 'rank-math-analytics' === $f[0]['id'] && 175 === $f[0]['count'] && 2 === count( $f[0]['lines'] ) );
check( 'Rank Math finding needs action', 'action' === $f[0]['level'] && '' !== $f[0]['text'] );
check( 'an unknown message comes next, with its own text as the title', 'unknown' === $f[1]['level'] && 0 === strpos( $f[1]['title'], 'Something nobody' ) );
check( 'the four bot groups become one harmless finding of 16 fatal hits, last', 'bots-core-files' === $f[2]['id'] && 16 === $f[2]['count'] && 'noise' === $f[2]['level'] && $f[2]['fatal'] && 4 === count( $f[2]['lines'] ) );
check( 'a hint exists for a known message and is empty for an unknown one', '' !== paulus_error_hint( 'Undefined constant "ABSPATH"' ) && '' === paulus_error_hint( 'nothing known' ) );
check( 'an empty log gives no findings', array() === paulus_watch_findings( array() ) );

echo "Checks: $n\n", $fail ? "FAIL ($fail)\n" : "PASS\n";
exit( $fail ? 1 : 0 );
