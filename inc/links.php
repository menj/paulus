<?php
/**
 * Contextual links in the text: where an article, a Journal entry or a page
 * names a thing this site or one of the author's other sites treats at
 * length, the first mention in the running text links to that page. Pages
 * here link to the article on Paul's conversion or the Jerusalem Council;
 * pages there to the Ebionites on injil.help, Quran 4:157 on bestofislam.org,
 * the Council of Nicaea on abrahamic-religions.com, and so on.
 *
 * The phrases come from inc/data/network-links.json, one file shared by the
 * seven themes of the network and identical in each: every entry names the
 * site that owns the page, its path there and the phrases that lead to it.
 * Entries for this site become links to home_url( path ), so a staging copy
 * links to itself; entries for the others go to that site's address. The
 * lines on Theme Options, Contextual links ("phrase | address") are read
 * first, so they add to the file and win over it.
 *
 * The links are made as the text renders, so every article carries them, an
 * edited one included, and the stored text is untouched. A phrase is matched
 * whole, without regard to capitals, in the running text only: never inside a
 * link, a heading, a quotation, a caption, a footnote, a reference mark, a
 * table, Arabic, Hebrew, Syriac or Greek, or any tag. Each address is linked
 * once a page, at the first mention of any of its phrases, at most one new
 * link to a paragraph or list item, and never to the page itself; links to
 * this site and to the others each have their own limit.
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

/**
 * This site's key in the shared map.
 *
 * @return string
 */
function paulus_ctx_site_key() {
	return 'paulus';
}

/**
 * Lower-case host of an address, without "www.".
 *
 * @param string $url Address.
 * @return string
 */
function paulus_ctx_host( $url ) {
	$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
	return 0 === strpos( $host, 'www.' ) ? substr( $host, 4 ) : $host;
}

/**
 * A path compared the same way whatever its capitals, query or final slash.
 *
 * @param string $url Address or path.
 * @return string
 */
function paulus_ctx_path( $url ) {
	$path = (string) wp_parse_url( $url, PHP_URL_PATH );
	return strtolower( '/' . trim( $path, '/' ) );
}

/**
 * The shared map, decoded once a request.
 *
 * @return array{sites:array<string,string>,links:array}
 */
function paulus_ctx_map() {
	static $map = null;
	if ( null === $map ) {
		$file = PAULUS_DIR . '/inc/data/network-links.json';
		$data = is_readable( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : null; // phpcs:ignore WordPress.WP.AlternativeFunctions
		$data = is_array( $data ) ? $data : array();
		$map  = apply_filters(
			'paulus_contextual_links_map',
			array(
				'sites' => isset( $data['sites'] ) && is_array( $data['sites'] ) ? $data['sites'] : array(),
				'links' => isset( $data['links'] ) && is_array( $data['links'] ) ? $data['links'] : array(),
			)
		);
	}
	return $map;
}

/**
 * Whether a saved field of phrases is the 28-line default of 2.62 and
 * earlier, untouched. Those lines are now in the shared map; read as
 * overrides they would win over it (the "Injīl" line led to injil.help's home
 * page), so until the 2.63.0 upgrade empties the field on the first admin
 * visit, the field is read as empty.
 *
 * @param string $saved Saved field.
 * @return bool
 */
function paulus_ctx_is_retired_default( $saved ) {
	return 'a75cf3f68b96b86ff8d264166fdf69d4' === md5( trim( str_replace( "\r\n", "\n", (string) $saved ) ) );
}

/**
 * An address on this site from a path, which may already carry the folder
 * WordPress is installed in ("/blog/the-man/" under home "/blog").
 *
 * @param string $path Path.
 * @return string
 */
function paulus_ctx_own_url( $path ) {
	$base = untrailingslashit( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ) );
	if ( '' !== $base && ( $path === $base || 0 === strpos( $path, $base . '/' ) ) ) {
		$path = substr( $path, strlen( $base ) );
	}
	return home_url( '' === $path ? '/' : $path );
}

