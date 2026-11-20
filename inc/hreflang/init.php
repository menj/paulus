<?php
/**
 * Hreflang – Initialisation
 *
 * @package Hreflang
 * @since   3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ── Includes ─────────────────────────────────────────────────────── */

$includes = array(
    'includes/notices.php',
    'includes/variables.php',
    'includes/functions.php',
);

foreach ( $includes as $inc ) {
    $path = PAULUS_HREFLANG_PATH . $inc;
    if ( file_exists( $path ) ) {
        require_once $path;
    }
}

/* ── Admin Assets ─────────────────────────────────────────────────── */

add_action( 'admin_enqueue_scripts', 'paulus_hreflang_enqueue_admin_assets' );

function paulus_hreflang_enqueue_admin_assets( $hook ) {
    if ( ! is_admin() || ! is_user_logged_in() ) {
        return;
    }

    // Load on our settings page and post editor screens only.
    $dominated = ( strpos( $hook, 'paulus' ) !== false ) || ( strpos( $hook, 'paulus-hreflang' ) !== false )
              || in_array( $hook, array( 'post.php', 'post-new.php', 'edit-tags.php', 'term.php' ), true );

    if ( ! $dominated ) {
        return;
    }

    wp_enqueue_style(
        'admin',
        PAULUS_URI . '/assets/css/paulus-hreflang-admin.css',
        array(),
        PAULUS_HREFLANG_VERSION
    );

    wp_enqueue_script(
        'admin',
        PAULUS_URI . '/assets/js/paulus-hreflang-admin.js',
        array( 'jquery' ),
        PAULUS_HREFLANG_VERSION,
        true
    );

    wp_localize_script( 'admin', 'paulusHreflang', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'paulus_hreflang_nonce' ),
        'i18n'     => array(
            'confirm_erase'      => __( 'Warning! This will permanently delete ALL hreflang tags from every post, page, and taxonomy term. This cannot be undone. Continue?', 'paulus' ),
            'confirm_bulk_save'  => __( 'Save all changes to hreflang tags?', 'paulus' ),
            'saved'              => __( 'Saved.', 'paulus' ),
            'error'              => __( 'An error occurred. Please try again.', 'paulus' ),
            'validating'         => __( 'Validating…', 'paulus' ),
            'valid'              => __( 'Valid', 'paulus' ),
            'invalid'            => __( 'Invalid', 'paulus' ),
            'copied'             => __( 'Copied to clipboard!', 'paulus' ),
        ),
    ) );

    wp_enqueue_script(
        'paulus-hreflang-notify',
        PAULUS_URI . '/assets/js/paulus-hreflang-notify.js',
        array( 'jquery' ),
        PAULUS_HREFLANG_VERSION,
        true
    );
}

/* ── Register Settings ────────────────────────────────────────────── */

add_action( 'admin_init', 'paulus_hreflang_register_settings' );

function paulus_hreflang_register_settings() {
    register_setting( 'paulus-hreflang-settings', 'paulus_hreflang_post_types', array(
        'type'              => 'array',
        'sanitize_callback' => 'paulus_hreflang_sanitize_post_types',
        'default'           => array(),
    ) );
    register_setting( 'paulus-hreflang-settings', 'paulus_hreflang_admin_bar', array(
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => 'false',
    ) );
    register_setting( 'paulus-hreflang-settings', 'paulus_hreflang_html_lang', array(
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => 'false',
    ) );
    register_setting( 'paulus-hreflang-settings', 'paulus_hreflang_woo_shop', array(
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => 'false',
    ) );
    register_setting( 'paulus-hreflang-settings', 'paulus_hreflang_scheme', array(
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => 'ocean',
    ) );
}

function paulus_hreflang_sanitize_post_types( $input ) {
    if ( ! is_array( $input ) ) {
        return array();
    }
    // The empty value the form sends so that "none" can be saved is dropped.
    return array_values( array_filter( array_map( 'sanitize_text_field', $input ), 'strlen' ) );
}

