<?php
/**
 * Structured data in depth. Every page and every component of the site is
 * described with the most specific schema.org type that is true of it, and
 * the page's main nodes are deepened, whether the theme or Rank Math built
 * them. Nothing is declared that the page does not contain: a type that
 * misdescribes content breaks Google's structured-data policy and puts a
 * false statement into the site's data.
 *
 * Entry point: paulus_schema_deepen( $nodes ), applied to the theme's own
 * graph (paulus_print_schema_graph) and to Rank Math's (its json_ld filter).
 *
 * Linked-data identifiers (Wikipedia, Wikidata) were each verified against
 * Wikipedia's API on 24 September 2026.
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

/**
 * Stable node identifiers for the site-wide entities.
 *
 * @param string $key Entity key.
 * @return string
 */
function paulus_sid( $key ) {
	// The author and publisher take the identifiers of the nodes that
	// already describe them on the page (Rank Math names its own), so the
	// graph never holds two unconnected copies of either.
	if ( ! empty( $GLOBALS['paulus_sd_ids'][ $key ] ) ) {
		return $GLOBALS['paulus_sd_ids'][ $key ];
	}
	// The book is the node the book page describes (paulus_book_schema).
	if ( 'book' === $key ) {
		$page = get_page_by_path( 'the-book' );
		if ( $page && 'publish' === $page->post_status ) {
			return get_permalink( $page ) . '#book';
		}
	}
	return home_url( '/' ) . '#' . $key;
}

/**
 * Whether a graph refers to an identifier.
 *
 * @param mixed  $graph Nodes.
 * @param string $id    Identifier.
 * @return bool
 */
