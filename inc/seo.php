<?php
/**
 * Search: meta descriptions, Open Graph, structured data, 404, sitemap.
 * Everything here steps aside when a dedicated SEO plugin is active.
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether an SEO plugin is handling the head.
 *
 * @return bool
 */
function paulus_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' ) || paulus_rank_math_active() || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' ) || defined( 'THE_SEO_FRAMEWORK_VERSION' );
}

/**
 * Is Rank Math running?
 *
 * @return bool
 */
function paulus_rank_math_active() {
	return class_exists( 'RankMath' );
}

/**
 * Does a Rank Math graph already carry one of these schema types?
 *
 * Rank Math may use a subtype (BlogPosting for Article, NewsMediaOrganization
 * for Organization) and @type may itself be an array, so every candidate is
 * compared against the flattened list.
 *
 * @param array $data  Rank Math's entities.
 * @param array $types Type names that would count as "already covered".
 * @return bool
 */
function paulus_schema_type_present( $data, $types ) {
	foreach ( (array) $data as $entity ) {
		if ( ! is_array( $entity ) || ! isset( $entity['@type'] ) ) {
			continue;
		}
		foreach ( (array) $entity['@type'] as $type ) {
			if ( in_array( $type, $types, true ) ) {
				return true;
			}
		}
	}
	return false;
}

/**
 * The "Format" admin field ("Paperback", "Hardcover", "E-book"…) as the
 * schema.org bookFormat URI it names, rather than a value hard-coded
 * regardless of what the field actually says.
 *
 * @param string $format Free-text format, as entered under Theme Options.
 * @return string
 */
function paulus_book_format_uri( $format ) {
	$map = array(
		'paperback' => 'https://schema.org/Paperback',
		'hardcover' => 'https://schema.org/Hardcover',
		'hardback'  => 'https://schema.org/Hardcover',
		'e-book'    => 'https://schema.org/EBook',
		'ebook'     => 'https://schema.org/EBook',
		'audiobook' => 'https://schema.org/AudiobookFormat',
	);
	foreach ( $map as $needle => $uri ) {
		if ( false !== stripos( (string) $format, $needle ) ) {
			return $uri;
		}
	}
	return 'https://schema.org/Paperback';
}

/**
 * The "Language" admin field as a BCP 47 code for schema.org. The field is
 * free text ("Malay (Bahasa Melayu)" by default), so a code is used as
 * given, a recognised language name is mapped, and anything else falls
 * back rather than publishing free text where a code is expected.
 *
 * @param string $value    Field value.
 * @param string $fallback Code to use when the value isn't recognised.
 * @return string
 */
function paulus_language_code( $value, $fallback ) {
	$value = trim( (string) $value );
	if ( preg_match( '/^[a-z]{2,3}(-[A-Za-z0-9]{2,8})*$/', $value ) ) {
		return $value;
	}
	$names = array(
		'malay'      => 'ms',
		'melayu'     => 'ms',
		'english'    => 'en',
		'indonesian' => 'id',
		'arabic'     => 'ar',
	);
	foreach ( $names as $name => $code ) {
		if ( false !== stripos( $value, $name ) ) {
			return $code;
		}
	}
	return $fallback;
}

/**
 * Length limits for search snippets. Every title reads
 * "<page title> | <site name>" in at most PAULUS_TITLE_MAX characters;
 * every description stays at or under PAULUS_META_MAX.
 */
const PAULUS_TITLE_MAX = 50;
const PAULUS_META_MAX  = 130;

/**
 * Trim text to a length at a word boundary, without an ellipsis.
 *
 * @param string $text  Text.
 * @param int    $limit Maximum characters.
 * @return string
 */
function paulus_fit( $text, $limit ) {
	$text = trim( preg_replace( '/\s+/u', ' ', (string) $text ) );
	if ( paulus_ulen( $text ) <= $limit ) {
		return $text;
	}
	$cut = paulus_usubstr( $text, 0, $limit + 1 );
	$cut = preg_match( '/^(.*)\s/su', $cut, $m ) ? $m[1] : paulus_usubstr( $text, 0, $limit );
	return rtrim( $cut, ' ,;:-' );
}

/**
 * The page's own part of the title, before " | <site name>": the short
 * search title stored on the article, page or section when there is one,
 * otherwise its ordinary name.
 *
 * @return string
 */
function paulus_title_part() {
	if ( is_front_page() ) {
		return (string) paulus_option( 'site_seo_title' );
	}
	if ( is_singular() ) {
		$short = get_post_meta( get_queried_object_id(), '_paulus_seo_title', true );
		return $short ? $short : wp_strip_all_tags( single_post_title( '', false ) );
	}
	if ( is_category() ) {
		$term  = get_queried_object();
		$short = get_term_meta( $term->term_id, 'paulus_seo_title', true );
		return $short ? $short : $term->name;
	}
	if ( is_post_type_archive( 'paulus_journal' ) ) {
		$period = paulus_journal_period();
		/* translators: 1: Journal title, 2: month and year, or year. */
		return $period ? sprintf( __( '%1$s: %2$s', 'paulus' ), paulus_option( 'journal_title' ), $period ) : (string) paulus_option( 'journal_title' );
	}
	if ( is_search() ) {
		/* translators: %s: search terms. */
		return sprintf( __( 'Search: %s', 'paulus' ), get_search_query( false ) );
	}
	if ( is_404() ) {
		return __( 'Page not found', 'paulus' );
	}
	if ( is_archive() ) {
		return wp_strip_all_tags( get_the_archive_title() );
	}
	return '';
}

/**
 * Full title for the current view: "<page title> | <site name>", at most
 * PAULUS_TITLE_MAX characters. A page part too long for the limit is cut at
 * a word boundary; the site name is never cut.
 *
 * @return string
 */