/* ── Admin Menu ───────────────────────────────────────────────────── */

add_action( 'admin_menu', 'paulus_hreflang_admin_menu' );

function paulus_hreflang_admin_menu() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    add_menu_page(
        __( 'Hreflang', 'paulus' ),
        __( 'Hreflang', 'paulus' ),
        'manage_options',
        'paulus-hreflang',
        'paulus_hreflang_render_admin_page',
        'dashicons-translation',
        80
    );
}

function paulus_hreflang_render_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Unauthorised.', 'paulus' ) );
    }
    include_once PAULUS_HREFLANG_PATH . 'admin.php';
}

/* ── Admin Bar Link ───────────────────────────────────────────────── */

if ( get_option( 'paulus_hreflang_admin_bar', 'false' ) === 'true' ) {
    add_action( 'wp_before_admin_bar_render', 'paulus_hreflang_admin_bar' );
}

function paulus_hreflang_admin_bar() {
    if ( ! is_admin() ) {
        return;
    }
    global $wp_admin_bar;
    $wp_admin_bar->add_node( array(
        'id'    => 'paulus-hreflang',
        'title' => '<span class="ab-icon dashicons dashicons-translation" style="margin-top:2px"></span> Hreflang',
        'href'  => admin_url( 'admin.php?page=paulus-hreflang' ),
    ) );
}

/* ── Front-end: Inject <link rel="alternate"> ─────────────────────── */

add_action( 'wp_head', 'paulus_hreflang_output_tags', 1 );

function paulus_hreflang_output_tags() {
    global $post;

    $version_comment = '';

    // Taxonomy archives.
    if ( is_category() || is_tax() || is_tag() ) {
        $term = get_queried_object();
        if ( $term ) {
            echo $version_comment;
            paulus_hreflang_print_term_tags( $term->term_id );
            echo '<!-- /Hreflang -->' . "\n";
        }
        return;
    }

    // WooCommerce shop page.
    if ( function_exists( 'is_shop' ) && is_shop() ) {
        if ( get_option( 'paulus_hreflang_woo_shop', 'false' ) === 'true' ) {
            $shop_id = wc_get_page_id( 'shop' );
            if ( $shop_id > 0 ) {
                echo $version_comment;
                paulus_hreflang_print_post_tags( $shop_id );
                echo '<!-- /Hreflang -->' . "\n";
            }
        }
        return;
    }

    // Exclude non-singular contexts.
    if ( is_home() || is_author() || is_tag() || is_search() || is_404() ) {
        return;
    }

    if ( $post ) {
        echo $version_comment;
        paulus_hreflang_print_post_tags( $post->ID );
        echo '<!-- /Hreflang -->' . "\n";
    }
}

function paulus_hreflang_print_post_tags( $post_id ) {
    $meta = get_post_meta( $post_id );
    paulus_hreflang_render_link_tags( $meta );
}

function paulus_hreflang_print_term_tags( $term_id ) {
    $meta = get_term_meta( $term_id );
    paulus_hreflang_render_link_tags( $meta );
}

function paulus_hreflang_render_link_tags( $meta ) {
    if ( ! is_array( $meta ) || empty( $meta ) ) {
        return;
    }
    foreach ( $meta as $key => $value ) {
        if ( stripos( $key, 'hreflang' ) === false ) {
            continue;
        }
        $parts = explode( '-', $key );
        $lang  = ( count( $parts ) === 3 ) ? 'x-default' : $parts[1];
        if ( $lang === 'Select one' || empty( $lang ) ) {
            continue;
        }
        $href = is_array( $value ) ? $value[0] : $value;
        printf(
            '<link rel="alternate" href="%s" hreflang="%s" />' . "\n",
            esc_url( $href ),
            esc_attr( str_replace( '_', '-', $lang ) )
        );
    }
}

/* ── Front-end: HTML Lang Attribute ───────────────────────────────── */

if ( get_option( 'paulus_hreflang_html_lang', 'false' ) === 'true' ) {
    add_filter( 'language_attributes', 'paulus_hreflang_html_lang_attribute' );
}

