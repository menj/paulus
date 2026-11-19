<?php
/**
 * Shortcodes and citation helpers.
 *
 * FIX #8:  itemscope/itemtype ScholarlyArticle microdata removed from citation
 *           box div (duplicate of JSON-LD block; Google flags this as ambiguous).
 * FIX #10: [citepress_cite] and [citepress_cite_bibliography] alias shortcodes registered.
 * FIX #11: citepress_get_citation_styles() memoized with a static variable.
 * FIX #15: 'styles' and 'ajaxUrl' removed from per-instance citation_data array;
 *           moved to a shared citepressSharedData object in assets.php.
 * FIX Bug: citepress_has_bibliography_shortcode() also detects the Gutenberg block
 *           comment so bibliography mode works when inserted as a block.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function citepress_get_author_name() {
    global $post, $citepress_setting;
    
    if (!isset($post) || !is_object($post)) {
        return '';
    }
    
    $authors = array();
    $guest_author = get_post_meta($post->ID, 'guest-author', true);
    
    if (!empty($guest_author)) {
        $authors[] = sanitize_text_field($guest_author);
    } elseif (function_exists('get_coauthors')) {
        $coauthors = get_coauthors($post->ID);
        foreach ($coauthors as $author) {
            $authors[] = sanitize_text_field($author->display_name);
        }
    } else {
        $author_id = absint($post->post_author);
        $authors[] = sanitize_text_field(get_the_author_meta('display_name', $author_id));
    }
    
    $et_al_threshold = isset($citepress_setting['et_al_threshold']) ? absint($citepress_setting['et_al_threshold']) : 3;
    
    if (count($authors) > $et_al_threshold) {
        return $authors[0] . ' et al.';
    }
    
    if (count($authors) > 1) {
        $last_author = array_pop($authors);
        return implode(', ', $authors) . ' & ' . $last_author;
    }
    
    return !empty($authors[0]) ? $authors[0] : '';
}

// Get ORCID if available
function citepress_get_orcid() {
    global $post;
    if (!isset($post) || !is_object($post)) {
        return '';
    }
    return sanitize_text_field(get_post_meta($post->ID, 'orcid', true));
}

/**
 * Get authors as an array for Wikipedia citation format
 * Returns array of author names (not combined with "et al." or "&")
 */
function citepress_get_authors_array() {
    global $post, $citepress_setting;
    
    if (!isset($post) || !is_object($post)) {
        return array();
    }
    
    $authors = array();
    $guest_author = get_post_meta($post->ID, 'guest-author', true);
    
    if (!empty($guest_author)) {
        $authors[] = sanitize_text_field($guest_author);
    } elseif (function_exists('get_coauthors')) {
        $coauthors = get_coauthors($post->ID);
        foreach ($coauthors as $author) {
            $authors[] = sanitize_text_field($author->display_name);
        }
    } else {
        $author_id = absint($post->post_author);
        $authors[] = sanitize_text_field(get_the_author_meta('display_name', $author_id));
    }
    
    return $authors;
}

/**
 * Get site language code (e.g., "en" from "en_US")
 */
function citepress_get_language_code() {
    $locale = get_locale();
    return substr($locale, 0, 2);
}

/**
 * Format date as "D Month YYYY" for Wikipedia (e.g., "31 January 2026")
 */
function citepress_format_date_dmy($date_string = null) {
    if ($date_string) {
        return date_i18n('j F Y', strtotime($date_string));
    }
    return date_i18n('j F Y');
}

/**
 * Get all registered citation style templates.
 *
 * FIX #11: Memoized — apply_filters fires once instead of 11+ times per request.
 *
 * Every entry in this array is a standard token template. Token replacement is
 * uniform across all formats — no special cases. Available tokens:
 *
 *   {author}               — formatted author name(s) as a single string
 *   {title}                — post title
 *   {sitename}             — site name
 *   {permalink}            — full URL wrapped in an HTML <a> link
 *   {permalink_url}        — raw URL, no HTML (use in structured/markup formats)
 *   {date}                 — access date in site date format
 *   {date_dmy}             — access date as "D Month YYYY" (e.g. 11 March 2026)
 *   {publication_date}     — publication date in site date format
 *   {publication_date_dmy} — publication date as "D Month YYYY"
 *   {publication_year}     — four-digit publication year
 *   {language}             — two-letter ISO language code from WordPress locale
 *
 * @filter citepress_citation_styles  array $styles  Full style id => template map.
 *
 * @return array Format id => template string.
 */
