<?php
/**
 * Endmark Admin
 *
 * Handles admin menu, settings registration, sanitisation,
 * and asset enqueueing for the settings page.
 *
 * @package Endmark
 * @since   4.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Endmark_Admin {

    /**
     * Reference to the main plugin instance.
     *
     * @var Endmark_Plugin
     */
    private $plugin;

    /**
     * Constructor.
     *
     * @param Endmark_Plugin $plugin Main plugin instance.
     */
    public function __construct( Endmark_Plugin $plugin ) {
        $this->plugin = $plugin;

        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    /* ======================================================================
       Menu & Links
       ====================================================================== */

    /**
     * Add Settings → Endmark page.
     */
    public function add_admin_menu() {
        add_options_page(
            __( 'Endmark Settings', 'paulus' ),
            __( 'Endmark', 'paulus' ),
            'manage_options',
            'endmark',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Add quick-link on the Plugins list table.
     *
     * @param  array $links Existing action links.
     * @return array
     */
    public function add_settings_link( $links ) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url( 'options-general.php?page=endmark' ),
            esc_html__( 'Settings', 'paulus' )
        );
        array_unshift( $links, $settings_link );
        return $links;
    }

    /* ======================================================================
       Assets
       ====================================================================== */

    /**
     * Enqueue admin CSS and JS on the settings page only.
     *
     * @param string $hook The current admin page hook.
     */
    public function enqueue_admin_assets( $hook ) {
        if ( 'settings_page_endmark' !== $hook ) {
            return;
        }

        wp_enqueue_media();

        // Admin CSS.
        wp_enqueue_style(
            'endmark-admin',
            PAULUS_URI . '/assets/css/endmark-admin.css',
            array(),
            Endmark_Plugin::VERSION
        );

        // Admin JavaScript.
        wp_enqueue_script(
            'endmark-admin',
            PAULUS_URI . '/assets/js/endmark-admin.js',
            array( 'jquery' ),
            Endmark_Plugin::VERSION,
            true
        );

        wp_localize_script( 'endmark-admin', 'endmarkAdmin', array(
            'optionName' => Endmark_Plugin::OPTION_SITE,
            'i18n'       => array(
                'selectImage' => __( 'Select Endmark Image', 'paulus' ),
                'useImage'    => __( 'Use this image', 'paulus' ),
            ),
        ) );
    }

    /* ======================================================================
       Settings Registration
       ====================================================================== */

    /**
     * Register the settings group and sanitisation callback.
     */
    public function register_settings() {
        register_setting(
            'endmark_settings_group',
            Endmark_Plugin::OPTION_SITE,
            array(
                'type'              => 'array',
                'sanitize_callback' => array( $this, 'sanitize_settings' ),
                'default'           => $this->plugin->get_site_defaults(),
            )
        );
    }

    /* ======================================================================
       Sanitisation
       ====================================================================== */

    /**
     * Sanitise settings input.
     *
     * @param  array $input Raw form input.
     * @return array        Sanitised settings.
     */
    public function sanitize_settings( $input ) {
        $sanitized  = array();
        $allowlists = $this->plugin->get_allowlists();

        // Boolean fields.
        $sanitized['enabled']          = ! empty( $input['enabled'] );
        $sanitized['hide_on_mobile']   = ! empty( $input['hide_on_mobile'] );
        $sanitized['disable_on_amp']   = ! empty( $input['disable_on_amp'] );
        $sanitized['use_css_variables'] = ! empty( $input['use_css_variables'] );
        $sanitized['schema_enabled']   = ! empty( $input['schema_enabled'] );

        // Allowlist-validated fields.
        $sanitized['post_types'] = $this->sanitize_post_types( $input );

        $sanitized['type'] = isset( $input['type'] ) && in_array( $input['type'], $allowlists['type'], true )
            ? $input['type']
            : 'symbol';

        $sanitized['placement_mode'] = isset( $input['placement_mode'] ) && in_array( $input['placement_mode'], $allowlists['placement_mode'], true )
            ? $input['placement_mode']
            : 'last_paragraph';

        $sanitized['schema_mode'] = isset( $input['schema_mode'] ) && in_array( $input['schema_mode'], $allowlists['schema_mode'], true )
            ? $input['schema_mode']
            : 'WebPageElement';

        // Symbol — sanitise and limit to 12 characters.
        $symbol                = isset( $input['symbol'] ) ? sanitize_text_field( $input['symbol'] ) : '∎';
        $sanitized['symbol']   = mb_substr( $symbol, 0, 12 );

        // Image URL.
        $sanitized['image_url'] = isset( $input['image_url'] ) ? esc_url_raw( $input['image_url'] ) : '';

        // Image ID.
        $sanitized['image_id'] = isset( $input['image_id'] ) ? absint( $input['image_id'] ) : 0;

        // Image size — clamp 12–32.
        $image_size                 = isset( $input['image_size_px'] ) ? absint( $input['image_size_px'] ) : 16;
        $sanitized['image_size_px'] = max( 12, min( 32, $image_size ) );

        // Placement selector — validate format.
        $selector = isset( $input['placement_selector'] ) ? sanitize_text_field( $input['placement_selector'] ) : '';
        $selector = mb_substr( $selector, 0, 200 );
        if ( ! empty( $selector ) && ! preg_match( '/^(\.[A-Za-z0-9_-]+|\#[A-Za-z0-9_-]+)$/', $selector ) ) {
            $selector = '';
        }
        $sanitized['placement_selector'] = $selector;

        // Min word count — clamp 0–50 000.
        $min_words                    = isset( $input['min_word_count'] ) ? absint( $input['min_word_count'] ) : 0;
        $sanitized['min_word_count']  = max( 0, min( 50000, $min_words ) );

        // Exclude arrays.
        $sanitized['exclude_categories'] = $this->sanitize_id_array( $input, 'exclude_categories' );
        $sanitized['exclude_tags']       = $this->sanitize_id_array( $input, 'exclude_tags' );
        $sanitized['exclude_post_ids']   = $this->sanitize_id_array( $input, 'exclude_post_ids' );

        // Margin values (CSS).
        $sanitized['margin_top']  = isset( $input['margin_top'] ) ? sanitize_text_field( $input['margin_top'] ) : '0';
        $sanitized['margin_left'] = isset( $input['margin_left'] ) ? sanitize_text_field( $input['margin_left'] ) : '0.25em';

        return $sanitized;
    }

    /**
     * Sanitise an array of numeric IDs from form input.
     *
     * @param  array  $input Raw form input.
     * @param  string $key   Array key to read.
     * @return array
     */
    private function sanitize_id_array( $input, $key ) {
        if ( ! isset( $input[ $key ] ) ) {
            return array();
        }

        $values = $input[ $key ];
        if ( is_string( $values ) ) {
            $values = array_map( 'trim', explode( ',', $values ) );
        }

        return array_values( array_filter( array_map( 'absint', (array) $values ) ) );
    }

    /**
     * Sanitise the post_types array from form input.
     *
     * Validates each slug against registered public post types.
     * Falls back to array( 'post' ) if nothing valid remains.
     *
     * @param  array $input Raw form input.
     * @return array
     */
    private function sanitize_post_types( $input ) {
        if ( ! isset( $input['post_types'] ) || ! is_array( $input['post_types'] ) ) {
            return array( 'post' );
        }

        $registered = get_post_types( array( 'public' => true ), 'names' );
        $sanitized  = array();

        foreach ( $input['post_types'] as $slug ) {
            $slug = sanitize_key( $slug );
            if ( isset( $registered[ $slug ] ) ) {
                $sanitized[] = $slug;
            }
        }

        return ! empty( $sanitized ) ? array_values( array_unique( $sanitized ) ) : array( 'post' );
    }

    /* ======================================================================
       Settings Page
       ====================================================================== */

    /**
     * Render the settings page by loading the template.
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'paulus' ) );
        }

        $settings   = $this->plugin->get_settings();
        $categories = get_categories( array( 'hide_empty' => false ) );
        $tags       = get_tags( array( 'hide_empty' => false ) );
        $plugin     = $this->plugin;

        include PAULUS_DIR . '/inc/endmark/templates/admin-settings.php';
    }
}
