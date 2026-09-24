<?php
/**
 * Paulus: Twenty Twenty-Five child theme.
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

// The theme version is kept in one place, the style.css header.
define( 'PAULUS_VERSION', (string) wp_get_theme( basename( __DIR__ ) )->get( 'Version' ) );
define( 'PAULUS_DIR', get_stylesheet_directory() );
define( 'PAULUS_URI', get_stylesheet_directory_uri() );

/**
 * Warn if the parent is missing or older than the version Paulus is built against.
 */
function paulus_parent_check() {
	$parent = wp_get_theme( 'twentytwentyfive' );
	if ( ! $parent->exists() || version_compare( $parent->get( 'Version' ), '1.5', '<' ) ) {
		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			esc_html__( 'Paulus requires the Twenty Twenty-Five parent theme, version 1.5 or later. Install or update it under Appearance, Themes.', 'paulus' )
		);
	}
}
add_action( 'admin_notices', 'paulus_parent_check' );

/*
 * Multibyte helpers. WordPress core polyfills mb_strlen() and mb_substr()
 * when the mbstring extension is missing, but not the four below, which
 * the search snippets and glossary index use; without mbstring those
 * calls were fatal errors. Each uses mbstring when present and otherwise
 * falls back to UTF-8-aware PCRE, returning character (not byte) offsets.
 */

/**
 * Character length of a UTF-8 string, mbstring or not.
 *
 * @param string $s String.
 * @return int
 */
function paulus_ulen( $s ) {
	return function_exists( 'mb_strlen' ) ? mb_strlen( $s ) : (int) preg_match_all( '/./us', (string) $s );
}

/**
 * Character offset of the first match of a PCRE pattern at or after a
 * character offset, or false.
 *
 * @param string $hay     Haystack.
 * @param string $pattern PCRE pattern (with the u flag).
 * @param int    $offset  Character offset to start from.
 * @return int|false
 */
function paulus_upos_pattern( $hay, $pattern, $offset = 0 ) {
	$hay   = (string) $hay;
	$bytes = $offset > 0 ? strlen( (string) paulus_usubstr( $hay, 0, $offset ) ) : 0;
	if ( ! preg_match( $pattern, $hay, $m, PREG_OFFSET_CAPTURE, $bytes ) ) {
		return false;
	}
	return paulus_ulen( substr( $hay, 0, $m[0][1] ) );
}

/**
 * UTF-8 substring by characters.
 *
 * @param string   $s      String.
 * @param int      $start  Start character.
 * @param int|null $length Length in characters.
 * @return string
 */
function paulus_usubstr( $s, $start, $length = null ) {
	if ( function_exists( 'mb_substr' ) ) {
		return mb_substr( $s, $start, $length );
	}
	preg_match_all( '/./us', (string) $s, $m );
	return implode( '', array_slice( $m[0], $start, $length ) );
}

/**
 * mb_stripos() with a fallback.
 *
 * @param string $hay    Haystack.
 * @param string $needle Needle.
 * @param int    $offset Character offset.
 * @return int|false
 */
function paulus_ustripos( $hay, $needle, $offset = 0 ) {
	if ( function_exists( 'mb_stripos' ) ) {
		return mb_stripos( $hay, $needle, $offset );
	}
	return '' === (string) $needle ? false : paulus_upos_pattern( $hay, '/' . preg_quote( $needle, '/' ) . '/iu', $offset );
}

/**
 * mb_strpos() with a fallback.
 *
 * @param string $hay    Haystack.
 * @param string $needle Needle.
 * @param int    $offset Character offset.
 * @return int|false
 */
function paulus_ustrpos( $hay, $needle, $offset = 0 ) {
	if ( function_exists( 'mb_strpos' ) ) {
		return mb_strpos( $hay, $needle, $offset );
	}
	return '' === (string) $needle ? false : paulus_upos_pattern( $hay, '/' . preg_quote( $needle, '/' ) . '/u', $offset );
}

