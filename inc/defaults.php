<?php
/**
 * Single source of truth for option defaults.
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

/**
 * Named color schemes. Keys match CSS classes in assets/css/schemes.css.
 *
 * @return array<string,string>
 */
function paulus_schemes() {
	return array(
		'ochre'    => __( 'Ochre (book cover)', 'paulus' ),
		'vellum'   => __( 'Vellum', 'paulus' ),
		'oxblood'  => __( 'Oxblood', 'paulus' ),
		'ink'      => __( 'Ink', 'paulus' ),
		'paper'    => __( 'Paper', 'paulus' ),
		'graphite' => __( 'Graphite', 'paulus' ),
	);
}

/**
 * Default option values.
 *
 * @return array<string,mixed>
 */
function paulus_defaults() {
	return array(
		// Site.
		'site_name'        => 'Apostle of Doom',
		'footer_badges'    => '<a href="https://www.ai-visibility.org.uk/apostleofdoom-org/" title="AI Visibility: 10/10 Platinum"><img src="https://ai-visibility-directory.winter-cake-bf57.workers.dev/badge/apostleofdoom-org.svg?style=themed&theme=dark&size=compact" alt="AI Visibility: 10/10 Platinum" width="180" height="28" loading="lazy"></a>',
		'footer_blurb'     => 'A case file against Paul of Tarsus: the man, the charges, the witnesses and the verdict, drawn from the book by Mohd Elfie Nieshaem Juferi.',
		'site_seo_title'   => 'The case against Paul of Tarsus',
		'site_meta'        => 'Saul of Tarsus hunted the followers of ʿĪsā ibn Maryam, then claimed to speak for him. The evidence, in his own letters, is here.',
		'site_tagline'     => 'Paul of Tarsus and the corruption of the message of ʿĪsā ibn Maryam',
		// Book.
		'cat_title'        => 'Paulus : perosak risalah al-Masih : sejarah bagaimana ajaran Kristian dicipta sepenuhnya / Mohd Elfie Nieshaem Juferi',
		'cat_extent'       => 'xxvi, 193 pages : illustrations ; 21 cm',
		'cat_language'     => 'Malay; this edition includes text in Arabic',
		'cat_note'         => "In Malay, with quotations from the Qur'an, hadith, etc., in Arabic\nIncludes bibliographical references",
		'cat_subjects'     => "Paul, the Apostle, Saint -- Conversion\nPaul, the Apostle, Saint -- Influence\nChristianity -- History of doctrines\nIslam -- Apologetic works\nIslam -- Doctrines\nApostasy -- Islam\nReligions",
		'cat_isbn'         => "9786299613503 (paperback)\n6299613505 (paperback)",
		'cat_bib'          => '300162873',
		'cat_edition'      => 'Cetakan kedua',
		'cat_imprint'      => 'Seri Kembangan, Selangor : Langgam Fikir, 2025',
		'book_title'       => 'Paulus Perosak Risalah Al-Masih',
		'book_title_en'    => 'Apostle of Doom',
		'book_subtitle'    => 'Sejarah Bagaimana Ajaran Kristian Dicipta Sepenuhnya',
		'book_subtitle_en' => 'How Paul of Tarsus Undid Jesus',
		'book_author'      => 'Mohd Elfie Nieshaem Juferi',
		'book_language'    => 'Malay (Bahasa Melayu)',
		'book_isbn'        => '978-629-96135-0-3',
		'book_oclc'        => '1531949453',
		'book_first_pub'   => 'August 2025',
		'book_reprint'     => 'Second printing, October 2025',
		'book_pages'       => 'xxvi + 195 pages',
		'book_format'      => 'Paperback',
		'book_price'       => 'RM 45.00',
		'book_buy_url'     => 'https://langgamfikir.my/publications/paulus-perosak-risalah-al-masih/',
		'image_license'     => 'https://creativecommons.org/publicdomain/mark/1.0/',
		'image_license_page' => '',
		'author_home'      => 'https://menj.blog',
		'author_url'       => 'https://bismikaallahuma.org',
		'author_youtube'   => 'https://www.youtube.com/@TheMuslimApologist',
		'social_links'     => '',
		'author_same_as'   => "https://ms.wikipedia.org/wiki/Mohd_Elfie_Nieshaem_Juferi\nhttps://my.linkedin.com/in/mohd-elfie-nieshaem-bin-juferi\nhttps://menj.blog/",
		'author_books'     => "Buddhism: A Muslim Primer (Jahabersa, 2005)\nWhen Apostates Become Shaykhs…: A Systematic Analysis of Their Dominant Ideas on the World Wide Web (Jahabersa, 2006)\nDua Dasawarsa: Sebuah Kumpulan Puisi 2005–2025 (Langgam Fikir, 2025)\nPolis Raja Di Malaysia: Power, Corruption & Control in Malaysia’s Police Force (Langgam Fikir, 2026)",
		'author_bio'       => 'Mohd Elfie Nieshaem Juferi is a Muslim apologist and an award-winning Malay-language author. He runs the apologetics site <a href="https://bismikaallahuma.org">Bismika Allahuma</a> and the YouTube channel <a href="https://www.youtube.com/@TheMuslimApologist">The Muslim Apologist</a>, and publishes his work through <a href="https://langgamfikir.my">Langgam Fikir</a>.',
		'book_blurb'       => 'Adakah benar ajaran-ajaran agama Kristian pada hari ini berasal daripada Nabi Isa a.s., atau sebenarnya hasil manipulasi teologi oleh seorang Yahudi bernama Paulus dari Tarsus?',
		// Publisher.
		'pub_name'         => 'Langgam Fikir',
		'pub_reg'          => '202503137729',
		'pub_address'      => "50-02A, Jalan Juara 1/4\n43300 Seri Kembangan\nSelangor, Malaysia",
		'pub_phone'        => '011-7346 8081',
		'pub_email'        => 'langgam.fikir@gmail.com',
		'pub_url'          => 'https://langgamfikir.my',
		// Appearance.
		'scheme'           => 'ochre',
		'ornament'         => 1,
		'login_image'      => 'paul-portrait-face',
		'login_logo_url'   => '',
		'login_tagline'    => 'Authors and editors only.',
		'login_greek'      => 1,
		'login_app_passwords' => 0,
		'security_headers' => 1,
		'maintenance_mode' => 0,
		'error_alerts'     => 1,
		'ctx_own'          => 1,
		'ctx_own_max'      => 4,
		'text_links'       => 1,
		'ctx_sister_max'   => 3,
		'text_links_map'   => '',
		'ctx_exclude'      => '',
		'journal_title'    => 'Journal',
		'journal_intro'    => 'Replies to missionary claims, notes on new sources and news of the book, dated as they are written.',
		'journal_meta'     => 'Dated replies to missionary claims, notes on new sources and news on the case against Paul of Tarsus. Read the latest entry.',
		'read_progress'    => 1,
		'read_progress_posts'   => 1,
		'read_progress_journal' => 1,
		'read_progress_pages'   => 0,
		'read_progress_position' => 'top',
		'read_progress_height'   => 3,
		'read_progress_fg'       => '',
		'read_progress_bg'       => '',
		'read_keys'        => 1,
		'read_memory'      => 1,
		'read_copy'        => 1,
		'read_top'         => 1,
		'media_no_sizes'   => 1,
		'media_no_scaled'  => 1,
		// Front page.
		'hero_kicker'      => 'The case against Paul of Tarsus',
		'hero_heading'     => 'Apostle of Doom',
		'hero_subheading'  => 'Paul of Tarsus and the corruption of the message of ʿĪsā ibn Maryam',
		'hero_lede'        => 'Saul of Tarsus hunted the followers of the Messiah, then claimed to speak for him. This website sets out the evidence, from his own letters and from fourteen centuries of Muslim scholarship.',
		'hero_image'       => 'paul-portrait',
	);
}