function paulus_hreflang_html_lang_attribute( $output ) {
    global $post;
    if ( ! $post ) {
        return $output;
    }
    $lang = get_post_meta( $post->ID, '_hrefmaster_html_lang', true );
    if ( $lang && ! empty( trim( $lang ) ) ) {
        $lang = str_replace( '_', '-', sanitize_text_field( $lang ) );
        $output = preg_replace( '/lang="[^"]*"/', 'lang="' . esc_attr( $lang ) . '"', $output );
    }
    return $output;
}

/* ── Meta Boxes ───────────────────────────────────────────────────── */

add_action( 'add_meta_boxes', 'paulus_hreflang_register_meta_boxes' );

function paulus_hreflang_register_meta_boxes() {
    $types = get_option( 'paulus_hreflang_post_types', array() );
    if ( ! is_array( $types ) || empty( $types ) ) {
        return;
    }
    foreach ( $types as $type ) {
        if ( post_type_exists( $type ) ) {
            add_meta_box(
                'paulus-hreflang-meta-box',
                __( 'Hreflang — Hreflang Tags', 'paulus' ),
                'paulus_hreflang_meta_box_callback',
                $type,
                'advanced',
                'high'
            );

            // HTML lang attribute box.
            if ( get_option( 'paulus_hreflang_html_lang', 'false' ) === 'true' ) {
                add_meta_box(
                    'paulus-hreflang-html-lang-box',
                    __( 'Hreflang — HTML Language', 'paulus' ),
                    'paulus_hreflang_html_lang_meta_box',
                    $type,
                    'side',
                    'default'
                );
            }
        }
    }
}

function paulus_hreflang_meta_box_callback( $post ) {
    global $paulus_hreflang_languages;
    wp_nonce_field( 'paulus_hreflang_save_meta', 'paulus_hreflang_nonce' );

    $keys   = array();
    $values = array();
    $meta   = get_post_meta( $post->ID );

    if ( is_array( $meta ) && ! empty( $meta ) ) {
        foreach ( $meta as $key => $value ) {
            if ( stripos( $key, 'hreflang' ) !== false ) {
                $keys[]   = $key;
                $values[] = $value;
            }
        }
    }

    echo '<div class="paulus-hreflang-metabox-wrap"><div class="href-container">';

    if ( empty( $keys ) ) {
        paulus_hreflang_render_tag_row( 1, '', '', $paulus_hreflang_languages );
    } else {
        $n = 0;
        foreach ( $keys as $key ) {
            // The code is whatever follows 'hreflang-': en, en-US, x-default.
            $href_lang = substr( $key, strlen( 'hreflang-' ) );
            $href_val  = isset( $values[ $n ][0] ) ? $values[ $n ][0] : '';
            paulus_hreflang_render_tag_row( $n + 1, $href_val, $href_lang, $paulus_hreflang_languages, ( $n + 1 === count( $keys ) ) );
            $n++;
        }
    }

    echo '</div></div>';
}

function paulus_hreflang_render_tag_row( $index, $href, $selected_lang, $languages, $is_last = true ) {
    echo '<div id="hreflang-' . intval( $index ) . '" class="href-lang paulus-hreflang-row">';
    echo '<label>' . esc_html__( 'URL', 'paulus' ) . '</label>';
    echo '<input name="hreflang-href[]" type="url" value="' . esc_attr( $href ) . '" placeholder="https://" class="paulus-hreflang-url-input" />';
    echo '<label>' . esc_html__( 'Language', 'paulus' ) . '</label>';
    echo '<select name="hreflang-lang[]" class="paulus-hreflang-lang-select">';
    echo '<option value="">' . esc_html__( 'Select one', 'paulus' ) . '</option>';
    if ( is_array( $languages ) && ! empty( $languages ) ) {
        foreach ( $languages as $lang => $data ) {
            $name = isset( $data['english_name'] ) ? $data['english_name'] : $lang;
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr( $lang ),
                selected( $lang, $selected_lang, false ),
                esc_html( $name )
            );
        }
    }
    echo '</select>';
    if ( $is_last ) {
        echo '<button type="button" class="button add-new-hreflang-tag" title="' . esc_attr__( 'Add', 'paulus' ) . '"><span class="dashicons dashicons-plus-alt2"></span></button>';
        if ( $index > 1 ) {
            echo ' <button type="button" class="button remove-new-hreflang-tag" title="' . esc_attr__( 'Remove', 'paulus' ) . '"><span class="dashicons dashicons-minus"></span></button>';
        }
    }
    echo '</div>';
}