/**
 * Every phrase with its address and kind ("own" for this site, "sister" for
 * the others), the lines on Theme Options first. A line's address is a path
 * on this site ("/the-man/the-damascus-road/"), or an address on this site or
 * on one of the sites the map names; any other site is refused. The first
 * entry for a phrase wins, capitals aside.
 *
 * @return array<int,array{phrase:string,url:string,kind:string}>
 */
function paulus_text_links() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$map    = paulus_ctx_map();
	$own    = paulus_ctx_site_key();
	$home   = paulus_ctx_host( home_url( '/' ) );
	$sister = array();
	foreach ( $map['sites'] as $key => $base ) {
		if ( $key !== $own && is_string( $base ) && '' !== $base ) {
			$sister[ paulus_ctx_host( $base ) ] = true;
		}
	}
	$own_base = isset( $map['sites'][ $own ] ) ? paulus_ctx_host( $map['sites'][ $own ] ) : '';

	$entries = array();
	$saved = (string) paulus_option( 'text_links_map' );
	foreach ( paulus_ctx_is_retired_default( $saved ) ? array() : preg_split( '/\r\n|\r|\n/', $saved ) as $line ) {
		$parts = array_map( 'trim', explode( '|', $line, 2 ) );
		if ( count( $parts ) < 2 || '' === $parts[0] || '' === $parts[1] ) {
			continue;
		}
		$addr = $parts[1];
		if ( '/' === $addr[0] && '/' !== ( $addr[1] ?? '' ) ) {
			$entries[] = array( 'phrase' => $parts[0], 'url' => paulus_ctx_own_url( $addr ), 'kind' => 'own' );
			continue;
		}
		if ( ! preg_match( '#^https?://#i', $addr ) ) {
			continue;
		}
		$host = paulus_ctx_host( $addr );
		if ( $host === $home || ( '' !== $own_base && $host === $own_base ) ) {
			$path  = (string) wp_parse_url( $addr, PHP_URL_PATH );
			$entries[] = array( 'phrase' => $parts[0], 'url' => paulus_ctx_own_url( $path ), 'kind' => 'own' );
		} elseif ( isset( $sister[ $host ] ) ) {
			$entries[] = array( 'phrase' => $parts[0], 'url' => $addr, 'kind' => 'sister' );
		}
	}
	foreach ( $map['links'] as $link ) {
		if ( empty( $link['site'] ) || empty( $link['path'] ) || empty( $link['phrases'] ) || ! isset( $map['sites'][ $link['site'] ] ) ) {
			continue;
		}
		$kind = $link['site'] === $own ? 'own' : 'sister';
		$url  = 'own' === $kind ? home_url( $link['path'] ) : untrailingslashit( $map['sites'][ $link['site'] ] ) . $link['path'];
		foreach ( (array) $link['phrases'] as $phrase ) {
			$phrase = trim( (string) $phrase );
			if ( '' !== $phrase ) {
				$entries[] = array( 'phrase' => $phrase, 'url' => $url, 'kind' => $kind );
			}
		}
	}

	$out  = array();
	$seen = array();
	foreach ( $entries as $entry ) {
		$key = paulus_ctx_key( $entry['phrase'] );
		if ( ! isset( $seen[ $key ] ) ) {
			$seen[ $key ] = true;
			$out[]        = $entry;
		}
	}
	return $cache = $out;
}

/**
 * The field of extra phrases as saved. sanitize_textarea_field() strips every
 * percent-encoded octet, so "/the-man/%D8%A7/" was saved as "/the-man//";
 * here the phrase is cleaned as text and the address as an address.
 *
 * @param string $text Submitted field.
 * @return string
 */
function paulus_ctx_sanitize_phrases( $text ) {
	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $text ) as $line ) {
		$parts = array_map( 'trim', explode( '|', $line, 2 ) );
		if ( count( $parts ) < 2 ) {
			$line = sanitize_text_field( $line );
			if ( '' !== $line ) {
				$out[] = $line;
			}
			continue;
		}
		$addr = $parts[1];
		if ( '' !== $addr && '/' === $addr[0] && '/' !== ( $addr[1] ?? '' ) ) {
			// A path: letters outside ASCII encoded, then only URL path characters.
			$addr = preg_replace_callback( '/[\x80-\xFF]/', static function ( $m ) { return rawurlencode( $m[0] ); }, $addr );
			$addr = preg_replace( '#[^A-Za-z0-9\-._~%!$&\'()*+,;=:@/]#', '', $addr );
		} elseif ( preg_match( '#^https?://#i', $addr ) ) {
			$addr = esc_url_raw( $addr );
		} else {
			$addr = sanitize_text_field( $addr );
		}
		$out[] = sanitize_text_field( $parts[0] ) . ' | ' . $addr;
	}
	return implode( "\n", $out );
}