function paulus_seo_title() {
	$site = get_bloginfo( 'name' );
	$sep  = ' | ';
	$part = paulus_title_part();
	if ( '' === $part || $part === $site ) {
		return $site;
	}
	$room = PAULUS_TITLE_MAX - paulus_ulen( $sep . $site );
	return paulus_fit( $part, max( 10, $room ) ) . $sep . $site;
}
add_filter( 'pre_get_document_title', 'paulus_seo_title', 99 );

/**
 * Meta description for the current view: the stored one on articles,
 * pages and sections, the front-page option, otherwise the excerpt or the
 * section description, always within PAULUS_META_MAX characters.
 *
 * @return string
 */
function paulus_meta_description() {
	$meta = '';
	if ( is_front_page() ) {
		$meta = paulus_option( 'site_meta' );
	} elseif ( is_singular() ) {
		$meta = get_post_meta( get_queried_object_id(), '_paulus_meta', true );
		if ( ! $meta && has_excerpt( get_queried_object_id() ) ) {
			$meta = wp_strip_all_tags( get_the_excerpt( get_queried_object_id() ) );
		}
	} elseif ( is_post_type_archive( 'paulus_journal' ) ) {
		$period = paulus_journal_period();
		/* translators: %s: month and year, or year. */
		if ( $period && ! get_query_var( 'journal_monthnum' ) ) {
			/* translators: %s: year. */
			$period = sprintf( __( 'the year %s', 'paulus' ), $period );
		}
		/* translators: %s: month and year, or "the year" and a year. */
		$meta = $period ? sprintf( __( 'Journal entries from %s: replies to missionary claims, notes on new sources and news of the case. Read them in order.', 'paulus' ), $period ) : paulus_option( 'journal_meta' );
	} elseif ( is_category() ) {
		$term = get_queried_object();
		$meta = get_term_meta( $term->term_id, 'paulus_meta', true );
		if ( ! $meta ) {
			$meta = wp_strip_all_tags( term_description( $term ) );
		}
	}
	return paulus_fit( $meta, PAULUS_META_MAX );
}

/**
 * The same title and description when an SEO plugin writes the head, so
 * every page follows one format whichever plugin is active. Only titles
 * and descriptions are replaced; the plugin keeps everything else.
 *
 * @param string $value The plugin's value.
 * @return string
 */
function paulus_plugin_title( $value ) {
	return is_admin() ? $value : paulus_seo_title();
}

/**
 * Plugin description filter: the theme's description when it has one.
 *
 * @param string $value The plugin's value.
 * @return string
 */
function paulus_plugin_description( $value ) {
	if ( is_admin() ) {
		return $value;
	}
	$desc = paulus_meta_description();
	return '' !== $desc ? $desc : paulus_fit( $value, PAULUS_META_MAX );
}
foreach ( array( 'rank_math/frontend/title', 'rank_math/opengraph/facebook/og_title', 'rank_math/opengraph/twitter/twitter_title', 'wpseo_title', 'wpseo_opengraph_title', 'wpseo_twitter_title' ) as $paulus_hook ) {
	add_filter( $paulus_hook, 'paulus_plugin_title', 99 );
}
foreach ( array( 'rank_math/frontend/description', 'rank_math/opengraph/facebook/og_description', 'rank_math/opengraph/twitter/twitter_description', 'wpseo_metadesc', 'wpseo_opengraph_desc', 'wpseo_twitter_description' ) as $paulus_hook ) {
	add_filter( $paulus_hook, 'paulus_plugin_description', 99 );
}
unset( $paulus_hook );

/**
 * Head tags: description, Open Graph, Twitter card, canonical is WP's own.
 */
function paulus_head_meta() {
	if ( paulus_seo_plugin_active() ) {
		return;
	}
	$desc  = paulus_meta_description();
	$title = wp_get_document_title();
	$url   = is_front_page() ? home_url( '/' ) : ( is_singular() ? get_permalink() : ( is_category() ? get_term_link( get_queried_object() ) : '' ) );
	$image = is_singular() && has_post_thumbnail() ? get_the_post_thumbnail_url( null, 'large' ) : paulus_image_url( 'paul-portrait' );
	if ( $desc ) {
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( $desc ) );
	}
	printf( '<meta property="og:site_name" content="%s">' . "\n", esc_attr( get_bloginfo( 'name' ) ) );
	printf( '<meta property="og:type" content="%s">' . "\n", is_single() ? 'article' : 'website' );
	printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $title ) );
	if ( $desc ) {
		printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $desc ) );
	}
	if ( $url && ! is_wp_error( $url ) ) {
		printf( '<meta property="og:url" content="%s">' . "\n", esc_url( $url ) );
	}
	printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $image ) );
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
}
add_action( 'wp_head', 'paulus_head_meta', 5 );

/**
 * Canonical link for views WordPress core doesn't cover on its own: core's
 * rel_canonical() only ever outputs a <link> for is_singular(). Category
 * archives and search results get no canonical tag at all without a
 * plugin — og:url above already computes a URL for the former, which
 * isn't a substitute (a canonical link, not an Open Graph property, is
 * what tells search engines which URL is authoritative).
 */
function paulus_canonical_link() {
	if ( paulus_seo_plugin_active() || is_singular() ) {
		return;
	}
	$url = '';
	if ( is_category() ) {
		$url = get_term_link( get_queried_object() );
	} elseif ( is_search() ) {
		$url = get_search_link();
	}
	if ( $url && ! is_wp_error( $url ) ) {
		printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $url ) );
	}
}
add_action( 'wp_head', 'paulus_canonical_link', 5 );

/**
 * Structured data: breadcrumbs on articles, the book on its page.
 */
function paulus_structured_data() {
	if ( paulus_seo_plugin_active() ) {
		return;
	}
	paulus_print_schema_graph();
}
add_action( 'wp_head', 'paulus_structured_data', 6 );

/**
 * Build and print the theme's own full graph: the plugin-free case, and the
 * fallback when Rank Math is active but publishing no graph of its own.
 */