function paulus_hreflang_html_lang_meta_box( $post ) {
    $lang = get_post_meta( $post->ID, '_hrefmaster_html_lang', true );
    echo '<label for="paulus_hreflang_html_lang">' . esc_html__( 'Override the HTML lang attribute for this page:', 'paulus' ) . '</label>';
    echo '<input type="text" id="paulus_hreflang_html_lang" name="paulus_hreflang_html_lang" value="' . esc_attr( $lang ) . '" placeholder="en-US" class="widefat" style="margin-top:6px" />';
    echo '<p class="description">' . esc_html__( 'e.g. en-US, fr-FR, de-DE. Leave blank to use the site default.', 'paulus' ) . '</p>';
}

/* ── Save Post Meta ───────────────────────────────────────────────── */

add_action( 'save_post', 'paulus_hreflang_save_post_meta', 10, 2 );

function paulus_hreflang_save_post_meta( $post_id, $post ) {
    if ( ! isset( $_POST['paulus_hreflang_nonce'] )
         || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['paulus_hreflang_nonce'] ) ), 'paulus_hreflang_save_meta' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // Delete existing hreflang keys first.
    $existing = get_post_meta( $post_id );
    if ( is_array( $existing ) ) {
        foreach ( $existing as $key => $val ) {
            if ( stripos( $key, 'hreflang' ) !== false ) {
                delete_post_meta( $post_id, $key );
            }
        }
    }

    // Save new.
    if ( isset( $_POST['hreflang-href'] ) && is_array( $_POST['hreflang-href'] ) ) {
        $hrefs = array_map( 'sanitize_text_field', wp_unslash( $_POST['hreflang-href'] ) );
        $langs = isset( $_POST['hreflang-lang'] )
            ? array_map( 'sanitize_text_field', wp_unslash( $_POST['hreflang-lang'] ) )
            : array();
        foreach ( $hrefs as $i => $href ) {
            $href = esc_url_raw( trim( $href ) );
            $lang = isset( $langs[ $i ] ) ? $langs[ $i ] : '';
            if ( empty( $href ) || empty( $lang ) || ! paulus_hreflang_is_valid_lang_code( $lang ) ) {
                continue;
            }
            update_post_meta( $post_id, 'hreflang-' . $lang, $href );
        }
    }

    // HTML lang.
    if ( isset( $_POST['paulus_hreflang_html_lang'] ) ) {
        $html_lang = sanitize_text_field( wp_unslash( $_POST['paulus_hreflang_html_lang'] ) );
        update_post_meta( $post_id, '_hrefmaster_html_lang', $html_lang );
    }
}

/* ── Save Term Meta ───────────────────────────────────────────────── */

add_action( 'create_term', 'paulus_hreflang_save_term_meta' );
add_action( 'edit_term',   'paulus_hreflang_save_term_meta', 10, 3 );