/**
 * The field of excluded slugs as saved: each one as WordPress stores a slug,
 * so a percent-encoded (non-ASCII) slug keeps its octets.
 *
 * @param string $text Submitted field.
 * @return string
 */
function paulus_ctx_sanitize_slugs( $text ) {
	$out = array();
	foreach ( preg_split( '/[\s,]+/', (string) $text, -1, PREG_SPLIT_NO_EMPTY ) as $slug ) {
		$slug = sanitize_title( $slug );
		if ( '' !== $slug ) {
			$out[] = $slug;
		}
	}
	return implode( "\n", $out );
}

/**
 * A phrase or a match with every apostrophe written as "'". wptexturize (10)
 * runs before the links (15) and writes "Mark's" as "Mark&#8217;s", so the
 * text and the map are compared with their apostrophes made alike.
 *
 * @param string $text Phrase or matched text.
 * @return string
 */
function paulus_ctx_apostrophes( $text ) {
	return str_replace( array( '&#8217;', '&#8216;', '&#039;', '&#39;', '&rsquo;', '&lsquo;', '&apos;', "\u{2019}", "\u{2018}", "\u{02BC}" ), "'", $text );
}

/**
 * The key a phrase or a match is looked up by: apostrophes made alike, every
 * run of spaces (a non-breaking one too, as a character or an entity) one
 * space, and lower case.
 *
 * @param string $text Phrase or matched text.
 * @return string
 */
function paulus_ctx_key( $text ) {
	$text = str_replace( array( '&nbsp;', '&#160;', '&#xA0;', '&#xa0;', "\u{00A0}" ), ' ', paulus_ctx_apostrophes( $text ) );
	return paulus_ustrtolower( (string) preg_replace( '/\s+/', ' ', trim( $text ) ) );
}

/**
 * Any form of apostrophe, and any run of spaces (a non-breaking one included,
 * as a character or an entity), as pattern fragments.
 *
 * @return array{0:string,1:string}
 */
function paulus_ctx_fragments() {
	return array(
		"(?:'|\u{2019}|\u{2018}|\u{02BC}|&\#8217;|&\#8216;|&\#0?39;|&rsquo;|&lsquo;|&apos;)",
		'(?:\s|&nbsp;|&\#160;|&\#x[aA]0;|\x{00A0})+',
	);
}

/**
 * The pattern for one phrase: any form of apostrophe matches any other, and a
 * space matches any run of spaces. With $named the two are written as calls
 * to the subpatterns q and s that paulus_ctx_compiled() defines once, which
 * keeps a pattern of hundreds of phrases within PCRE's size limit.
 *
 * @param string $phrase Phrase.
 * @param bool   $named  Refer to the defined subpatterns.
 * @return string
 */
function paulus_ctx_phrase_pattern( $phrase, $named = false ) {
	$quoted = preg_quote( (string) preg_replace( '/\s+/', ' ', trim( paulus_ctx_apostrophes( $phrase ) ) ), '/' );
	return str_replace( array( "'", ' ' ), $named ? array( '(?&q)', '(?&s)' ) : paulus_ctx_fragments(), $quoted );
}

/**
 * One pattern of the given phrases, longest first, whole phrases only.
 *
 * @param string[] $phrases Phrases, longest first.
 * @return string
 */
function paulus_ctx_pattern_of( $phrases ) {
	list( $q, $s ) = paulus_ctx_fragments();
	$alts = array();
	foreach ( $phrases as $phrase ) {
		$alts[] = paulus_ctx_phrase_pattern( $phrase, true );
	}
	return '/(?(DEFINE)(?<q>' . $q . ')(?<s>' . $s . '))(?<![\p{L}\p{N}])(?:' . implode( '|', $alts ) . ')(?![\p{L}\p{N}])/iu';
}

