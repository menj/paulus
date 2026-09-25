<?php
/**
 * Theme Options: field definitions, sanitization, and defaults resolution.
 *
 * Split out of inc/options.php so field data/logic and screen markup can
 * evolve independently.
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

/**
 * Field map: tab => field => [label, type].
 *
 * @return array
 */
function paulus_fields() {
	return array(
		'book'       => array(
			'label'  => __( 'Book', 'paulus' ),
			'fields' => array(
				'book_title'       => array( __( 'Title (Malay)', 'paulus' ), 'text' ),
				'book_title_en'    => array( __( 'Title (English rendering)', 'paulus' ), 'text' ),
				'book_subtitle'    => array( __( 'Subtitle (Malay)', 'paulus' ), 'text' ),
				'book_subtitle_en' => array( __( 'Subtitle (English rendering)', 'paulus' ), 'text' ),
				'book_author'      => array( __( 'Author', 'paulus' ), 'text' ),
				'author_home'      => array( __( 'Author website (the name in the author card links here)', 'paulus' ), 'url' ),
				'author_url'       => array( __( 'Author apologetics site (for structured data)', 'paulus' ), 'text' ),
				'author_bio'       => array( __( 'About the author (shown above the footer on every page; links allowed)', 'paulus' ), 'bio' ),
				'author_youtube'   => array( __( 'Author YouTube channel (optional link in the author card)', 'paulus' ), 'url' ),
				'social_links'     => array( __( 'Social profiles, one URL per line (the platform is detected from the address)', 'paulus' ), 'textarea' ),
				'author_books'     => array( __( 'Other books by the author, one per line: Title (Publisher, Year)', 'paulus' ), 'textarea' ),
				'book_language'    => array( __( 'Language', 'paulus' ), 'text' ),
				'book_isbn'        => array( __( 'ISBN', 'paulus' ), 'text' ),
				'book_oclc'        => array( __( 'OCLC number', 'paulus' ), 'text' ),
				'book_first_pub'   => array( __( 'First published', 'paulus' ), 'text' ),
				'cat_edition'      => array( __( 'Edition, as catalogued (footer colophon)', 'paulus' ), 'text' ),
				'cat_imprint'      => array( __( 'Imprint: place : publisher, year (footer colophon)', 'paulus' ), 'text' ),
				'book_reprint'     => array( __( 'Current printing', 'paulus' ), 'text' ),
				'book_pages'       => array( __( 'Extent (pages)', 'paulus' ), 'text' ),
				'book_format'      => array( __( 'Format', 'paulus' ), 'text' ),
				'book_price'       => array( __( 'Price', 'paulus' ), 'text' ),
				'book_buy_url'     => array( __( 'Order link', 'paulus' ), 'url' ),
				'book_blurb'       => array( __( 'Synopsis (Malay)', 'paulus' ), 'textarea' ),
				'image_license'    => array( __( 'Image licence URL (article illustrations; blank to omit)', 'paulus' ), 'url' ),
				'image_license_page' => array( __( 'Where to acquire an image licence (optional; leave blank for public-domain images)', 'paulus' ), 'url' ),
			),
		),
		'catalogue'  => array(
			'label'  => __( 'Catalogue record', 'paulus' ),
			'fields' => array(
				'cat_title'    => array( __( 'Title and statement of responsibility', 'paulus' ), 'text' ),
				'cat_extent'   => array( __( 'Physical description', 'paulus' ), 'text' ),
				'cat_language' => array( __( 'Language', 'paulus' ), 'text' ),
				'cat_note'     => array( __( 'Notes, one per line', 'paulus' ), 'textarea' ),
				'cat_subjects' => array( __( 'Subject headings, one per line', 'paulus' ), 'textarea' ),
				'cat_isbn'     => array( __( 'ISBNs as catalogued, one per line', 'paulus' ), 'textarea' ),
				'cat_bib'      => array( __( 'Library record number (Bib ID)', 'paulus' ), 'text' ),
			),
		),
		'publisher'  => array(
			'label'  => __( 'Publisher', 'paulus' ),
			'fields' => array(
				'pub_name'    => array( __( 'Name', 'paulus' ), 'text' ),
				'pub_reg'     => array( __( 'Registration number', 'paulus' ), 'text' ),
				'pub_address' => array( __( 'Address (structured data only; not shown on the page)', 'paulus' ), 'textarea' ),
				'pub_phone'   => array( __( 'Telephone (structured data only; not shown on the page)', 'paulus' ), 'text' ),
				'pub_email'   => array( __( 'Email', 'paulus' ), 'email' ),
				'pub_url'     => array( __( 'Website', 'paulus' ), 'url' ),
			),
		),
		'appearance' => array(
			'label'  => __( 'Colors', 'paulus' ),
			'fields' => array(
				'scheme'   => array( __( 'Color scheme', 'paulus' ), 'scheme' ),
				'ornament' => array( __( 'First-century ornament (textures, meander borders, rosettes, coin numerals)', 'paulus' ), 'checkbox' ),
			),
		),
		'reading'    => array(
			'label'  => __( 'Reading', 'paulus' ),
			'fields' => array(
				'read_progress' => array( __( 'Reading progress bar across the top of articles', 'paulus' ), 'checkbox' ),
				'read_keys'     => array( __( 'Arrow keys move to the previous and next article in the reading order', 'paulus' ), 'checkbox' ),
				'read_memory'   => array( __( 'Offer to resume a long article or page where the reader left off (kept in the reader\'s browser only)', 'paulus' ), 'checkbox' ),
				'read_copy'     => array( __( 'Copy-link button in the share row', 'paulus' ), 'checkbox' ),
				'read_top'      => array( __( 'Back-to-top button on long pages', 'paulus' ), 'checkbox' ),
			),
		),
		'login'      => array(
			'label'  => __( 'Login', 'paulus' ),
			'fields' => array(
				'login_image'    => array( __( 'Portrait in the medallion above the login form', 'paulus' ), 'image' ),
				'login_logo_url' => array( __( 'Logo image address, in place of the medallion (leave empty for the medallion; a file named login-logo.png in wp-content is used when this is empty)', 'paulus' ), 'url' ),
				'login_tagline'  => array( __( 'Line under the site name', 'paulus' ), 'text' ),
				'login_greek'    => array( __( 'Greek inscription above the form: ΕΙΣΕΛΘΑΤΕ, "enter", Matthew 7:13', 'paulus' ), 'checkbox' ),
			),
		),
		'journal'    => array(
			'label'  => __( 'Journal', 'paulus' ),
			'fields' => array(
				'journal_title' => array( __( 'Title of the Journal', 'paulus' ), 'text' ),
				'journal_intro' => array( __( 'Introduction, under the title on the Journal page', 'paulus' ), 'textarea' ),
				'journal_meta'  => array( __( 'Search description of the Journal page (120 to 130 characters)', 'paulus' ), 'textarea' ),
			),
		),
		'front'      => array(
			'label'  => __( 'Front page', 'paulus' ),
			'fields' => array(
				'site_seo_title'  => array( __( 'Front-page title, shown before "| Apostle of Doom" (up to 32 characters)', 'paulus' ), 'text' ),
				'site_meta'       => array( __( 'Front-page meta description (120 to 130 characters, ending on a quiet invitation to read on)', 'paulus' ), 'textarea' ),
				'footer_blurb'    => array( __( 'Footer description (under the wordmark; leave empty to use the tagline from Settings, General)', 'paulus' ), 'textarea' ),
				'footer_badges'   => array( __( 'Footer badges (HTML: links and images only)', 'paulus' ), 'html' ),
				'hero_kicker'     => array( __( 'Kicker (small line above the heading)', 'paulus' ), 'text' ),
				'hero_heading'    => array( __( 'Heading', 'paulus' ), 'text' ),
				'hero_subheading' => array( __( 'Subheading', 'paulus' ), 'text' ),
				'hero_lede'       => array( __( 'Introduction', 'paulus' ), 'textarea' ),
				'hero_image'      => array( __( 'Hero image', 'paulus' ), 'image' ),
			),
		),
	);
}