function paulus_hreflang_save_term_meta( $term_id, $tt_id = 0, $taxonomy = '' ) {
    if ( ! isset( $_POST['hreflang-href'] ) || ! is_array( $_POST['hreflang-href'] ) ) {
        return;
    }
    // The taxonomy's own capability, so editors of a custom taxonomy can save.
    $taxonomy_object = $taxonomy ? get_taxonomy( $taxonomy ) : null;
    $capability      = ( $taxonomy_object && ! empty( $taxonomy_object->cap->edit_terms ) ) ? $taxonomy_object->cap->edit_terms : 'manage_categories';
    if ( ! current_user_can( $capability ) ) {
        return;
    }

    // Verify nonce (shared with post meta box or taxonomy form).
    $nonce_valid = false;
    if ( isset( $_POST['paulus_hreflang_nonce'] ) ) {
        $nonce_valid = wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['paulus_hreflang_nonce'] ) ), 'paulus_hreflang_save_meta' );
    }
    if ( isset( $_POST['paulus_hreflang_term_nonce'] ) ) {
        $nonce_valid = wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['paulus_hreflang_term_nonce'] ) ), 'paulus_hreflang_save_term_meta' );
    }
    if ( ! $nonce_valid ) {
        return;
    }

    // Delete existing.
    $existing = get_term_meta( $term_id );
    if ( is_array( $existing ) ) {
        foreach ( $existing as $key => $val ) {
            if ( stripos( $key, 'hreflang' ) !== false ) {
                delete_term_meta( $term_id, $key );
            }
        }
    }

    // Save new.
    $hrefs = array_map( 'sanitize_text_field', wp_unslash( $_POST['hreflang-href'] ) );
    $langs = isset( $_POST['hreflang-lang'] )
        ? array_map( 'sanitize_text_field', wp_unslash( $_POST['hreflang-lang'] ) )
        : array();
    foreach ( $hrefs as $i => $href ) {
        $href = esc_url_raw( trim( $href ) );
        $lang = isset( $langs[ $i ] ) ? $langs[ $i ] : '';
        if ( empty( $href ) || empty( $lang ) || ! paulus_hreflang_is_valid_lang_code( $lang ) ) {
            continue;
        }
        update_term_meta( $term_id, 'hreflang-' . $lang, $href );
    }
}

/* ── Taxonomy Form Fields ─────────────────────────────────────────── */

add_action( 'init', 'paulus_hreflang_register_taxonomy_fields', 99 );

function paulus_hreflang_register_taxonomy_fields() {
    $types = get_option( 'paulus_hreflang_post_types', array() );
    if ( ! is_array( $types ) || empty( $types ) ) {
        return;
    }
    foreach ( $types as $type ) {
        if ( ! post_type_exists( $type ) ) {
            add_action( $type . '_add_form_fields',  'paulus_hreflang_taxonomy_add_form', 99 );
            add_action( $type . '_edit_form_fields', 'paulus_hreflang_taxonomy_edit_form', 10, 1 );
        }
    }
}

function paulus_hreflang_taxonomy_add_form() {
    global $paulus_hreflang_languages;
    wp_nonce_field( 'paulus_hreflang_save_term_meta', 'paulus_hreflang_term_nonce' );
    echo '<div class="href-container-cat">';
    echo '<h3>' . esc_html__( 'Hreflang — Hreflang Tags', 'paulus' ) . '</h3>';
    paulus_hreflang_render_cat_row( 1, '', '', $paulus_hreflang_languages, true, 'cat' );
    echo '</div><br><br>';
}

function paulus_hreflang_taxonomy_edit_form( $term ) {
    global $paulus_hreflang_languages;
    $keys   = array();
    $values = array();
    $meta   = get_term_meta( $term->term_id );

    if ( is_array( $meta ) && ! empty( $meta ) ) {
        foreach ( $meta as $key => $value ) {
            if ( stripos( $key, 'hreflang' ) !== false ) {
                $keys[]   = $key;
                $values[] = $value;
            }
        }
    }

    echo '<tr class="term-hreflang-wrap"><th scope="row"><label>' . esc_html__( 'Hreflang — Hreflang Tags', 'paulus' ) . '</label></th>';
    echo '<td class="term-hreflang-data">';
    wp_nonce_field( 'paulus_hreflang_save_term_meta', 'paulus_hreflang_term_nonce' );

    if ( empty( $keys ) ) {
        paulus_hreflang_render_cat_row( 1, '', '', $paulus_hreflang_languages, true, 'cat-edit' );
    } else {
        $n = 0;
        foreach ( $keys as $key ) {
            $parts     = explode( '-', $key );
            $href_lang = ( count( $parts ) === 3 ) ? 'x-default' : $parts[1];
            $href_val  = isset( $values[ $n ][0] ) ? $values[ $n ][0] : '';
            paulus_hreflang_render_cat_row( $n + 1, $href_val, $href_lang, $paulus_hreflang_languages, ( $n + 1 === count( $keys ) ), 'cat-edit' );
            $n++;
        }
    }
    echo '</td></tr>';
}