function citepress_get_citation_styles() {
    static $cached = null;
    if ( $cached !== null ) { return $cached; }
    $styles = array(
        // Original 10 formats
        'apa'       => '{author}. ({publication_date}). {title}. <em>{sitename}</em>. {permalink}',
        'mla'       => '{author}. "{title}." <em>{sitename}</em>, {publication_date}, {permalink}',
        'chicago'   => '{author}. "{title}." <em>{sitename}</em>. Last modified {date}. {permalink}',
        'harvard'   => '{author}. ({publication_date}). {title}. <em>{sitename}</em>. Available at: {permalink} (Accessed: {date})',
        'vancouver' => '{author}. {title}. <em>{sitename}</em>. {publication_date}; [cited {date}]. Available from: {permalink}',
        'ieee'      => '{author}, "{title}," <em>{sitename}</em>, {publication_date}. [Online]. Available: {permalink}. [Accessed: {date}]',
        'ama'       => '{author}. {title}. <em>{sitename}</em>. Published {publication_date}. Accessed {date}. {permalink}',
        'asa'       => '{author}. {publication_date}. "{title}." <em>{sitename}</em>. Retrieved {date} ({permalink})',
        'turabian'  => '{author}. "{title}." <em>{sitename}</em>. {publication_date}. {permalink}',
        'wikipedia' => '{{cite web |url={permalink_url} |title={title} |website={sitename} |author={author} |date={publication_date_dmy} |access-date={date_dmy} |language={language}}}',

        // New 10 formats (v2.4.0)
        'acs'      => '{author}. {title}. <em>{sitename}</em> [Online] {publication_date}. {permalink} (accessed {date}).',
        'aip'      => '{author}, "{title}," <em>{sitename}</em> {publication_year}. [Online]. Available: {permalink}',
        'nlm'      => '{author}. {title}. <em>{sitename}</em> [Internet]. {publication_date} [cited {date}]. Available from: {permalink}',
        'aaa'      => '{author} {publication_date} {title}. <em>{sitename}</em>, electronic document, {permalink}, accessed {date}.',
        'apsa'     => '{author}. {publication_date}. "{title}." <em>{sitename}</em>. {permalink} (accessed {date}).',
        'oscola'   => '{author}, \'{title}\' (<em>{sitename}</em>, {publication_date}) &lt;{permalink}&gt; accessed {date}',
        'nature'   => '{author}. {title}. <em>{sitename}</em> {permalink} ({publication_date}).',
        'acm'      => '{author}. {publication_date}. {title}. <em>{sitename}</em>. Retrieved {date} from {permalink}',
        'bluebook' => '{author}, <em>{title}</em>, {sitename} ({publication_date}), {permalink}.',
        'iso690'   => '{author}. {title}. <em>{sitename}</em> [online]. {publication_date} [viewed {date}]. Available from: {permalink}'
    );

    $cached = apply_filters( 'citepress_citation_styles', $styles );
    return $cached;
}


/**
 * Get list of enabled citation formats based on admin settings
 * @return array List of enabled format IDs
 */
function citepress_get_enabled_formats() {
    global $citepress_setting;
    
    $enabled = isset($citepress_setting['enabled_formats']) && is_array($citepress_setting['enabled_formats']) 
        ? $citepress_setting['enabled_formats'] 
        : array_keys(citepress_get_citation_styles());
    
    // Ensure at least one format is enabled
    if (empty($enabled)) {
        $enabled = array('apa'); // Fallback to APA if nothing selected
    }
    
    return $enabled;
}

/**
 * Get format metadata (display name, description, icon, category)
 * @return array Format metadata indexed by format ID
 */
function citepress_get_format_metadata() {
    return array(
        // Original formats
        'apa' => array(
            'name' => __('APA', 'paulus'),
            'full_name' => __('American Psychological Association', 'paulus'),
            'icon' => '📚',
            'category' => 'social-sciences'
        ),
        'mla' => array(
            'name' => __('MLA', 'paulus'),
            'full_name' => __('Modern Language Association', 'paulus'),
            'icon' => '📖',
            'category' => 'humanities'
        ),
        'ieee' => array(
            'name' => __('IEEE', 'paulus'),
            'full_name' => __('Institute of Electrical and Electronics Engineers', 'paulus'),
            'icon' => '⚡',
            'category' => 'engineering'
        ),
        'harvard' => array(
            'name' => __('Harvard', 'paulus'),
            'full_name' => __('Harvard Referencing', 'paulus'),
            'icon' => '🎓',
            'category' => 'general'
        ),
        'chicago' => array(
            'name' => __('Chicago', 'paulus'),
            'full_name' => __('Chicago Manual of Style', 'paulus'),
            'icon' => '📜',
            'category' => 'humanities'
        ),
        'vancouver' => array(
            'name' => __('Vancouver', 'paulus'),
            'full_name' => __('Vancouver Style', 'paulus'),
            'icon' => '🏥',
            'category' => 'medical'
        ),
        'ama' => array(
            'name' => __('AMA', 'paulus'),
            'full_name' => __('American Medical Association', 'paulus'),
            'icon' => '⚕️',
            'category' => 'medical'
        ),
        'asa' => array(
            'name' => __('ASA', 'paulus'),
            'full_name' => __('American Sociological Association', 'paulus'),
            'icon' => '👥',
            'category' => 'social-sciences'
        ),
        'turabian' => array(
            'name' => __('Turabian', 'paulus'),
            'full_name' => __('Turabian Style', 'paulus'),
            'icon' => '✍️',
            'category' => 'general'
        ),
        'wikipedia' => array(
            'name' => __('Wikipedia', 'paulus'),
            'full_name' => __('Wikipedia {{cite web}} Template', 'paulus'),
            'icon' => '🌐',
            'category' => 'web'
        ),
        
        // New formats (v2.4.0)
        'acs' => array(
            'name' => __('ACS', 'paulus'),
            'full_name' => __('American Chemical Society', 'paulus'),
            'icon' => '🧪',
            'category' => 'sciences'
        ),
        'aip' => array(
            'name' => __('AIP', 'paulus'),
            'full_name' => __('American Institute of Physics', 'paulus'),
            'icon' => '⚛️',
            'category' => 'sciences'
        ),
        'nlm' => array(
            'name' => __('NLM', 'paulus'),
            'full_name' => __('National Library of Medicine', 'paulus'),
            'icon' => '🏥',
            'category' => 'medical'
        ),
        'aaa' => array(
            'name' => __('AAA', 'paulus'),
            'full_name' => __('American Anthropological Association', 'paulus'),
            'icon' => '🌍',
            'category' => 'social-sciences'
        ),
        'apsa' => array(
            'name' => __('APSA', 'paulus'),
            'full_name' => __('American Political Science Association', 'paulus'),
            'icon' => '🗳️',
            'category' => 'social-sciences'
        ),
        'oscola' => array(
            'name' => __('OSCOLA', 'paulus'),
            'full_name' => __('Oxford Standard for Citation of Legal Authorities', 'paulus'),
            'icon' => '⚖️',
            'category' => 'legal'
        ),
        'nature' => array(
            'name' => __('Nature', 'paulus'),
            'full_name' => __('Nature Journal Style', 'paulus'),
            'icon' => '🔬',
            'category' => 'sciences'
        ),
        'acm' => array(
            'name' => __('ACM', 'paulus'),
            'full_name' => __('Association for Computing Machinery', 'paulus'),
            'icon' => '💻',
            'category' => 'engineering'
        ),
        'bluebook' => array(
            'name' => __('Bluebook', 'paulus'),
            'full_name' => __('Bluebook Legal Citation', 'paulus'),
            'icon' => '⚖️',
            'category' => 'legal'
        ),
        'iso690' => array(
            'name' => __('ISO 690', 'paulus'),
            'full_name' => __('ISO 690 International Standard', 'paulus'),
            'icon' => '🌐',
            'category' => 'general'
        )
    );
}