/**
 * mb_strrpos() with a fallback.
 *
 * @param string $hay    Haystack.
 * @param string $needle Needle.
 * @return int|false
 */
function paulus_ustrrpos( $hay, $needle ) {
	if ( function_exists( 'mb_strrpos' ) ) {
		return mb_strrpos( $hay, $needle );
	}
	$pos = strrpos( (string) $hay, (string) $needle );
	return false === $pos ? false : paulus_ulen( substr( (string) $hay, 0, $pos ) );
}

/**
 * mb_strtoupper() with a fallback. Without mbstring, only ASCII letters
 * change case; the glossary passes text through remove_accents() first,
 * so Latin initials still come out right.
 *
 * @param string $s String.
 * @return string
 */
function paulus_ustrtoupper( $s ) {
	return function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $s ) : strtoupper( (string) $s );
}

require_once PAULUS_DIR . '/inc/defaults.php';

/**
 * One-time upgrade: sites that saved Theme Options before 1.2.3 stored an
 * empty order link. Fill it with the publisher's page for the book.
 */
function paulus_upgrade() {
	if ( version_compare( (string) get_option( 'paulus_db_version', '0' ), '1.2.3', '>=' ) ) {
		return;
	}
	$opts = get_option( 'paulus_options' );
	if ( is_array( $opts ) && empty( $opts['book_buy_url'] ) ) {
		$opts['book_buy_url'] = paulus_defaults()['book_buy_url'];
		update_option( 'paulus_options', $opts );
	}
	update_option( 'paulus_db_version', '1.2.3', false );
}

/**
 * One-time upgrade to 1.3.1: the front-page heading becomes the site name,
 * and the former heading moves to the new subheading. Only applied where the
 * heading still holds the old default.
 */
function paulus_upgrade_131() {
	if ( version_compare( (string) get_option( 'paulus_db_version', '0' ), '1.3.1', '>=' ) ) {
		return;
	}
	$opts = get_option( 'paulus_options' );
	$old  = 'Paul of Tarsus and the corruption of the message of ʿĪsā';
	if ( is_array( $opts ) && ( $opts['hero_heading'] ?? '' ) === $old ) {
		$defaults                = paulus_defaults();
		$opts['hero_heading']    = $defaults['hero_heading'];
		$opts['hero_subheading'] = $old;
		update_option( 'paulus_options', $opts );
	}
	update_option( 'paulus_db_version', '1.3.1', false );
}

/**
 * One-time upgrade to 1.5.0: the book's synopsis and contents move to Malay.
 * Options still at their old defaults take the new ones, and the book page is
 * replaced only if its text is exactly what an earlier installer wrote.
 */
function paulus_upgrade_150() {
	if ( version_compare( (string) get_option( 'paulus_db_version', '0' ), '1.5.0', '>=' ) ) {
		return;
	}
	$defaults = paulus_defaults();
	$opts     = get_option( 'paulus_options' );
	if ( is_array( $opts ) ) {
		$old = array(
			'book_title' => 'Paulus: Perosak Risalah al-Masih',
			'book_blurb' => 'Christianity as it is practiced today rests on the letters of one man who never met ʿĪsā. This book traces how Saul of Tarsus, persecutor of the first community of believers, recast the message of the Messiah into a new religion, and how Muslim scholars recognized the corruption a thousand years before Western criticism named it.',
		);
		foreach ( $old as $key => $value ) {
			if ( ( $opts[ $key ] ?? '' ) === $value ) {
				$opts[ $key ] = $defaults[ $key ];
			}
		}
		foreach ( array( 'book_pages', 'book_format', 'book_price' ) as $key ) {
			if ( empty( $opts[ $key ] ) ) {
				$opts[ $key ] = $defaults[ $key ];
			}
		}
		update_option( 'paulus_options', $opts );
	}
	$page = get_page_by_path( 'the-book' );
	if ( $page && '0898d6473163a9dc911372ecfd2cb5f2' === md5( $page->post_content ) ) {
		$new = paulus_content_file( 'the-book.html' );
		if ( '' !== $new ) {
			wp_update_post( array( 'ID' => $page->ID, 'post_content' => $new ) );
		}
	}
	update_option( 'paulus_db_version', '1.5.0', false );
}