function paulus_hreflang_render_cat_row( $index, $href, $selected_lang, $languages, $is_last, $prefix ) {
    echo '<div id="hreflang-' . esc_attr( $prefix ) . '-' . intval( $index ) . '" class="href-lang paulus-hreflang-row">';
    echo '<label>' . esc_html__( 'URL', 'paulus' ) . '</label>';
    echo '<input name="hreflang-href[]" type="url" value="' . esc_attr( $href ) . '" placeholder="https://" />';
    echo '<label>' . esc_html__( 'Language', 'paulus' ) . '</label>';
    echo '<select name="hreflang-lang[]">';
    echo '<option value="">' . esc_html__( 'Select one', 'paulus' ) . '</option>';
    if ( is_array( $languages ) ) {
        foreach ( $languages as $lang => $data ) {
            $name = isset( $data['english_name'] ) ? $data['english_name'] : $lang;
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr( $lang ),
                selected( $lang, $selected_lang, false ),
                esc_html( $name )
            );
        }
    }
    echo '</select>';
    if ( $is_last ) {
        echo '<button type="button" class="button add-new-' . esc_attr( $prefix ) . '-hreflang-tag"><span class="dashicons dashicons-plus-alt2"></span></button>';
        if ( $index > 1 ) {
            echo ' <button type="button" class="button remove-new-' . esc_attr( $prefix ) . '-hreflang-tag"><span class="dashicons dashicons-minus"></span></button>';
        }
    }
    echo '</div>';
}

/* ── AJAX: Delete All Data ────────────────────────────────────────── */

add_action( 'wp_ajax_paulus_hreflang_delete_all_data', 'paulus_hreflang_ajax_delete_all' );

function paulus_hreflang_ajax_delete_all() {
    check_ajax_referer( 'paulus_hreflang_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( __( 'Unauthorised.', 'paulus' ) );
    }

    $all_types  = get_option( 'paulus_hreflang_post_types', array() );
    $post_types = array();
    $tax_types  = array();
    $taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );

    foreach ( $taxonomies as $tax ) {
        if ( in_array( $tax->name, $all_types, true ) ) {
            $tax_types[] = $tax->name;
        }
    }
    $post_types = array_diff( $all_types, $tax_types );

    $n = 0;

    // Posts.
    if ( ! empty( $post_types ) ) {
        $query = new WP_Query( array(
            'post_type'      => $post_types,
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ) );
        foreach ( $query->posts as $pid ) {
            $meta = get_post_meta( $pid );
            if ( is_array( $meta ) ) {
                foreach ( $meta as $key => $val ) {
                    if ( stripos( $key, 'hreflang-' ) !== false ) {
                        if ( delete_post_meta( $pid, $key ) ) {
                            $n++;
                        }
                    }
                }
            }
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
                if ( is_array( $meta ) ) {
                    foreach ( $meta as $key => $val ) {
                        if ( stripos( $key, 'hreflang-' ) !== false ) {
                            if ( delete_term_meta( $term->term_id, $key ) ) {
                                $n++;
                            }
                        }
                    }
                }
            }
        }
    }

    wp_send_json_success( array(
        /* translators: %d: number of deleted tags */
        'message' => sprintf( __( '%d hreflang tags deleted.', 'paulus' ), $n ),
    ) );
}

/* ── AJAX: Bulk Save ──────────────────────────────────────────────── */