function paulus_print_schema_graph() {
	$home   = home_url( '/' );
	$org_id = $home . '#publisher';
	$web_id = $home . '#website';
	$per_id = $home . '#author';
	$author = array( '@id' => $per_id );
	$pub    = array( '@id' => $org_id );
	$graph  = array();

	$graph[] = array( '@id' => $org_id ) + paulus_org_schema();
	$graph[] = array( '@id' => $per_id ) + paulus_person_schema();
	$graph[] = array( '@id' => $web_id ) + paulus_website_schema( $pub );

	$crumbs = paulus_breadcrumb_schema();
	if ( $crumbs ) {
		$graph[] = $crumbs;
	}
	if ( is_single() ) {
		$graph[] = array( '@id' => get_permalink() . '#article' ) + paulus_article_schema( $author, $pub, array( '@id' => $web_id ) );
	}
	if ( is_page( 'the-book' ) ) {
		$graph[] = paulus_book_schema( $author, $pub );
	}
	$page = paulus_webpage_schema( array( '@id' => $web_id ) );
	if ( $page ) {
		$graph[] = $page;
	}
	$list = paulus_itemlist_schema();
	if ( $list ) {
		$graph[] = $list;
	}
	foreach ( paulus_figure_images_schema() as $img ) {
		$graph[] = $img;
	}
	paulus_print_json_ld( $graph );
}

/**
 * Organization: the publisher.
 *
 * @return array
 */
function paulus_org_schema() {
	$org = array(
		'@type' => 'Organization',
		'name'  => paulus_option( 'pub_name' ),
		'url'   => paulus_option( 'pub_url' ),
		// The publisher's own logo, not the book cover: Google expects an
		// organisation logo here, and a cover is a different thing.
		'logo'  => array(
			'@type'  => 'ImageObject',
			'url'    => PAULUS_URI . '/assets/images/publisher-logo.png',
			'width'  => 500,
			'height' => 500,
		),
	);
	if ( paulus_option( 'pub_email' ) ) {
		$org['email'] = paulus_option( 'pub_email' );
	}
	if ( paulus_option( 'pub_phone' ) ) {
		$org['telephone'] = paulus_phone_e164( paulus_option( 'pub_phone' ) );
	}
	if ( paulus_option( 'pub_address' ) ) {
		$org['address'] = paulus_address_schema( paulus_option( 'pub_address' ) );
	}
	return $org;
}

/**
 * Person: the author, shared by every article and the book.
 *
 * @return array
 */
function paulus_person_schema() {
	$person = array( '@type' => 'Person', 'name' => paulus_option( 'book_author' ) );
	if ( paulus_option( 'author_home' ) ) {
		$person['url'] = paulus_option( 'author_home' );
	} elseif ( paulus_option( 'author_url' ) ) {
		$person['url'] = paulus_option( 'author_url' );
	}
	$same = array();
	foreach ( array( 'author_home', 'author_url', 'author_youtube' ) as $paulus_key ) {
		if ( paulus_option( $paulus_key ) ) {
			$same[] = paulus_option( $paulus_key );
		}
	}
	// The social profiles from Theme Options join the author's own sites in
	// sameAs, which is what that property is for.
	foreach ( paulus_social_profiles() as $profile ) {
		$same[] = $profile[2];
	}
	if ( $same ) {
		$person['sameAs'] = array_values( array_unique( $same ) );
	}
	if ( function_exists( 'paulus_author_portrait_url' ) && paulus_author_portrait_url() ) {
		$person['image'] = paulus_author_portrait_url();
	}
	if ( paulus_option( 'author_bio' ) ) {
		// The bio may contain links; schema.org wants plain text.
		$person['description'] = wp_strip_all_tags( paulus_option( 'author_bio' ) );
	}
	return $person;
}

/**
 * WebSite.
 *
 * @param array $publisher Publisher reference.
 * @return array
 */
function paulus_website_schema( $publisher ) {
	return array(
		'@type'      => 'WebSite',
		'url'        => home_url( '/' ),
		'name'       => get_bloginfo( 'name' ),
		'inLanguage' => paulus_site_language(),
		'publisher'  => $publisher,
		'potentialAction' => array(
			'@type'       => 'SearchAction',
			'target'      => array(
				'@type'       => 'EntryPoint',
				'urlTemplate' => home_url( '/?s={search_term_string}' ),
			),
			'query-input' => 'required name=search_term_string',
		),
	);
}

/**
 * The site's language as a BCP 47 code, from its own locale rather than a
 * fixed value.
 *
 * @return string
 */
function paulus_site_language() {
	return str_replace( '_', '-', get_bloginfo( 'language' ) ) ?: 'en';
}

/**
 * BreadcrumbList for everything below the front page, or an empty array.
 *
 * @return array
 */
function paulus_breadcrumb_schema() {
	$crumbs = array( array( __( 'Home', 'paulus' ), home_url( '/' ) ) );
	if ( is_category() ) {
		$crumbs[] = array( get_queried_object()->name, get_term_link( get_queried_object() ) );
	} elseif ( is_single() ) {
		$part = paulus_part_of( get_the_ID() );
		if ( $part ) {
			$crumbs[] = array( $part->name, get_term_link( $part ) );
		}
		$crumbs[] = array( get_the_title(), get_permalink() );
	} elseif ( is_page() ) {
		$post = get_post();
		if ( $post->post_parent ) {
			$crumbs[] = array( get_the_title( $post->post_parent ), get_permalink( $post->post_parent ) );
		}
		$crumbs[] = array( get_the_title(), get_permalink() );
	}
	if ( count( $crumbs ) < 2 ) {
		return array();
	}
	$list = array();
	foreach ( $crumbs as $i => $c ) {
		$list[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => $c[0], 'item' => $c[1] );
	}
	return array( '@type' => 'BreadcrumbList', 'itemListElement' => $list );
}

