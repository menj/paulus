<?php
/**
 * Metadata: auto-display, Google Scholar tags, JSON-LD, Open Graph. Built into
 * the Paulus theme from the plugin Citation by MENJ (GPL v2 or later). In the
 * theme only the Google Scholar tags are printed; the theme prints its own
 * Open Graph tags and JSON-LD.
 *
 *
 * FIX #7: All three head-tag blocks (Scholar, JSON-LD, OG) are now guarded
 *         against Yoast SEO, RankMath, AIOSEO, and The SEO Framework.
 *         Duplicate og:type or ScholarlyArticle JSON-LD causes ambiguous
 *         structured data that Google Search Console flags as an error.
 *         A paulus_cite_output_og_tags / paulus_cite_output_jsonld / paulus_cite_output_scholar
 *         filter is also exposed so site owners can override the guard.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_filter('the_content', 'paulus_cite_auto_display_citation');

function paulus_cite_auto_display_citation($content) {
    global $post, $paulus_cite_setting;
    
    if (!is_main_query()) {
        return $content;
    }
    
    if (!isset($paulus_cite_setting['auto_display']) || $paulus_cite_setting['auto_display'] !== 'yes') {
        return $content;
    }
    
    $allowed_types = isset($paulus_cite_setting['post_types']) ? (array)$paulus_cite_setting['post_types'] : array('post');
    if (!in_array(get_post_type(), $allowed_types, true)) {
        return $content;
    }
    
    if (!empty($paulus_cite_setting['excluded_posts'])) {
        $excluded = array_map('absint', array_map('trim', explode(',', $paulus_cite_setting['excluded_posts'])));
        if (in_array(absint($post->ID), $excluded, true)) {
            return $content;
        }
    }
    
    if (!is_singular()) {
        return $content;
    }
    
    $citation = do_shortcode('[cite]');
    $position = isset($paulus_cite_setting['display_position']) ? $paulus_cite_setting['display_position'] : 'bottom';
    
    if ($position === 'top') {
        return $citation . $content;
    } else {
        return $content . $citation;
    }
}

/**
 * FIX #7: Returns false when a major SEO plugin is active that will output
 * its own og:type, article:* properties, and/or ScholarlyArticle JSON-LD.
 * Developers can override with the paulus_cite_output_seo_meta filter.
 */
function paulus_cite_seo_plugin_active() {
    // Paulus prints its own Open Graph tags and JSON-LD graph (inc/seo.php),
    // so Citation adds neither; the Google Scholar tags are still added
    // (inc/cite.php turns the guard off for them).
    if ( function_exists( 'paulus_structured_data' ) ) { return true; }
    if ( class_exists( 'WPSEO_Frontend' ) )          { return true; } // Yoast SEO
    if ( class_exists( 'RankMath' ) )                { return true; } // RankMath
    if ( class_exists( 'AIOSEO_L10n' ) )             { return true; } // All-in-One SEO
    if ( class_exists( 'The_SEO_Framework\Load' ) )  { return true; } // The SEO Framework
    return false;
}

// Add Google Scholar meta tags
add_action('wp_head', 'paulus_cite_add_google_scholar_tags', 5);