add_action( 'wp_ajax_paulus_hreflang_bulk_save', 'paulus_hreflang_ajax_bulk_save' );

function paulus_hreflang_ajax_bulk_save() {
    check_ajax_referer( 'paulus_hreflang_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( __( 'Unauthorised.', 'paulus' ) );
    }

    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- deep array; sanitised per-field below.
    $entries = isset( $_POST['entries'] ) ? wp_unslash( $_POST['entries'] ) : array();
    $saved   = 0;

    if ( ! is_array( $entries ) ) {
        wp_send_json_error( __( 'Invalid data.', 'paulus' ) );
    }

    foreach ( $entries as $entry ) {
        if ( ! is_array( $entry ) || ! isset( $entry['id'], $entry['type'] ) ) {
            continue;
        }

        $object_id   = absint( $entry['id'] );
        $object_type = sanitize_text_field( $entry['type'] );

        // Whitelist object type.
        if ( ! in_array( $object_type, array( 'post', 'term' ), true ) ) {
            continue;
        }

        $tags = isset( $entry['tags'] ) && is_array( $entry['tags'] ) ? $entry['tags'] : array();

        // Clear existing.
        if ( $object_type === 'post' ) {
            $meta = get_post_meta( $object_id );
            foreach ( (array) $meta as $k => $v ) {
                if ( stripos( $k, 'hreflang' ) !== false ) {
                    delete_post_meta( $object_id, $k );
                }
            }
        } else {
            $meta = get_term_meta( $object_id );
            foreach ( (array) $meta as $k => $v ) {
                if ( stripos( $k, 'hreflang' ) !== false ) {
                    delete_term_meta( $object_id, $k );
                }
            }
        }

        // Save new.
        foreach ( $tags as $tag ) {
            if ( ! is_array( $tag ) || ! isset( $tag['href'], $tag['lang'] ) ) {
                continue;
            }
            $href = esc_url_raw( trim( sanitize_text_field( $tag['href'] ) ) );
            $lang = sanitize_text_field( $tag['lang'] );
            if ( empty( $href ) || empty( $lang ) || ! paulus_hreflang_is_valid_lang_code( $lang ) ) {
                continue;
            }
            if ( $object_type === 'post' ) {
                update_post_meta( $object_id, 'hreflang-' . $lang, $href );
            } else {
                update_term_meta( $object_id, 'hreflang-' . $lang, $href );
            }
            $saved++;
        }
    }

    wp_send_json_success( array(
        /* translators: %d: number of saved tags */
        'message' => sprintf( __( '%d tags saved.', 'paulus' ), $saved ),
    ) );
}

/* ── AJAX: Validate URL ───────────────────────────────────────────── */

add_action( 'wp_ajax_paulus_hreflang_validate_url', 'paulus_hreflang_ajax_validate_url' );