/**
 * Article, with image metadata.
 *
 * @param array      $author    Author reference.
 * @param array      $publisher Publisher reference.
 * @param array|null $website   WebSite reference, when one is in the graph.
 * @return array
 */
function paulus_article_schema( $author, $publisher, $website = null ) {
	$post  = get_post();
	$image = has_post_thumbnail() ? get_the_post_thumbnail_url( null, 'large' ) : paulus_image_url( 'paul-portrait' );
	$slug  = has_post_thumbnail() ? (string) array_search( (int) get_post_thumbnail_id(), array_map( 'intval', (array) get_option( 'paulus_attachments', array() ) ), true ) : 'paul-portrait';
	$alt   = paulus_image_alts()[ $slug ] ?? '';
	$part  = paulus_part_of( $post->ID );
	$art   = array(
		'@type'            => 'Article',
		'mainEntityOfPage' => get_permalink(),
		'headline'         => get_the_title(),
		'description'      => paulus_meta_description(),
		'author'           => $author,
		'publisher'        => $publisher,
		'datePublished'    => get_the_date( 'c' ),
		'dateModified'     => get_the_modified_date( 'c' ),
		'inLanguage'       => paulus_site_language(),
		'wordCount'        => paulus_word_count( $post->post_content ),
		'image'            => paulus_article_image_schema( $image, $alt, $author ),
		'isBasedOn'        => array( '@type' => 'Book', 'name' => paulus_option( 'book_title' ), 'isbn' => paulus_option( 'book_isbn' ), 'author' => $author ),
	);
	if ( $website ) {
		$art['isPartOf'] = $website;
	}
	if ( $part ) {
		$art['articleSection'] = $part->name;
	}
	$series = function_exists( 'paulus_series_parts' ) ? paulus_series_parts( $post->ID ) : null;
	if ( $series ) {
		$art['position'] = (int) get_post_meta( $post->ID, '_paulus_part_no', true );
		$art['isPartOf'] = array( '@type' => 'CreativeWorkSeries', 'name' => $series['title'] );
	}
	return $art;
}

/**
 * The article illustration as an ImageObject.
 *
 * The site's article illustrations are public domain, so the licence URL
 * doubles as the terms Google asks for and no copyrightNotice is asserted
 * over them; credit is still given. A licence-acquisition page is only
 * emitted when one is set, since public-domain images need none. The book
 * cover and the publisher logo are not covered here: neither is public
 * domain, and neither carries these fields.
 *
 * @param string $url    Image URL.
 * @param string $alt    Alt text.
 * @param array  $author Author reference.
 * @return array
 */
function paulus_article_image_schema( $url, $alt, $author ) {
	$image   = array(
		'@type'      => 'ImageObject',
		'url'        => $url,
		'caption'    => $alt,
		'creator'    => $author,
		'creditText' => paulus_option( 'pub_name' ),
	);
	$license = (string) paulus_option( 'image_license' );
	if ( $license ) {
		$image['license'] = $license;
		$acquire          = (string) paulus_option( 'image_license_page' );
		if ( $acquire ) {
			$image['acquireLicensePage'] = $acquire;
		}
	} else {
		$image['copyrightNotice'] = paulus_option( 'pub_name' );
	}
	return $image;
}

/**
 * Visible words in post content: shortcodes and markup removed first, and
 * counted with a Unicode-aware pattern, since str_word_count() misreads
 * the Greek, Arabic and transliterated words these articles are full of.
 *
 * @param string $content Post content.
 * @return int
 */
function paulus_word_count( $content ) {
	$text = wp_strip_all_tags( strip_shortcodes( (string) $content ) );
	return (int) preg_match_all( '/[\p{L}\p{N}][\p{L}\p{N}\p{M}\'’\-]*/u', $text );
}

/**
 * The Book entity, built from the admin's own book/publisher fields.
 *
 * @param array $author    Author reference: either an inline Person or an
 *                          {@id} pointer into the surrounding graph.
 * @param array $publisher Publisher reference, same shape.
 * @return array
 */
function paulus_book_schema( $author, $publisher ) {
	$book = array(
		'@type'         => 'Book',
		'@id'           => get_permalink() . '#book',
		'name'          => paulus_option( 'book_title' ),
		'alternateName' => paulus_option( 'book_title_en' ),
		'author'        => $author,
		'publisher'     => $publisher,
		'isbn'          => paulus_option( 'book_isbn' ),
		'inLanguage'    => paulus_language_code( paulus_option( 'book_language' ), 'ms' ),
		'bookFormat'    => paulus_book_format_uri( paulus_option( 'book_format' ) ),
		'image'         => paulus_image_url( 'book-cover' ),
		'description'   => paulus_option( 'book_blurb' ),
	);
	// schema.org has no OCLC property, so the control number goes in the
	// standard identifier slot with its scheme named.
	if ( paulus_option( 'book_oclc' ) ) {
		$book['identifier'] = array(
			'@type'      => 'PropertyValue',
			'propertyID' => 'OCLC',
			'value'      => paulus_option( 'book_oclc' ),
		);
	}
	// "Extent (pages)" is free text such as "xxvi + 195 pages"; the
	// schema property wants a page count, so the last number in the
	// string is used (the convention for such notation is roman front
	// matter followed by the arabic body count). Left out entirely
	// rather than guessed at when no number is present.
	if ( preg_match_all( '/\d+/', (string) paulus_option( 'book_pages' ), $nums ) ) {
		$book['numberOfPages'] = (int) end( $nums[0] );
	}
	// "First published" is also free text ("August 2025"); parsed to a
	// schema-friendly date where possible, otherwise left out.
	$published = strtotime( (string) paulus_option( 'book_first_pub' ) );
	if ( false !== $published ) {
		$book['datePublished'] = gmdate( 'Y-m', $published );
	}
	if ( paulus_option( 'book_buy_url' ) ) {
		$book['offers'] = array(
			'@type'         => 'Offer',
			'url'           => paulus_option( 'book_buy_url' ),
			'price'         => preg_replace( '/[^0-9.]/', '', paulus_option( 'book_price' ) ),
			'priceCurrency' => 'MYR',
			'availability'  => 'https://schema.org/InStock',
			'seller'        => $publisher,
		);
	}
	return $book;
}

