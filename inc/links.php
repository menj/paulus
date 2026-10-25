<?php
/**
 * Links in the text to the author's other sites, bestofislam.org and
 * injil.help: where an article, an entry or a page names a thing those
 * sites treat at length (the Ebionites, the Islamic Dilemma, the Gospel of
 * Thomas, Matthew 5:17, Quran 4:157, the Talmud), the first mention in the
 * running text links to the page there. The links are made as the text
 * renders, so every article carries them, an edited one included, and the
 * stored text is untouched. The phrases and their addresses are a field on
 * Theme Options, Links, one per line: "phrase | address".
 *
 * A phrase is matched whole, without regard to capitals, in the running
 * text only: never inside a link, a heading, a quotation, a caption, a
 * footnote, a reference mark, Arabic or Greek, or any tag. Each address is
 * linked once a page, at the first mention of any of its phrases, up to the
 * number set on the same tab.
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

/**
 * The phrases and their addresses, from the option: "phrase | address" per
 * line, in the order given. Several phrases may share an address.
 *
 * @return array<int,array{phrase:string,url:string}>
 */
function paulus_text_links() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$out = array();
	foreach ( preg_split( '/\R/u', (string) paulus_option( 'text_links_map' ) ) as $line ) {
		$parts = array_map( 'trim', explode( '|', $line, 2 ) );
		if ( count( $parts ) < 2 || '' === $parts[0] || ! preg_match( '#^https?://#i', $parts[1] ) ) {
			continue;
		}
		$out[] = array( 'phrase' => $parts[0], 'url' => $parts[1] );
	}
	return $cache = $out;
}

/**
 * Link the first mention of each phrase in the running text.
 *
 * @param string $content Rendered content.
 * @return string
 */
function paulus_text_links_filter( $content ) {
	if ( ! paulus_option( 'text_links' ) || is_admin() || ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$links = paulus_text_links();
	$max   = max( 1, min( 20, (int) paulus_option( 'text_links_max' ) ) );
	if ( ! $links || false === strpos( $content, '<' ) ) {
		return $content;
	}
	// Each address once a page, and never to the page itself.
	$here = (string) get_permalink();
	$done = array();
	$made = 0;

	// Where no link may be made: inside these elements, at any depth. A
	// paragraph class marks the Arabic and Greek blocks; the footnotes are a
	// list with its own class.
	$skip_tags = array( 'a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'q', 'figcaption', 'figure', 'sup', 'sub', 'code', 'pre', 'cite', 'script', 'style', 'nav', 'table', 'button', 'label', 'select', 'textarea', 'summary' );
	$skip_class = '/\bclass="[^"]*\b(?:footnotes|quran|hadith|arabic|greek|paulus-contents|paulus-faq-index|paulus-alpha|paulus-honorific|paulus-anchor|paulus-figure|paulus-chart)\b/';

	// One pattern of every phrase, longest first, and the address each
	// phrase (lower-cased) leads to.
	$phrases   = wp_list_pluck( $links, 'phrase' );
	$by_phrase = array();
	foreach ( $links as $link ) {
		$by_phrase[ paulus_ustrtolower( $link['phrase'] ) ] = $link['url'];
	}
	usort(
		$phrases,
		static function ( $a, $b ) {
			return paulus_ulen( $b ) <=> paulus_ulen( $a );
		}
	);
	$pattern = '/(?<![\p{L}\p{N}])(?:' . implode( '|', array_map( static function ( $p ) { return preg_quote( $p, '/' ); }, $phrases ) ) . ')(?![\p{L}\p{N}])/iu';
	$pieces  = preg_split( '/(<[^>]+>)/', $content, -1, PREG_SPLIT_DELIM_CAPTURE );
	if ( false === $pieces ) {
		return $content;
	}
	$stack = array(); // Open elements that forbid links, by tag name.
	$out   = '';
	foreach ( $pieces as $piece ) {
		if ( '' === $piece ) {
			continue;
		}
		if ( '<' === $piece[0] ) {
			if ( preg_match( '#^<(/?)([a-zA-Z][a-zA-Z0-9]*)#', $piece, $m ) ) {
				$tag     = strtolower( $m[2] );
				$closing = '/' === $m[1];
				$void    = in_array( $tag, array( 'br', 'img', 'hr', 'wbr', 'input', 'source', 'meta', 'link' ), true );
				if ( $closing ) {
					// Close the innermost matching element on the stack.
					for ( $i = count( $stack ) - 1; $i >= 0; $i-- ) {
						if ( $stack[ $i ] === $tag ) {
							array_splice( $stack, $i, 1 );
							break;
						}
					}
				} elseif ( ! $void && '/' !== substr( $piece, -2, 1 ) && ( in_array( $tag, $skip_tags, true ) || preg_match( $skip_class, $piece ) ) ) {
					$stack[] = $tag;
				}
			}
			$out .= $piece;
			continue;
		}
		if ( $stack || $made >= $max || '' === trim( $piece ) ) {
			$out .= $piece;
			continue;
		}
		// The text is walked in its own order, every phrase at once, so the
		// first mention on the page is the one linked whatever the order of
		// the list; a longer phrase is tried before a shorter one it begins
		// with. A whole phrase: no letter or digit on either side, so
		// "Ebionites" does not match inside "Ebionitism" and "church" not
		// inside "Islamicjerusalem church". The text is plain here, so no tag
		// or entity can be cut by the match.
		$piece = preg_replace_callback(
			$pattern,
			static function ( $m ) use ( $by_phrase, $links, $here, &$done, &$made, $max ) {
				$url = $by_phrase[ paulus_ustrtolower( $m[0] ) ] ?? '';
				// Without mbstring only ASCII letters are lowered, so a match
				// with an accented capital is looked up phrase by phrase.
				if ( '' === $url ) {
					foreach ( $links as $l ) {
						if ( preg_match( '/^' . preg_quote( $l['phrase'], '/' ) . '$/iu', $m[0] ) ) {
							$url = $l['url'];
							break;
						}
					}
				}
				if ( '' === $url || $made >= $max || isset( $done[ $url ] ) || 0 === strpos( $here, $url ) ) {
					return $m[0];
				}
				$done[ $url ] = true;
				++$made;
				return '<a class="paulus-xlink" href="' . esc_url( $url ) . '" rel="noopener">' . $m[0] . '</a>';
			},
			$piece
		);
		$out .= $piece;
	}
	return $out;
}
// After the honorific (12), before the Answers index and the page
// contents (20) read the headings.
add_filter( 'the_content', 'paulus_text_links_filter', 15 );