/**
 * The phrases as patterns that each compile: one while it fits PCRE's size
 * limit, halves of the list where it does not. A phrase that will not compile
 * on its own is left out.
 *
 * @param string[] $phrases Phrases, longest first.
 * @return string[]
 */
function paulus_ctx_patterns_of( $phrases ) {
	if ( ! $phrases ) {
		return array();
	}
	$pattern = paulus_ctx_pattern_of( $phrases );
	if ( false !== @preg_match( $pattern, '' ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors
		return array( $pattern );
	}
	if ( 1 === count( $phrases ) ) {
		error_log( 'Paulus: a contextual-link phrase does not compile and is left out: ' . $phrases[0] ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
		return array();
	}
	$half = (int) ceil( count( $phrases ) / 2 );
	return array_merge( paulus_ctx_patterns_of( array_slice( $phrases, 0, $half ) ), paulus_ctx_patterns_of( array_slice( $phrases, $half ) ) );
}

/**
 * Link the matches of the patterns in a run of text, walked in the text's
 * order: the leftmost match first and, at the same place, the longest. The
 * callback returns the replacement, or null to pass the match by; a match
 * passed by does not hide a phrase that overlaps it ("Peter and Paul of
 * Tarsus", where "Peter and Paul" is already linked, still links "Paul of
 * Tarsus"), but a phrase wholly inside it stays unlinked ("Barnabas" in
 * "Gospel of Barnabas"). Null when a pattern fails on the text (invalid
 * UTF-8), so the caller keeps the text as it was.
 *
 * @param string[] $patterns Patterns.
 * @param string   $text     Text.
 * @param callable $replace  Gets the matched text, returns its replacement or null.
 * @return string|null
 */
function paulus_ctx_replace( $patterns, $text, $replace ) {
	$out    = '';
	$copied = 0; // Bytes of $text already written to $out.
	$pos    = 0; // Where the next search starts.
	$passed = 0; // End of the furthest match passed by.
	$len    = strlen( $text );
	while ( $pos < $len ) {
		$best = null;
		foreach ( $patterns as $pattern ) {
			$found = preg_match( $pattern, $text, $m, PREG_OFFSET_CAPTURE, $pos );
			if ( false === $found ) {
				return null;
			}
			if ( $found && ( null === $best || $m[0][1] < $best[1] || ( $m[0][1] === $best[1] && strlen( $m[0][0] ) > strlen( $best[0] ) ) ) ) {
				$best = $m[0];
			}
		}
		if ( null === $best ) {
			break;
		}
		$start = $best[1];
		$end   = $start + strlen( $best[0] );
		$with  = $end > $passed ? $replace( $best[0] ) : null;
		if ( null === $with ) {
			// Pass it by and search again from its next character.
			$passed = max( $passed, $end );
			$byte   = ord( $text[ $start ] );
			$pos    = $start + ( $byte >= 0xF0 ? 4 : ( $byte >= 0xE0 ? 3 : ( $byte >= 0xC0 ? 2 : 1 ) ) );
			continue;
		}
		$out   .= substr( $text, $copied, $start - $copied ) . $with;
		$copied = $end;
		$pos    = $end;
	}
	return $out . substr( $text, $copied );
}

/**
 * The entries of the kinds switched on, the lower-cased phrase to its entry,
 * and the patterns of every phrase, longest first. Built once a request.
 *
 * @param bool $own    Links to this site.
 * @param bool $sister Links to the other sites.
 * @return array{0:array,1:array<string,array>,2:string[]}
 */
function paulus_ctx_compiled( $own, $sister ) {
	static $cache = array();
	$key = ( $own ? 'o' : '' ) . ( $sister ? 's' : '' );
	if ( isset( $cache[ $key ] ) ) {
		return $cache[ $key ];
	}
	$links = array();
	foreach ( paulus_text_links() as $entry ) {
		if ( ( 'own' === $entry['kind'] && $own ) || ( 'sister' === $entry['kind'] && $sister ) ) {
			$links[] = $entry;
		}
	}
	$by_phrase = array();
	$phrases   = array();
	foreach ( $links as $entry ) {
		$by_phrase[ paulus_ctx_key( $entry['phrase'] ) ] = $entry;
		$phrases[] = $entry['phrase'];
	}
	usort(
		$phrases,
		static function ( $a, $b ) {
			return paulus_ulen( $b ) <=> paulus_ulen( $a );
		}
	);
	// A list past PCRE's size limit (many extra lines on the tab) is split
	// into patterns that each compile, matched together.
	return $cache[ $key ] = array( $links, $by_phrase, paulus_ctx_patterns_of( $phrases ) );
}

/**
 * Link the first mention of each phrase in the running text.
 *
 * @param string $content Rendered content.
 * @return string
 */
function paulus_text_links_filter( $content ) {
	// Only the queried post is linked, never another post's text a page may
	// show. The check is the queried object, not in_the_loop(), so the links
	// do not depend on whether a block template enters the loop.
	if ( is_admin() || is_feed() || ! is_singular() || is_front_page() || ! is_main_query() || doing_filter( 'get_the_excerpt' ) || (int) get_the_ID() !== (int) get_queried_object_id() ) {
		return $content;
	}
	$own_on    = (bool) paulus_option( 'ctx_own' );
	$sister_on = (bool) paulus_option( 'text_links' );
	$caps      = array(
		'own'    => $own_on ? max( 0, min( 15, (int) paulus_option( 'ctx_own_max' ) ) ) : 0,
		'sister' => $sister_on ? max( 0, min( 10, (int) paulus_option( 'ctx_sister_max' ) ) ) : 0,
	);
	if ( ( ! $caps['own'] && ! $caps['sister'] ) || false === strpos( $content, '<' ) ) {
		return $content;
	}
	$post = get_post();
	if ( $post && in_array( $post->post_name, preg_split( '/[\s,]+/', strtolower( (string) paulus_option( 'ctx_exclude' ) ), -1, PREG_SPLIT_NO_EMPTY ), true ) ) {
		return $content;
	}
	if ( ! apply_filters( 'paulus_contextual_links_enabled', true, $post ) ) {
		return $content;
	}
	list( $links, $by_phrase, $patterns ) = paulus_ctx_compiled( $caps['own'] > 0, $caps['sister'] > 0 );
	if ( ! $patterns ) {
		return $content;
	}
	$here = paulus_ctx_path( (string) get_permalink() );
	$done = array();
	$made = array( 'own' => 0, 'sister' => 0 );
	$used = false; // A link already added in the current paragraph or list item.

	// Where no link may be made: inside these elements, at any depth. A
	// paragraph class marks the Arabic and Greek blocks; the footnotes are a
	// list with its own class; a lang attribute marks the other scripts.
	$skip_tags  = array( 'a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'q', 'figcaption', 'figure', 'sup', 'sub', 'code', 'pre', 'cite', 'script', 'style', 'nav', 'table', 'button', 'label', 'select', 'textarea', 'summary' );
	$skip_class = '/\bclass=(?:["\'][^"\']*)?\b(?:footnotes|quran|hadith|arabic|greek|paulus-contents|paulus-faq-index|paulus-alpha|paulus-honorific|paulus-anchor|paulus-figure|paulus-chart)\b/';
	$skip_lang  = '/\blang=(["\']?)(?:ar|he|el|grc|syr)(?:-[a-z0-9-]*)?\1(?=[\s\/>])/i';

	$pieces = preg_split( '/(<(?:[^>"\']|"[^"]*"|\'[^\']*\')*>)/', $content, -1, PREG_SPLIT_DELIM_CAPTURE );
	if ( false === $pieces ) {
		return $content;
	}
	$stack = array(); // Open elements that forbid links, by tag name.
	$li    = 0;       // Open list items: a paragraph inside one shares its link.
	$lists = array(); // The list-item depth at each open list, so a list item
	                  // left unclosed ("<li>a<li>b</ul>") ends with its list.
	$out   = '';
	foreach ( $pieces as $piece ) {
		if ( '' === $piece ) {
			continue;
		}
		if ( '<' === $piece[0] ) {
			if ( preg_match( '#^<(/?)([a-zA-Z][a-zA-Z0-9]*)#', $piece, $m ) ) {
				$tag     = strtolower( $m[2] );
				$closing = '/' === $m[1];
				$void    = in_array( $tag, array( 'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr' ), true );
				// One new link to a list item, however many paragraphs it holds,
				// and one to each paragraph outside a list item.
				if ( 'ul' === $tag || 'ol' === $tag ) {
					if ( $closing ) {
						$li = (int) array_pop( $lists );
					} else {
						$lists[] = $li;
					}
				} elseif ( 'li' === $tag ) {
					$li   = $closing ? max( 0, $li - 1 ) : $li + 1;
					$used = false;
				} elseif ( 'p' === $tag && 0 === $li ) {
					$used = false;
				}
				if ( $closing ) {
					// Close the innermost matching element on the stack, and any
					// element left open inside it, as a browser does.
					for ( $i = count( $stack ) - 1; $i >= 0; $i-- ) {
						if ( $stack[ $i ] === $tag ) {
							array_splice( $stack, $i );
							break;
						}
					}
				} elseif ( ! $void && '/' !== substr( $piece, -2, 1 ) && ( $stack || in_array( $tag, $skip_tags, true ) || preg_match( $skip_class, $piece ) || preg_match( $skip_lang, $piece ) ) ) {
					// Inside a skip region every element is tracked, so a nested
					// element of the same name cannot close the region early.
					$stack[] = $tag;
				}
			}
			$out .= $piece;
			continue;
		}
		if ( $stack || $used || ( $made['own'] >= $caps['own'] && $made['sister'] >= $caps['sister'] ) || '' === trim( $piece ) ) {
			$out .= $piece;
			continue;
		}
		// The text is walked in its own order, every phrase at once, so the
		// first mention on the page is the one linked whatever the order of
		// the list; a longer phrase is tried before a shorter one it begins
		// with. A whole phrase: no letter or digit on either side, so
		// "Ebionites" does not match inside "Ebionitism". The text is plain
		// here, so no tag or entity can be cut by the match.
		$linked = paulus_ctx_replace(
			$patterns,
			$piece,
			static function ( $found ) use ( $by_phrase, $links, $here, $caps, &$done, &$made, &$used ) {
				$m     = array( $found );
				$entry = $by_phrase[ paulus_ctx_key( $m[0] ) ] ?? null;
				// Without mbstring only ASCII letters are lowered, so a match
				// with an accented capital is looked up phrase by phrase.
				if ( null === $entry ) {
					foreach ( $links as $l ) {
						if ( preg_match( '/^' . paulus_ctx_phrase_pattern( $l['phrase'] ) . '$/iu', $m[0] ) ) {
							$entry = $l;
							break;
						}
					}
				}
				if ( null === $entry || $used || isset( $done[ $entry['url'] ] ) || $made[ $entry['kind'] ] >= $caps[ $entry['kind'] ] ) {
					return null;
				}
				if ( 'own' === $entry['kind'] && paulus_ctx_path( $entry['url'] ) === $here ) {
					return null;
				}
				$done[ $entry['url'] ] = true;
				++$made[ $entry['kind'] ];
				$used = true;
				if ( 'sister' === $entry['kind'] ) {
					return '<a class="paulus-ctx-link paulus-ctx-link--sister" href="' . esc_url( $entry['url'] ) . '" rel="noopener">' . $m[0] . '</a>';
				}
				return '<a class="paulus-ctx-link" href="' . esc_url( $entry['url'] ) . '">' . $m[0] . '</a>';
			}
		);
		// On invalid UTF-8 the /u patterns fail and the result is null: the
		// piece is kept as it was rather than dropped.
		$out .= null === $linked ? $piece : $linked;
	}
	return $out;
}
// After the honorific (12), before the Answers index and the page
// contents (20) read the headings.
add_filter( 'the_content', 'paulus_text_links_filter', 15 );