/**
 * Menu entry.
 */
function paulus_options_menu() {
	add_theme_page(
		__( 'Theme Options', 'paulus' ),
		__( 'Theme Options', 'paulus' ),
		'edit_theme_options',
		'paulus-options',
		'paulus_options_page'
	);
}
add_action( 'admin_menu', 'paulus_options_menu' );

/**
 * Register setting.
 */
function paulus_register_setting() {
	register_setting(
		'paulus_options_group',
		'paulus_options',
		array( 'sanitize_callback' => 'paulus_sanitize_options' )
	);
}
add_action( 'admin_init', 'paulus_register_setting' );

/**
 * Sanitise every field by type.
 *
 * @param array $input Raw input.
 * @return array
 */
function paulus_sanitize_options( $input ) {
	$input  = is_array( $input ) ? $input : array();
	$clean  = array();
	$stored = get_option( 'paulus_options', array() );
	$stored = is_array( $stored ) ? $stored : array();
	foreach ( paulus_fields() as $tab ) {
		foreach ( $tab['fields'] as $key => $def ) {
			// This runs on every write to the option, including the theme's
			// own upgrades and structure sync, which pass only the keys they
			// store. A field absent from the input keeps its stored value, or
			// its default; it is never blanked. (Before 2.33.5 it was blanked,
			// which emptied the footer description and badges.) The form
			// always submits every field, checkboxes through a hidden input.
			if ( ! array_key_exists( $key, $input ) ) {
				if ( array_key_exists( $key, $stored ) ) {
					$clean[ $key ] = $stored[ $key ];
				}
				continue;
			}
			$value = $input[ $key ];
			switch ( $def[1] ) {
				case 'textarea':
					$clean[ $key ] = sanitize_textarea_field( $value );
					break;
				case 'html':
					$clean[ $key ] = wp_kses( $value, paulus_badge_kses() );
					break;
				case 'bio':
					$clean[ $key ] = wp_kses( $value, paulus_bio_kses() );
					break;
				case 'url':
					$clean[ $key ] = esc_url_raw( $value );
					break;
				case 'email':
					$clean[ $key ] = sanitize_email( $value );
					break;
				case 'checkbox':
					$clean[ $key ] = empty( $value ) ? 0 : 1;
					break;
				case 'scheme':
					$clean[ $key ] = array_key_exists( $value, paulus_schemes() ) ? $value : 'ochre';
					break;
				case 'image':
					$clean[ $key ] = array_key_exists( $value, paulus_images() ) ? $value : 'paul-portrait';
					break;
				default:
					$clean[ $key ] = sanitize_text_field( $value );
			}
		}
	}
	return $clean;
}

/**
 * Allowed markup for footer badges: links and images, nothing else.
 *
 * @return array
 */
/**
 * Allowed markup in the author bio: links and light emphasis, nothing else.
 *
 * @return array
 */
function paulus_bio_kses() {
	return array(
		'a'      => array( 'href' => true, 'title' => true, 'rel' => true, 'target' => true ),
		'em'     => array(),
		'strong' => array(),
		'cite'   => array(),
	);
}

function paulus_badge_kses() {
	return array(
		'a'   => array( 'href' => true, 'title' => true, 'rel' => true, 'target' => true ),
		'img' => array( 'src' => true, 'alt' => true, 'width' => true, 'height' => true, 'loading' => true ),
	);
}
