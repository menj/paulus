<?php
/**
 * Shortcodes that render option-driven sections inside block templates.
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

/**
 * Collapse whitespace between tags so wpautop in the Shortcode block
 * does not wrap template markup in stray paragraphs and line breaks.
 *
 * @param string $html Markup.
 * @return string
 */
function paulus_compact( $html ) {
	return trim( (string) preg_replace( '/>\s+</', '><', $html ) );
}

/**
 * Permalink of the book page, or an empty string if it does not exist.
 *
 * @return string
 */
function paulus_book_url() {
	$page = get_page_by_path( 'the-book' );
	return ( $page && 'publish' === $page->post_status ) ? (string) get_permalink( $page ) : '';
}

/**
 * Hero: heading, introduction and portrait.
 *
 * @return string
 */
function paulus_sc_hero() {
	$book_url = paulus_book_url();
	ob_start();
	?>
	<section class="paulus-hero">
		<div class="paulus-hero__text">
			<?php if ( paulus_option( 'hero_kicker' ) ) : ?>
				<p class="paulus-hero__kicker"><?php echo esc_html( paulus_option( 'hero_kicker' ) ); ?></p>
			<?php endif; ?>
			<h1 class="paulus-hero__heading"><?php echo esc_html( paulus_option( 'hero_heading' ) ); ?></h1>
			<?php if ( paulus_option( 'hero_subheading' ) ) : ?>
				<p class="paulus-hero__subheading"><?php echo esc_html( paulus_option( 'hero_subheading' ) ); ?></p>
			<?php endif; ?>
			<?php if ( paulus_option( 'book_author' ) ) : ?>
				<p class="paulus-hero__author"><span class="paulus-hero__name"><?php echo esc_html( paulus_option( 'book_author' ) ); ?></span></p>
			<?php endif; ?>
			<p class="paulus-hero__lede"><?php echo esc_html( paulus_option( 'hero_lede' ) ); ?></p>
			<p class="paulus-hero__actions">
				<?php $charges = get_term_by( 'slug', 'the-charges', 'category' ); ?>
				<a class="paulus-button" href="<?php echo esc_url( $charges ? get_term_link( $charges ) : '#the-charges' ); ?>"><?php esc_html_e( 'Read the charges', 'paulus' ); ?></a>
				<?php if ( $book_url ) : ?>
					<a class="paulus-button paulus-button--outline" href="<?php echo esc_url( $book_url ); ?>"><?php esc_html_e( 'About the book', 'paulus' ); ?></a>
				<?php endif; ?>
			</p>
		</div>
		<figure class="paulus-hero__figure">
			<?php $hero_src = paulus_hero_image_url( paulus_option( 'hero_image' ) ); $hero_set = paulus_hero_srcset( $hero_src ); ?>
			<img src="<?php echo esc_url( $hero_src ); ?>"<?php if ( $hero_set ) : ?> srcset="<?php echo esc_attr( $hero_set ); ?>" sizes="(max-width: 700px) 92vw, (max-width: 1024px) 50vw, 640px"<?php endif; ?> alt="<?php echo esc_attr( paulus_image_alts()[ paulus_option( 'hero_image' ) ] ?? '' ); ?>" width="962" height="1024" fetchpriority="high" decoding="async">
		</figure>
	</section>
	<?php
	return paulus_compact( ob_get_clean() );
}
add_shortcode( 'paulus_hero', 'paulus_sc_hero' );

/**
 * Bibliographic details as a definition list.
 *
 * @return string
 */
function paulus_book_details() {
	$rows = array(
		__( 'Author', 'paulus' )          => paulus_option( 'book_author' ),
		__( 'Publisher', 'paulus' )       => paulus_option( 'pub_name' ),
		__( 'First published', 'paulus' ) => paulus_option( 'book_first_pub' ),
		__( 'Printing', 'paulus' )        => paulus_option( 'book_reprint' ),
		__( 'Language', 'paulus' )        => paulus_option( 'book_language' ),
		__( 'Extent', 'paulus' )          => paulus_option( 'book_pages' ),
		__( 'Format', 'paulus' )          => paulus_option( 'book_format' ),
		__( 'ISBN', 'paulus' )            => paulus_option( 'book_isbn' ),
		__( 'OCLC', 'paulus' )            => paulus_option( 'book_oclc' ),
		__( 'Price', 'paulus' )           => paulus_option( 'book_price' ),
	);
	$out = '<dl class="paulus-details">';
	foreach ( $rows as $label => $value ) {
		if ( '' === trim( (string) $value ) ) {
			continue;
		}
		$out .= '<div><dt>' . esc_html( $label ) . '</dt><dd>' . esc_html( $value ) . '</dd></div>';
	}
	return $out . '</dl>';
}