/**
 * Read one option, falling back to the default.
 *
 * @param string $key Option key.
 * @return mixed
 */
function paulus_option( $key ) {
	// Read dozens of times a page; the defaults (eighty translated strings)
	// are built once a request, not once a read.
	static $defaults = null;
	$opts = get_option( 'paulus_options', array() );
	if ( is_array( $opts ) && array_key_exists( $key, $opts ) ) {
		return $opts[ $key ];
	}
	if ( null === $defaults ) {
		$defaults = paulus_defaults();
	}
	return $defaults[ $key ] ?? '';
}

/**
 * Hero image URL. Uses the transparent cutout of an illustration when one
 * is bundled (assets/images/<slug>-cutout.avif), so the figure sits on the
 * page background in every color scheme instead of in a square of its own.
 *
 * @param string $slug Image slug.
 * @return string
 */
function paulus_hero_image_url( $slug ) {
	$slug = array_key_exists( $slug, paulus_images() ) ? $slug : 'paul-portrait';
	$file = sanitize_file_name( $slug ) . '-cutout.avif';
	if ( file_exists( PAULUS_DIR . '/assets/images/' . $file ) ) {
		return PAULUS_URI . '/assets/images/' . $file;
	}
	return paulus_image_url( $slug );
}

/**
 * srcset for a hero image: the 480 and 720 widths, when they exist beside the
 * full file: all AVIF (name-480.avif,
 * name-720.avif).
 *
 * @param string $url Full-size image URL.
 * @return string srcset attribute value, or empty.
 */