/**
 * The author and publisher as self-contained entities, for schema added
 * alongside another plugin's graph: that graph has its own @id values for
 * these, so a reference into it would be a guess, and repeating our own
 * ids would introduce a second, competing Person or Organization.
 *
 * @return array [ author, publisher ]
 */
function paulus_schema_parties() {
	$author = array( '@type' => 'Person', 'name' => paulus_option( 'book_author' ) );
	if ( paulus_option( 'author_home' ) ) {
		$author['url'] = paulus_option( 'author_home' );
	}
	$publisher = array( '@type' => 'Organization', 'name' => paulus_option( 'pub_name' ) );
	if ( paulus_option( 'pub_url' ) ) {
		$publisher['url'] = paulus_option( 'pub_url' );
	}
	return array( $author, $publisher );
}

/**
 * Schema this theme can supply that Rank Math's graph doesn't already
 * contain, for the current request.
 *
 * Only types absent from $data are returned, so Rank Math always wins
 * wherever the two overlap; what it doesn't emit (its schema module has no
 * Book type) still gets published rather than being lost.
 *
 * @param array $data Rank Math's entities.
 * @return array
 */
function paulus_schema_additions( $data ) {
	list( $author, $publisher ) = paulus_schema_parties();
	$add = array();

	// Book, on the book page. Rank Math's schema module has no Book type,
	// so in practice this is the one the theme always supplies.
	if ( is_page( 'the-book' ) && ! paulus_schema_type_present( $data, array( 'Book' ) ) ) {
		$add[] = paulus_book_schema( $author, $publisher );
	}

	// Anything else Rank Math isn't emitting for this request. Subtypes
	// count as covered: BlogPosting or NewsArticle means Rank Math is
	// describing the article, so the theme stays out of it.
	if ( is_single() && ! paulus_schema_type_present( $data, array( 'Article', 'BlogPosting', 'NewsArticle', 'ScholarlyArticle', 'TechArticle' ) ) ) {
		$add[] = paulus_article_schema( $author, $publisher );
	}
	if ( ! paulus_schema_type_present( $data, array( 'BreadcrumbList' ) ) ) {
		$crumbs = paulus_breadcrumb_schema();
		if ( $crumbs ) {
			$add[] = $crumbs;
		}
	}
	if ( ! paulus_schema_type_present( $data, array( 'Organization', 'NewsMediaOrganization', 'OnlineBusiness', 'LocalBusiness', 'Corporation' ) ) ) {
		$add[] = paulus_org_schema();
	}
	if ( ! paulus_schema_type_present( $data, array( 'Person' ) ) ) {
		$add[] = paulus_person_schema();
	}
	if ( ! paulus_schema_type_present( $data, array( 'WebSite' ) ) ) {
		$add[] = paulus_website_schema( $publisher );
	}
	// Rank Math describes the page itself (WebPage, CollectionPage and so on);
	// what it cannot know is the site's own structure. The article list of a
	// section, the questions on the Answers page and the licensed photographs
	// in the text are added as their own nodes, never replacing its page node.
	if ( is_category() && ! paulus_schema_type_present( $data, array( 'ItemList' ) ) ) {
		$list = paulus_itemlist_schema();
		if ( $list ) {
			$add[] = $list;
		}
	}
	if ( is_page( 'answers' ) && ! paulus_schema_type_present( $data, array( 'FAQPage' ) ) ) {
		$faq = paulus_faq_schema();
		if ( $faq ) {
			$add[] = $faq;
		}
	}
	foreach ( paulus_figure_images_schema() as $img ) {
		$add[] = $img;
	}
	return $add;
}

/**
 * A Malaysian telephone number in international form: "011-7346 8081"
 * becomes "+60-11-7346 8081". Numbers already international are kept.
 *
 * @param string $phone As entered.
 * @return string
 */
function paulus_phone_e164( $phone ) {
	$phone = trim( (string) $phone );
	if ( '' === $phone || '+' === $phone[0] ) {
		return $phone;
	}
	if ( '0' === $phone[0] ) {
		return '+60-' . ltrim( substr( $phone, 1 ), ' -' );
	}
	return $phone;
}

/**
 * The publisher's address, entered as free lines, split into the parts a
 * PostalAddress has: street, postcode and town, state, country.
 *
 * @param string $raw Address as entered, one line per part.
 * @return array
 */
function paulus_address_schema( $raw ) {
	$lines = array_values( array_filter( array_map( 'trim', preg_split( '/\R/', (string) $raw ) ) ) );
	$addr  = array( '@type' => 'PostalAddress', 'addressCountry' => 'MY' );
	$street = array();
	foreach ( $lines as $line ) {
		if ( preg_match( '/^(\d{5})\s+(.+)$/u', $line, $m ) ) {
			$addr['postalCode']      = $m[1];
			$addr['addressLocality'] = $m[2];
		} elseif ( preg_match( '/^([^,]+),\s*Malaysia$/iu', $line, $m ) ) {
			$addr['addressRegion'] = $m[1];
		} elseif ( ! preg_match( '/^Malaysia$/iu', $line ) ) {
			$street[] = rtrim( $line, ',' );
		}
	}
	if ( $street ) {
		$addr['streetAddress'] = implode( ', ', $street );
	}
	return $addr;
}

/**
 * The page node for the current request, typed by what the page is:
 * CollectionPage for sections, FAQPage for Answers, ItemPage for the book,
 * SearchResultsPage for search, WebPage otherwise. Articles are described
 * by their Article node, so none is added for them.
 *
 * @param array $website WebSite reference.
 * @return array
 */