function paulus_cite_add_google_scholar_tags() {
    global $post, $paulus_cite_setting;
    
    if (!is_singular() || !isset($paulus_cite_setting['show_google_scholar']) || $paulus_cite_setting['show_google_scholar'] !== 'yes') {
        return;
    }
    // FIX #7: Skip if a supported SEO plugin is managing <head> meta tags,
    // unless the site owner explicitly overrides via filter.
    if ( paulus_cite_seo_plugin_active() && apply_filters( 'paulus_cite_output_scholar_meta', false ) === false ) {
        return;
    }
    
    if (!isset($post) || !is_object($post)) {
        return;
    }
    
    // Only output on enabled post types
    $allowed_types = isset($paulus_cite_setting['post_types']) ? (array)$paulus_cite_setting['post_types'] : array('post');
    if (!in_array(get_post_type(), $allowed_types, true)) {
        return;
    }
    
    $authors = paulus_cite_get_authors_array();
    $title = html_entity_decode(get_the_title(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $date = get_the_date('Y/m/d');
    $url = get_permalink();
    $orcid = paulus_cite_get_orcid();
    $site_name = html_entity_decode(get_bloginfo('name'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $locale = get_locale();
    $language = substr($locale, 0, 2);
    
    /**
     * Filter Google Scholar meta tag data before output.
     *
     * @param array $meta_data Array of meta tag key => value pairs.
     * @param WP_Post $post The current post object.
     */
    $meta_data = apply_filters('paulus_cite_scholar_meta', array(
        'citation_title' => $title,
        'citation_authors' => $authors,
        'citation_publication_date' => $date,
        'citation_journal_title' => $site_name,
        'citation_public_url' => $url,
        'citation_language' => $language,
        'citation_author_orcid' => $orcid,
    ), $post);
    
    echo "\n<!-- Cite Plugin: Google Scholar Meta Tags -->\n";
    echo '<meta name="citation_title" content="' . esc_attr($meta_data['citation_title']) . '">' . "\n";
    
    // Output one citation_author tag per author
    if (!empty($meta_data['citation_authors']) && is_array($meta_data['citation_authors'])) {
        foreach ($meta_data['citation_authors'] as $author) {
            echo '<meta name="citation_author" content="' . esc_attr($author) . '">' . "\n";
        }
    }
    
    echo '<meta name="citation_publication_date" content="' . esc_attr($meta_data['citation_publication_date']) . '">' . "\n";
    echo '<meta name="citation_journal_title" content="' . esc_attr($meta_data['citation_journal_title']) . '">' . "\n";
    echo '<meta name="citation_public_url" content="' . esc_url($meta_data['citation_public_url']) . '">' . "\n";
    echo '<meta name="citation_language" content="' . esc_attr($meta_data['citation_language']) . '">' . "\n";
    
    if (!empty($meta_data['citation_author_orcid'])) {
        echo '<meta name="citation_author_orcid" content="' . esc_attr($meta_data['citation_author_orcid']) . '">' . "\n";
    }
    
    echo '<meta name="citation_fulltext_html_url" content="' . esc_url($url) . '">' . "\n";
    echo "<!-- End Google Scholar Meta Tags -->\n\n";
}

// Add JSON-LD ScholarlyArticle structured data
add_action('wp_head', 'paulus_cite_add_jsonld_structured_data', 10);

function paulus_cite_add_jsonld_structured_data() {
    global $post, $paulus_cite_setting;
    
    if (!is_singular() || !isset($paulus_cite_setting['show_jsonld']) || $paulus_cite_setting['show_jsonld'] !== 'yes') {
        return;
    }
    // FIX #7: Skip if SEO plugin already outputs ScholarlyArticle / Article JSON-LD.
    if ( paulus_cite_seo_plugin_active() && apply_filters( 'paulus_cite_output_jsonld', false ) === false ) {
        return;
    }
    
    if (!isset($post) || !is_object($post)) {
        return;
    }
    
    // Only output on enabled post types
    $allowed_types = isset($paulus_cite_setting['post_types']) ? (array)$paulus_cite_setting['post_types'] : array('post');
    if (!in_array(get_post_type(), $allowed_types, true)) {
        return;
    }
    
    $authors = paulus_cite_get_authors_array();
    $title = html_entity_decode(get_the_title(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $permalink = get_permalink();
    $site_name = html_entity_decode(get_bloginfo('name'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $date_published = get_the_date('c');
    $date_modified = get_the_modified_date('c');
    
    // Build author array for schema
    $schema_authors = array();
    foreach ($authors as $author_name) {
        $schema_authors[] = array(
            '@type' => 'Person',
            'name' => $author_name,
        );
    }
    // Single author: use object; multiple: use array
    $author_schema = (count($schema_authors) === 1) ? $schema_authors[0] : $schema_authors;
    
    // Build the JSON-LD data
    $jsonld = array(
        '@context' => 'https://schema.org',
        '@type' => 'ScholarlyArticle',
        'headline' => $title,
        'author' => $author_schema,
        'datePublished' => $date_published,
        'dateModified' => $date_modified,
        'publisher' => array(
            '@type' => 'Organization',
            'name' => $site_name,
        ),
        'url' => $permalink,
        'mainEntityOfPage' => array(
            '@type' => 'WebPage',
            '@id' => $permalink,
        ),
        'isPartOf' => array(
            '@type' => 'WebSite',
            'name' => $site_name,
            'url' => home_url('/'),
        ),
    );
    
    // Add optional description from excerpt
    $excerpt = get_the_excerpt($post);
    if (!empty($excerpt)) {
        $jsonld['description'] = wp_strip_all_tags($excerpt);
    }
    
    // Add featured image if available
    $thumbnail_id = get_post_thumbnail_id($post->ID);
    if ($thumbnail_id) {
        $image_url = wp_get_attachment_url($thumbnail_id);
        if ($image_url) {
            $jsonld['image'] = $image_url;
        }
    }
    
    // Add publisher logo if available (custom logo)
    $custom_logo_id = get_theme_mod('custom_logo');
    if ($custom_logo_id) {
        $logo_url = wp_get_attachment_url($custom_logo_id);
        if ($logo_url) {
            $jsonld['publisher']['logo'] = array(
                '@type' => 'ImageObject',
                'url' => $logo_url,
            );
        }
    }
    
    // Add language
    $locale = get_locale();
    $jsonld['inLanguage'] = substr($locale, 0, 2);
    
    /**
     * Filter JSON-LD structured data before output.
     *
     * @param array   $jsonld The JSON-LD data array.
     * @param WP_Post $post   The current post object.
     */
    $jsonld = apply_filters('paulus_cite_jsonld_data', $jsonld, $post);
    
    $output = wp_json_encode($jsonld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
    if ($output) {
        echo "\n<!-- Cite Plugin: JSON-LD Structured Data -->\n";
        echo '<script type="application/ld+json">' . "\n";
        echo wp_json_encode( $jsonld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) . "\n";
        echo '</script>' . "\n";
        echo "<!-- End JSON-LD Structured Data -->\n\n";
    }
}

// Add Open Graph academic tags
add_action('wp_head', 'paulus_cite_add_og_academic_tags', 5);

function paulus_cite_add_og_academic_tags() {
    global $post, $paulus_cite_setting;
    
    if (!is_singular() || !isset($paulus_cite_setting['show_og_academic']) || $paulus_cite_setting['show_og_academic'] !== 'yes') {
        return;
    }
    // FIX #7: og:type and article:* tags from SEO plugins would duplicate these.
    if ( paulus_cite_seo_plugin_active() && apply_filters( 'paulus_cite_output_og_tags', false ) === false ) {
        return;
    }
    
    if (!isset($post) || !is_object($post)) {
        return;
    }
    
    // Only output on enabled post types
    $allowed_types = isset($paulus_cite_setting['post_types']) ? (array)$paulus_cite_setting['post_types'] : array('post');
    if (!in_array(get_post_type(), $allowed_types, true)) {
        return;
    }
    
    $date_published = get_the_date('c');
    $date_modified = get_the_modified_date('c');
    $author_id = absint($post->post_author);
    $author_url = get_author_posts_url($author_id);
    
    // Get primary category
    $categories = get_the_category($post->ID);
    $primary_category = !empty($categories) ? $categories[0]->name : '';
    
    /**
     * Filter Open Graph academic tag data before output.
     *
     * @param array   $og_data Array of OG property => content pairs.
     * @param WP_Post $post    The current post object.
     */
    $og_data = apply_filters('paulus_cite_og_academic_data', array(
        'og:type' => 'article',
        'article:published_time' => $date_published,
        'article:modified_time' => $date_modified,
        'article:author' => $author_url,
        'article:section' => $primary_category,
    ), $post);
    
    echo "\n<!-- Cite Plugin: Open Graph Academic Tags -->\n";
    
    foreach ($og_data as $property => $content) {
        if (!empty($content)) {
            echo '<meta property="' . esc_attr($property) . '" content="' . esc_attr($content) . '">' . "\n";
        }
    }
    
    echo "<!-- End Open Graph Academic Tags -->\n\n";
}