// Globals are declared and initialised in cite.php; we just reference them here.

/**
 * Resolve which formats to display for a given shortcode instance.
 * Implements per-post shortcode overrides (Feature #05).
 *
 * @param array $atts Shortcode attributes (may contain 'formats' and 'exclude_formats')
 * @return array Array of validated format IDs
 */
function citepress_resolve_formats($atts) {
    $all_styles = array_keys(citepress_get_citation_styles());
    
    if (!empty($atts['formats'])) {
        $formats = array_map('trim', explode(',', sanitize_text_field($atts['formats'])));
        $formats = array_intersect($formats, $all_styles);
    } else {
        $formats = citepress_get_enabled_formats();
    }
    
    if (!empty($atts['exclude_formats'])) {
        $exclude = array_map('trim', explode(',', sanitize_text_field($atts['exclude_formats'])));
        $formats = array_diff($formats, $exclude);
    }
    
    $formats = array_values($formats);
    
    if (empty($formats)) {
        $formats = array('apa');
    }
    
    return $formats;
}

/**
 * Get inline citation templates for parenthetical in-text citations (Feature #03).
 *
 * @return array Format ID => inline template string
 */
function citepress_get_inline_templates() {
    $templates = array(
        'apa'       => '({author}, {publication_year})',
        'mla'       => '({author}{page})',
        'chicago'   => '({author} {publication_year}{page})',
        'harvard'   => '({author} {publication_year})',
        'vancouver' => '({ref_number})',
        'ieee'      => '[{ref_number}]',
        'ama'       => '({ref_number})',
        'asa'       => '({author} {publication_year})',
        'turabian'  => '({author} {publication_year}{page})',
        'wikipedia' => '({author} {publication_year})',
        'acs'       => '({ref_number})',
        'aip'       => '{ref_number}',
        'nlm'       => '({ref_number})',
        'aaa'       => '({author} {publication_year})',
        'apsa'      => '({author} {publication_year})',
        'oscola'    => '(n {ref_number})',
        'nature'    => '{ref_number}',
        'acm'       => '[{ref_number}]',
        'bluebook'  => '({author} {publication_year})',
        'iso690'    => '({author}, {publication_year})',
    );
    
    return apply_filters('citepress_inline_templates', $templates);
}

/**
 * Check if the current post content contains a [cite_bibliography] shortcode
 * OR the citepress/block Gutenberg block in bibliography mode.
 *
 * FIX Bug: has_shortcode() only inspects raw shortcode text. When the
 * bibliography is placed via the block editor, post_content contains block
 * comment markup — [cite_bibliography] never appears, so $citepress_bibliography_mode
 * stays false and all [cite] shortcodes render as full boxes instead of footnote
 * markers. We now also scan for the block comment.
 */
function citepress_has_bibliography_shortcode() {
    global $post;
    if ( ! isset( $post ) || ! is_object( $post ) ) { return false; }
    if ( has_shortcode( $post->post_content, 'cite_bibliography' ) || has_shortcode( $post->post_content, 'citepress_cite_bibliography' ) ) { return true; }
    // Block editor stores a block comment like: <!-- wp:citepress/block {"mode":"bibliography"} -->
    if ( strpos( $post->post_content, '"mode":"bibliography"' ) !== false ) { return true; }
    return false;
}

/**
 * Get the author surname(s) for inline citations.
 * Single: "Smith", Two: "Smith & Jones", 3+: "Smith et al."
 *
 * @param string $custom_author Optional custom author override
 * @return string Formatted author surname(s)
 */