function paulus_schema_refers( $graph, $id ) {
	$json = (string) wp_json_encode( $graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	return false !== strpos( $json, '"' . $id . '"' );
}

/**
 * Types of a node as a list.
 *
 * @param array $node Node.
 * @return string[]
 */
function paulus_node_types( $node ) {
	$t = $node['@type'] ?? array();
	return is_array( $t ) ? $t : array( $t );
}

/**
 * Whether a node carries one of the given types.
 *
 * @param array    $node  Node.
 * @param string[] $types Types.
 * @return bool
 */
function paulus_node_is( $node, $types ) {
	return (bool) array_intersect( paulus_node_types( $node ), $types );
}

/**
 * Add a type to a node, keeping its existing ones.
 *
 * @param array  $node Node.
 * @param string $type Type.
 * @return array
 */
function paulus_node_add_type( $node, $type ) {
	$types = paulus_node_types( $node );
	if ( ! in_array( $type, $types, true ) ) {
		$types[] = $type;
	}
	$node['@type'] = 1 === count( $types ) ? $types[0] : array_values( $types );
	return $node;
}

/**
 * Set a property only where the node has none.
 *
 * @param array  $node  Node.
 * @param string $prop  Property.
 * @param mixed  $value Value.
 * @return array
 */
function paulus_node_default( $node, $prop, $value ) {
	if ( ! isset( $node[ $prop ] ) && null !== $value && '' !== $value && array() !== $value ) {
		$node[ $prop ] = $value;
	}
	return $node;
}

/**
 * The subjects the site treats, as linked entities.
 *
 * @return array<string, array>
 */
function paulus_schema_entities() {
	return array(
		'paul'   => array(
			'@type'         => 'Person',
			'@id'           => paulus_sid( 'paul-of-tarsus' ),
			'name'          => 'Paul of Tarsus',
			'alternateName' => array( 'Saul of Tarsus', 'Paul the Apostle', 'Paulus' ),
			'birthPlace'    => array(
				'@type'  => 'Place',
				'name'   => 'Tarsus, Cilicia',
				'sameAs' => array( 'https://en.wikipedia.org/wiki/Tarsus,_Mersin', 'https://www.wikidata.org/wiki/Q134287' ),
			),
			'sameAs'        => array( 'https://en.wikipedia.org/wiki/Paul_the_Apostle', 'https://www.wikidata.org/wiki/Q9200' ),
		),
		'isa'    => array(
			'@type'         => 'Person',
			'@id'           => paulus_sid( 'isa-ibn-maryam' ),
			'name'          => 'ʿĪsā ibn Maryam',
			'alternateName' => array( 'Jesus', 'Jesus son of Mary' ),
			'sameAs'        => array( 'https://en.wikipedia.org/wiki/Jesus_in_Islam', 'https://www.wikidata.org/wiki/Q51664', 'https://www.wikidata.org/wiki/Q302' ),
		),
		'acts'   => array(
			'@type'  => 'Book',
			'@id'    => paulus_sid( 'acts-of-the-apostles' ),
			'name'   => 'The Acts of the Apostles',
			'sameAs' => array( 'https://en.wikipedia.org/wiki/Acts_of_the_Apostles', 'https://www.wikidata.org/wiki/Q40309' ),
		),
		'kjv'    => array(
			'@type'  => 'Book',
			'@id'    => paulus_sid( 'king-james-version' ),
			'name'   => 'The Holy Bible, King James Version',
			'sameAs' => array( 'https://en.wikipedia.org/wiki/King_James_Version', 'https://www.wikidata.org/wiki/Q623398' ),
		),
		'pew'    => array(
			'@type'  => 'ResearchOrganization',
			'@id'    => paulus_sid( 'pew-research-center' ),
			'name'   => 'Pew Research Center',
			'url'    => 'https://www.pewresearch.org/',
			'sameAs' => array( 'https://en.wikipedia.org/wiki/Pew_Research_Center', 'https://www.wikidata.org/wiki/Q1635722' ),
		),
		'martin' => array(
			'@type'       => 'Person',
			'@id'         => paulus_sid( 'dale-b-martin' ),
			'name'        => 'Dale B. Martin',
			'affiliation' => array( '@type' => 'CollegeOrUniversity', 'name' => 'Yale University' ),
			'sameAs'      => array( 'https://en.wikipedia.org/wiki/Dale_Martin_(scholar)', 'https://www.wikidata.org/wiki/Q26923442' ),
		),
	);
}

/**
 * The book the site is drawn from, as a reference node.
 *
 * @return array
 */
function paulus_schema_book_ref() {
	$book = array(
		'@type'      => 'Book',
		'@id'        => paulus_sid( 'book' ),
		'name'       => (string) paulus_option( 'book_title' ),
		'author'     => array( '@id' => paulus_sid( 'author' ) ),
		'inLanguage' => 'ms',
	);
	if ( paulus_option( 'book_isbn' ) ) {
		$book['isbn'] = (string) paulus_option( 'book_isbn' );
	}
	$page = get_page_by_path( 'the-book' );
	if ( $page && 'publish' === $page->post_status ) {
		$book['url'] = get_permalink( $page );
	}
	return $book;
}

/**
 * Plain text from markup, collapsed.
 *
 * @param string $html Markup.
 * @return string
 */
function paulus_schema_text( $html ) {
	$html = preg_replace( '#<sup>.*?</sup>#s', '', (string) $html );
	$html = preg_replace( '#\[/?paulus_[^\]]*\]#', '', $html );
	return trim( preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
}

/**
 * The page's own stored content.
 *
 * @return string
 */
function paulus_schema_content() {
	return is_singular() ? (string) get_post_field( 'post_content', get_queried_object_id() ) : '';
}

/**
 * The footnotes of the current text, as citations.
 *
 * @param string $content Content.
 * @return string[]
 */
function paulus_schema_citations( $content ) {
	if ( ! preg_match( '#<ol class="footnotes"[^>]*>(.*?)</ol>#s', $content, $m ) ) {
		return array();
	}
	preg_match_all( '#<li[^>]*>(.*?)</li>#s', $m[1], $li );
	$out = array();
	foreach ( $li[1] as $note ) {
		$note = preg_replace( '#<a [^>]*aria-label="Back to text"[^>]*>.*?</a>#s', '', $note );
		$text = paulus_schema_text( $note );
		if ( '' !== $text ) {
			$out[] = mb_substr( $text, 0, 400 );
		}
	}
	return array_slice( array_values( array_unique( $out ) ), 0, 100 );
}

/**
 * The scripture quoted in the current text, as Quotation nodes: the King
 * James wording of each block, from the English paragraph beneath the Greek.
 *
 * @param string $content Content.
 * @param string $base    Page address.
 * @return array
 */
function paulus_schema_quotations( $content, $base ) {
	if ( ! preg_match_all( '#<blockquote class="scripture[^"]*">(.*?)</blockquote>#s', $content, $m ) ) {
		return array();
	}
	$out = array();
	foreach ( $m[1] as $i => $block ) {
		$english = '';
		foreach ( preg_split( '#</p>#', $block ) as $p ) {
			if ( false === strpos( $p, 'class="greek"' ) && '' !== paulus_schema_text( $p ) ) {
				$english .= ' ' . paulus_schema_text( preg_replace( '#<span class="v">\d+</span>#', '', $p ) );
			}
		}
		$english = trim( $english );
		if ( '' === $english ) {
			continue;
		}
		$out[] = array(
			'@type'     => 'Quotation',
			'@id'       => $base . '#quotation-' . ( $i + 1 ),
			'text'      => mb_substr( $english, 0, 1000 ),
			'isBasedOn' => array( '@id' => paulus_sid( 'king-james-version' ) ),
		);
	}
	return array_slice( $out, 0, 40 );
}

/**
 * The header, footer and navigation of every page.
 *
 * @return array
 */
function paulus_schema_page_elements() {
	$nav   = array();
	$items = array();
	foreach ( paulus_parts() as $part ) {
		$items[] = array( $part->name, get_term_link( $part ) );
	}
	$verdict = get_page_by_path( 'the-verdict' );
	if ( $verdict && 'publish' === $verdict->post_status ) {
		$items[] = array( __( 'The Verdict', 'paulus' ), get_permalink( $verdict ) );
	}
	$book = get_page_by_path( 'the-book' );
	if ( $book && 'publish' === $book->post_status ) {
		$items[] = array( get_the_title( $book ), get_permalink( $book ) );
	}
	if ( function_exists( 'paulus_journal_url' ) && post_type_exists( 'paulus_journal' ) && wp_count_posts( 'paulus_journal' )->publish ) {
		$items[] = array( (string) paulus_option( 'journal_title' ), paulus_journal_url() );
	}
	$header_parts = array();
	foreach ( $items as $i => $item ) {
		if ( is_wp_error( $item[1] ) ) {
			continue;
		}
		$id             = paulus_sid( 'nav-main-' . ( $i + 1 ) );
		$nav[]          = array( '@type' => 'SiteNavigationElement', '@id' => $id, 'name' => $item[0], 'url' => $item[1] );
		$header_parts[] = array( '@id' => $id );
	}
	$footer_parts = array();
	$legal        = array( 'privacy-policy', 'terms-of-use', 'dmca', 'contact' );
	foreach ( $legal as $i => $slug ) {
		$page = 'privacy-policy' === $slug && (int) get_option( 'wp_page_for_privacy_policy' ) ? get_post( (int) get_option( 'wp_page_for_privacy_policy' ) ) : get_page_by_path( $slug );
		if ( $page && 'publish' === $page->post_status ) {
			$id             = paulus_sid( 'nav-footer-' . ( $i + 1 ) );
			$nav[]          = array( '@type' => 'SiteNavigationElement', '@id' => $id, 'name' => get_the_title( $page ), 'url' => get_permalink( $page ) );
			$footer_parts[] = array( '@id' => $id );
		}
	}
	$map = function_exists( 'paulus_sitemap_page' ) ? paulus_sitemap_page() : null;
	if ( $map && 'publish' === $map->post_status ) {
		$id             = paulus_sid( 'nav-footer-sitemap' );
		$nav[]          = array( '@type' => 'SiteNavigationElement', '@id' => $id, 'name' => get_the_title( $map ), 'url' => get_permalink( $map ) );
		$footer_parts[] = array( '@id' => $id );
	}
	$nodes   = $nav;
	$nodes[] = array( '@type' => 'WPHeader', '@id' => paulus_sid( 'header' ), 'name' => get_bloginfo( 'name' ), 'hasPart' => $header_parts );
	$footer  = array(
		'@type'           => 'WPFooter',
		'@id'             => paulus_sid( 'footer' ),
		'hasPart'         => $footer_parts,
		'copyrightHolder' => array( '@id' => paulus_sid( 'author' ) ),
	);
	if ( paulus_option( 'footer_blurb' ) ) {
		$footer['description'] = wp_strip_all_tags( (string) paulus_option( 'footer_blurb' ) );
	}
	$nodes[] = $footer;
	return $nodes;
}

/**
 * A list of pages as an ItemList.
 *
 * @param string    $id    Node identifier.
 * @param string    $name  Name.
 * @param WP_Post[] $pages Pages.
 * @return array
 */
function paulus_schema_page_itemlist( $id, $name, $pages ) {
	$items = array();
	foreach ( array_values( $pages ) as $i => $p ) {
		$items[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => get_the_title( $p ), 'url' => get_permalink( $p ) );
	}
	return array( '@type' => 'ItemList', '@id' => $id, 'name' => $name, 'numberOfItems' => count( $items ), 'itemListElement' => $items );
}

/**
 * Nodes that describe the components of the current page.
 *
 * @return array
 */
function paulus_schema_component_nodes() {
	$nodes   = array();
	$content = paulus_schema_content();
	$url     = is_singular() ? get_permalink() : '';
	$slug    = is_page() ? get_post_field( 'post_name', get_queried_object_id() ) : '';

	// The front page: its three sections.
	if ( is_front_page() ) {
		$items = array();
		foreach ( array_values( paulus_parts() ) as $i => $part ) {
			$items[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => $part->name, 'url' => get_term_link( $part ) );
		}
		if ( $items ) {
			$nodes[] = array( '@type' => 'ItemList', '@id' => paulus_sid( 'sections' ), 'name' => __( 'The case', 'paulus' ), 'numberOfItems' => count( $items ), 'itemListElement' => $items );
		}
	}

	// The front page's questions: an FAQPage of exactly the questions and
	// answers its section shows, each pointing to the full, referenced
	// answer on the Answers page. The Answers page keeps its own FAQPage.
	if ( is_front_page() ) {
		$faq = paulus_schema_front_faq();
		if ( $faq ) {
			$nodes[] = $faq;
		}
	}

	// Reference, Appendices and the Sitemap: the pages they index.
	if ( in_array( $slug, array( 'reference', 'appendices' ), true ) ) {
		$nodes[] = paulus_schema_page_itemlist( $url . '#pages', get_the_title(), get_pages( array( 'parent' => get_queried_object_id(), 'sort_column' => 'menu_order' ) ) );
	}
	if ( in_array( $slug, array( 'sitemap', 'site-map' ), true ) ) {
		$pages = array();
		foreach ( paulus_parts() as $part ) {
			foreach ( paulus_chapters( $part->term_id ) as $post ) {
				$pages[] = $post;
			}
		}
		foreach ( get_pages( array( 'sort_column' => 'menu_order' ) ) as $p ) {
			if ( ! in_array( $p->post_name, array( 'sitemap', 'site-map' ), true ) ) {
				$pages[] = $p;
			}
		}
		$nodes[] = paulus_schema_page_itemlist( $url . '#contents', __( 'Every article and page on the site', 'paulus' ), $pages );
	}

	// The timeline: each dated entry as an event in Paul's life.
	if ( 'chronology' === $slug && preg_match_all( '#<li><strong>(.*?)</strong>(.*?)</li>#s', $content, $m, PREG_SET_ORDER ) ) {
		$items = array();
		foreach ( $m as $i => $row ) {
			$when = paulus_schema_text( $row[1] );
			$what = paulus_schema_text( preg_replace( '#<span class="paulus-timeline__links">.*?</span>#s', '', $row[2] ) );
			if ( '' === $what ) {
				continue;
			}
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $i + 1,
				'item'     => array(
					'@type'       => 'Event',
					'@id'         => $url . '#event-' . ( $i + 1 ),
					'name'        => mb_substr( $what, 0, 110 ),
					'description' => $when . ': ' . $what,
					'about'       => array( '@id' => paulus_sid( 'paul-of-tarsus' ) ),
				),
			);
		}
		$nodes[] = array( '@type' => 'ItemList', '@id' => $url . '#timeline', 'name' => get_the_title(), 'numberOfItems' => count( $items ), 'itemListElement' => $items );
	}

	// The glossary: a set of defined terms.
	if ( 'glossary' === $slug && preg_match_all( '#<dt[^>]*>(.*?)</dt>\s*<dd[^>]*>(.*?)</dd>#s', $content, $m, PREG_SET_ORDER ) ) {
		$set   = $url . '#glossary';
		$terms = array();
		foreach ( $m as $row ) {
			$name    = paulus_schema_text( $row[1] );
			$terms[] = array(
				'@type'            => 'DefinedTerm',
				'@id'              => $url . '#term-' . sanitize_title( remove_accents( $name ) ),
				'name'             => $name,
				'description'      => paulus_schema_text( $row[2] ),
				'inDefinedTermSet' => array( '@id' => $set ),
			);
		}
		$nodes[] = array( '@type' => 'DefinedTermSet', '@id' => $set, 'name' => get_the_title(), 'url' => $url, 'hasDefinedTerm' => $terms );
	}

	// The study guide: its questions, open-ended, grouped by article.
	if ( 'study-questions' === $slug && preg_match_all( '#<h2[^>]*>(.*?)</h2>\s*<ol[^>]*>(.*?)</ol>#s', $content, $m, PREG_SET_ORDER ) ) {
		$questions = array();
		$n         = 0;
		foreach ( $m as $group ) {
			preg_match_all( '#<li>(.*?)</li>#s', $group[2], $li );
			foreach ( $li[1] as $q ) {
				++$n;
				$questions[] = array(
					'@type'           => 'Question',
					'@id'             => $url . '#question-' . $n,
					'name'            => paulus_schema_text( $q ),
					'eduQuestionType' => 'Open ended',
					'about'           => array( '@type' => 'Thing', 'name' => paulus_schema_text( $group[1] ) ),
				);
			}
		}
		$nodes[] = array(
			'@type'               => 'Quiz',
			'@id'                 => $url . '#study-guide',
			'name'                => get_the_title(),
			'url'                 => $url,
			'about'               => array( '@id' => paulus_sid( 'paul-of-tarsus' ) ),
			'educationalUse'      => 'discussion',
			'learningResourceType' => 'study guide',
			'numberOfItems'       => count( $questions ),
			'hasPart'             => $questions,
		);
	}

	// The sources: the bibliography, grouped under its headings.
	if ( 'sources' === $slug && preg_match_all( '#<h2[^>]*>(.*?)</h2>\s*<ul[^>]*>(.*?)</ul>#s', $content, $m, PREG_SET_ORDER ) ) {
		$items = array();
		foreach ( $m as $group ) {
			preg_match_all( '#<li>(.*?)</li>#s', $group[2], $li );
			foreach ( $li[1] as $entry ) {
				$items[] = array(
					'@type'    => 'ListItem',
					'position' => count( $items ) + 1,
					'item'     => array( '@type' => 'CreativeWork', 'name' => mb_substr( paulus_schema_text( $entry ), 0, 400 ), 'genre' => paulus_schema_text( $group[1] ) ),
				);
			}
		}
		$nodes[] = array( '@type' => 'ItemList', '@id' => $url . '#bibliography', 'name' => get_the_title(), 'numberOfItems' => count( $items ), 'itemListElement' => $items );
	}

	// The charts on a page: each a dataset drawn from its source.
	if ( function_exists( 'paulus_charts' ) && preg_match_all( '#\[paulus_chart id="([a-z0-9-]+)"#', $content, $m ) ) {
		$charts = paulus_charts();
		foreach ( array_unique( $m[1] ) as $id ) {
			if ( empty( $charts[ $id ] ) ) {
				continue;
			}
			$c     = $charts[ $id ];
			$rows  = array();
			foreach ( $c['rows'] as $row ) {
				$vals   = (array) $row[1];
				$pairs  = array();
				foreach ( $vals as $k => $v ) {
					$pairs[] = ( isset( $c['series'][ $k ] ) ? $c['series'][ $k ] . ' ' : '' ) . $v . ( $c['unit'] ?? '' );
				}
				$rows[] = $row[0] . ': ' . implode( ', ', $pairs );
			}
			$data = array(
				'@type'           => 'Dataset',
				'@id'             => $url . '#chart-' . $id,
				'name'            => $c['title'],
				'description'     => $c['title'] . ( ! empty( $c['note'] ) ? ' (' . $c['note'] . ')' : '' ) . '. ' . implode( '; ', $rows ) . '.',
				'creator'         => array( '@id' => paulus_sid( 'pew-research-center' ) ),
				'variableMeasured' => $c['title'],
				'isAccessibleForFree' => true,
			);
			if ( ! empty( $c['series'] ) ) {
				$years = array_filter( (array) $c['series'], static function ( $s ) {
					return (bool) preg_match( '/^\d{4}$/', (string) $s );
				} );
				if ( count( $years ) >= 2 ) {
					$data['temporalCoverage'] = min( $years ) . '/' . max( $years );
				}
			}
			if ( ! empty( $c['source'][1] ) ) {
				$data['isBasedOn'] = $c['source'][1];
				$data['citation']  = $c['source'][0];
			}
			$nodes[] = $data;
		}
	}

	// Martin's notes: the manuscript itself, and its file.
	if ( 'dale-b-martin-luke-versus-paul' === $slug ) {
		$pdf = PAULUS_URI . '/assets/docs/dale-b-martin-luke-versus-paul.pdf';
		$nodes[] = array(
			'@type'               => 'Manuscript',
			'@id'                 => $url . '#manuscript',
			'name'                => '“Luke” versus Paul',
			'description'         => __( 'Unfinished notes by Dale B. Martin on the Paul of the Acts of the Apostles and the Paul of his own letters, published with his permission.', 'paulus' ),
			'author'              => array( '@id' => paulus_sid( 'dale-b-martin' ) ),
			'dateCreated'         => '2019',
			'inLanguage'          => 'en',
			'about'               => array( array( '@id' => paulus_sid( 'paul-of-tarsus' ) ), array( '@id' => paulus_sid( 'acts-of-the-apostles' ) ) ),
			'publisher'           => array( '@id' => paulus_sid( 'author' ) ),
			'datePublished'       => get_the_date( 'c' ),
			'isAccessibleForFree' => true,
			'url'                 => $url,
			'encoding'            => array( '@type' => 'MediaObject', 'contentUrl' => $pdf, 'encodingFormat' => 'application/pdf', 'name' => __( 'The notes as received (PDF)', 'paulus' ) ),
		);
	}

	// The Journal: a blog and its latest posts.
	if ( function_exists( 'paulus_journal_url' ) && is_post_type_archive( 'paulus_journal' ) ) {
		$posts = array();
		foreach ( get_posts( array( 'post_type' => 'paulus_journal', 'post_status' => 'publish', 'numberposts' => 20 ) ) as $e ) {
			$posts[] = array( '@type' => 'BlogPosting', '@id' => get_permalink( $e ) . '#entry', 'headline' => get_the_title( $e ), 'url' => get_permalink( $e ), 'datePublished' => get_the_date( 'c', $e ), 'author' => array( '@id' => paulus_sid( 'author' ) ) );
		}
		$nodes[] = array(
			'@type'       => 'Blog',
			'@id'         => paulus_journal_url() . '#blog',
			'name'        => (string) paulus_option( 'journal_title' ),
			'description' => (string) paulus_option( 'journal_intro' ),
			'url'         => paulus_journal_url(),
			'author'      => array( '@id' => paulus_sid( 'author' ) ),
			'publisher'   => array( '@id' => paulus_sid( 'publisher' ) ),
			'inLanguage'  => paulus_site_language(),
			'blogPost'    => $posts,
		);
	}

	// The essays among the pages (The Verdict, the answer to Martin's
	// question): an article node, where nothing on the page describes one
	// (the theme's own path; Rank Math supplies its own). paulus_schema_deepen()
	// then deepens it like every article.
	if ( in_array( $slug, array( 'the-verdict', 'why-luke-does-not-know-pauls-letters' ), true ) && empty( $GLOBALS['paulus_sd_has_article'] ) && function_exists( 'paulus_article_schema' ) ) {
		$nodes[] = array( '@id' => $url . '#article' ) + paulus_article_schema( array( '@id' => paulus_sid( 'author' ) ), array( '@id' => paulus_sid( 'publisher' ) ), array( '@id' => home_url( '/' ) . '#website' ) );
	}

	// Photographed works of art in the text: each a VisualArtwork.
	foreach ( paulus_schema_artworks( $content ) as $work ) {
		$nodes[] = $work;
	}
	return $nodes;
}

/**
 * The art form of a figure, read from its description.
 *
 * @param string $alt Description.
 * @return string
 */
function paulus_schema_artform( $alt ) {
	$forms = array(
		'painting'  => 'Painting',
		'self-portrait' => 'Painting',
		'fresco'    => 'Fresco',
		'mosaic'    => 'Mosaic',
		'relief'    => 'Sculpture',
		'statue'    => 'Sculpture',
		'bust'      => 'Sculpture',
		'graffito'  => 'Graffito',
		'engraving' => 'Engraving',
		'enamel'    => 'Enamel',
		'cartoon'   => 'Drawing',
		'icon'      => 'Icon',
		'manuscript' => 'Manuscript',
		'codex'     => 'Manuscript',
		'papyrus'   => 'Manuscript',
		'scroll'    => 'Manuscript',
		'woodcut'   => 'Woodcut',
		'map '      => 'Map',
	);
	foreach ( $forms as $needle => $form ) {
		if ( false !== stripos( $alt, $needle ) ) {
			return $form;
		}
	}
	return '';
}

/**
 * The works of art photographed in the current text.
 *
 * @param string $content Content.
 * @return array
 */
function paulus_schema_artworks( $content ) {
	if ( ! function_exists( 'paulus_figures' ) || ! preg_match_all( '#\[paulus_figure name="([a-z0-9-]+)"[^\]]*\](.*?)\[/paulus_figure\]#s', $content, $m, PREG_SET_ORDER ) ) {
		return array();
	}
	$figs = paulus_figures();
	$out  = array();
	foreach ( $m as $row ) {
		$f = $figs[ $row[1] ] ?? null;
		if ( ! $f ) {
			continue;
		}
		$form = paulus_schema_artform( $f['alt'] );
		if ( '' === $form ) {
			continue;
		}
		$img   = PAULUS_URI . '/assets/images/church/' . $row[1] . '.webp';
		$cap   = paulus_schema_text( $row[2] );
		$type  = in_array( $form, array( 'Manuscript', 'Map' ), true ) ? $form : 'VisualArtwork';
		$out[] = array_filter(
			array(
				'@type'       => $type,
				'@id'         => $img . '#work',
				'name'        => mb_substr( rtrim( $cap, '.' ), 0, 160 ),
				'description' => $f['alt'],
				'artform'     => 'VisualArtwork' === $type ? $form : null,
				'image'       => array( '@id' => $img . '#image' ),
			)
		) + array(
			'about' => false !== stripos( $cap . $f['alt'], 'Paul' ) ? array( '@id' => paulus_sid( 'paul-of-tarsus' ) ) : null,
		);
		$last = count( $out ) - 1;
		if ( null === $out[ $last ]['about'] ) {
			unset( $out[ $last ]['about'] );
		}
	}
	return $out;
}

/**
 * Deepen one node in the context of the current page.
 *
 * @param array $node Node.
 * @return array
 */
function paulus_schema_deepen_node( $node ) {
	if ( ! is_array( $node ) || empty( $node['@type'] ) ) {
		return $node;
	}
	$content = paulus_schema_content();
	$url     = is_singular() ? get_permalink() : '';
	$slug    = is_page() ? get_post_field( 'post_name', get_queried_object_id() ) : '';
	$paul    = array( '@id' => paulus_sid( 'paul-of-tarsus' ) );
	$pub     = (string) paulus_option( 'pub_name' );
	$author  = (string) paulus_option( 'book_author' );

	// The publisher: contact point and published policies.
	if ( paulus_node_is( $node, array( 'Organization' ) ) && ( $node['name'] ?? '' ) === $pub ) {
		$email = sanitize_email( (string) paulus_option( 'pub_email' ) );
		if ( $email ) {
			$node = paulus_node_default( $node, 'email', $email );
			$node = paulus_node_default( $node, 'contactPoint', array( '@type' => 'ContactPoint', 'contactType' => 'editorial', 'email' => $email, 'availableLanguage' => array( 'en', 'ms' ) ) );
		}
		$terms   = get_page_by_path( 'terms-of-use' );
		$contact = get_page_by_path( 'contact' );
		if ( $terms && 'publish' === $terms->post_status ) {
			$node = paulus_node_default( $node, 'publishingPrinciples', get_permalink( $terms ) );
		}
		if ( $contact && 'publish' === $contact->post_status ) {
			$node = paulus_node_default( $node, 'correctionsPolicy', get_permalink( $contact ) );
		}
		return $node;
	}

	// The author: the same person on every page, with profiles.
	if ( paulus_node_is( $node, array( 'Person' ) ) && ( $node['name'] ?? '' ) === $author && function_exists( 'paulus_person_schema' ) ) {
		foreach ( paulus_person_schema() as $k => $v ) {
			if ( '@type' !== $k ) {
				$node = paulus_node_default( $node, $k, $v );
			}
		}
		return paulus_node_default( $node, 'knowsAbout', array( $paul, 'Christian origins', 'Islamic apologetics' ) );
	}

	// The site.
	if ( paulus_node_is( $node, array( 'WebSite' ) ) ) {
		$node = paulus_node_default( $node, 'about', $paul );
		$node = paulus_node_default( $node, 'inLanguage', paulus_site_language() );
		$node = paulus_node_default( $node, 'copyrightHolder', array( '@id' => paulus_sid( 'author' ) ) );
		return paulus_node_default( $node, 'isBasedOn', array( '@id' => paulus_sid( 'book' ) ) );
	}

	// The page node: its most specific kind, its subject and its frame.
	if ( paulus_node_is( $node, array( 'WebPage', 'CollectionPage', 'ItemPage', 'FAQPage', 'SearchResultsPage', 'ContactPage', 'AboutPage', 'ProfilePage', 'ImageGallery' ) )
		&& ! paulus_node_is( $node, array( 'Article', 'BlogPosting', 'ScholarlyArticle' ) ) ) {
		$kind = array(
			'contact'              => 'ContactPage',
			'paul-in-the-churches' => 'ImageGallery',
			'reference'            => 'CollectionPage',
			'appendices'           => 'CollectionPage',
			'sitemap'              => 'CollectionPage',
		);
		if ( isset( $kind[ $slug ] ) && ! paulus_node_is( $node, array( $kind[ $slug ] ) ) ) {
			$node = paulus_node_add_type( $node, $kind[ $slug ] );
		}
		if ( ! is_404() && ! is_search() ) {
			$node = paulus_node_default( $node, 'about', $paul );
		}
		$node = paulus_node_default( $node, 'hasPart', array( array( '@id' => paulus_sid( 'header' ) ), array( '@id' => paulus_sid( 'footer' ) ) ) );
		$main = array(
			'glossary'                       => '#glossary',
			'study-questions'                => '#study-guide',
			'sources'                        => '#bibliography',
			'chronology'                     => '#timeline',
			'dale-b-martin-luke-versus-paul' => '#manuscript',
			'reference'                      => '#pages',
			'appendices'                     => '#pages',
			'sitemap'                        => '#contents',
		);
		if ( isset( $main[ $slug ] ) ) {
			$node = paulus_node_default( $node, 'mainEntity', array( '@id' => $url . $main[ $slug ] ) );
		}
		if ( 'contact' === $slug ) {
			$node = paulus_node_default( $node, 'mainEntity', array( '@id' => paulus_sid( 'publisher' ) ) );
		}
		if ( is_front_page() ) {
			$node = paulus_node_default( $node, 'mainEntity', array( '@id' => paulus_sid( 'sections' ) ) );
		}
		if ( function_exists( 'paulus_journal_url' ) && is_post_type_archive( 'paulus_journal' ) ) {
			$node = paulus_node_default( $node, 'mainEntity', array( '@id' => paulus_journal_url() . '#blog' ) );
		}
		return $node;
	}

	// The article node, on articles, Journal entries and pages.
	if ( paulus_node_is( $node, array( 'Article', 'BlogPosting', 'NewsArticle', 'ScholarlyArticle', 'TechArticle' ) ) && is_singular() ) {
		$post = get_queried_object();
		if ( is_singular( 'paulus_journal' ) ) {
			$node = paulus_node_add_type( $node, 'BlogPosting' );
			$node = paulus_node_default( $node, 'isPartOf', array( '@id' => paulus_journal_url() . '#blog' ) );
		} elseif ( is_singular( 'post' ) || 'why-luke-does-not-know-pauls-letters' === $slug ) {
			// Footnoted argument from the sources: a scholarly article. An SEO
			// plugin's default BlogPosting gives way: these are not blog posts.
			$node['@type'] = array( 'Article', 'ScholarlyArticle' );
		}
		$node = paulus_node_default( $node, 'about', 'why-luke-does-not-know-pauls-letters' === $slug ? array( $paul, array( '@id' => paulus_sid( 'acts-of-the-apostles' ) ) ) : $paul );
		if ( false !== strpos( $content, 'ʿĪsā' ) ) {
			$node = paulus_node_default( $node, 'mentions', array( '@id' => paulus_sid( 'isa-ibn-maryam' ) ) );
		}
		if ( is_singular( 'post' ) ) {
			$node = paulus_node_default( $node, 'isBasedOn', array( '@id' => paulus_sid( 'book' ) ) );
		}
		if ( 'why-luke-does-not-know-pauls-letters' === $slug ) {
			$archive = get_page_by_path( 'appendices/dale-b-martin-luke-versus-paul' );
			if ( $archive ) {
				$node = paulus_node_default( $node, 'isBasedOn', array( '@id' => get_permalink( $archive ) . '#manuscript' ) );
			}
		}
		$words = function_exists( 'paulus_word_count' ) ? paulus_word_count( $content ) : 0;
		$node  = paulus_node_default( $node, 'wordCount', $words ?: null );
		if ( $words ) {
			$node = paulus_node_default( $node, 'timeRequired', 'PT' . max( 1, (int) round( $words / 230 ) ) . 'M' );
		}
		$node = paulus_node_default( $node, 'inLanguage', paulus_site_language() );
		$node = paulus_node_default( $node, 'isAccessibleForFree', true );
		$node = paulus_node_default( $node, 'copyrightHolder', array( '@id' => paulus_sid( 'author' ) ) );
		$node = paulus_node_default( $node, 'copyrightYear', (int) get_the_date( 'Y', $post ) );
		$node = paulus_node_default( $node, 'speakable', array( '@type' => 'SpeakableSpecification', 'cssSelector' => array( '.paulus-hero-panel__title', '.paulus-hero-panel__standfirst' ) ) );
		if ( is_singular( 'post' ) ) {
			$part = paulus_part_of( $post->ID );
			if ( $part ) {
				$label = (string) get_post_meta( $post->ID, '_paulus_label', true );
				$node  = paulus_node_default( $node, 'articleSection', $part->name . ( $label ? ', ' . $label : '' ) );
			}
			$series = function_exists( 'paulus_series_parts' ) ? paulus_series_parts( $post->ID ) : null;
			if ( $series && ! isset( $node['position'] ) ) {
				$node['position'] = (int) get_post_meta( $post->ID, '_paulus_part_no', true );
				$node['isPartOf'] = array_values( array_filter( array_merge( (array) ( isset( $node['isPartOf'] ) && ! isset( $node['isPartOf']['@type'] ) ? array( $node['isPartOf'] ) : array() ), array( array( '@type' => 'CreativeWorkSeries', 'name' => $series['title'] ) ) ) ) );
			}
		}
		$cites = paulus_schema_citations( $content );
		if ( $cites ) {
			$node = paulus_node_default( $node, 'citation', $cites );
		}
		$parts = paulus_schema_quotations( $content, $url );
		if ( $parts ) {
			$node = paulus_node_default( $node, 'hasPart', $parts );
		}
		return $node;
	}

	// The site's own illustrations (portraits, featured images, the cover):
	// image metadata crediting the author, with the Terms of Use as the
	// licence and the Contact page as the place to ask permission.
	if ( paulus_node_is( $node, array( 'ImageObject' ) ) ) {
		$src = (string) ( $node['contentUrl'] ?? $node['url'] ?? $node['@id'] ?? '' );
		if ( '' !== $src && false === strpos( $src, '/assets/images/church/' ) && ( false !== strpos( $src, '/wp-content/uploads/' ) || false !== strpos( $src, '/assets/images/' ) ) && empty( $node['license'] ) ) {
			$name  = (string) paulus_option( 'book_author' );
			$terms = get_page_by_path( 'terms-of-use' );
			$ask   = get_page_by_path( 'contact' );
			$node  = paulus_node_default( $node, 'creator', array( '@id' => paulus_sid( 'author' ) ) );
			$node  = paulus_node_default( $node, 'creditText', $name . ', ' . get_bloginfo( 'name' ) );
			$node  = paulus_node_default( $node, 'copyrightNotice', '© ' . $name );
			$node  = paulus_node_default( $node, 'copyrightHolder', array( '@id' => paulus_sid( 'author' ) ) );
			if ( $terms && 'publish' === $terms->post_status ) {
				$node = paulus_node_default( $node, 'license', get_permalink( $terms ) );
			}
			if ( $ask && 'publish' === $ask->post_status ) {
				$node = paulus_node_default( $node, 'acquireLicensePage', get_permalink( $ask ) );
			}
			return $node;
		}
	}

	// Photographs of works of art point to the works themselves.
	if ( paulus_node_is( $node, array( 'ImageObject' ) ) && ! empty( $node['@id'] ) && false !== strpos( $node['@id'], '/assets/images/church/' ) ) {
		$key = basename( (string) wp_parse_url( $node['@id'], PHP_URL_PATH ), '.webp' );
		$f   = function_exists( 'paulus_figures' ) ? ( paulus_figures()[ $key ] ?? null ) : null;
		if ( $f && '' !== paulus_schema_artform( $f['alt'] ) ) {
			$node = paulus_node_default( $node, 'about', array( '@id' => preg_replace( '/#image$/', '#work', $node['@id'] ) ) );
		}
		return $node;
	}
	return $node;
}

/**
 * Deepen a graph and add the nodes it lacks. Keys are kept, so Rank Math's
 * keyed entities pass through its own entity linking unchanged.
 *
 * @param array $nodes Graph nodes (a list, or Rank Math's keyed array).
 * @return array
 */
function paulus_schema_deepen( $nodes ) {
	if ( ! is_array( $nodes ) || is_admin() || is_feed() ) {
		return $nodes;
	}
	// Find the nodes that already describe the author and the publisher.
	$GLOBALS['paulus_sd_ids'] = array();
	foreach ( $nodes as $node ) {
		if ( ! is_array( $node ) || empty( $node['@id'] ) ) {
			continue;
		}
		if ( paulus_node_is( $node, array( 'Person' ) ) && ( $node['name'] ?? '' ) === (string) paulus_option( 'book_author' ) && empty( $GLOBALS['paulus_sd_ids']['author'] ) ) {
			$GLOBALS['paulus_sd_ids']['author'] = $node['@id'];
		}
		if ( paulus_node_is( $node, array( 'Organization' ) ) && ( $node['name'] ?? '' ) === (string) paulus_option( 'pub_name' ) && empty( $GLOBALS['paulus_sd_ids']['publisher'] ) ) {
			$GLOBALS['paulus_sd_ids']['publisher'] = $node['@id'];
		}
	}
	$GLOBALS['paulus_sd_has_article'] = false;
	foreach ( $nodes as $node ) {
		if ( is_array( $node ) && paulus_node_is( $node, array( 'Article', 'BlogPosting', 'ScholarlyArticle', 'NewsArticle', 'TechArticle' ) ) ) {
			$GLOBALS['paulus_sd_has_article'] = true;
		}
	}
	$ids = array();
	foreach ( $nodes as $k => $node ) {
		$nodes[ $k ] = paulus_schema_deepen_node( $node );
		if ( is_array( $nodes[ $k ] ) && ! empty( $nodes[ $k ]['@id'] ) ) {
			$ids[ $nodes[ $k ]['@id'] ] = true;
		}
	}
	// Page elements and components, then every site entity the graph now
	// refers to, each as a node of its own.
	$extra = array_merge( paulus_schema_page_elements(), array_map( 'paulus_schema_deepen_node', paulus_schema_component_nodes() ) );
	foreach ( paulus_schema_entities() as $entity ) {
		if ( paulus_schema_refers( array( $nodes, $extra ), $entity['@id'] ) ) {
			$extra[] = $entity;
		}
	}
	if ( paulus_schema_refers( array( $nodes, $extra ), paulus_sid( 'book' ) ) && ! isset( $ids[ paulus_sid( 'book' ) ] ) ) {
		$extra[] = paulus_schema_book_ref();
	}
	// The author and publisher, where no node on the page describes them.
	if ( empty( $GLOBALS['paulus_sd_ids']['author'] ) && ! isset( $ids[ paulus_sid( 'author' ) ] ) && paulus_schema_refers( array( $nodes, $extra ), paulus_sid( 'author' ) ) ) {
		$extra[] = paulus_schema_deepen_node( array( '@id' => paulus_sid( 'author' ) ) + paulus_person_schema() );
	}
	if ( empty( $GLOBALS['paulus_sd_ids']['publisher'] ) && ! isset( $ids[ paulus_sid( 'publisher' ) ] ) && paulus_schema_refers( array( $nodes, $extra ), paulus_sid( 'publisher' ) ) ) {
		$extra[] = paulus_schema_deepen_node( array( '@id' => paulus_sid( 'publisher' ) ) + paulus_org_schema() );
	}
	// Nodes on other pages that this page points to: named, with their address.
	if ( function_exists( 'paulus_journal_url' ) && paulus_schema_refers( array( $nodes, $extra ), paulus_journal_url() . '#blog' ) && ! is_post_type_archive( 'paulus_journal' ) ) {
		$extra[] = array( '@type' => 'Blog', '@id' => paulus_journal_url() . '#blog', 'name' => (string) paulus_option( 'journal_title' ), 'url' => paulus_journal_url() );
	}
	$archive = get_page_by_path( 'appendices/dale-b-martin-luke-versus-paul' );
	if ( $archive && ! is_page( $archive->ID ) && paulus_schema_refers( array( $nodes, $extra ), get_permalink( $archive ) . '#manuscript' ) ) {
		$extra[] = array( '@type' => 'Manuscript', '@id' => get_permalink( $archive ) . '#manuscript', 'name' => '“Luke” versus Paul', 'author' => array( '@id' => paulus_sid( 'dale-b-martin' ) ), 'url' => get_permalink( $archive ) );
		if ( ! paulus_schema_refers( array( $nodes, $extra ), paulus_sid( 'dale-b-martin' ) . '"' ) ) {
			$extra[] = paulus_schema_entities()['martin'];
		}
	}
	$i = 0;
	foreach ( $extra as $node ) {
		if ( ! empty( $node['@id'] ) && isset( $ids[ $node['@id'] ] ) ) {
			continue;
		}
		if ( ! empty( $node['@id'] ) ) {
			$ids[ $node['@id'] ] = true;
		}
		$nodes[ 'paulus_deep_' . ( $i++ ) ] = $node;
	}
	return $nodes;
}

/**
 * The FAQPage of the front page's questions, from what the section showed.
 *
 * @return array
 */
function paulus_schema_front_faq() {
	$shown = $GLOBALS['paulus_front_faq'] ?? null;
	if ( ! $shown && function_exists( 'paulus_answers' ) ) {
		$page  = get_page_by_path( 'answers' );
		$shown = $page ? array( 'url' => get_permalink( $page ), 'items' => array_slice( paulus_answers(), 0, 6 ) ) : null;
	}
	if ( empty( $shown['items'] ) ) {
		return array();
	}
	$questions = array();
	foreach ( $shown['items'] as $item ) {
		// The answer as the reader sees it, without the "Full evidence" line.
		// Typography as WordPress displays it (curly apostrophes and quotes),
		// so the marked-up answer is word for word the one on the page.
		$answer = paulus_schema_text( wptexturize( preg_replace( '#<p class="paulus-readmore">.*?</p>#s', '', (string) $item['answer'] ) ) );
		if ( '' === $answer ) {
			continue;
		}
		$questions[] = array(
			'@type'          => 'Question',
			'@id'            => home_url( '/' ) . '#faq-' . $item['id'],
			'name'           => paulus_schema_text( wptexturize( $item['question'] ) ),
			'url'            => $shown['url'] . '#' . $item['id'],
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $answer,
				'url'   => $shown['url'] . '#' . $item['id'],
			),
		);
	}
	if ( ! $questions ) {
		return array();
	}
	return array(
		'@type'      => 'FAQPage',
		'@id'        => home_url( '/' ) . '#faq',
		'name'       => __( 'Answers to missionary claims', 'paulus' ),
		'url'        => home_url( '/' ),
		'inLanguage' => paulus_site_language(),
		'about'      => array( '@id' => paulus_sid( 'paul-of-tarsus' ) ),
		'isPartOf'   => array( '@id' => home_url( '/' ) . '#website' ),
		'mainEntity' => $questions,
	);
}