function paulus_hero_srcset( $url ) {
	$file = basename( wp_parse_url( $url, PHP_URL_PATH ) );
	if ( ! preg_match( '/^(.+)\.(avif|webp|jpg|png)$/', $file, $m ) ) {
		return '';
	}
	$set = array();
	foreach ( array( 480, 720 ) as $w ) {
		$name = $m[1] . '-' . $w . '.avif';
		if ( file_exists( PAULUS_DIR . '/assets/images/' . $name ) ) {
			$set[] = PAULUS_URI . '/assets/images/' . $name . ' ' . $w . 'w';
		}
	}
	if ( ! $set ) {
		return '';
	}
	$full  = paulus_image_size( PAULUS_DIR . '/assets/images/' . $file );
	$set[] = $url . ' ' . ( $full ? (int) $full[0] : 962 ) . 'w';
	return implode( ', ', $set );
}

/**
 * The image registry in content/images.json: "labels" (slug => title),
 * "alts" (slug => alt text) and "figures" (slug => alt, author, licence,
 * licence URL and source). Data only, read with json_decode(); a missing or
 * malformed file gives empty lists.
 *
 * @param string $part labels, alts or figures.
 * @return array
 */
function paulus_images_data( $part ) {
	static $data = null;
	$read = $data;
	if ( null === $read ) {
		$file = PAULUS_DIR . '/content/images.json';
		$read = is_readable( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : null; // phpcs:ignore WordPress.WP.AlternativeFunctions
		$read = is_array( $read ) ? $read : array();
		if ( $read ) {
			$data = $read; // A failed read is not cached, so the next figure tries again.
		}
	}
	return isset( $read[ $part ] ) && is_array( $read[ $part ] ) ? $read[ $part ] : array();
}

/**
 * Bundled images: slug => label.
 *
 * @return array<string,string>
 */
function paulus_images() {
	return paulus_images_data( 'labels' );
}

/**
 * Alt text for each bundled image, describing what is drawn.
 *
 * @return array<string,string>
 */
function paulus_image_alts() {
	return paulus_images_data( 'alts' );
}

/**
 * URL of a bundled image.
 *
 * @param string $slug Image slug.
 * @return string
 */
function paulus_image_url( $slug ) {
	if ( 'book-cover' !== $slug && ! array_key_exists( $slug, paulus_images() ) ) {
		$slug = 'paul-portrait';
	}
	return PAULUS_URI . '/assets/images/' . sanitize_file_name( $slug ) . '.avif';
}

/**
 * A JPEG copy of an illustration for link previews (Open Graph, X), where
 * AVIF is not shown: assets/social/<slug>.jpg.
 *
 * @param string $slug Image slug.
 * @return string URL, or empty when there is no copy.
 */
function paulus_social_image_url( $slug ) {
	$slug = sanitize_file_name( (string) $slug );
	return file_exists( PAULUS_DIR . '/assets/social/' . $slug . '.jpg' ) ? PAULUS_URI . '/assets/social/' . $slug . '.jpg' : '';
}

/**
 * The social copy for an image address of the theme's: a bundled
 * illustration, or a featured image the theme imported into the media
 * library (named <slug>.avif, <slug>-<hash>.avif, or a sub-size of either).
 *
 * @param string $url Image address.
 * @return string Social copy's URL, or empty.
 */
function paulus_social_for_url( $url ) {
	$base = basename( (string) wp_parse_url( (string) $url, PHP_URL_PATH ) );
	if ( ! preg_match( '/^(.+?)(-[0-9a-f]{8})?(-\d+x\d+)?\.(avif|jpe?g|webp|png)$/i', $base, $m ) ) {
		return '';
	}
	return array_key_exists( $m[1], paulus_images() ) || 'book-cover' === $m[1] ? paulus_social_image_url( $m[1] ) : '';
}

/**
 * An image's width and height, from assets/images/sizes.json (written when
 * the images are made), so no page depends on the server's PHP reading AVIF
 * (getimagesize() reads it only from PHP 8.2). Falls back to getimagesize().
 *
 * @param string $path Absolute path under assets/images.
 * @return array|false Width and height, or false.
 */
function paulus_image_size( $path ) {
	static $sizes = null;
	if ( null === $sizes ) {
		$json  = PAULUS_DIR . '/assets/images/sizes.json';
		$sizes = file_exists( $json ) ? (array) json_decode( (string) file_get_contents( $json ), true ) : array(); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
	$key = ltrim( str_replace( PAULUS_DIR . '/assets/images/', '', (string) $path ), '/' );
	if ( isset( $sizes[ $key ] ) ) {
		return array( (int) $sizes[ $key ][0], (int) $sizes[ $key ][1] );
	}
	return file_exists( $path ) ? @getimagesize( $path ) : false; // phpcs:ignore WordPress.PHP.NoSilencedErrors
}
