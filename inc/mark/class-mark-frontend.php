<?php
/**
 * End mark Frontend
 *
 * Handles the_content filtering, end mark HTML rendering,
 * eligibility checks, and frontend style enqueueing.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Paulus_Mark_Frontend {

    /**
     * Reference to the main plugin instance.
     *
     * @var Paulus_Mark
     */
    private $plugin;

    /**
     * Flag: end mark was rendered (consumed by schema output).
     *
     * @var bool
     */
    private $paulus_mark_rendered = false;

    /**
     * Unsafe HTML containers that must not receive an end mark.
     *
     * @var array
     */
    private $unsafe_containers = array( 'table', 'ul', 'ol', 'blockquote', 'figure', 'pre', 'code' );

    /**
     * Items already marked in this request, by ID.
     *
     * @var array
     */
    private $marked = array();

    /**
     * Footnote detection patterns.
     *
     * @var array
     */
    private $footnote_patterns = array(
        '<ol class="footnotes"',
        '<div class="footnotes"',
        '<div id="footnotes"',
        '<section class="footnotes"',
        '<div class="simple-footnotes"',
        '<div id="notes"',
        '<h2 class="wp-block-heading" id="notes"',
        '<h3 class="wp-block-heading" id="notes"',
        '>Notes</h2>',
        '>Notes</h3>',
        '>Notes</',
    );

    /**
     * Protected Gutenberg comment ranges.
     *
     * @var array
     */
    private $protected_gutenberg_ranges = array(
        array( 'start' => '<!-- wp:yoast/faq-block',       'end' => '<!-- /wp:yoast/faq-block -->' ),
        array( 'start' => '<!-- wp:yoast/how-to-block',    'end' => '<!-- /wp:yoast/how-to-block -->' ),
        array( 'start' => '<!-- wp:rank-math/faq-block',   'end' => '<!-- /wp:rank-math/faq-block -->' ),
        array( 'start' => '<!-- wp:rank-math/howto-block',  'end' => '<!-- /wp:rank-math/howto-block -->' ),
        array( 'start' => '<!-- wp:aioseo/faq',            'end' => '<!-- /wp:aioseo/faq -->' ),
        array( 'start' => '<!-- wp:seopress/faq-block',    'end' => '<!-- /wp:seopress/faq-block -->' ),
        array( 'start' => '<!-- wp:schema/faq',            'end' => '<!-- /wp:schema/faq -->' ),
        array( 'start' => '<!-- wp:schema/',               'end' => '<!-- /wp:schema/' ),
    );

    /**
     * Constructor.
     *
     * @param Paulus_Mark $plugin Main plugin instance.
     */
    public function __construct( Paulus_Mark $plugin ) {
        $this->plugin = $plugin;

        add_filter( 'the_content', array( $this, 'insert_mark' ), 9999 );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_styles' ) );
    }

    /* ======================================================================
       Public Accessors
       ====================================================================== */

    /**
     * Whether the end mark was rendered on this page load.
     *
     * @return bool
     */
    public function was_rendered() {
        return $this->paulus_mark_rendered;
    }

    /* ======================================================================
       Eligibility
       ====================================================================== */

    /**
     * Check if the current request is AMP.
     *
     * @return bool
     */
    private function is_amp_request() {
        return function_exists( 'amp_is_request' ) && amp_is_request();
    }

    /**
     * Check if a post is excluded by category.
     *
     * @param  int   $post_id             Post ID.
     * @param  array $excluded_categories  Array of term IDs.
     * @return bool
     */
    private function is_excluded_by_category( $post_id, $excluded_categories ) {
        if ( empty( $excluded_categories ) ) {
            return false;
        }
        $post_categories = wp_get_post_categories( $post_id, array( 'fields' => 'ids' ) );
        return ! empty( array_intersect( $post_categories, $excluded_categories ) );
    }

    /**
     * Check if a post is excluded by tag.
     *
     * @param  int   $post_id        Post ID.
     * @param  array $excluded_tags  Array of term IDs.
     * @return bool
     */
    private function is_excluded_by_tag( $post_id, $excluded_tags ) {
        if ( empty( $excluded_tags ) ) {
            return false;
        }
        $post_tags = wp_get_post_tags( $post_id, array( 'fields' => 'ids' ) );
        return ! empty( array_intersect( $post_tags, $excluded_tags ) );
    }

    /**
     * Count words in content.
     *
     * @param  string $content HTML content.
     * @return int
     */
    private function count_words( $content ) {
        return str_word_count( wp_strip_all_tags( $content ) );
    }

    /**
     * Check full eligibility for end mark insertion.
     *
     * @param  string $content  Post content.
     * @param  array  $settings Plugin settings.
     * @return bool
     */
    private function check_eligibility( $content, $settings ) {
        if ( ! is_singular() || ! in_the_loop() || ! is_main_query() || is_feed() ) {
            return false;
        }

        if ( empty( $settings['enabled'] ) ) {
            return false;
        }

        // Check current post type against the enabled post types array.
        $current_type  = get_post_type();
        $allowed_types = isset( $settings['post_types'] ) ? (array) $settings['post_types'] : array( 'post' );

        if ( ! in_array( $current_type, $allowed_types, true ) ) {
            return false;
        }

        $post_id = get_the_ID();

        // Per-post override: check if explicitly disabled via post meta.
        if ( get_post_meta( $post_id, Paulus_Mark::META_DISABLE, true ) ) {
            return false;
        }

        if ( ! empty( $settings['exclude_post_ids'] ) && in_array( $post_id, $settings['exclude_post_ids'], true ) ) {
            return false;
        }
        if ( $settings['min_word_count'] > 0 && $this->count_words( $content ) < $settings['min_word_count'] ) {
            return false;
        }
        if ( $this->is_excluded_by_category( $post_id, $settings['exclude_categories'] ) ) {
            return false;
        }
        if ( $this->is_excluded_by_tag( $post_id, $settings['exclude_tags'] ) ) {
            return false;
        }
        if ( $settings['disable_on_amp'] && $this->is_amp_request() ) {
            return false;
        }
        if ( $settings['hide_on_mobile'] && wp_is_mobile() ) {
            return false;
        }

        return true;
    }

    /**
     * Check for manual placement (shortcode, block, or already inserted).
     *
     * @param  string $content Post content.
     * @return bool
     */
    private function has_manual_placement( $content ) {
        if ( has_shortcode( $content, Paulus_Mark::SHORTCODE_TAG ) ) {
            return true;
        }
        if ( has_block( Paulus_Mark::BLOCK_NAME ) ) {
            return true;
        }
        if ( false !== strpos( $content, Paulus_Mark::WRAPPER_CLASS_TOKEN ) ) {
            return true;
        }
        if ( false !== strpos( $content, Paulus_Mark::ANTI_DUP_COMMENT ) ) {
            return true;
        }
        return false;
    }

    /* ======================================================================
       Content Preparation
       ====================================================================== */

    /**
     * Hygiene pre-pass — trim trailing empty paragraphs and excess breaks.
     *
     * @param  string $content Post content.
     * @return string
     */
    private function hygiene_prepass( $content ) {
        $content = rtrim( $content );
        $content = preg_replace( '/(<p[^>]*>\s*(&nbsp;|\s)*<\/p>\s*)+$/i', '', $content );
        $content = preg_replace( '/(<br\s*\/?>\s*){2,}$/i', '', $content );
        return $content;
    }

    /* ======================================================================
       Protected Range Detection
       ====================================================================== */

    /**
     * Build regex patterns from the PROTECTED_SCHEMA_TYPES constant.
     *
     * @return array
     */
    private function get_protected_microdata_patterns() {
        $patterns = array();
        foreach ( Paulus_Mark::PROTECTED_SCHEMA_TYPES as $type ) {
            $patterns[] = '/itemtype=["\']https?:\/\/schema\.org\/' . preg_quote( $type, '/' ) . '["\']/i';
        }
        return $patterns;
    }

    /**
     * Find all protected microdata ranges in content.
     *
     * @param  string $content HTML content.
     * @return array
     */
    private function find_protected_microdata_ranges( $content ) {
        $ranges   = array();
        $patterns = $this->get_protected_microdata_patterns();

        foreach ( $patterns as $pattern ) {
            if ( preg_match_all( $pattern, $content, $matches, PREG_OFFSET_CAPTURE ) ) {
                foreach ( $matches[0] as $match ) {
                    $start_pos = $match[1];
                    $tag_start = strrpos( substr( $content, 0, $start_pos + 1 ), '<' );
                    if ( false === $tag_start ) {
                        continue;
                    }

                    preg_match( '/<([a-z0-9]+)/i', substr( $content, $tag_start, 50 ), $tag_match );
                    if ( empty( $tag_match[1] ) ) {
                        continue;
                    }

                    $end_pos = $this->find_closing_tag_position( $content, $tag_match[1], $tag_start );
                    if ( false !== $end_pos ) {
                        $ranges[] = array(
                            'start' => $tag_start,
                            'end'   => $end_pos,
                            'type'  => 'microdata',
                        );
                    }
                }
            }
        }

        return $ranges;
    }

    /**
     * Find all protected Gutenberg block ranges in content.
     *
     * @param  string $content HTML content.
     * @return array
     */
    private function find_protected_gutenberg_ranges( $content ) {
        $ranges = array();

        foreach ( $this->protected_gutenberg_ranges as $range_def ) {
            $search_pos = 0;
            while ( ( $start_pos = strpos( $content, $range_def['start'], $search_pos ) ) !== false ) {
                $end_pos = strpos( $content, $range_def['end'], $start_pos );
                if ( false !== $end_pos ) {
                    $ranges[] = array(
                        'start' => $start_pos,
                        'end'   => $end_pos + strlen( $range_def['end'] ),
                        'type'  => 'gutenberg',
                    );
                    $search_pos = $end_pos + strlen( $range_def['end'] );
                } else {
                    break;
                }
            }
        }

        return $ranges;
    }

    /**
     * Find all protected ranges (combined), sorted by start position.
     *
     * @param  string $content HTML content.
     * @return array
     */
    private function find_all_protected_ranges( $content ) {
        $all_ranges = array_merge(
            $this->find_protected_microdata_ranges( $content ),
            $this->find_protected_gutenberg_ranges( $content )
        );

        usort( $all_ranges, function ( $a, $b ) {
            return $a['start'] - $b['start'];
        } );

        return apply_filters( 'paulus_mark_protected_patterns', $all_ranges, $content );
    }

    /**
     * Find closing tag position (depth-aware).
     *
     * @param  string $content  HTML content.
     * @param  string $tag_name Tag name.
     * @param  int    $start_pos Opening tag position.
     * @return int|false
     */
    private function find_closing_tag_position( $content, $tag_name, $start_pos ) {
        $depth = 0;
        $pos   = $start_pos;
        $len   = strlen( $content );

        while ( $pos < $len ) {
            $next_open  = stripos( $content, '<' . $tag_name, $pos + 1 );
            $next_close = stripos( $content, '</' . $tag_name, $pos + 1 );

            if ( false === $next_close ) {
                return false;
            }

            if ( false !== $next_open && $next_open < $next_close ) {
                $tag_end = strpos( $content, '>', $next_open );
                if ( false !== $tag_end && '/' !== $content[ $tag_end - 1 ] ) {
                    $depth++;
                }
                $pos = $next_open;
            } else {
                if ( 0 === $depth ) {
                    $close_end = strpos( $content, '>', $next_close );
                    return false !== $close_end ? $close_end + 1 : false;
                }
                $depth--;
                $pos = $next_close;
            }
        }

        return false;
    }

    /**
     * Check if a position falls within any protected range.
     *
     * @param  int   $position         Byte position.
     * @param  array $protected_ranges Array of ranges.
     * @return bool
     */
    private function is_position_in_protected_range( $position, $protected_ranges ) {
        foreach ( $protected_ranges as $range ) {
            if ( $position >= $range['start'] && $position < $range['end'] ) {
                return true;
            }
        }
        return false;
    }

    /* ======================================================================
       Insertion Point Discovery
       ====================================================================== */

    /**
     * Find footnotes position in content.
     *
     * @param  string $content HTML content.
     * @return int|false
     */
    private function find_footnotes_position( $content ) {
        foreach ( $this->footnote_patterns as $pattern ) {
            $pos = stripos( $content, $pattern );
            if ( false !== $pos ) {
                return $pos;
            }
        }
        return false;
    }

    /**
     * Check if a position is inside an unsafe container.
     *
     * @param  string $content  HTML content.
     * @param  int    $position Byte position.
     * @return bool
     */
    private function is_inside_unsafe_container( $content, $position ) {
        // A quotation is not unsafe here: when an article ends in one, the last
        // paragraph of the article is inside it and takes the mark.
        foreach ( array_diff( $this->unsafe_containers, array( 'blockquote' ) ) as $tag ) {
            preg_match_all( '/<' . $tag . '\b[^>]*>/i', substr( $content, 0, $position ), $opens );
            preg_match_all( '/<\/' . $tag . '>/i', substr( $content, 0, $position ), $closes );

            if ( count( $opens[0] ) > count( $closes[0] ) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Where the article's own text ends: at its notes, or at the questions or
     * reading list that follows it, whichever comes first. The "Back to top" line the
     * theme puts before that section is skipped by the paragraph search.
     *
     * @param  string    $content       HTML content.
     * @param  int|false $footnotes_pos Position of the notes, or false.
     * @return int|false
     */
    private function body_end_position( $content, $footnotes_pos ) {
        $end = $footnotes_pos;
        // Back matter ends the text too: the questions, and a reading or sources list.
        if ( preg_match( '/<h2\b[^>]*\bid\s*=\s*["\'][^"\']*(?:questions|further-reading|sources|bibliography)[^"\']*["\']/i', $content, $m, PREG_OFFSET_CAPTURE ) ) {
            if ( false === $end || $m[0][1] < $end ) {
                $end = $m[0][1];
            }
        }
        return $end;
    }

    /**
     * Find insertion point for a CSS selector mode.
     *
     * @param  string $content  HTML content.
     * @param  string $selector CSS class or ID selector.
     * @return int|false
     */
    private function find_selector_insertion_point( $content, $selector ) {
        $selector_type  = substr( $selector, 0, 1 );
        $selector_value = substr( $selector, 1 );

        if ( '.' === $selector_type ) {
            $pattern = '/<[^>]*\bclass\s*=\s*["\'][^"\']*\b' . preg_quote( $selector_value, '/' ) . '\b[^"\']*["\'][^>]*>/i';
        } elseif ( '#' === $selector_type ) {
            $pattern = '/<[^>]*\bid\s*=\s*["\']' . preg_quote( $selector_value, '/' ) . '["\'][^>]*>/i';
        } else {
            return false;
        }

        if ( preg_match( $pattern, $content, $match, PREG_OFFSET_CAPTURE ) ) {
            return $match[0][1];
        }

        return false;
    }

    /**
     * Find the last valid paragraph for insertion.
     *
     * @param  string    $content          HTML content.
     * @param  int|false $footnotes_pos    Position of footnotes or false.
     * @param  array     $protected_ranges Protected ranges to skip.
     * @return int|false
     */
    private function find_last_paragraph_insertion_point( $content, $footnotes_pos = false, $protected_ranges = array() ) {
        if ( ! preg_match_all( '/<p\b[^>]*>(.*?)<\/p>/is', $content, $matches, PREG_OFFSET_CAPTURE ) ) {
            return false;
        }

        // The article's text ends where its questions section or its notes begin.
        $footnotes_pos = $this->body_end_position( $content, $footnotes_pos );

        $last_valid_index = -1;

        foreach ( $matches[0] as $i => $match ) {
            $p_start = $match[1];
            $p_html  = $match[0];
            $p_end   = $p_start + strlen( $p_html );

            if ( false !== $footnotes_pos && $p_end > $footnotes_pos ) {
                continue;
            }
            if ( ! empty( $protected_ranges ) && $this->is_position_in_protected_range( $p_start, $protected_ranges ) ) {
                continue;
            }
            if ( $this->is_inside_unsafe_container( $content, $p_start ) ) {
                continue;
            }
            // The theme's own furniture is not text: the "Back to top" line, the
            // contents label, share and credit lines.
            if ( preg_match( '/^<p\b[^>]*\bclass\s*=\s*["\'][^"\']*\b(?:paulus-faq-back|paulus-faq-index__label|paulus-share|paulus-figure|paulus-hero)/i', $p_html ) ) {
                continue;
            }

            $inner_html = $matches[1][ $i ][0];
            $text       = trim( wp_strip_all_tags( str_replace( '&nbsp;', '', preg_replace( '/<br\s*\/?>/i', '', $inner_html ) ) ) );

            if ( '' !== $text ) {
                $last_valid_index = $i;
            }
        }

        if ( -1 === $last_valid_index ) {
            return false;
        }

        $p_html       = $matches[0][ $last_valid_index ][0];
        $p_start      = $matches[0][ $last_valid_index ][1];
        $close_tag_pos = strripos( $p_html, '</p>' );

        return $p_start + $close_tag_pos;
    }

    /* ======================================================================
       End mark Insertion (the_content filter)
       ====================================================================== */

    /**
     * Insert end mark into post content.
     *
     * Hooked at priority 9999 on the_content.
     *
     * @param  string $content Post content.
     * @return string
     */
    public function insert_mark( $content ) {
        $settings = $this->plugin->get_settings();

        if ( ! $this->check_eligibility( $content, $settings ) ) {
            return $content;
        }
        // One mark for one item in one request, however often its text is filtered.
        $item_id = (int) get_the_ID();
        if ( isset( $this->marked[ $item_id ] ) ) {
            return $content;
        }
        if ( $this->has_manual_placement( $content ) ) {
            return $content;
        }

        $mark = $this->render_mark_html( $settings );
        if ( empty( $mark ) ) {
            return $content;
        }

        $this->marked[ $item_id ] = true;
        $content          = $this->hygiene_prepass( $content );
        $protected_ranges = $this->find_all_protected_ranges( $content );
        $footnotes_pos    = $this->find_footnotes_position( $content );
        $placement_mode   = $settings['placement_mode'];

        // fail_safe: insertion_failure = return_original_content
        try {
            switch ( $placement_mode ) {
                case 'selector':
                    $insertion_point = ! empty( $settings['placement_selector'] )
                        ? $this->find_selector_insertion_point( $content, $settings['placement_selector'] )
                        : false;

                    if ( false === $insertion_point || $this->is_position_in_protected_range( $insertion_point, $protected_ranges ) ) {
                        $this->paulus_mark_rendered = true;
                        return $content . $mark;
                    }
                    $this->paulus_mark_rendered = true;
                    return substr_replace( $content, $mark, $insertion_point, 0 );

                case 'before_footnotes':
                    if ( false !== $footnotes_pos && ! $this->is_position_in_protected_range( $footnotes_pos, $protected_ranges ) ) {
                        $this->paulus_mark_rendered = true;
                        return substr_replace( $content, $mark, $footnotes_pos, 0 );
                    }
                    $insertion_point = $this->find_last_paragraph_insertion_point( $content, $footnotes_pos, $protected_ranges );
                    if ( false !== $insertion_point ) {
                        $this->paulus_mark_rendered = true;
                        return substr_replace( $content, $mark, $insertion_point, 0 );
                    }
                    $this->paulus_mark_rendered = true;
                    return $content . $mark;

                case 'after_footnotes':
                case 'append':
                    $this->paulus_mark_rendered = true;
                    return $content . $mark;

                case 'last_paragraph':
                default:
                    $insertion_point = $this->find_last_paragraph_insertion_point( $content, $footnotes_pos, $protected_ranges );
                    if ( false !== $insertion_point ) {
                        $this->paulus_mark_rendered = true;
                        return substr_replace( $content, $mark, $insertion_point, 0 );
                    }
                    if ( false !== $footnotes_pos && ! $this->is_position_in_protected_range( $footnotes_pos, $protected_ranges ) ) {
                        $this->paulus_mark_rendered = true;
                        return substr_replace( $content, $mark, $footnotes_pos, 0 );
                    }
                    $this->paulus_mark_rendered = true;
                    return $content . $mark;
            }
        } catch ( Exception $e ) {
            return $content;
        }
    }

    /* ======================================================================
       HTML Rendering
       ====================================================================== */

    /**
     * Check if a URL points to an SVG file.
     *
     * @param  string $url Image URL.
     * @return bool
     */
    private function is_svg_url( $url ) {
        $path = wp_parse_url( $url, PHP_URL_PATH );
        if ( ! $path ) {
            return false;
        }
        return 'svg' === strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
    }

    /**
     * Render the end mark HTML fragment.
     *
     * @param  array $settings Plugin settings.
     * @return string
     */
    public function render_mark_html( $settings ) {
        $inner = '';

        if ( 'symbol' === $settings['type'] ) {
            $symbol = esc_html( $settings['symbol'] );
            if ( empty( $symbol ) ) {
                $symbol = '∎';
            }
            $inner = '<span class="paulus-mark paulus-mark-symbol">' . $symbol . '</span>';
        } elseif ( 'image' === $settings['type'] && ! empty( $settings['image_url'] ) ) {
            $url = esc_url( $settings['image_url'] );
            $size = absint( $settings['image_size_px'] );

            if ( $this->is_svg_url( $url ) && empty( $settings['allow_svg'] ) ) {
                $inner = '<span class="paulus-mark paulus-mark-symbol">∎</span>';
            } else {
                $inner = sprintf(
                    '<img class="paulus-mark paulus-mark-image" src="%s" alt="" width="%d" height="%d" loading="lazy" decoding="async" />',
                    $url,
                    $size,
                    $size
                );
            }
        } elseif ( 'image' === $settings['type'] && empty( $settings['image_url'] ) ) {
            $inner = '<span class="paulus-mark paulus-mark-symbol">∎</span>';
        }

        if ( empty( $inner ) ) {
            return '';
        }

        return '<span class="' . Paulus_Mark::WRAPPER_CLASS . '" aria-hidden="true" role="presentation">'
            . $inner
            . '</span>'
            . Paulus_Mark::ANTI_DUP_COMMENT;
    }

    /* ======================================================================
       Frontend Styles
       ====================================================================== */

    /**
     * Enqueue static frontend CSS and inject dynamic overrides.
     */
    public function enqueue_frontend_styles() {
        if ( ! is_singular() ) {
            return;
        }

        $settings = $this->plugin->get_settings();

        // Static base styles.
        wp_enqueue_style(
            'paulus-mark-frontend',
            PAULUS_URI . '/assets/css/paulus-mark-frontend.css',
            array(),
            Paulus_Mark::VERSION
        );

        // Dynamic overrides via inline CSS.
        $margin_top  = sanitize_text_field( $settings['margin_top'] );
        $margin_left = sanitize_text_field( $settings['margin_left'] );
        $size        = absint( $settings['image_size_px'] );

        $css = ':root{';
        $css .= '--paulus-mark-margin-top:' . $margin_top . ';';
        $css .= '--paulus-mark-margin-left:' . $margin_left . ';';
        $css .= '--paulus-mark-image-size:' . $size . 'px;';
        $css .= '}';

        // Non-variable fallback when CSS variables are disabled.
        if ( empty( $settings['use_css_variables'] ) ) {
            $css .= '.paulus-mark-wrap{margin-top:' . $margin_top . ';margin-left:' . $margin_left . ';}';
        }

        // Mobile hide.
        if ( $settings['hide_on_mobile'] ) {
            $css .= '@media(max-width:767px){.paulus-mark-wrap{display:none!important;}}';
        }

        wp_add_inline_style( 'paulus-mark-frontend', $css );
    }
}