/**
 * One-time upgrade to 1.6.0: illustrations are cut back to four, and the
 * articles that no longer carry one lose the image the theme gave them.
 */
function paulus_upgrade_160() {
	if ( version_compare( (string) get_option( 'paulus_db_version', '0' ), '1.6.0', '>=' ) ) {
		return;
	}
	$opts = get_option( 'paulus_options' );
	if ( is_array( $opts ) ) {
		unset( $opts['show_gallery'], $opts['gallery_heading'] );
		if ( ! array_key_exists( $opts['hero_image'] ?? '', paulus_images() ) ) {
			$opts['hero_image'] = 'paul-portrait';
		}
		update_option( 'paulus_options', $opts );
	}
	paulus_sync_images();
	update_option( 'paulus_db_version', '1.6.0', false );
}

/**
 * One-time upgrade to 1.8.0: the header menu drops The Verdict and Reference,
 * which move to the footer.
 */
function paulus_upgrade_180() {
	if ( version_compare( (string) get_option( 'paulus_db_version', '0' ), '1.8.0', '>=' ) ) {
		return;
	}
	if ( get_option( 'paulus_nav_id' ) ) {
		paulus_install_navigation( paulus_install_parts() );
	}
	update_option( 'paulus_db_version', '1.8.0', false );
}

/**
 * One-time upgrade to 1.9.0: rebuild the header menu so "The book" carries
 * the call-to-action class.
 */
function paulus_upgrade_190() {
	if ( version_compare( (string) get_option( 'paulus_db_version', '0' ), '1.9.0', '>=' ) ) {
		return;
	}
	if ( get_option( 'paulus_nav_id' ) ) {
		paulus_install_navigation( paulus_install_parts() );
	}
	update_option( 'paulus_db_version', '1.9.0', false );
}

/**
 * One-time upgrade to 2.0.0: the chapter that opened with ʿĪsā gets its new first
 * words, so the drop cap falls on a letter, if its text was never edited.
 */
function paulus_upgrade_200() {
	if ( version_compare( (string) get_option( 'paulus_db_version', '0' ), '2.0.0', '>=' ) ) {
		return;
	}
	$post = get_page_by_path( 'the-foundations-of-pauline-doctrine', OBJECT, 'post' );
	if ( $post && '0a549bd2d8351ddaff1a8c2dbfb02ab9' === md5( $post->post_content ) ) {
		$new = paulus_content_file( '04-foundations-of-pauline-doctrine.html' );
		if ( '' !== $new ) {
			wp_update_post( array( 'ID' => $post->ID, 'post_content' => $new ) );
		}
	}
	$post = get_page_by_path( 'an-epistle-to-the-churches-of-paul', OBJECT, 'post' );
	if ( $post && 'c9709b5a7b978e4ff6946cb112389324' === md5( $post->post_content ) ) {
		$new = paulus_content_file( '11-epistle.html' );
		if ( '' !== $new ) {
			wp_update_post( array( 'ID' => $post->ID, 'post_content' => $new ) );
		}
	}
	update_option( 'paulus_db_version', '2.0.0', false );
}

/**
 * One-time upgrade to 2.2.0: The witnesses gains a third article. Sites with
 * the content installed get it, with the new section description if the old
 * one was never edited.
 */
function paulus_upgrade_220() {
	if ( version_compare( (string) get_option( 'paulus_db_version', '0' ), '2.2.0', '>=' ) ) {
		return;
	}
	if ( paulus_is_installed() ) {
		$term = get_term_by( 'slug', 'the-witnesses', 'category' );
		if ( $term && 'What Muslim scholars have said about Paul since the ninth century, and a letter from one who once followed him.' === $term->description ) {
			foreach ( paulus_manifest()['parts'] as $part ) {
				if ( 'the-witnesses' === $part['slug'] ) {
					wp_update_term( $term->term_id, 'category', array( 'description' => $part['description'] ) );
				}
			}
		}
		paulus_run_install();
	}
	update_option( 'paulus_db_version', '2.2.0', false );
}

