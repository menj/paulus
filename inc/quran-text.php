<?php
/**
 * Quranic verse text for the KFGQPC HAFS Uthmanic Script face.
 *
 * The verses in the articles follow the Tanzil encoding: sukun is U+0652 and
 * the small rounded zero over a silent letter is U+06DF. The KFGQPC face
 * follows the Complex's own encoding, where the Madani sukun is U+06E1 and
 * the rounded zero is U+0652; it draws U+06DF as a spacing ornament, so a
 * Tanzil verse shows a round sukun everywhere and a stray circle wherever a
 * letter is silent. Each verse paragraph (class "quran") is mapped as it
 * renders; the stored text stays standard. A verse that already carries
 * U+06E1 is taken to be in the Complex's encoding and left alone.
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

/**
 * Map Tanzil-encoded Uthmani text to the KFGQPC encoding.
 *
 * @param string $text Verse text or markup.
 * @return string
 */
function paulus_quran_text_for_font( $text ) {
	if ( false !== strpos( $text, "\u{06E1}" ) ) {
		return $text;
	}
	return strtr(
		$text,
		array(
			"\u{0652}" => "\u{06E1}",
			"\u{06DF}" => "\u{0652}",
		)
	);
}

/**
 * Apply the mapping to every Quranic verse element in the content.
 *
 * @param string $content Content.
 * @return string
 */
function paulus_quran_verses_filter( $content ) {
	if ( false === strpos( $content, 'quran' ) ) {
		return $content;
	}
	// A mapped element is marked, so a second pass over the same text (an
	// excerpt or a feed that filters it again) cannot map its rounded zeros
	// to sukun.
	return (string) preg_replace_callback(
		'#(<(p|span|div)\b[^>]*\bclass="[^"]*\bquran\b[^"]*"[^>]*)(>)(.*?)(</\2>)#su',
		static function ( $m ) {
			if ( false !== strpos( $m[1], 'data-quran-encoding=' ) ) {
				return $m[0];
			}
			return $m[1] . ' data-quran-encoding="kfgqpc"' . $m[3] . paulus_quran_text_for_font( $m[4] ) . $m[5];
		},
		$content
	);
}
add_filter( 'the_content', 'paulus_quran_verses_filter', 6 );