function citepress_get_inline_author($custom_author = '') {
    global $citepress_setting;
    
    if (!empty($custom_author)) {
        // Extract surname from custom author
        $parts = explode(' ', trim($custom_author));
        return end($parts);
    }
    
    $authors = citepress_get_authors_array();
    $et_al_threshold = isset($citepress_setting['et_al_threshold']) ? absint($citepress_setting['et_al_threshold']) : 3;
    
    // Extract surnames (last word of each name)
    $surnames = array();
    foreach ($authors as $name) {
        $parts = explode(' ', trim($name));
        $surnames[] = end($parts);
    }
    
    if (empty($surnames)) {
        return html_entity_decode(get_bloginfo('name'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    if (count($surnames) > $et_al_threshold) {
        return $surnames[0] . ' et al.';
    }
    
    if (count($surnames) === 2) {
        return $surnames[0] . ' & ' . $surnames[1];
    }
    
    if (count($surnames) > 2) {
        $last = array_pop($surnames);
        return implode(', ', $surnames) . ' & ' . $last;
    }
    
    return $surnames[0];
}

// Shortcode function
function citepress_display_citation($atts = array()) {
    global $post, $citepress_setting, $citepress_citation_data, $citepress_cited_posts, $citepress_ref_counter, $citepress_bibliography_mode, $citepress_instance_counter;
    
    if (!isset($post) || !is_object($post)) {
        return '';
    }
    
    // Unique instance ID for this citation box
    $citepress_instance_counter++;
    $instance_id = $citepress_instance_counter;
    
    $atts = shortcode_atts(array(
        'style' => 'apa',
        'show_copy' => 'true',
        'show_export' => 'true',
        'show_toggle' => 'true',
        'custom_author' => '',
        'mode' => 'box',
        'formats' => '',
        'exclude_formats' => '',
        'page' => '',
        'link' => 'true',
    ), $atts);
    
    // Validate mode
    $allowed_modes = array('box', 'inline', 'bibliography');
    if (!in_array($atts['mode'], $allowed_modes, true)) {
        $atts['mode'] = 'box';
    }
    
    // Resolve which formats to display (Feature #05)
    $active_formats = citepress_resolve_formats($atts);
    
    $allowed_styles = array_keys(citepress_get_citation_styles());
    if (!in_array($atts['style'], $allowed_styles, true)) {
        $atts['style'] = 'apa';
    }
    
    // Ensure default style is in the active formats list
    if (!in_array($atts['style'], $active_formats, true)) {
        $atts['style'] = $active_formats[0];
    }
    
    $atts['custom_author'] = sanitize_text_field($atts['custom_author']);
    $styles = citepress_get_citation_styles();
    
    if (!empty($citepress_setting['excluded_posts'])) {
        $excluded = array_map('absint', array_map('trim', explode(',', $citepress_setting['excluded_posts'])));
        if (in_array(absint($post->ID), $excluded, true)) {
            return '';
        }
    }
    
    // Detect bibliography mode (two-pass: Feature #02)
    if (!$citepress_bibliography_mode) {
        $citepress_bibliography_mode = citepress_has_bibliography_shortcode();
    }
    
    // =========================================================================
    // MODE: INLINE — parenthetical in-text citation (Feature #03)
    // =========================================================================
    if ($atts['mode'] === 'inline') {
        $inline_templates = citepress_get_inline_templates();
        $inline_author = citepress_get_inline_author($atts['custom_author']);
        $publication_year = get_the_date('Y');
        $permalink = get_permalink();
        $page_str = '';
        
        if (!empty($atts['page'])) {
            $page_str = ', p. ' . esc_html(sanitize_text_field($atts['page']));
        }
        
        // Determine reference number for numeric styles
        $ref_number = '';
        if ($citepress_bibliography_mode) {
            // Register in bibliography tracking
            $citepress_ref_counter++;
            $current_ref = $citepress_ref_counter;
            
            // Check for deduplication (same post cited multiple times)
            $existing_ref = null;
            foreach ($citepress_cited_posts as $entry) {
                if ($entry['post_id'] === $post->ID) {
                    $existing_ref = $entry['ref_number'];
                    break;
                }
            }
            
            if ($existing_ref !== null) {
                $ref_number = $existing_ref;
            } else {
                $ref_number = count(array_unique(array_column($citepress_cited_posts, 'post_id'))) + 1;
            }
            
            $citepress_cited_posts[] = array(
                'post_id' => $post->ID,
                'ref_number' => $ref_number,
                'marker_id' => 'citepress-marker-' . $citepress_ref_counter,
                'anchor_id' => 'citepress-ref-' . $ref_number,
                'style' => $atts['style'],
            );
        }
        
        $template = isset($inline_templates[$atts['style']]) ? $inline_templates[$atts['style']] : '({author}, {publication_year})';
        
        /**
         * Filter the inline citation template for a specific style.
         *
         * @param string $template The template string.
         * @param string $style    The citation style ID.
         */
        $template = apply_filters('citepress_inline_template', $template, $atts['style']);
        
        // For numeric styles without bibliography, fall back to author-year
        $is_numeric_style = (strpos($template, '{ref_number}') !== false);
        if ($is_numeric_style && !$citepress_bibliography_mode) {
            $template = '({author}, {publication_year})';
        }
        
        $citation_text = str_replace(
            array('{author}', '{publication_year}', '{ref_number}', '{page}'),
            array(esc_html($inline_author), esc_html($publication_year), esc_html($ref_number), $page_str),
            $template
        );
        
        $show_link = ($atts['link'] === 'true' || $atts['link'] === '1');
        
        $html = '<span class="citepress-inline-citation"';
        if ($citepress_bibliography_mode) {
            $html .= ' id="' . esc_attr('citepress-marker-' . $citepress_ref_counter) . '"';
        }
        $html .= '>';
        
        if ($show_link) {
            $href = $citepress_bibliography_mode ? '#citepress-ref-' . intval($ref_number) : esc_url($permalink);
            $html .= '<a href="' . $href . '"';
            if ($citepress_bibliography_mode) {
                /* translators: %d: reference number */
                $html .= ' role="doc-noteref" aria-label="' . esc_attr(sprintf(__('See reference %d', 'paulus'), $ref_number)) . '"';
            }
            $html .= '>' . $citation_text . '</a>';
        } else {
            $html .= $citation_text;
        }
        
        $html .= '</span>';
        
        /**
         * Filter the complete inline citation HTML.
         *
         * @param string  $html The inline citation HTML.
         * @param array   $atts The shortcode attributes.
         * @param WP_Post $post The current post.
         */
        return apply_filters('citepress_inline_citation', $html, $atts, $post);
    }
    
    // =========================================================================
    // MODE: BIBLIOGRAPHY — plain text citation without box wrapper (Feature #03)
    // =========================================================================
    if ($atts['mode'] === 'bibliography') {
        $author = !empty($atts['custom_author']) ? $atts['custom_author'] : citepress_get_author_name();
        $template = isset($styles[$atts['style']]) ? $styles[$atts['style']] : $styles['apa'];

        $citation = str_replace(
            array( '{author}', '{sitename}', '{title}', '{date}', '{date_dmy}', '{publication_date}', '{publication_date_dmy}', '{publication_year}', '{permalink}', '{permalink_url}', '{language}' ),
            array(
                esc_html( $author ),
                esc_html( html_entity_decode( get_bloginfo( 'name' ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ),
                esc_html( html_entity_decode( get_the_title(), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ),
                esc_html( date_i18n( get_option( 'date_format' ) ) ),
                esc_html( citepress_format_date_dmy() ),
                esc_html( get_the_date() ),
                esc_html( citepress_format_date_dmy( get_the_date( 'Y-m-d' ) ) ),
                esc_html( get_the_date( 'Y' ) ),
                '<a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_permalink() ) . '</a>',
                esc_html( get_permalink() ),
                esc_html( citepress_get_language_code() ),
            ),
            $template
        );

        $citation = preg_replace('/\s\./', '.', $citation);
        $citation = preg_replace('/\s,/', ',', $citation);

        return '<span class="citepress-bibliography-citation">' . $citation . '</span>';
    }
    
    // =========================================================================
    // MODE: BOX — full citation box with selector (default, Feature #04/#05)
    // =========================================================================
    
    // If bibliography mode is active and mode is box, render as footnote marker instead
    if ($citepress_bibliography_mode && $atts['mode'] === 'box') {
        $citepress_ref_counter++;
        
        // Check for deduplication
        $existing_ref = null;
        foreach ($citepress_cited_posts as $entry) {
            if ($entry['post_id'] === $post->ID) {
                $existing_ref = $entry['ref_number'];
                break;
            }
        }
        
        if ($existing_ref !== null) {
            $ref_number = $existing_ref;
        } else {
            $ref_number = count(array_unique(array_column($citepress_cited_posts, 'post_id'))) + 1;
        }
        
        $citepress_cited_posts[] = array(
            'post_id' => $post->ID,
            'ref_number' => $ref_number,
            'marker_id' => 'citepress-marker-' . $citepress_ref_counter,
            'anchor_id' => 'citepress-ref-' . $ref_number,
            'style' => $atts['style'],
        );
        
        return '<sup class="citepress-ref-marker" id="' . esc_attr('citepress-marker-' . $citepress_ref_counter) . '">'
            /* translators: %d: reference number */
            . '<a href="#citepress-ref-' . intval($ref_number) . '" role="doc-noteref" aria-label="' . esc_attr(sprintf(__('See reference %d', 'paulus'), $ref_number)) . '">'
            . '[' . intval($ref_number) . ']</a></sup>';
    }
    
    $show_toggle = ($atts['show_toggle'] === 'true' || $atts['show_toggle'] === '1') && 
                   isset($citepress_setting['show_toggle']) && $citepress_setting['show_toggle'] === 'yes';
    $show_copy = ($atts['show_copy'] === 'true' || $atts['show_copy'] === '1');
    $show_export = ($atts['show_export'] === 'true' || $atts['show_export'] === '1');
    
    // Get display mode
    $display_mode = isset($citepress_setting['format_display_mode']) ? $citepress_setting['format_display_mode'] : 'dropdown';
    
    // FIX #15: 'styles' and 'ajaxUrl' removed — hoisted to citepressSharedData in assets.php.
    // FIX #3:  'nonce' removed — nonces break under full-page caching; see analytics.php.
    $citation_data = array(
        'postId'             => absint($post->ID),
        'author'             => !empty($atts['custom_author']) ? $atts['custom_author'] : citepress_get_author_name(),
        'authors'            => !empty($atts['custom_author']) ? array($atts['custom_author']) : citepress_get_authors_array(),
        'siteName'           => html_entity_decode(get_bloginfo('name'), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        'title'              => html_entity_decode(get_the_title(), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        'dateAccessed'       => date_i18n(get_option('date_format')),
        'dateAccessedDMY'    => citepress_format_date_dmy(),
        'publicationDate'    => get_the_date(),
        'publicationDateDMY' => citepress_format_date_dmy(get_the_date('Y-m-d')),
        'publicationYear'    => get_the_date('Y'),
        'publicationDateISO' => get_the_date('Y-m-d'),
        'permalink'          => get_permalink(),
        'permalinkUrl'       => esc_url(get_permalink()),
        'language'           => citepress_get_language_code(),
        'displayMode'        => $display_mode,
        'activeFormats'      => $active_formats,
        'instance'           => $instance_id,
    );
    
    $citepress_citation_data[] = $citation_data;
    
    $format_metadata = citepress_get_format_metadata();
    $show_selector = (count($active_formats) > 1);
    
    // FIX #8: itemscope/itemtype="ScholarlyArticle" removed.
    // The JSON-LD block (metadata.php) already emits ScholarlyArticle schema.
    // Having both Microdata and JSON-LD for the same entity on the same page
    // creates ambiguity that Google can flag as a structured-data error.
    $color_scheme  = isset( $citepress_setting['color_scheme'] ) ? $citepress_setting['color_scheme'] : 'auto';
    $box_style     = isset( $citepress_setting['box_style'] ) ? $citepress_setting['box_style'] : 'classic';
    $sharp_corners = isset( $citepress_setting['sharp_corners'] ) && $citepress_setting['sharp_corners'] === 'yes';
    $theme_attr    = ( $color_scheme !== 'auto' ) ? ' data-theme="' . esc_attr( $color_scheme ) . '"' : '';
    $style_attr    = ' data-box-style="' . esc_attr( $box_style ) . '"';
    $corner_attr   = $sharp_corners ? ' data-corners="sharp"' : '';

    ob_start();
    ?>
    <div id="citation-box-<?php echo absint($post->ID); ?>-<?php echo absint($instance_id); ?>" class="citepress-citation-box" role="region" aria-label="<?php esc_attr_e('Citation Information', 'paulus'); ?>" data-display-mode="<?php echo esc_attr($display_mode); ?>"<?php echo $theme_attr . $style_attr . $corner_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- all escaped above ?>>
        <div class="citation-header">
            <div class="citation-title">
                <svg class="citepress-cite-icon" width="20" height="20" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M8.5 5.5C8.5 4.12 7.38 3 6 3S3.5 4.12 3.5 5.5c0 1.03.63 1.92 1.52 2.3-.15 1.52-.78 2.87-1.87 3.93a.5.5 0 0 0 .35.85c2.14 0 3.92-1.15 4.67-2.85.23-.53.33-1.1.33-1.73V5.5zm8 0C16.5 4.12 15.38 3 14 3s-2.5 1.12-2.5 2.5c0 1.03.63 1.92 1.52 2.3-.15 1.52-.78 2.87-1.87 3.93a.5.5 0 0 0 .35.85c2.14 0 3.92-1.15 4.67-2.85.23-.53.33-1.1.33-1.73V5.5z"/></svg>
                <span><?php esc_html_e('Cite This As:', 'paulus'); ?></span>
                <?php if ($show_selector): ?>
                    <?php if ($display_mode === 'tabs'): ?>
                    <div class="citepress-style-tabs" role="tablist" aria-label="<?php esc_attr_e('Citation format', 'paulus'); ?>">
                        <?php foreach ($active_formats as $format_id): ?>
                            <?php if (isset($format_metadata[$format_id])): ?>
                                <button type="button" class="citepress-style-tab<?php echo ($atts['style'] === $format_id) ? ' citepress-style-tab--active' : ''; ?>" role="tab" aria-selected="<?php echo ($atts['style'] === $format_id) ? 'true' : 'false'; ?>" data-style="<?php echo esc_attr($format_id); ?>" data-post-id="<?php echo absint($post->ID); ?>" tabindex="<?php echo ($atts['style'] === $format_id) ? '0' : '-1'; ?>">
                                    <?php echo esc_html($format_metadata[$format_id]['name']); ?>
                                </button>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <?php elseif ($display_mode === 'buttons'): ?>
                    <div class="citepress-style-buttons" role="radiogroup" aria-label="<?php esc_attr_e('Citation format', 'paulus'); ?>">
                        <?php foreach ($active_formats as $format_id): ?>
                            <?php if (isset($format_metadata[$format_id])): ?>
                                <button type="button" class="citepress-style-btn<?php echo ($atts['style'] === $format_id) ? ' citepress-style-btn--active' : ''; ?>" role="radio" aria-checked="<?php echo ($atts['style'] === $format_id) ? 'true' : 'false'; ?>" data-style="<?php echo esc_attr($format_id); ?>" data-post-id="<?php echo absint($post->ID); ?>">
                                    <?php echo esc_html($format_metadata[$format_id]['name']); ?>
                                </button>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <select class="citation-style-select" data-post-id="<?php echo absint($post->ID); ?>" aria-label="<?php esc_attr_e('Select citation style', 'paulus'); ?>" data-default-style="<?php echo esc_attr($atts['style']); ?>">
                        <?php foreach ($active_formats as $format_id): ?>
                            <?php if (isset($format_metadata[$format_id])): ?>
                                <option value="<?php echo esc_attr($format_id); ?>" <?php selected($atts['style'], $format_id); ?>>
                                    <?php echo esc_html($format_metadata[$format_id]['name']); ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="citepress-single-format-label"><?php echo isset($format_metadata[$active_formats[0]]) ? esc_html($format_metadata[$active_formats[0]]['name']) : ''; ?></span>
                <?php endif; ?>
                <?php if ($show_copy): ?>
                <button class="copy-button" data-post-id="<?php echo absint($post->ID); ?>" title="<?php esc_attr_e('Copy citation', 'paulus'); ?>" aria-label="<?php esc_attr_e('Copy citation to clipboard', 'paulus'); ?>">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M13.5 2H6.5C5.67 2 5 2.67 5 3.5V10.5C5 11.33 5.67 12 6.5 12H13.5C14.33 12 15 11.33 15 10.5V3.5C15 2.67 14.33 2 13.5 2ZM13.5 10.5H6.5V3.5H13.5V10.5ZM2.5 6H4V7.5H2.5V12.5H9.5V11H11V12.5C11 13.33 10.33 14 9.5 14H2.5C1.67 14 1 13.33 1 12.5V7.5C1 6.67 1.67 6 2.5 6Z" fill="currentColor"/>
                    </svg>
                </button>
                <?php endif; ?>
                <?php if ($show_export): ?>
                <div class="export-menu">
                    <button class="export-button" data-post-id="<?php echo absint($post->ID); ?>" title="<?php esc_attr_e('Export citation', 'paulus'); ?>" aria-label="<?php esc_attr_e('Export citation in various formats', 'paulus'); ?>" aria-expanded="false" aria-haspopup="true">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M8 12L3 7L4.4 5.6L7 8.2V0H9V8.2L11.6 5.6L13 7L8 12ZM2 16C1.45 16 0.979 15.804 0.587 15.412C0.195 15.02 -0.000667 14.5493 7.78539e-07 14V11H2V14H14V11H16V14C16 14.55 15.804 15.021 15.412 15.413C15.02 15.805 14.5493 16.0007 14 16H2Z" fill="currentColor"/>
                        </svg>
                    </button>
                    <div class="export-dropdown" role="menu" aria-label="<?php esc_attr_e('Export options', 'paulus'); ?>">
                        <?php
                        $export_labels = array(
                            'bibtex'      => __('Export as BibTeX', 'paulus'),
                            'ris'         => __('Export as RIS', 'paulus'),
                            'endnote'     => __('Export as EndNote', 'paulus'),
                            'csl-json'    => __('Export as CSL-JSON', 'paulus'),
                            'cff'         => __('Export as CFF', 'paulus'),
                            'dublin-core' => __('Export as Dublin Core', 'paulus'),
                        );
                        $export_exts = array(
                            'bibtex'      => '.bib',
                            'ris'         => '.ris',
                            'endnote'     => '.enw',
                            'csl-json'    => '.json',
                            'cff'         => '.cff',
                            'dublin-core' => '.xml',
                        );
                        $active_exports = isset($citepress_setting['enabled_export_formats']) ? $citepress_setting['enabled_export_formats'] : array_keys($export_labels);
                        foreach ($active_exports as $ef_id):
                            if (isset($export_labels[$ef_id])):
                        ?>
                        <button class="export-option" data-format="<?php echo esc_attr($ef_id); ?>" data-post-id="<?php echo absint($post->ID); ?>" data-ext="<?php echo esc_attr( isset($export_exts[$ef_id]) ? $export_exts[$ef_id] : '' ); ?>" role="menuitem"><?php echo esc_html($export_labels[$ef_id]); ?></button>
                        <?php
                            endif;
                        endforeach;
                        ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($show_toggle): ?>
                <button class="toggle-button" data-post-id="<?php echo absint($post->ID); ?>" aria-label="<?php esc_attr_e('Toggle citation visibility', 'paulus'); ?>" aria-expanded="true">
                    <svg class="toggle-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M8 11L3 6L4.4 4.6L8 8.2L11.6 4.6L13 6L8 11Z" fill="currentColor"/>
                    </svg>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="citation-content" data-post-id="<?php echo absint($post->ID); ?>">
            <div class="citation-output" data-post-id="<?php echo absint($post->ID); ?>"></div>
        </div>
        <div class="copy-notification" data-post-id="<?php echo absint($post->ID); ?>" role="status" aria-live="polite" aria-atomic="true"><?php esc_html_e('Citation copied!', 'paulus'); ?></div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('cite', 'citepress_display_citation');
// FIX #10: Namespaced aliases to prevent collision with other plugins using [cite].
// New content should use [citepress_cite] and [citepress_cite_bibliography].
add_shortcode('citepress_cite', 'citepress_display_citation');

// =============================================================================
// BIBLIOGRAPHY SHORTCODE (Feature #02)
// =============================================================================

/**
 * [cite_bibliography] shortcode — Renders a collected reference list.
 *
 * Collects all [cite] instances registered in $citepress_cited_posts and generates
 * a formatted, numbered reference list (bibliography) at the shortcode location.
 *
 * @param array $atts Shortcode attributes.
 * @return string Bibliography HTML
 */
function citepress_display_bibliography($atts = array()) {
    global $post, $citepress_setting, $citepress_cited_posts;
    
    if (!isset($post) || !is_object($post)) {
        return '';
    }
    
    $atts = shortcode_atts(array(
        'style'       => '',
        'heading'     => __('References', 'paulus'),
        'heading_tag' => 'h3',
        'numbered'    => 'true',
        'link_back'   => 'true',
    ), $atts);
    
    // If no citations were collected, output nothing
    if (empty($citepress_cited_posts)) {
        return '';
    }
    
    // Validate heading tag
    $allowed_heading_tags = array('h2', 'h3', 'h4', 'h5', 'h6');
    if (!in_array($atts['heading_tag'], $allowed_heading_tags, true)) {
        $atts['heading_tag'] = 'h3';
    }
    
    $numbered = ($atts['numbered'] === 'true' || $atts['numbered'] === '1');
    $link_back = ($atts['link_back'] === 'true' || $atts['link_back'] === '1');
    
    // Determine bibliography style
    if (!empty($atts['style'])) {
        $bib_style = sanitize_text_field($atts['style']);
        $all_styles = array_keys(citepress_get_citation_styles());
        if (!in_array($bib_style, $all_styles, true)) {
            $bib_style = 'apa';
        }
    } else {
        $enabled = citepress_get_enabled_formats();
        $bib_style = !empty($enabled[0]) ? $enabled[0] : 'apa';
    }
    
    $styles = citepress_get_citation_styles();
    $template = isset($styles[$bib_style]) ? $styles[$bib_style] : $styles['apa'];
    
    // Deduplicate: build unique entries by post_id preserving first-appearance order
    $unique_entries = array();
    $marker_map = array(); // ref_number => array of marker_ids
    
    foreach ($citepress_cited_posts as $entry) {
        $pid = $entry['post_id'];
        $rn = $entry['ref_number'];
        
        if (!isset($unique_entries[$pid])) {
            $unique_entries[$pid] = array(
                'post_id' => $pid,
                'ref_number' => $rn,
                'style' => $entry['style'],
            );
        }
        
        if (!isset($marker_map[$rn])) {
            $marker_map[$rn] = array();
        }
        $marker_map[$rn][] = $entry['marker_id'];
    }
    
    // Build output
    /**
     * Filter the bibliography heading text.
     *
     * @param string $heading The heading text.
     */
    $heading_text = apply_filters('citepress_bibliography_heading', $atts['heading']);
    
    $output = '<div class="citepress-bibliography" role="doc-endnotes">';
    
    if (!empty($heading_text)) {
        $output .= '<' . $atts['heading_tag'] . ' class="citepress-bibliography-heading">' . esc_html($heading_text) . '</' . $atts['heading_tag'] . '>';
    }
    
    $list_tag = $numbered ? 'ol' : 'ul';
    $output .= '<' . $list_tag . ' class="citepress-bibliography-list">';
    
    foreach ($unique_entries as $entry) {
        $ref_post = get_post($entry['post_id']);
        if (!$ref_post) {
            continue;
        }
        
        // Temporarily set global $post to generate citation for this entry
        $original_post = $GLOBALS['post'];
        $GLOBALS['post'] = $ref_post;
        setup_postdata($ref_post);
        
        $author = citepress_get_author_name();
        $title = html_entity_decode(get_the_title(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $site_name = html_entity_decode(get_bloginfo('name'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $permalink = get_permalink();
        $date_accessed = date_i18n(get_option('date_format'));
        $pub_date = get_the_date();
        $pub_year = get_the_date('Y');
        
        if ($bib_style === 'wikipedia') {
            $citation_html = str_replace(
                array( '{author}', '{sitename}', '{title}', '{date}', '{date_dmy}', '{publication_date}', '{publication_date_dmy}', '{publication_year}', '{permalink}', '{permalink_url}', '{language}' ),
                array(
                    esc_html( $author ),
                    esc_html( $site_name ),
                    esc_html( $title ),
                    esc_html( $date_accessed ),
                    esc_html( citepress_format_date_dmy() ),
                    esc_html( $pub_date ),
                    esc_html( citepress_format_date_dmy( get_the_date( 'Y-m-d' ) ) ),
                    esc_html( $pub_year ),
                    '<a href="' . esc_url( $permalink ) . '">' . esc_html( $permalink ) . '</a>',
                    esc_html( $permalink ),
                    esc_html( citepress_get_language_code() ),
                ),
                $template
            );
        } else {
            $citation_html = str_replace(
                array( '{author}', '{sitename}', '{title}', '{date}', '{date_dmy}', '{publication_date}', '{publication_date_dmy}', '{publication_year}', '{permalink}', '{permalink_url}', '{language}' ),
                array(
                    esc_html( $author ),
                    esc_html( $site_name ),
                    esc_html( $title ),
                    esc_html( $date_accessed ),
                    esc_html( citepress_format_date_dmy() ),
                    esc_html( $pub_date ),
                    esc_html( citepress_format_date_dmy( get_the_date( 'Y-m-d' ) ) ),
                    esc_html( $pub_year ),
                    '<a href="' . esc_url( $permalink ) . '">' . esc_html( $permalink ) . '</a>',
                    esc_html( $permalink ),
                    esc_html( citepress_get_language_code() ),
                ),
                $template
            );
            $citation_html = preg_replace('/\s\./', '.', $citation_html);
            $citation_html = preg_replace('/\s,/', ',', $citation_html);
        }
        
        // Restore original post
        $GLOBALS['post'] = $original_post;
        setup_postdata($original_post);
        
        $rn = $entry['ref_number'];
        
        // Build backlinks
        $backlinks = '';
        if ($link_back && isset($marker_map[$rn])) {
            $bl_parts = array();
            foreach ($marker_map[$rn] as $idx => $marker_id) {
                $label = (count($marker_map[$rn]) > 1) ? chr(97 + $idx) : '↩';
                $bl_parts[] = '<a href="#' . esc_attr($marker_id) . '" class="citepress-bibliography-backlink" aria-label="' . esc_attr(sprintf(
                    /* translators: %d: citation number in text */
                    __('Back to citation %d in text', 'paulus'),
                    $rn
                )) . '">' . $label . '</a>';
            }
            $backlinks = ' ' . implode(' ', $bl_parts);
        }
        
        $entry_html = '<li id="citepress-ref-' . intval($rn) . '" class="citepress-bibliography-entry" role="doc-endnote">'
            . '<span class="citepress-bibliography-text">' . $citation_html . '</span>'
            . $backlinks
            . '</li>';
        
        /**
         * Filter an individual bibliography entry.
         *
         * @param string $entry_html The entry HTML.
         * @param int    $ref_number The reference number.
         * @param array  $entry      The entry data.
         */
        $output .= apply_filters('citepress_bibliography_entry', $entry_html, $rn, $entry);
    }
    
    $output .= '</' . $list_tag . '>';
    $output .= '</div>';
    
    /**
     * Filter the complete bibliography output HTML.
     *
     * @param string $output The complete bibliography HTML.
     * @param array  $unique_entries The deduplicated citation entries.
     */
    return apply_filters('citepress_bibliography_output', $output, $unique_entries);
}
add_shortcode('cite_bibliography', 'citepress_display_bibliography');
add_shortcode('citepress_cite_bibliography', 'citepress_display_bibliography');
