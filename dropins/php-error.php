<?php
// Paulus error page drop-in {{VERSION}}. Written by the Paulus theme; safe to delete.
// WordPress shows this page when PHP stops with a fatal error. A file missing
// or half written, in the theme or in WordPress itself, is an upload in
// progress: that is answered as maintenance (503, back in a minute or two);
// anything else as a server error (500). Each time, one line goes to
// paulus-incidents.log.php beside this file, for the theme's dashboard; it
// begins with an exit, so it cannot be read over the web.
$paulus_e        = error_get_last();
$paulus_message  = is_array( $paulus_e ) ? (string) $paulus_e['message'] : '';
$paulus_file     = is_array( $paulus_e ) ? (string) $paulus_e['file'] : '';
$paulus_updating = '' !== $paulus_message
	&& preg_match( '~/(wp-content/themes/|wp-includes/|wp-admin/)~', $paulus_file . ' ' . $paulus_message )
	&& preg_match( '~Failed opening required|No such file or directory|syntax error|Unclosed|Unterminated|unexpected end of file|Call to undefined (function|method)|Class "[^"]+" not found~', $paulus_message );
$paulus_log = __DIR__ . '/paulus-incidents.log.php';
if ( ! is_file( $paulus_log ) || filesize( $paulus_log ) < 262144 ) {
	$paulus_line = json_encode(
		array(
			't' => time(),
			'k' => $paulus_updating ? 'update' : 'error',
			'u' => substr( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), 0, 200 ),
			'm' => substr( $paulus_message, 0, 500 ),
			'f' => str_replace( dirname( __DIR__ ), '', $paulus_file ),
			'l' => is_array( $paulus_e ) ? (int) $paulus_e['line'] : 0,
		)
	);
	@file_put_contents( $paulus_log, ( is_file( $paulus_log ) ? '' : "<?php exit; ?>\n" ) . $paulus_line . "\n", FILE_APPEND | LOCK_EX ); // phpcs:ignore
}
if ( ! headers_sent() ) {
	if ( $paulus_updating ) {
		header( 'HTTP/1.1 503 Service Unavailable', true, 503 );
		header( 'Retry-After: 120' );
	} else {
		header( 'HTTP/1.1 500 Internal Server Error', true, 500 );
	}
	header( 'Content-Type: text/html; charset=utf-8' );
	header( 'Cache-Control: no-store, max-age=0' );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<?php if ( $paulus_updating ) : ?><meta http-equiv="refresh" content="60"><?php endif; ?>
<title><?php echo $paulus_updating ? 'Back shortly' : 'Something went wrong'; ?> | {{SITE_NAME}}</title>
<link rel="stylesheet" href="{{THEME_URL}}/assets/css/fonts.css">
<link rel="stylesheet" href="{{CONTENT_URL}}/paulus-error-page.css">
</head>
<body>
<main role="main">
<div class="card">
<p class="name">{{SITE_NAME}}</p>
<?php if ( $paulus_updating ) : ?>
<p class="status">Updating</p>
<h1>Back shortly</h1>
<p>The site is being updated and will be back in a minute or two.</p>
<p>This page will try again by itself.</p>
<?php else : ?>
<p class="status">Server error</p>
<h1>Something went wrong</h1>
<p>The site hit an unexpected error while preparing this page.</p>
<p>Please try again shortly.</p>
<?php endif; ?>
<a class="again" href="{{HOME_URL}}">Try again</a>
</div>
</main>
</body>
</html>