/**
 * One-time upgrade to 2.2.1: 2.0.0 moved sites from Ochre to Vellum. That
 * move is undone: a site on Vellum returns to Ochre.
 */
function paulus_upgrade_221() {
	if ( version_compare( (string) get_option( 'paulus_db_version', '0' ), '2.2.1', '>=' ) ) {
		return;
	}
	$opts = get_option( 'paulus_options' );
	if ( is_array( $opts ) && 'vellum' === ( $opts['scheme'] ?? '' ) ) {
		$opts['scheme'] = 'ochre';
		update_option( 'paulus_options', $opts );
	}
	update_option( 'paulus_db_version', '2.2.1', false );
}

/**
 * One-time upgrade to 2.4.0: count labels become Roman numerals.
 */
function paulus_upgrade_240() {
	if ( version_compare( (string) get_option( 'paulus_db_version', '0' ), '2.4.0', '>=' ) ) {
		return;
	}
	if ( paulus_is_installed() ) {
		paulus_run_install();
	}
	update_option( 'paulus_db_version', '2.4.0', false );
}

/**
 * One-time upgrade to 2.4.1: set the site icon where none exists.
 */
function paulus_upgrade_241() {
	if ( version_compare( (string) get_option( 'paulus_db_version', '0' ), '2.4.1', '>=' ) ) {
		return;
	}
	paulus_install_site_icon();
	update_option( 'paulus_db_version', '2.4.1', false );
}

/**
 * One-time upgrade to 2.9.2: the hero subheading and the site tagline name
 * ʿĪsā ibn Maryam in full, where they still hold the earlier default.
 */
function paulus_upgrade_292() {
	if ( version_compare( (string) get_option( 'paulus_db_version', '0' ), '2.9.2', '>=' ) ) {
		return;
	}
	$old  = 'Paul of Tarsus and the corruption of the message of ʿĪsā';
	$new  = $old . ' ibn Maryam';
	$opts = get_option( 'paulus_options' );
	if ( is_array( $opts ) && $old === ( $opts['hero_subheading'] ?? null ) ) {
		$opts['hero_subheading'] = $new;
		update_option( 'paulus_options', $opts );
	}
	if ( $old === get_option( 'blogdescription' ) ) {
		update_option( 'blogdescription', $new );
	}
	update_option( 'paulus_db_version', '2.9.2', false );
}

/**
 * One-time upgrade to 2.11.1: the front-page lede reads "This website sets
 * out the evidence" where it still holds the earlier wording.
 */
function paulus_upgrade_2111() {
	if ( version_compare( (string) get_option( 'paulus_db_version', '0' ), '2.11.1', '>=' ) ) {
		return;
	}
	$opts = get_option( 'paulus_options' );
	if ( is_array( $opts ) && isset( $opts['hero_lede'] ) && false !== strpos( $opts['hero_lede'], 'The articles here set out the evidence' ) ) {
		$opts['hero_lede'] = str_replace( 'The articles here set out the evidence', 'This website sets out the evidence', $opts['hero_lede'] );
		update_option( 'paulus_options', $opts );
	}
	update_option( 'paulus_db_version', '2.11.1', false );
}

/**
 * One-time upgrade to 2.19.1: the English title of the book is
 * "Apostle of Doom: How Paul of Tarsus Undid Jesus" where the options
 * still hold the earlier rendering.
 */
