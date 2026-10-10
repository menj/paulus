<?php
// Paulus error page drop-in {{VERSION}}. Written by the Paulus theme; safe to delete.
if ( ! headers_sent() ) {
	header( 'HTTP/1.1 500 Internal Server Error', true, 500 );
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
<title>Something went wrong | {{SITE_NAME}}</title>
<link rel="stylesheet" href="{{THEME_URL}}/assets/css/fonts.css">
<link rel="stylesheet" href="{{THEME_URL}}/assets/css/error-page.css">
</head>
<body>
<main role="main">
<div class="card">
<p class="name">{{SITE_NAME}}</p>
<p class="status">Server error</p>
<h1>Something went wrong</h1>
<p>The site hit an unexpected error while preparing this page.</p>
<p>Please try again shortly.</p>
<a class="again" href="{{HOME_URL}}">Try again</a>
</div>
</main>
</body>
</html>