function paulus_webpage_schema( $website ) {
	if ( is_single() || is_404() ) {
		return array();
	}
	$url  = is_search() ? home_url( '/?s=' . rawurlencode( get_search_query( false ) ) ) : ( is_category() ? get_term_link( get_queried_object() ) : ( is_front_page() ? home_url( '/' ) : get_permalink() ) );
	$name = is_search() ? sprintf( /* translators: %s: search terms. */ __( 'Search results for “%s”', 'paulus' ), get_search_query( false ) ) : ( is_category() ? single_cat_title( '', false ) : ( is_front_page() ? get_bloginfo( 'name' ) : get_the_title() ) );
	$node = array(
		'@type'      => 'WebPage',
		'@id'        => $url . '#webpage',
		'url'        => $url,
		'name'       => wp_strip_all_tags( $name ),
		'isPartOf'   => $website,
		'inLanguage' => paulus_site_language(),
	);
	$desc = paulus_meta_description();
	if ( $desc && ! is_search() ) {
		$node['description'] = $desc;
	}
	if ( is_category() ) {
		$node['@type']      = 'CollectionPage';
		$node['mainEntity'] = array( '@id' => $url . '#articles' );
	} elseif ( is_search() ) {
		$node['@type'] = 'SearchResultsPage';
	} elseif ( is_page( 'the-book' ) ) {
		$node['@type']      = 'ItemPage';
		$node['mainEntity'] = array( '@id' => get_permalink() . '#book' );
	} elseif ( is_page( 'answers' ) ) {
		$faq = paulus_faq_schema();
		if ( $faq ) {
			unset( $faq['@id'] );
			$node = array_merge( $node, $faq, array( '@id' => $node['@id'] ) );
		}
	} elseif ( is_front_page() ) {
		$node['about'] = array( '@type' => 'Person', 'name' => 'Paul of Tarsus' );
	}
	return $node;
}

/**
 * On a section page, its articles in reading order as an ItemList: one entry
 * per series (its first part) and per stand-alone article, as the page lists them.
 *
 * @return array
 */
function paulus_itemlist_schema() {
	if ( ! is_category() || ! function_exists( 'paulus_chapters' ) ) {
		return array();
	}
	$term  = get_queried_object();
	$items = array();
	$seen  = array();
	foreach ( paulus_chapters( $term->term_id ) as $c ) {
		$series = get_post_meta( $c->ID, '_paulus_series', true );
		if ( $series && isset( $seen[ $series ] ) ) {
			continue;
		}
		if ( $series ) {
			$seen[ $series ] = true;
		}
		$title   = $series ? ( get_post_meta( $c->ID, '_paulus_series_title', true ) ?: get_the_title( $c ) ) : get_the_title( $c );
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => count( $items ) + 1,
			'url'      => get_permalink( $c ),
			'name'     => wp_strip_all_tags( $title ),
		);
	}
	if ( ! $items ) {
		return array();
	}
	return array(
		'@type'           => 'ItemList',
		'@id'             => get_term_link( $term ) . '#articles',
		'name'            => $term->name,
		'numberOfItems'   => count( $items ),
		'itemListOrder'   => 'https://schema.org/ItemListOrderAscending',
		'itemListElement' => $items,
	);
}

/**
 * The Answers page as an FAQPage: each h2 is a question, and the paragraphs
 * under it, up to the "Full evidence" link, are its answer.
 *
 * @return array
 */
function paulus_faq_schema() {
	$post = get_page_by_path( 'answers' );
	if ( ! $post ) {
		return array();
	}
	$html = preg_replace( '#<sup[^>]*>.*?</sup>|<ol class="footnotes">.*?</ol>#s', '', (string) $post->post_content );
	$html = strip_shortcodes( $html );
	if ( ! preg_match_all( '#<h2([^>]*)>(.*?)</h2>(.*?)(?=<h2|\z)#s', $html, $m, PREG_SET_ORDER ) ) {
		return array();
	}
	$qs = array();
	foreach ( $m as $row ) {
		$id   = preg_match( '#\sid="([^"]+)"#', $row[1], $idm ) ? $idm[1] : '';
		$row  = array( $row[0], $row[2], $row[3] );
		$body = preg_replace( '#<p class="paulus-readmore">.*?</p>#s', '', $row[2] );
		$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $body ) ) );
		if ( '' === $text ) {
			continue;
		}
		$q = array(
			'@type'          => 'Question',
			'name'           => wp_strip_all_tags( $row[1] ),
			'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $text ),
		);
		if ( $id ) {
			// Each question's own address, so a result can open at its answer.
			$q['url'] = get_permalink( $post ) . '#' . $id;
		}
		$qs[] = $q;
	}
	if ( ! $qs ) {
		return array();
	}
	return array(
		'@type'      => 'FAQPage',
		'@id'        => get_permalink( $post ) . '#faq',
		'mainEntity' => $qs,
	);
}

/**
 * The photographs placed with [paulus_figure] on this page, as ImageObjects
 * carrying their licence, the Commons page to acquire it, the credit and the
 * creator: the image-licence metadata Google reads for Images results.
 *
 * @return array
 */