function paulus_upgrade_2191() {
	if ( version_compare( (string) get_option( 'paulus_db_version', '0' ), '2.19.1', '>=' ) ) {
		return;
	}
	$opts = get_option( 'paulus_options' );
	if ( is_array( $opts ) ) {
		if ( 'Paul: Corrupter of the Message of the Messiah' === ( $opts['book_title_en'] ?? null ) ) {
			$opts['book_title_en'] = 'Apostle of Doom';
		}
		if ( 'A history of how Christian teaching was invented in full' === ( $opts['book_subtitle_en'] ?? null ) ) {
			$opts['book_subtitle_en'] = 'How Paul of Tarsus Undid Jesus';
		}
		update_option( 'paulus_options', $opts );
	}
	update_option( 'paulus_db_version', '2.19.1', false );
}
/**
 * One-time repair to 2.33.5: the structure sync of 2.30.0 to 2.33.4 saved the
 * options record through a sanitiser that blanked every field it was not
 * given, emptying fields a site had never saved itself. Restore the footer
 * description, the footer badges, the front-page search title and the hero
 * kicker, where they are still empty. Runs again under 2.33.6, so a site that
 * already took the 2.33.5 repair also gets the kicker back.
 */
function paulus_upgrade_2335() {
	if ( version_compare( (string) get_option( 'paulus_db_version', '0' ), '2.33.6', '>=' ) ) {
		return;
	}
	$opts = get_option( 'paulus_options' );
	if ( is_array( $opts ) ) {
		$defaults = paulus_defaults();
		foreach ( array( 'footer_blurb', 'footer_badges', 'site_seo_title', 'hero_kicker' ) as $key ) {
			if ( '' === trim( (string) ( $opts[ $key ] ?? '' ) ) ) {
				$opts[ $key ] = $defaults[ $key ];
			}
		}
		update_option( 'paulus_options', $opts );
	}
	update_option( 'paulus_db_version', '2.33.6', false );
}

/**
 * Run every one-time upgrade, in the order they were introduced.
 *
 * Gated once here, rather than on each individual upgrade function: an
 * unprivileged visitor loading any wp-admin screen (admin_init fires for
 * every logged-in user, not just administrators) can never trigger a
 * content or option write through these, since none of them otherwise
 * checked who was asking.
 */
function paulus_run_upgrades() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	paulus_upgrade();
	paulus_upgrade_131();
	paulus_upgrade_150();
	paulus_upgrade_160();
	paulus_upgrade_180();
	paulus_upgrade_190();
	paulus_upgrade_200();
	paulus_upgrade_220();
	paulus_upgrade_221();
	paulus_upgrade_240();
	paulus_upgrade_241();
	paulus_upgrade_292();
	paulus_upgrade_2111();
	paulus_upgrade_2191();
	paulus_upgrade_2335();
}
add_action( 'admin_init', 'paulus_run_upgrades' );
require_once PAULUS_DIR . '/inc/options-fields.php';
require_once PAULUS_DIR . '/inc/options-render.php';
require_once PAULUS_DIR . '/inc/shortcodes.php';
require_once PAULUS_DIR . '/inc/structure.php';
require_once PAULUS_DIR . '/inc/importer.php';
require_once PAULUS_DIR . '/inc/seo.php';
require_once PAULUS_DIR . '/inc/figures.php';
require_once PAULUS_DIR . '/inc/charts.php';
require_once PAULUS_DIR . '/inc/dashboard.php';
require_once PAULUS_DIR . '/inc/journal.php';
require_once PAULUS_DIR . '/inc/schema-deep.php';
require_once PAULUS_DIR . '/inc/search.php';

/**
 * Preload the three fonts every page paints with above the fold, so the
 * browser fetches them alongside the stylesheets instead of after them.
 */
function paulus_preload_fonts() {
	foreach ( array( 'sabon-next-lt-regular', 'dubidam-bold', 'cinzel' ) as $font ) {
		printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( PAULUS_URI . '/assets/fonts/' . $font . '.woff2' ) );
	}
}
add_action( 'wp_head', 'paulus_preload_fonts', 1 );

