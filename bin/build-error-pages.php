<?php
/**
 * Build the static error pages in errors/ for the web server's ErrorDocument
 * lines, from the maintenance drop-in's markup. They link the theme's
 * fonts.css and error-page.css, which the web server serves from the theme
 * folder, as the drop-ins link their copies.
 * Run: php bin/build-error-pages.php (add --check to fail when a page is stale).
 *
 * @package Paulus
 */

$paulus_root  = dirname( __DIR__ );
$paulus_check = in_array( '--check', $argv, true );
$paulus_theme = '/wp-content/themes/paulus';
$paulus_site  = 'Apostle of Doom';
$paulus_pages = array(
	'403' => array( 'Access denied', 'Error 403', array( 'This address is closed to visitors.', 'Every page of the site is open from the home page.' ), 'Go to the home page' ),
	'500' => array( 'Something went wrong', 'Error 500', array( 'A fault on our side stopped this page. It is not something you did.', 'Please try again in a few minutes.' ), 'Try again' ),
	'502' => array( 'Bad gateway', 'Error 502', array( 'A server between you and the site gave a wrong answer.', 'Please try again in a few minutes.' ), 'Try again' ),
	'503' => array( 'Back shortly', 'Temporarily unavailable', array( 'The site is busy or offline for a short while.', 'Please try again in a few minutes.' ), 'Try again' ),
);
$paulus_tpl = file_get_contents( $paulus_root . '/dropins/maintenance.php' );
if ( false === $paulus_tpl || '' === $paulus_tpl ) {
	fwrite( STDERR, "cannot read dropins/maintenance.php\n" );
	exit( 1 );
}
$paulus_tpl = ltrim( (string) preg_replace( '/^<\?php.*?\?>/s', '', $paulus_tpl ) );
$paulus_tpl = str_replace( "<meta http-equiv=\"refresh\" content=\"60\">\n", '', $paulus_tpl );
$paulus_tpl = strtr(
	$paulus_tpl,
	array(
		'{{SITE_NAME}}'                       => $paulus_site,
		'{{THEME_URL}}'                       => $paulus_theme,
		'{{HOME_URL}}'                        => '/',
		'{{CONTENT_URL}}/paulus-error-page.css' => $paulus_theme . '/assets/css/error-page.css',
	)
);
$paulus_stale = 0;
if ( ! $paulus_check && ! is_dir( $paulus_root . '/errors' ) ) {
	mkdir( $paulus_root . '/errors' );
}
foreach ( $paulus_pages as $paulus_code => $paulus_p ) {
	$paulus_lines = '';
	foreach ( $paulus_p[2] as $paulus_line ) {
		$paulus_lines .= '<p>' . htmlspecialchars( $paulus_line, ENT_QUOTES, 'UTF-8' ) . "</p>\n";
	}
	$paulus_html = (string) preg_replace(
		array( '~<title>[^<]*</title>~', '~<p class="status">[^<]*</p>~', '~<h1>[^<]*</h1>\n(<p>[^\n]*</p>\n)+~', '~>Try again</a>~' ),
		array( '<title>' . $paulus_p[0] . ' | ' . $paulus_site . '</title>', '<p class="status">' . $paulus_p[1] . '</p>', '<h1>' . $paulus_p[0] . "</h1>\n" . $paulus_lines, '>' . $paulus_p[3] . '</a>' ),
		$paulus_tpl
	);
	$paulus_path = $paulus_root . '/errors/' . $paulus_code . '.html';
	if ( $paulus_check ) {
		if ( ! is_readable( $paulus_path ) || file_get_contents( $paulus_path ) !== $paulus_html ) {
			fwrite( STDERR, "stale: errors/$paulus_code.html\n" );
			++$paulus_stale;
		}
		continue;
	}
	if ( false === file_put_contents( $paulus_path, $paulus_html ) ) {
		fwrite( STDERR, "cannot write errors/$paulus_code.html\n" );
		exit( 1 );
	}
	echo "wrote errors/$paulus_code.html\n";
}
exit( $paulus_stale ? 1 : 0 );