function paulus_figure_images_schema() {
	if ( ! is_singular() || ! function_exists( 'paulus_figures' ) ) {
		return array();
	}
	$content = (string) get_post_field( 'post_content', get_queried_object_id() );
	if ( ! preg_match_all( '#\[paulus_figure name="([a-z0-9-]+)"[^\]]*\](.*?)\[/paulus_figure\]#s', $content, $m, PREG_SET_ORDER ) ) {
		return array();
	}
	$figs = paulus_figures();
	$out  = array();
	foreach ( $m as $row ) {
		if ( ! isset( $figs[ $row[1] ] ) ) {
			continue;
		}
		$f   = $figs[ $row[1] ];
		$url = PAULUS_URI . '/assets/images/church/' . $row[1] . '.webp';
		$lic = $f['license_url'] ? $f['license_url'] : ( 'Public domain' === $f['license'] ? 'https://creativecommons.org/publicdomain/mark/1.0/' : '' );
		$img = array(
			'@type'              => 'ImageObject',
			'@id'                => $url . '#image',
			'contentUrl'         => $url,
			'url'                => $url,
			'caption'            => trim( wp_strip_all_tags( $row[2] ) ),
			'description'        => $f['alt'],
			'creditText'         => $f['author'] . ', ' . $f['license'] . ( $f['source'] ? ', via Wikimedia Commons' : '' ),
		);
		if ( $f['source'] ) {
			$img['acquireLicensePage'] = $f['source'];
		}
		// Unknown painters and scribes get no creator; museums and firms are
		// organisations, everyone else a person.
		if ( ! preg_match( '/^Unknown/i', $f['author'] ) ) {
			$org            = preg_match( '/Museum|Company|Library|Gallery/i', $f['author'] );
			$img['creator'] = array( '@type' => $org ? 'Organization' : 'Person', 'name' => preg_replace( '/^Photograph:\s*/', '', $f['author'] ) );
		}
		if ( $lic ) {
			$img['license'] = $lic;
		}
		$out[] = $img;
	}
	return $out;
}

/**
 * Merge those additions into Rank Math's own JSON-LD graph.
 *
 * Going through Rank Math's filter, rather than printing a second script
 * tag, means the theme can see exactly what Rank Math is emitting for this
 * request and stay out of its way: anything it manages (titles, canonicals,
 * robots, Article, Breadcrumb, Organization, Person, WebSite) is left
 * untouched, including a type the site owner has deliberately switched off
 * — an absent type is not re-added by us unless the theme is its only
 * source.
 *
 * @param array $data Rank Math's entities.
 * @return array
 */
function paulus_rank_math_json_ld( $data ) {
	$GLOBALS['paulus_rank_math_json_ld_ran'] = true;
	// Keys are prefixed rather than appended numerically: Rank Math's
	// connect_schema_entities() only rewrites entries whose key starts with
	// "schema-" (or "richSnippet"), so a paulus_* key is passed through
	// untouched instead of being folded into its own entity graph.
	foreach ( paulus_schema_additions( is_array( $data ) ? $data : array() ) as $i => $entity ) {
		$data[ 'paulus_' . $i ] = $entity;
	}
	return $data;
}
// Priority 100: Rank Math's own entity-linking pass runs at 99, so this
// sees the final graph rather than racing it on registration order.
add_filter( 'rank_math/json_ld', 'paulus_rank_math_json_ld', 100 );

/**
 * Schema for the other supported SEO plugins, and for a Rank Math whose
 * JSON-LD never ran.
 *
 * Yoast, AIOSEO, SEOPress and The SEO Framework are detected but not
 * inspected, so only the Book is added there: it's the one type none of
 * them emits, which makes duplication impossible. If Rank Math is active
 * but its filter didn't fire (its schema module switched off, so it is
 * publishing no graph at all), the theme's own full graph is printed
 * instead, since nothing would otherwise be output.
 */
function paulus_schema_alongside_plugin() {
	if ( ! paulus_seo_plugin_active() ) {
		return;
	}
	if ( paulus_rank_math_active() ) {
		if ( empty( $GLOBALS['paulus_rank_math_json_ld_ran'] ) ) {
			paulus_print_schema_graph();
		}
		return;
	}
	if ( ! is_page( 'the-book' ) ) {
		return;
	}
	list( $author, $publisher ) = paulus_schema_parties();
	paulus_print_json_ld( array( paulus_book_schema( $author, $publisher ) ) );
}
add_action( 'wp_head', 'paulus_schema_alongside_plugin', 99 );

/**
 * Print a JSON-LD graph.
 *
 * @param array $graph Entities.
 */
function paulus_print_json_ld( $graph ) {
	if ( ! $graph ) {
		return;
	}
	echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $graph ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}

/**
 * Meta description field in the editor sidebar.
 */
function paulus_meta_box() {
	add_meta_box( 'paulus_meta', __( 'Search title and description', 'paulus' ), 'paulus_meta_box_html', array( 'post', 'page' ), 'side' );
}
add_action( 'add_meta_boxes', 'paulus_meta_box' );

/**
 * Meta box markup.
 *
 * @param WP_Post $post Post.
 */
function paulus_meta_box_html( $post ) {
	wp_nonce_field( 'paulus_meta', 'paulus_meta_nonce' );
	$room = PAULUS_TITLE_MAX - paulus_ulen( ' | ' . get_bloginfo( 'name' ) );
	$t    = get_post_meta( $post->ID, '_paulus_seo_title', true );
	$v    = get_post_meta( $post->ID, '_paulus_meta', true );
	/* translators: 1: characters available, 2: site name. */
	echo '<p><label for="paulus-seo-title">' . esc_html( sprintf( __( 'Title, up to %1$d characters, shown before "| %2$s". Leave empty to use the page title.', 'paulus' ), $room, get_bloginfo( 'name' ) ) ) . '</label></p>';
	echo '<input type="text" id="paulus-seo-title" name="paulus_seo_title" maxlength="' . (int) $room . '" value="' . esc_attr( $t ) . '" style="width:100%">';
	echo '<p><label for="paulus-meta">' . esc_html__( 'Description, 120 to 130 characters, ending on a quiet invitation to read on.', 'paulus' ) . '</label></p>';
	echo '<textarea id="paulus-meta" name="paulus_meta" rows="4" maxlength="' . (int) PAULUS_META_MAX . '" style="width:100%">' . esc_textarea( $v ) . '</textarea>';
}

/**
 * Save the meta description.
 *
 * @param int $post_id Post ID.
 */
