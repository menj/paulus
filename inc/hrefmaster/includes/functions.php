<?php
/**
 * HrefMaster – Helper Functions
 *
 * @package HrefMaster
 * @since   3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get all content items (posts + terms) that have hreflang tags.
 *
 * Used by Bulk Editor and Bulk Validation tabs.
 *
 * @return array
 */
function hrefmaster_get_all_tagged_items() {
    $all_types  = get_option( 'paulus_hreflang_post_types', array() );
    $items      = array();
    $post_types = array();
    $tax_types  = array();

    if ( ! is_array( $all_types ) || empty( $all_types ) ) {
        return $items;
    }

    $taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
    foreach ( $taxonomies as $tax ) {
        if ( in_array( $tax->name, $all_types, true ) ) {
            $tax_types[] = $tax->name;
        }
    }
    $post_types = array_values( array_diff( $all_types, $tax_types ) );

    // Posts.
    if ( ! empty( $post_types ) ) {
        $query = new WP_Query( array(
            'post_type'      => $post_types,
            'posts_per_page' => -1,
            'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
        ) );
        foreach ( $query->posts as $p ) {
            $meta = get_post_meta( $p->ID );
            $tags = array();
            foreach ( (array) $meta as $k => $v ) {
                if ( stripos( $k, 'hreflang' ) !== false ) {
                    $parts = explode( '-', $k );
                    $lang  = ( count( $parts ) === 3 ) ? 'x-default' : $parts[1];
                    $tags[] = array( 'lang' => $lang, 'href' => $v[0] );
                }
            }
            $items[] = array(
                'id'         => $p->ID,
                'title'      => $p->post_title,
                'type'       => 'post',
                'post_type'  => $p->post_type,
                'status'     => $p->post_status,
                'edit_url'   => get_edit_post_link( $p->ID, 'raw' ),
                'permalink'  => get_permalink( $p->ID ),
                'tags'       => $tags,
            );
        }
    }

    // Terms.
    if ( ! empty( $tax_types ) ) {
        $terms = get_terms( array(
            'taxonomy'   => $tax_types,
            'hide_empty' => false,
        ) );
        if ( is_array( $terms ) ) {
            foreach ( $terms as $term ) {
                $meta = get_term_meta( $term->term_id );
                $tags = array();
                foreach ( (array) $meta as $k => $v ) {
                    if ( stripos( $k, 'hreflang' ) !== false ) {
                        $parts = explode( '-', $k );
                        $lang  = ( count( $parts ) === 3 ) ? 'x-default' : $parts[1];
                        $tags[] = array( 'lang' => $lang, 'href' => $v[0] );
                    }
                }
                $items[] = array(
                    'id'        => $term->term_id,
                    'title'     => $term->name,
                    'type'      => 'term',
                    'taxonomy'  => $term->taxonomy,
                    'edit_url'  => get_edit_term_link( $term->term_id ),
                    'permalink' => get_term_link( $term ),
                    'tags'      => $tags,
                );
            }
        }
    }

    return $items;
}

/**
 * Validate an ISO 639-1 / ISO 3166-1 language-region code.
 *
 * @param string $code e.g. "en", "en-US", "x-default".
 * @return bool
 */
function hrefmaster_is_valid_lang_code( $code ) {
    if ( $code === 'x-default' ) {
        return true;
    }
    // ISO 639 language, optionally followed by region.
    return (bool) preg_match( '/^[a-z]{2,3}(-[A-Za-z]{2,4})?$/', str_replace( '_', '-', $code ) );
}

/**
 * Get colour scheme CSS variables for a given scheme name.
 *
 * @param string $scheme Scheme identifier.
 * @return array Associative array of CSS variable names => values.
 */
function hrefmaster_get_color_scheme( $scheme = 'ocean' ) {
    $schemes = array(
        'ocean' => array(
            '--hm-primary'    => '#0f5e8a',
            '--hm-primary-lt' => '#e8f2f8',
            '--hm-accent'     => '#f97316',
            '--hm-accent-lt'  => '#fff5eb',
            '--hm-success'    => '#16a34a',
            '--hm-danger'     => '#dc2626',
            '--hm-bg'         => '#f8fafc',
            '--hm-card'       => '#ffffff',
            '--hm-border'     => '#e2e8f0',
            '--hm-text'       => '#1e293b',
            '--hm-muted'      => '#64748b',
        ),
        'slate' => array(
            '--hm-primary'    => '#475569',
            '--hm-primary-lt' => '#f1f5f9',
            '--hm-accent'     => '#6366f1',
            '--hm-accent-lt'  => '#eef2ff',
            '--hm-success'    => '#16a34a',
            '--hm-danger'     => '#dc2626',
            '--hm-bg'         => '#f8fafc',
            '--hm-card'       => '#ffffff',
            '--hm-border'     => '#e2e8f0',
            '--hm-text'       => '#0f172a',
            '--hm-muted'      => '#64748b',
        ),
        'emerald' => array(
            '--hm-primary'    => '#065f46',
            '--hm-primary-lt' => '#ecfdf5',
            '--hm-accent'     => '#d97706',
            '--hm-accent-lt'  => '#fffbeb',
            '--hm-success'    => '#16a34a',
            '--hm-danger'     => '#dc2626',
            '--hm-bg'         => '#f8faf9',
            '--hm-card'       => '#ffffff',
            '--hm-border'     => '#d1e7dd',
            '--hm-text'       => '#1a2e25',
            '--hm-muted'      => '#5a7a6e',
        ),
        'midnight' => array(
            '--hm-primary'    => '#7c3aed',
            '--hm-primary-lt' => '#f5f3ff',
            '--hm-accent'     => '#ec4899',
            '--hm-accent-lt'  => '#fdf2f8',
            '--hm-success'    => '#16a34a',
            '--hm-danger'     => '#dc2626',
            '--hm-bg'         => '#faf5ff',
            '--hm-card'       => '#ffffff',
            '--hm-border'     => '#e9d5ff',
            '--hm-text'       => '#1e1b3a',
            '--hm-muted'      => '#6b668a',
        ),
    );

    return isset( $schemes[ $scheme ] ) ? $schemes[ $scheme ] : $schemes['ocean'];
}