function paulus_hreflang_ajax_validate_url() {
    check_ajax_referer( 'paulus_hreflang_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( __( 'Unauthorised.', 'paulus' ) );
    }

    $url = isset( $_POST['url'] )
        ? esc_url_raw( wp_unslash( $_POST['url'] ) )
        : '';
    if ( empty( $url ) ) {
        wp_send_json_error( __( 'No URL provided.', 'paulus' ) );
    }

    // SSRF protection: only allow http/https and block internal IPs.
    $parsed = wp_parse_url( $url );
    if ( ! $parsed || ! isset( $parsed['scheme'] ) || ! in_array( $parsed['scheme'], array( 'http', 'https' ), true ) ) {
        wp_send_json_error( __( 'Only http and https URLs are allowed.', 'paulus' ) );
    }
    if ( isset( $parsed['host'] ) ) {
        $host = strtolower( $parsed['host'] );
        $blocked = array( 'localhost', '127.0.0.1', '0.0.0.0', '[::1]' );
        if ( in_array( $host, $blocked, true )
             || preg_match( '/^(10\.|172\.(1[6-9]|2\d|3[01])\.|192\.168\.|169\.254\.)/', $host ) ) {
            wp_send_json_error( __( 'Internal or reserved IP addresses are not allowed.', 'paulus' ) );
        }
    }

    $response = wp_remote_get( $url, array(
        'timeout'   => 15,
        'sslverify' => true,
    ) );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( $response->get_error_message() );
    }

    $body = wp_remote_retrieve_body( $response );
    $code = wp_remote_retrieve_response_code( $response );

    // Parse hreflang tags from the fetched page.
    $found_tags = array();
    if ( preg_match_all( '/<link[^>]+hreflang=["\']([^"\']+)["\'][^>]+href=["\']([^"\']+)["\'][^>]*>/i', $body, $matches, PREG_SET_ORDER ) ) {
        foreach ( $matches as $m ) {
            $found_tags[] = array(
                'lang' => sanitize_text_field( $m[1] ),
                'href' => esc_url_raw( $m[2] ),
            );
        }
    }
    // Also try reverse attribute order.
    if ( preg_match_all( '/<link[^>]+href=["\']([^"\']+)["\'][^>]+hreflang=["\']([^"\']+)["\'][^>]*>/i', $body, $matches, PREG_SET_ORDER ) ) {
        foreach ( $matches as $m ) {
            $found_tags[] = array(
                'lang' => sanitize_text_field( $m[2] ),
                'href' => esc_url_raw( $m[1] ),
            );
        }
    }

    // Deduplicate.
    $seen = array();
    $unique_tags = array();
    foreach ( $found_tags as $tag ) {
        $key = $tag['lang'] . '|' . $tag['href'];
        if ( ! isset( $seen[ $key ] ) ) {
            $unique_tags[] = $tag;
            $seen[ $key ]  = true;
        }
    }

    wp_send_json_success( array(
        'status_code' => intval( $code ),
        'tags'        => $unique_tags,
        'url'         => esc_url( $url ),
    ) );
}

/* ── AJAX: Bulk Validate ──────────────────────────────────────────── */

add_action( 'wp_ajax_paulus_hreflang_bulk_validate', 'paulus_hreflang_ajax_bulk_validate' );

function paulus_hreflang_ajax_bulk_validate() {
    check_ajax_referer( 'paulus_hreflang_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( __( 'Unauthorised.', 'paulus' ) );
    }

    $all_types  = get_option( 'paulus_hreflang_post_types', array() );
    $results    = array();
    $post_types = array();
    $tax_types  = array();
    $taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );

    foreach ( $taxonomies as $tax ) {
        if ( in_array( $tax->name, $all_types, true ) ) {
            $tax_types[] = $tax->name;
        }
    }
    $post_types = array_diff( $all_types, $tax_types );

    if ( ! empty( $post_types ) ) {
        $query = new WP_Query( array(
            'post_type'      => array_values( $post_types ),
            'posts_per_page' => -1,
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
            if ( ! empty( $tags ) ) {
                $results[] = array(
                    'id'    => $p->ID,
                    'title' => $p->post_title,
                    'type'  => 'post',
                    'url'   => get_permalink( $p->ID ),
                    'tags'  => $tags,
                );
            }
        }
    }

    wp_send_json_success( array( 'items' => $results ) );
}

/* ── Version fix (legacy compat) ──────────────────────────────────── */

add_action( 'admin_init', 'paulus_hreflang_version_fix' );

function paulus_hreflang_version_fix() {
    // Migrate old option names if upgrading from legacy plugin.
    $old_types = get_option( 'hreflang_post_types' );
    if ( $old_types !== false && get_option( 'paulus_hreflang_post_types' ) === false ) {
        update_option( 'paulus_hreflang_post_types', $old_types );
    }

    // Fix legacy "categories" → "category".
    $types = get_option( 'paulus_hreflang_post_types', array() );
    if ( is_array( $types ) ) {
        $fixed = array_map( function ( $t ) {
            return $t === 'categories' ? 'category' : $t;
        }, $types );
        if ( $fixed !== $types ) {
            update_option( 'paulus_hreflang_post_types', $fixed );
        }
    }
}