/**
 * Keep the installed structure in step with the manifest.
 *
 * Runs whenever an administrator loads a page, admin or front end, and the
 * manifest's content_version differs from the one last applied. It adds new
 * articles and sections, updates labels, descriptions and the menu, and
 * never touches text that already exists. This replaces reliance on the
 * one-time version upgrades for structural changes.
 */
function paulus_sync_structure() {
	if ( ! current_user_can( 'edit_theme_options' ) || wp_doing_ajax() || wp_doing_cron() ) {
		return;
	}
	$target = (string) ( paulus_manifest()['content_version'] ?? '' );
	if ( '' === $target || (string) get_option( 'paulus_content_version' ) === $target ) {
		return;
	}
	if ( ! paulus_is_installed() ) {
		return;
	}
	// A simple mutex: this can run on both admin_init and template_redirect,
	// so two requests landing close together must not both attempt the sync.
	if ( get_transient( 'paulus_sync_lock' ) ) {
		return;
	}
	set_transient( 'paulus_sync_lock', 1, MINUTE_IN_SECONDS );
	$result = paulus_run_install();
	delete_transient( 'paulus_sync_lock' );
	// Only a fully successful pass advances the recorded version: a
	// partial failure (a wp_insert_post error, say) is retried on the
	// next page load rather than being marked done.
	if ( false !== $result ) {
		update_option( 'paulus_content_version', $target, false );
	}
}
add_action( 'admin_init', 'paulus_sync_structure', 30 );
add_action( 'template_redirect', 'paulus_sync_structure' );

/**
 * Front-end assets.
 */
