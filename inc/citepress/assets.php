<?php
/**
 * Assets — enqueue scripts/styles and register Gutenberg block.
 *
 * FIX #4:  Block category 'common' → 'text' (renamed in WP 5.8).
 * FIX #5:  'style' => 'citepress-block-editor' → 'editor_style' so block-editor.css
 *          is not loaded on the public frontend.
 * FIX #9:  'wp-editor' → 'wp-block-editor' in block script dependencies
 *          ('wp-editor' deprecated since WP 5.8).
 * FIX #15: citepressSharedData object added to footer — contains styles, ajaxUrl,
 *          enableAnalytics, requireConsent. Per-instance citation_data no longer
 *          duplicates these values, shrinking the inline payload significantly.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action('wp_enqueue_scripts', 'citepress_enqueue_frontend_styles');

function citepress_enqueue_frontend_styles() {
    if (is_singular()) {
        wp_enqueue_style(
            'citepress-frontend',
            CITEPRESS_ASSETS_URL . 'css/citepress-frontend.css',
            array(),
            CITEPRESS_VERSION
        );
    }
}

// Enqueue frontend scripts
add_action('wp_enqueue_scripts', 'citepress_enqueue_frontend_scripts');

function citepress_enqueue_frontend_scripts() {
    if (is_singular()) {
        wp_enqueue_script('jquery');
        wp_enqueue_script(
            'citepress-frontend',
            CITEPRESS_ASSETS_URL . 'js/citepress-frontend.js',
            array('jquery'),
            CITEPRESS_VERSION,
            true
        );
    }
}

// Output citation data in footer
add_action('wp_footer', 'citepress_output_citation_data');

/**
 * FIX #15: Split into citepressSharedData (once per page) + citepressCitationData
 * (per-instance, without the 20-style templates or shared config).
 *
 * In v2.8.0 each citation_data entry duplicated the full styles object
 * (~3 KB × number of citation boxes). On a page with 10 boxes that was
 * ~30 KB of duplicated inline JSON. Now styles live only in citepressSharedData.
 */
function citepress_output_citation_data() {
    global $citepress_citation_data, $citepress_setting;
    
    if (empty($citepress_citation_data)) {
        return;
    }
    
    $shared = array(
        'styles'         => citepress_get_citation_styles(),
        'ajaxUrl'        => esc_url(admin_url('admin-ajax.php')),
        'enableAnalytics' => (isset($citepress_setting['enable_analytics']) && $citepress_setting['enable_analytics'] === 'yes'),
        'requireConsent'  => (isset($citepress_setting['require_consent_for_analytics']) && $citepress_setting['require_consent_for_analytics'] === 'yes'),
    );
    ?>
    <script>
        window.citepressSharedData = <?php echo wp_json_encode($shared); ?>;
        window.citepressCitationData = <?php echo wp_json_encode($citepress_citation_data); ?>;
    </script>
    <?php
}

// Enqueue admin styles and scripts
add_action('admin_enqueue_scripts', 'citepress_enqueue_admin_assets');

function citepress_enqueue_admin_assets($hook) {
    if ('toplevel_page_citepress' !== $hook && 'citepress_page_citepress-analytics' !== $hook) {
        return;
    }
    
    wp_enqueue_style(
        'citepress-admin',
        CITEPRESS_ASSETS_URL . 'css/citepress-admin.css',
        array(),
        CITEPRESS_VERSION
    );
    
    wp_enqueue_script('jquery');
    
    // Enqueue admin JavaScript
    wp_enqueue_script(
        'citepress-admin',
        CITEPRESS_ASSETS_URL . 'js/citepress-admin.js',
        array('jquery'),
        CITEPRESS_VERSION,
        true
    );
    
    // Also load frontend styles for preview
    wp_enqueue_style(
        'citepress-frontend',
        CITEPRESS_ASSETS_URL . 'css/citepress-frontend.css',
        array(),
        CITEPRESS_VERSION
    );
    
    wp_enqueue_script(
        'citepress-frontend',
        CITEPRESS_ASSETS_URL . 'js/citepress-frontend.js',
        array('jquery'),
        CITEPRESS_VERSION,
        true
    );
}

