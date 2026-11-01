<?php
/**
 * The error-log report: what a log message means, and the log's groups
 * gathered into a short list of findings, most pressing first.
 *
 * No WordPress calls beyond translation and the active theme's name, so
 * tests/watch-report-test.php runs it on its own.
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

/**
 * What a recurring log message means and what to do about it, for the ones
 * this site's logs have shown.
 *
 * Levels: "action" (something to fix), "upload" (a file was caught half
 * written during an upload; clears itself), "noise" (harmless).
 *
 * @param string $msg Log message.
 * @return array{id:string,title:string,level:string,text:string}|array{} Empty when the message is not known.
 */
function paulus_error_hint_info( $msg ) {
	$theme = function_exists( 'get_stylesheet' ) ? get_stylesheet() : 'paulus';
	$known = array(
		'rank_math_analytics_objects'           => array( 'rank-math-analytics', __( 'Rank Math Analytics table is missing', 'paulus' ), 'action', __( 'The table is missing from the database. In Rank Math, switch the Analytics module off and on again (Dashboard, Modules), or open Status & Tools, Database Tools, and create its tables again. If you do not use Rank Math Analytics, switch the module off.', 'paulus' ) ),
		'wps_scan_lock'                         => array( 'scan-lock', __( 'Two security scans started at once', 'paulus' ), 'noise', __( 'Two runs of the security plugin\'s scan started together. Harmless.', 'paulus' ) ),
		'cron event list could not be saved'    => array( 'cron-save', __( 'WordPress saved its schedule twice at once', 'paulus' ), 'noise', __( 'Usually two requests saving the schedule together. Harmless unless it repeats.', 'paulus' ) ),
		'Undefined constant "ABSPATH"'          => array( 'bots-core-files', __( 'Bots requesting WordPress core files directly', 'paulus' ), 'noise', __( 'A bot asked for one of WordPress\'s internal files by its address. PHP could not run it outside WordPress, so it failed. Harmless.', 'paulus' ) ),
		'Call to undefined function add_action' => array( 'bots-core-files', __( 'Bots requesting WordPress core files directly', 'paulus' ), 'noise', __( 'A bot asked for one of WordPress\'s internal files by its address. PHP could not run it outside WordPress, so it failed. Harmless.', 'paulus' ) ),
		'Undefined array key "url"'             => array( 'author-snippet', __( 'Rank Math author snippet warning', 'paulus' ), 'noise', __( 'Rank Math\'s author snippet on an author archive. The theme now redirects those pages before they render.', 'paulus' ) ),
		'themes/' . $theme . '/inc/'            => array( 'theme-upload', __( 'Theme file missing during an upload', 'paulus' ), 'upload', __( 'A theme file was missing or half written while the theme was uploaded by FTP, and visitors saw the maintenance page. Use Maintenance mode while uploading.', 'paulus' ) ),
		'/wp-includes/'                         => array( 'core-upload', __( 'WordPress file missing during an upload', 'paulus' ), 'upload', __( 'A WordPress file was missing, most often while WordPress itself was uploaded by FTP. Use Maintenance mode while uploading, or update from Dashboard, Updates.', 'paulus' ) ),
	);
	foreach ( $known as $needle => $row ) {
		if ( false !== strpos( $msg, $needle ) ) {
			return array( 'id' => $row[0], 'title' => $row[1], 'level' => $row[2], 'text' => $row[3] );
		}
	}
	return array();
}

/**
 * The explanation alone, for the emails and for callers that want text.
 *
 * @param string $msg Log message.
 * @return string Empty when there is nothing to add.
 */
function paulus_error_hint( $msg ) {
	$info = paulus_error_hint_info( $msg );
	return $info ? $info['text'] : '';
}

/**
 * Gather the log's groups into findings: every group with the same meaning
 * becomes one finding, with its lines kept underneath. Those that need a look
 * come first, then unrecognised messages, then upload mishaps, and last the
 * harmless noise.
 *
 * @param array<int|string, array> $groups Groups as paulus_php_error_summary() returns them (each may carry 'info', the meaning found on the raw line).
 * @return array<int, array{id:string,title:string,level:string,text:string,count:int,fatal:bool,last:int,lines:array}>
 */
function paulus_watch_findings( array $groups ) {
	$out = array();
	foreach ( $groups as $g ) {
		// The group's message has its quoted values blanked, so the meaning is
		// the one recorded when the raw line was read; the message is a fallback.
		$info = isset( $g['info'] ) ? (array) $g['info'] : paulus_error_hint_info( (string) $g['message'] );
		$id   = $info ? $info['id'] : 'other-' . md5( (string) $g['message'] );
		if ( ! isset( $out[ $id ] ) ) {
			$out[ $id ] = array(
				'id'    => $id,
				'title' => $info ? $info['title'] : mb_substr( (string) $g['message'], 0, 110 ),
				'level' => $info ? $info['level'] : 'unknown',
				'text'  => $info ? $info['text'] : '',
				'count' => 0,
				'fatal' => false,
				'last'  => 0,
				'lines' => array(),
			);
		}
		$out[ $id ]['count'] += (int) $g['count'];
		$out[ $id ]['fatal']  = $out[ $id ]['fatal'] || ! empty( $g['fatal'] );
		$out[ $id ]['last']   = max( $out[ $id ]['last'], (int) $g['last'] );
		$out[ $id ]['lines'][] = array(
			'message' => (string) $g['message'],
			'count'   => (int) $g['count'],
			'source'  => (string) ( $g['source'] ?? '' ),
			'last'    => (int) $g['last'],
		);
	}
	$rank = array( 'action' => 0, 'unknown' => 1, 'upload' => 2, 'noise' => 3 );
	uasort(
		$out,
		static fn( $a, $b ) => ( $rank[ $a['level'] ] <=> $rank[ $b['level'] ] ) ?: ( (int) $b['fatal'] <=> (int) $a['fatal'] ) ?: ( $b['count'] <=> $a['count'] )
	);
	return array_values( $out );
}
