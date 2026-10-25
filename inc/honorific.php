<?php
/**
 * The honorific after the name of ʿĪsā ibn Maryam, written in calligraphy.
 *
 * Wherever the text gives "ʿalayhi al-salām" (peace be upon him) straight
 * after his name, the page shows the calligraphy of the phrase
 * (assets/images/alayhi-al-salam.avif, drawn as a mask in the colour of the
 * text, so it follows every colour scheme and prints). The words stay in the
 * stored text and in the page, hidden from the eye and open to screen
 * readers, search engines and a copy and paste; where a browser cannot draw
 * the mask, they show as written. Other names that take the same phrase,
 * such as Ādam, keep the words.
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

/**
 * The calligraphy as markup: the words for readers who cannot see it, inside
 * a span the stylesheet draws as the mask.
 *
 * @return string
 */
function paulus_honorific_html() {
	$words = 'ʿalayhi al-salām, peace be upon him';
	return '<span class="paulus-honorific" lang="ar-Latn" title="' . esc_attr( $words ) . '"><span class="paulus-honorific__text">' . esc_html( $words ) . '</span></span>';
}

/**
 * Replace the written honorific after his name with the calligraphy.
 *
 * @param string $content Rendered content.
 * @return string
 */
function paulus_honorific_filter( $content ) {
	if ( false === strpos( $content, 'ʿalayhi al-salām' ) || is_feed() || is_admin() ) {
		return $content;
	}
	$mark = paulus_honorific_html();
	$name = 'ʿĪsā(?: ibn Maryam| al-Masīḥ)?';
	// "ʿĪsā ibn Maryam (ʿalayhi al-salām, peace be upon him)", with or without the words after the phrase.
	$content = preg_replace(
		'#(' . $name . '(?:</(?:em|strong|a|span)>)*)\s*\(ʿalayhi al-salām(?:, peace be upon him)?\)#u',
		'$1 ' . $mark,
		$content
	);
	// "ʿĪsā ibn Maryam, <em>ʿalayhi al-salām</em> (peace be upon him)".
	$content = preg_replace(
		'#(' . $name . ')\s*,\s*<em>ʿalayhi al-salām</em>\s*\(peace be upon him\)#u',
		'$1 ' . $mark,
		$content
	);
	return $content;
}
add_filter( 'the_content', 'paulus_honorific_filter', 12 );

/**
 * [paulus_lanah] The curse "laʿnat Allāh" (the curse of God) after the name
 * of Paul, in calligraphy (assets/images/lanat-allah.avif, drawn as a mask
 * like the honorific, set in Arslan Wessam). It is never added on its own:
 * it appears only where the text places the shortcode, which is twice, at
 * the close of the Verdict and of the classical Islamic assessment in the
 * chapter on the Islamic view of Paul. The words stay in the page for
 * screen readers and a copy and paste; excerpts and search snippets, which
 * strip shortcodes, leave it out.
 *
 * @return string
 */
function paulus_sc_lanah() {
	$words = 'laʿnat Allāh, the curse of God';
	return '<span class="paulus-honorific paulus-lanah" lang="ar-Latn" title="' . esc_attr( $words ) . '"><span class="paulus-honorific__text">' . esc_html( $words ) . '</span></span>';
}
add_shortcode( 'paulus_lanah', 'paulus_sc_lanah' );