// Enqueue block editor assets
add_action('enqueue_block_editor_assets', 'citepress_enqueue_block_editor_assets');

function citepress_enqueue_block_editor_assets() {
    wp_enqueue_script(
        'citepress-block',
        CITEPRESS_ASSETS_URL . 'js/citepress-block.js',
        // FIX #9: 'wp-editor' deprecated since WP 5.8; replaced with 'wp-block-editor'.
        array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-data'),
        CITEPRESS_VERSION,
        true
    );
    
    // Pass format options to block JS
    $format_metadata = citepress_get_format_metadata();
    $block_formats = array();
    // Only the enabled formats: the server renders against that list, so a
    // disabled one chosen in the block would silently become another style.
    $enabled_formats = citepress_get_enabled_formats();
    foreach ($format_metadata as $key => $meta) {
        if ( ! in_array( $key, $enabled_formats, true ) ) {
            continue;
        }
        $block_formats[] = array(
            'label' => $meta['name'],
            'value' => $key,
        );
    }

    // Pass citation style templates and site context for live editor preview
    $current_user = wp_get_current_user();
    wp_localize_script('citepress-block', 'citepressBlockData', array(
        'formats'         => $block_formats,
        'styles'          => citepress_get_citation_styles(),
        'siteName'        => html_entity_decode( get_bloginfo('name'), ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
        'siteUrl'         => home_url('/'),
        'language'        => citepress_get_language_code(),
        'currentUserName' => $current_user->display_name ?: __('Author', 'paulus'),
    ));
    
    wp_enqueue_style(
        'citepress-block-editor',
        CITEPRESS_ASSETS_URL . 'css/citepress-block-editor.css',
        array('wp-edit-blocks'),
        CITEPRESS_VERSION
    );
}

// Register block type
add_action('init', 'citepress_register_block_type');

function citepress_register_block_type() {
    register_block_type('citepress/block', array(
        'editor_script'   => 'citepress-block',
        'editor_style'    => 'citepress-block-editor',
        'attributes'      => array(
            'citationStyle'  => array('type' => 'string',  'default' => 'apa'),
            'mode'           => array('type' => 'string',  'default' => 'box'),
            'showExport'     => array('type' => 'boolean', 'default' => true),
            'showToggle'     => array('type' => 'boolean', 'default' => true),
            'showCopy'       => array('type' => 'boolean', 'default' => true),
            'showLink'       => array('type' => 'boolean', 'default' => true),
            'customAuthor'   => array('type' => 'string',  'default' => ''),
            'formats'        => array('type' => 'string',  'default' => ''),
            'excludeFormats' => array('type' => 'string',  'default' => ''),
            'page'           => array('type' => 'string',  'default' => ''),
        ),
        'render_callback' => 'citepress_render_citation_block',
    ));
}

/**
 * Server-side render callback for the Citation Block.
 * Translates block attributes into shortcode atts and calls citepress_display_citation().
 */
function citepress_render_citation_block($attributes) {
    $atts = array(
        'style'           => isset($attributes['citationStyle']) ? $attributes['citationStyle'] : 'apa',
        'mode'            => isset($attributes['mode']) ? $attributes['mode'] : 'box',
        'show_copy'       => (!isset($attributes['showCopy']) || $attributes['showCopy']) ? 'true' : 'false',
        'show_export'     => (!isset($attributes['showExport']) || $attributes['showExport']) ? 'true' : 'false',
        'show_toggle'     => (!isset($attributes['showToggle']) || $attributes['showToggle']) ? 'true' : 'false',
        'link'            => (!isset($attributes['showLink']) || $attributes['showLink']) ? 'true' : 'false',
        'custom_author'   => isset($attributes['customAuthor']) ? $attributes['customAuthor'] : '',
        'formats'         => isset($attributes['formats']) ? $attributes['formats'] : '',
        'exclude_formats' => isset($attributes['excludeFormats']) ? $attributes['excludeFormats'] : '',
        'page'            => isset($attributes['page']) ? $attributes['page'] : '',
    );

    return citepress_display_citation($atts);
}