function paulus_enqueue_assets() {
	wp_enqueue_style( 'paulus-fonts', PAULUS_URI . '/assets/css/fonts.css', array(), PAULUS_VERSION );
	// Load after the parent stylesheet (handle registered by Twenty Twenty-Five).
	wp_enqueue_style( 'paulus-schemes', PAULUS_URI . '/assets/css/schemes.css', array( 'twentytwentyfive-style', 'paulus-fonts' ), PAULUS_VERSION );
	wp_enqueue_style( 'paulus-theme', PAULUS_URI . '/assets/css/theme.css', array( 'paulus-schemes' ), PAULUS_VERSION );
	if ( paulus_option( 'ornament' ) ) {
		wp_enqueue_style( 'paulus-ornament', PAULUS_URI . '/assets/css/ornament.css', array( 'paulus-theme' ), PAULUS_VERSION );
	}
	wp_enqueue_style( 'paulus-print', PAULUS_URI . '/assets/css/print.css', array( 'paulus-theme' ), PAULUS_VERSION, 'print' );
	$paulus_content = is_singular() ? (string) get_post_field( 'post_content', get_queried_object_id() ) : '';
	if ( $paulus_content && ( has_shortcode( $paulus_content, 'paulus_figure' ) || preg_match( '#<a[^>]+href="[^"]+\.(?:jpe?g|png|webp|gif)"[^>]*>\s*<img#i', $paulus_content ) ) ) {
		wp_enqueue_style( 'lightbox2', PAULUS_URI . '/assets/vendor/lightbox2/css/lightbox.min.css', array(), '2.12.0' );
		wp_enqueue_style( 'paulus-lightbox', PAULUS_URI . '/assets/css/lightbox.css', array( 'lightbox2' ), PAULUS_VERSION );
		wp_enqueue_script( 'lightbox2', PAULUS_URI . '/assets/vendor/lightbox2/js/lightbox.min.js', array( 'jquery' ), '2.12.0', array( 'strategy' => 'defer', 'in_footer' => true ) );
		wp_enqueue_script( 'paulus-lightbox', PAULUS_URI . '/assets/js/lightbox-init.js', array( 'lightbox2' ), PAULUS_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
		wp_add_inline_script( 'lightbox2', "lightbox.option({ albumLabel: 'Figure %1 of %2', wrapAround: true, fadeDuration: 250, imageFadeDuration: 250, resizeDuration: 300, positionFromTop: 40, disableScrolling: true, sanitizeTitle: true });" );
	}
	if ( is_singular() ) {
		wp_enqueue_script( 'paulus-reader', PAULUS_URI . '/assets/js/reader.js', array(), PAULUS_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
		// The reading aids of Theme Options, Reading, with their labels.
		$paulus_reader = array(
			'progress' => (bool) paulus_option( 'read_progress' ),
			'keys'     => (bool) paulus_option( 'read_keys' ),
			'memory'   => (bool) paulus_option( 'read_memory' ),
			'copy'     => (bool) paulus_option( 'read_copy' ),
			'top'      => (bool) paulus_option( 'read_top' ),
			'resume'   => __( 'Resume reading where you left off', 'paulus' ),
			'resumeAt' => __( 'Resume reading at', 'paulus' ),
			'dismiss'  => __( 'Dismiss', 'paulus' ),
			'copied'   => __( 'Link copied', 'paulus' ),
			'toTop'    => __( 'Back to top', 'paulus' ),
		);
		wp_add_inline_script( 'paulus-reader', 'window.paulusReader = ' . wp_json_encode( $paulus_reader ) . ';', 'before' );
	}
	if ( is_single() ) {
		wp_enqueue_script( 'paulus-print', PAULUS_URI . '/assets/js/print.js', array(), PAULUS_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
	}
}
add_action( 'wp_enqueue_scripts', 'paulus_enqueue_assets', 20 );

/**
 * Editor gets the same schemes and type.
 */
function paulus_editor_styles() {
	add_editor_style( array( 'assets/css/fonts.css', 'assets/css/schemes.css', 'assets/css/theme.css' ) );
}
add_action( 'after_setup_theme', 'paulus_editor_styles' );

/**
 * Give the block editor iframe the active scheme's own colours.
 *
 * schemes.css scopes every scheme to `body.paulus-scheme-*`, but nothing
 * ever adds that class inside the editor iframe: the `body_class` filter
 * below is a front-end template tag, never applied there. So although
 * add_editor_style() above does load schemes.css into the editor, none of
 * its selectors can ever match, and the editor always renders un-schemed.
 * This reads the active scheme's own declaration block straight out of
 * schemes.css and adds it to the editor settings' styles, unscoped.
 *
 * @param array $settings Block editor settings.
 * @return array
 */
function paulus_editor_scheme_style( $settings ) {
	$scheme = sanitize_html_class( paulus_option( 'scheme' ) );
	$file   = PAULUS_DIR . '/assets/css/schemes.css';
	if ( ! $scheme || ! file_exists( $file ) ) {
		return $settings;
	}
	$css = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	$css = preg_replace( '#/\*.*?\*/#s', '', $css );
	$out = '';
	if ( preg_match( '/body\.paulus-scheme-' . preg_quote( $scheme, '/' ) . '\s*\{([^}]*)\}/', $css, $m ) ) {
		$out .= trim( $m[1] );
	}
	if ( preg_match( '/body:where\(\[class\*="paulus-scheme-"\]\)\s*\{([^}]*)\}/', $css, $m ) ) {
		$out .= ' ' . trim( $m[1] );
	}
	if ( '' === trim( $out ) ) {
		return $settings;
	}
	// Styles passed through the editor settings are loaded inside the
	// iframed canvas, and WordPress rewrites `body` to the canvas root.
	$settings['styles']   = $settings['styles'] ?? array();
	$settings['styles'][] = array( 'css' => 'body{' . $out . '}' );
	return $settings;
}
add_filter( 'block_editor_settings_all', 'paulus_editor_scheme_style' );

/**
 * Active color scheme as a body class. CSS in assets/css/schemes.css maps
 * each class onto the theme.json preset variables, so no inline CSS is needed.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function paulus_body_class( $classes ) {
	$classes[] = 'paulus-scheme-' . sanitize_html_class( paulus_option( 'scheme' ) );
	return $classes;
}
add_filter( 'body_class', 'paulus_body_class' );
