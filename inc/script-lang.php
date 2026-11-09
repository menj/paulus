<?php
/**
 * Language of passing words (WCAG 3.1.2 Language of Parts).
 *
 * The articles quote Arabic and Greek words inside English sentences. A screen
 * reader needs lang="ar" or lang="grc" on them to pronounce them. Marking up
 * 800 runs by hand in the content files would change every article's hash, so
 * the filter does it on output: a run of Arabic-script or Greek-script text in
 * the body that sits outside an element with its own lang attribute is
 * wrapped in a span. Nothing in the stored content changes.
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

/**
 * Wrap unmarked Arabic and Greek runs in a lang span.
 *
 * @param string $content Post content.
 * @return string
 */
function paulus_script_lang_filter( $content ) {
	if ( ! is_string( $content ) || ! preg_match( '/[\x{0600}-\x{06FF}\x{0370}-\x{03FF}\x{1F00}-\x{1FFF}]/u', $content ) ) {
		return $content;
	}
	$parts = preg_split( '/(<[^>]*>)/', $content, -1, PREG_SPLIT_DELIM_CAPTURE );
	if ( ! is_array( $parts ) ) {
		return $content;
	}
	$void  = array( 'br', 'hr', 'img', 'input', 'meta', 'link', 'wbr', 'source', 'col', 'area', 'base', 'embed', 'param', 'track' );
	$stack = array(); // One entry per open element: true when it carries a lang attribute, or is script/style.
	$out   = '';
	foreach ( $parts as $part ) {
		if ( '' !== $part && '<' === $part[0] ) {
			if ( preg_match( '#^<\s*/\s*([a-z0-9]+)#i', $part ) ) {
				array_pop( $stack );
			} elseif ( preg_match( '#^<\s*([a-z0-9]+)#i', $part, $m ) && ! in_array( strtolower( $m[1] ), $void, true ) && '/>' !== substr( $part, -2 ) ) {
				$stack[] = (bool) preg_match( '/\slang\s*=/i', $part ) || in_array( strtolower( $m[1] ), array( 'script', 'style', 'code', 'pre' ), true );
			}
			$out .= $part;
			continue;
		}
		if ( in_array( true, $stack, true ) ) {
			$out .= $part;
			continue;
		}
		$part = preg_replace_callback(
			'/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]+(?:[ \x{060C}\x{061B}\x{061F}]+[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]+)*/u',
			static function ( $m ) {
				return '<span lang="ar">' . $m[0] . '</span>';
			},
			$part
		);
		$part = preg_replace_callback(
			'/[\x{0370}-\x{03FF}\x{1F00}-\x{1FFF}]+(?:[ ]+[\x{0370}-\x{03FF}\x{1F00}-\x{1FFF}]+)*/u',
			static function ( $m ) {
				return '<span lang="grc">' . $m[0] . '</span>';
			},
			is_string( $part ) ? $part : ''
		);
		$out .= (string) $part;
	}
	return $out;
}
add_filter( 'the_content', 'paulus_script_lang_filter', 100 );