/**
 * Order call to action. Falls back to emailing the publisher.
 *
 * @return string
 */
function paulus_order_link() {
	$url = paulus_option( 'book_buy_url' );
	if ( $url ) {
		return '<a class="paulus-button" href="' . esc_url( $url ) . '">' . esc_html__( 'Order the book', 'paulus' ) . '</a>';
	}
	$email = paulus_option( 'pub_email' );
	if ( $email ) {
		return '<a class="paulus-button" href="' . esc_url( 'mailto:' . antispambot( $email ) . '?subject=' . rawurlencode( paulus_option( 'book_title' ) ) ) . '">' . esc_html__( 'Order the book', 'paulus' ) . '</a>';
	}
	return '';
}

/**
 * Book feature: cover, titles, description, details, order link.
 *
 * @param array $atts Shortcode attributes. full="1" adds publisher contact.
 * @return string
 */
function paulus_sc_book( $atts ) {
	$atts = shortcode_atts( array( 'full' => '0' ), $atts, 'paulus_book' );
	ob_start();
	?>
	<section class="paulus-book" id="book">
		<figure class="paulus-book__cover">
			<img src="<?php echo esc_url( paulus_image_url( 'book-cover' ) ); ?>" alt="<?php echo esc_attr( sprintf( /* translators: %s: book title. */ __( 'Front cover of %s', 'paulus' ), paulus_option( 'book_title' ) ) ); ?>" width="960" height="1291" loading="lazy">
		</figure>
		<div class="paulus-book__body">
			<?php if ( '1' === $atts['full'] && paulus_greek_mark( 'the-book' ) ) : ?>
				<p class="paulus-greek-line"><?php echo paulus_greek_mark( 'the-book', 'paulus-greek--panel' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
			<?php endif; ?>
			<p class="paulus-book__kicker"><?php esc_html_e( 'The book', 'paulus' ); ?></p>
			<?php $h = '1' === $atts['full'] ? 'h1' : 'h2'; ?>
			<<?php echo $h; // phpcs:ignore WordPress.Security.EscapeOutput ?> class="paulus-book__title" lang="ms"><?php echo esc_html( paulus_option( 'book_title' ) ); ?></<?php echo $h; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
			<p class="paulus-book__subtitle"><span lang="ms"><?php echo esc_html( paulus_option( 'book_subtitle' ) ); ?></span><br><span class="paulus-book__gloss"><?php echo esc_html( paulus_option( 'book_title_en' ) ); ?>: <?php echo esc_html( paulus_option( 'book_subtitle_en' ) ); ?></span></p>
			<p class="paulus-book__blurb" lang="ms"><?php echo esc_html( paulus_option( 'book_blurb' ) ); ?></p>
			<?php echo paulus_book_details(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<p class="paulus-book__actions"><?php echo paulus_order_link(); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
			<?php if ( '1' === $atts['full'] ) : ?>
				<address class="paulus-publisher">
					<strong><?php echo esc_html( paulus_option( 'pub_name' ) ); ?></strong>
					<?php if ( paulus_option( 'pub_reg' ) ) : ?>
						<span><?php echo esc_html( sprintf( /* translators: %s: registration number. */ __( 'Registration no. %s', 'paulus' ), paulus_option( 'pub_reg' ) ) ); ?></span>
					<?php endif; ?>
					<?php if ( paulus_option( 'pub_email' ) ) : ?>
						<a href="<?php echo esc_url( 'mailto:' . antispambot( paulus_option( 'pub_email' ) ) ); ?>"><?php echo esc_html( antispambot( paulus_option( 'pub_email' ) ) ); ?></a>
					<?php endif; ?>
					<?php if ( paulus_option( 'pub_url' ) ) : ?>
						<a href="<?php echo esc_url( paulus_option( 'pub_url' ) ); ?>"><?php echo esc_html( preg_replace( '#^https?://#', '', paulus_option( 'pub_url' ) ) ); ?></a>
					<?php endif; ?>
				</address>
			<?php endif; ?>
		</div>
	</section>
	<?php
	return paulus_compact( ob_get_clean() );
}
add_shortcode( 'paulus_book', 'paulus_sc_book' );

/**
 * The portrait gallery was removed in 1.6.0. The shortcode stays registered
 * and returns nothing, so pages that still contain it render cleanly.
 *
 * @return string
 */
function paulus_sc_gallery() {
	return '';
}
add_shortcode( 'paulus_gallery', 'paulus_sc_gallery' );

/**
 * Footer colophon.
 *
 * @return string
 */
function paulus_sc_colophon() {
	// The footer credit, in the tablet: that the site's English text is drawn
	// from the Malay book, then its author, title, edition, imprint,
	// ISBN and OCLC number, set to run three or four lines. Edition and
	// imprint follow the library record (Theme Options, Book).
	$parts = array();
	if ( paulus_option( 'book_author' ) ) {
		$parts[] = esc_html__( 'The English text of this site is drawn from the Malay original:', 'paulus' ) . ' ' . esc_html( rtrim( paulus_option( 'book_author' ), ' .' ) ) . ', <span lang="ms">' . esc_html( rtrim( (string) paulus_option( 'book_title' ), ' .' ) ) . '</span>.';
	}
	if ( paulus_option( 'cat_edition' ) ) {
		$parts[] = '<span lang="ms">' . esc_html( rtrim( paulus_option( 'cat_edition' ), ' .' ) ) . '</span>.';
	}
	if ( paulus_option( 'cat_imprint' ) ) {
		$parts[] = esc_html( rtrim( paulus_option( 'cat_imprint' ), ' .' ) ) . '.';
	}
	if ( paulus_option( 'book_isbn' ) ) {
		$parts[] = '<span class="paulus-colophon__id">ISBN ' . esc_html( paulus_option( 'book_isbn' ) ) . '.</span>';
	}
	// OCLC only when one is recorded: the field is optional.
	if ( paulus_option( 'book_oclc' ) ) {
		/* translators: %s: OCLC control number. */
		$parts[] = '<span class="paulus-colophon__id">' . sprintf( esc_html__( 'OCLC %s.', 'paulus' ), esc_html( paulus_option( 'book_oclc' ) ) ) . '</span>';
	}
	return '<p class="paulus-colophon">' . implode( ' ', $parts ) . '</p>';
}
add_shortcode( 'paulus_colophon', 'paulus_sc_colophon' );

/**
 * [paulus_download file="…"]Label[/paulus_download] A link to a document
 * bundled in assets/docs/. Only a plain file name is accepted.
 *
 * @param array  $atts    Shortcode attributes.
 * @param string $content Link text.
 * @return string
 */
function paulus_sc_download( $atts, $content = '' ) {
	$atts = shortcode_atts( array( 'file' => '' ), $atts, 'paulus_download' );
	$file = (string) $atts['file'];
	if ( ! preg_match( '/^[a-z0-9._-]+$/i', $file ) || ! file_exists( PAULUS_DIR . '/assets/docs/' . $file ) ) {
		return '';
	}
	return '<a href="' . esc_url( PAULUS_URI . '/assets/docs/' . $file ) . '" download>' . esc_html( $content ?: $file ) . '</a>';
}
add_shortcode( 'paulus_download', 'paulus_sc_download' );

/**
 * [paulus_catalogue] The book's library catalogue record, for the book page:
 * label beside value, from Theme Options (Catalogue record, with edition,
 * imprint and OCLC number from the Book tab). The contents are left out,
 * since the page lists them in full.
 *
 * @return string
 */
function paulus_sc_catalogue() {
	$lines = static function ( $key ) {
		return array_values( array_filter( array_map( 'trim', explode( "\n", (string) paulus_option( $key ) ) ) ) );
	};
	$rows = array(
		__( 'Title', 'paulus' )                => array( paulus_option( 'cat_title' ), 'ms' ),
		__( 'Edition', 'paulus' )              => array( paulus_option( 'cat_edition' ), 'ms' ),
		__( 'Author', 'paulus' )               => array( paulus_option( 'book_author' ), '' ),
		__( 'Publication', 'paulus' )          => array( paulus_option( 'cat_imprint' ), '' ),
		__( 'Physical description', 'paulus' ) => array( paulus_option( 'cat_extent' ), '' ),
		__( 'Language', 'paulus' )             => array( paulus_option( 'cat_language' ), '' ),
		__( 'Notes', 'paulus' )                => array( $lines( 'cat_note' ), '' ),
		__( 'Subjects', 'paulus' )             => array( $lines( 'cat_subjects' ), '' ),
		__( 'ISBN', 'paulus' )                 => array( $lines( 'cat_isbn' ), '' ),
		__( 'Bib ID', 'paulus' )               => array( paulus_option( 'cat_bib' ), '' ),
		__( 'OCLC', 'paulus' )                 => array( paulus_option( 'book_oclc' ), '' ),
	);
	$out = '';
	foreach ( $rows as $label => list( $value, $lang ) ) {
		$values = array_filter( array_map( 'trim', (array) $value ) );
		if ( ! $values ) {
			continue;
		}
		$attr = $lang ? ' lang="' . esc_attr( $lang ) . '"' : '';
		$out .= '<div><dt>' . esc_html( $label ) . '</dt><dd' . $attr . '>' . implode( '<br>', array_map( 'esc_html', $values ) ) . '</dd></div>';
	}
	return $out ? '<dl class="paulus-catalogue">' . $out . '</dl>' : '';
}
add_shortcode( 'paulus_catalogue', 'paulus_sc_catalogue' );

/**
 * Footer badges, from Theme Options, Front page: links and images only.
 *
 * @return string
 */
function paulus_sc_footer_badges() {
	$html = paulus_option( 'footer_badges' );
	if ( ! $html ) {
		return '';
	}
	return '<div class="paulus-badges">' . wp_kses( $html, paulus_badge_kses() ) . '</div>';
}
add_shortcode( 'paulus_footer_badges', 'paulus_sc_footer_badges' );

/**
 * Compact pointer to the book page, used on the front page and articles.
 * The full bibliographic details live only on the book page.
 *
 * @return string
 */
function paulus_sc_book_teaser() {
	$url = paulus_book_url();
	if ( ! $url || is_page( 'the-book' ) ) {
		return '';
	}
	$buy     = paulus_option( 'book_buy_url' );
	$details = array_filter( array( paulus_option( 'book_format' ), paulus_option( 'book_pages' ), trim( paulus_option( 'pub_name' ) . ( paulus_option( 'book_first_pub' ) ? ', ' . paulus_option( 'book_first_pub' ) : '' ), ', ' ), paulus_option( 'book_isbn' ) ? 'ISBN ' . paulus_option( 'book_isbn' ) : '' ) );
	ob_start();
	?>
	<aside class="paulus-teaser" aria-label="<?php esc_attr_e( 'The book', 'paulus' ); ?>">
		<a class="paulus-teaser__cover" href="<?php echo esc_url( $url ); ?>" tabindex="-1" aria-hidden="true">
			<img src="<?php echo esc_url( paulus_image_url( 'book-cover' ) ); ?>" alt="" width="960" height="1291" loading="lazy">
		</a>
		<div class="paulus-teaser__body">
			<p class="paulus-teaser__kicker"><?php esc_html_e( 'The book behind the case', 'paulus' ); ?></p>
			<p class="paulus-teaser__title" lang="ms"><?php echo esc_html( paulus_option( 'book_title' ) ); ?></p>
			<?php if ( paulus_option( 'book_title_en' ) ) : ?>
				<p class="paulus-teaser__subtitle"><?php echo esc_html( paulus_option( 'book_title_en' ) . ( paulus_option( 'book_subtitle_en' ) ? ': ' . paulus_option( 'book_subtitle_en' ) : '' ) ); ?></p>
			<?php endif; ?>
			<p class="paulus-teaser__text"><?php echo esc_html( sprintf( /* translators: %s: author name. */ __( 'Every article on this site is drawn from the book by %s: the full argument in print, with its apparatus and sources, in Malay.', 'paulus' ), paulus_option( 'book_author' ) ) ); ?></p>
			<?php if ( $details ) : ?>
				<p class="paulus-teaser__details"><?php echo implode( '<span class="paulus-teaser__sep" aria-hidden="true"> · </span>', array_map( function ( $d ) { return '<span class="paulus-teaser__detail">' . esc_html( $d ) . '</span>'; }, $details ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
			<?php endif; ?>
			<div class="paulus-teaser__actions">
				<?php if ( paulus_option( 'book_price' ) ) : ?>
					<p class="paulus-teaser__price"><span class="paulus-teaser__price-label"><?php echo esc_html( paulus_option( 'book_format' ) ? paulus_option( 'book_format' ) : __( 'Price', 'paulus' ) ); ?></span> <span class="paulus-teaser__price-value"><?php echo esc_html( paulus_option( 'book_price' ) ); ?></span></p>
				<?php endif; ?>
				<div class="paulus-teaser__buttons">
					<?php if ( $buy ) : ?>
						<a class="paulus-button" href="<?php echo esc_url( $buy ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Order the book', 'paulus' ); ?></a>
					<?php endif; ?>
					<a class="paulus-button paulus-button--outline" href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'About the book', 'paulus' ); ?></a>
				</div>
			</div>
		</div>
	</aside>
	<?php
	return paulus_compact( ob_get_clean() );
}
add_shortcode( 'paulus_book_teaser', 'paulus_sc_book_teaser' );

/**
 * [paulus_email] The publisher's address (Theme Options, Book) as a mail
 * link, so every page that gives it follows a change of address.
 *
 * @return string
 */
function paulus_sc_email() {
	$email = sanitize_email( (string) paulus_option( 'pub_email' ) );
	return $email ? '<a href="' . esc_url( 'mailto:' . antispambot( $email ) ) . '">' . esc_html( antispambot( $email ) ) . '</a>' : '';
}
add_shortcode( 'paulus_email', 'paulus_sc_email' );