function paulus_meta_save( $post_id ) {
	if ( ! isset( $_POST['paulus_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['paulus_meta_nonce'] ), 'paulus_meta' ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$room   = PAULUS_TITLE_MAX - paulus_ulen( ' | ' . get_bloginfo( 'name' ) );
	$fields = array(
		'_paulus_meta'      => paulus_fit( sanitize_text_field( wp_unslash( $_POST['paulus_meta'] ?? '' ) ), PAULUS_META_MAX ),
		'_paulus_seo_title' => paulus_fit( sanitize_text_field( wp_unslash( $_POST['paulus_seo_title'] ?? '' ) ), $room ),
	);
	foreach ( $fields as $key => $v ) {
		if ( '' !== $v ) {
			update_post_meta( $post_id, $key, $v );
		} else {
			delete_post_meta( $post_id, $key );
		}
	}
}
add_action( 'save_post', 'paulus_meta_save' );

/**
 * Sitemap page: every page with its summary, for the HTML sitemap.
 *
 * @return string
 */
function paulus_sc_page_list() {
	$pages = get_pages( array( 'sort_column' => 'menu_order,post_title' ) );
	$out   = '<ul class="paulus-children">';
	foreach ( $pages as $page ) {
		if ( in_array( $page->post_name, array( 'sitemap', 'site-map' ), true ) ) {
			continue;
		}
		$out .= '<li><a class="paulus-children__title" href="' . esc_url( get_permalink( $page ) ) . '">' . esc_html( get_the_title( $page ) ) . '</a>';
		if ( $page->post_excerpt ) {
			$out .= '<p>' . esc_html( $page->post_excerpt ) . '</p>';
		}
		$out .= '</li>';
	}
	return $out . '</ul>';
}
add_shortcode( 'paulus_page_list', 'paulus_sc_page_list' );

/**
 * Sections and pages as plain links, for the 404 page.
 *
 * @return string
 */
function paulus_sc_404_links() {
	$out = '<ul class="paulus-404__list">';
	foreach ( paulus_parts() as $part ) {
		$out .= '<li><a href="' . esc_url( get_term_link( $part ) ) . '">' . esc_html( $part->name ) . '</a></li>';
	}
	foreach ( array( 'answers', 'the-verdict', 'the-book', 'sitemap' ) as $slug ) {
		$page = 'sitemap' === $slug ? paulus_sitemap_page() : get_page_by_path( $slug );
		if ( $page ) {
			$out .= '<li><a href="' . esc_url( get_permalink( $page ) ) . '">' . esc_html( get_the_title( $page ) ) . '</a></li>';
		}
	}
	return $out . '</ul>';
}
add_shortcode( 'paulus_404_links', 'paulus_sc_404_links' );

/**
 * The not-found page's excuses: each one a lost page explained in Paul's
 * own manner, with the passage it borrows from. Only letters Paul wrote,
 * and Acts, are drawn on (see "Two Pauls" in Count·V).
 *
 * @return array<int, array{0:string,1:string,2:string}> Heading, line, source.
 */
function paulus_404_quips() {
	return array(
		array( __( 'This page went to Arabia.', 'paulus' ), __( 'After Damascus, Paul vanished into Arabia and came back three years later with a gospel no apostle had given him. This page went the same way, and has come back with even less.', 'paulus' ), 'Galatians 1:15–18' ),
		array( __( 'This page saw a light on the road.', 'paulus' ), __( 'Luke tells that story three times, and the witnesses cannot agree whether they stood, fell or heard anything at all. We cannot agree where this page went either.', 'paulus' ), 'Acts 9:7; 22:9; 26:14' ),
		array( __( 'Five hundred witnesses saw this page.', 'paulus' ), __( 'Not one of them left a name, and none of them wrote it down. You may take our word for it, as Corinth took Paul\'s.', 'paulus' ), '1 Corinthians 15:6' ),
		array( __( 'This page was let down the wall in a basket.', 'paulus' ), __( 'It slipped out by night, as Paul slipped out of Damascus. The governor\'s men are still waiting at the gate.', 'paulus' ), '2 Corinthians 11:32–33' ),
		array( __( 'This page became all things to all men.', 'paulus' ), __( 'A Jew to the Jews, lawless to the lawless, and in the end nothing at all to anyone. Paul called it a mission strategy. Here it is a broken link.', 'paulus' ), '1 Corinthians 9:20–22' ),
		array( __( 'Thrice shipwrecked. This page is still at sea.', 'paulus' ), __( 'Paul counted his shipwrecks with some pride. We count this one as a loss, and invite you back to dry land below.', 'paulus' ), '2 Corinthians 11:25' ),
		array( __( 'Behold, before God, this page lies not.', 'paulus' ), __( 'Paul swore as much about his travels, and Acts tells them differently. We swear nothing. The page is simply gone.', 'paulus' ), 'Galatians 1:20; Acts 9:26–27' ),
		array( __( 'Absent in body, present in spirit.', 'paulus' ), __( 'Paul passed judgement on Corinth from a distance on those terms. This page is absent in body, and its spirit has left with it.', 'paulus' ), '1 Corinthians 5:3' ),
	);
}

/**
 * [paulus_404_quip] One excuse at random, with a link for another.
 *
 * @return string
 */
function paulus_sc_404_quip() {
	$quips = paulus_404_quips();
	list( $title, $line, $source ) = $quips[ random_int( 0, count( $quips ) - 1 ) ];
	$again = esc_url( add_query_arg( array() ) );
	return '<p class="paulus-404__kicker">' . paulus_greek_mark( '404' ) . ' <span aria-hidden="true">·</span> ' . esc_html__( 'Error 404 · Page not found', 'paulus' ) . '</p>'
		. '<h1 class="paulus-page__title paulus-404__title">' . esc_html( $title ) . '</h1>'
		. '<p class="paulus-404__quip">' . esc_html( $line ) . '</p>'
		. '<p class="paulus-404__source"><span>' . esc_html( $source ) . '</span> <a href="' . $again . '" rel="nofollow">' . esc_html__( 'Hear another excuse', 'paulus' ) . '</a></p>';
}
add_shortcode( 'paulus_404_quip', 'paulus_sc_404_quip' );
