<?php
// Paulus error page drop-in {{VERSION}}. Written by the Paulus theme; safe to delete.
if ( ! headers_sent() ) {
	header( 'HTTP/1.1 503 Service Unavailable', true, 503 );
	header( 'Content-Type: text/html; charset=utf-8' );
	header( 'Retry-After: 300' );
	header( 'Cache-Control: no-store, max-age=0' );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<meta http-equiv="refresh" content="60">
<title>Temporarily unavailable | {{SITE_NAME}}</title>
<link rel="stylesheet" href="{{THEME_URL}}/assets/css/fonts.css">
<link rel="stylesheet" href="{{THEME_URL}}/assets/css/error-page.css">
</head>
<body>
<main role="main">
<div class="card">
<p class="name">{{SITE_NAME}}</p>
<p class="status">Database error</p>
<h1>Temporarily unavailable</h1>
<p>The site could not reach its database just now. Nothing has been lost.</p>
<p>Please try again in a few minutes.</p>
<a class="again" href="{{HOME_URL}}">Try again</a>
</div>
</main>
</body>
</html>
