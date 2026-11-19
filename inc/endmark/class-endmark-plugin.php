<?php
/**
 * Main Endmark Plugin Class
 *
 * Bootstraps the plugin, declares constants and defaults,
 * and wires up all sub-components via WordPress hooks.
 *
 * @package Endmark
 * @since   4.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Endmark_Plugin {

    /* ======================================================================
       Constants
       ====================================================================== */

    /**
     * Plugin version.
     */
    const VERSION = '4.4.0';

    /**
     * Option keys.
     */
    const OPTION_SITE    = 'paulus_endmark';
    const OPTION_NETWORK = 'paulus_endmark_network';
    const OPTION_PRESETS = 'paulus_endmark_presets';
    const OPTION_VERSION = 'paulus_endmark_version';

    /**
     * Detection tokens.
     */
    const ANTI_DUP_COMMENT    = '<!-- endmark:inserted -->';
    const WRAPPER_CLASS       = 'endmark-wrap';
    const WRAPPER_CLASS_TOKEN = 'class="endmark-wrap"';
    const SHORTCODE_TAG       = 'endmark';
    const BLOCK_NAME          = 'endmark/endmark';

    /**
     * Schema.org types that endmark must never interfere with.
     */
    const PROTECTED_SCHEMA_TYPES = array(
        'FAQPage',
        'HowTo',
        'HowToStep',
        'QAPage',
        'Question',
        'Answer',
        'Recipe',
        'Review',
        'Product',
    );

    /**
     * Schema.org types allowed for endmark output.
     */
    const ALLOWED_SCHEMA_TYPES = array( 'WebPageElement', 'CreativeWork' );

    /**
     * Schema.org types that endmark must never output.
     */
    const FORBIDDEN_SCHEMA_TYPES = array(
        'Article',
        'BlogPosting',
        'FAQPage',
        'HowTo',
        'BreadcrumbList',
        'Product',
        'Review',
    );

    /**
     * Post meta key for per-post override.
     */
    const META_DISABLE = '_paulus_endmark_disable';

    /* ======================================================================
       Instance / Singleton
       ====================================================================== */

    /**
     * Single instance.
     *
     * @var self|null
     */
    private static $instance = null;

    /**
     * Sub-component references.
     *
     * @var Endmark_Admin|null
     */
    public $admin = null;

    /**
     * @var Endmark_Frontend|null
     */
    public $frontend = null;

    /**
     * @var Endmark_Schema|null
     */
    public $schema = null;

    /**
     * @var Endmark_Block|null
     */
    public $block = null;

    /**
     * @var Endmark_Meta|null
     */
    public $meta = null;

    /**
     * Site-level defaults.
     *
     * @var array
     */
    private $site_defaults = array(
        'enabled'             => true,
        'post_types'          => array( 'post', 'paulus_journal' ),
        'type'                => 'image',
        'symbol'              => '∎',
        'image_url'           => PAULUS_URI . '/assets/images/endmark.svg',
        'image_id'            => 0,
        'image_size_px'       => 20,
        'placement_mode'      => 'last_paragraph',
        'placement_selector'  => '',
        'min_word_count'      => 0,
        'exclude_categories'  => array(),
        'exclude_tags'        => array(),
        'exclude_post_ids'    => array(),
        'hide_on_mobile'      => false,
        'disable_on_amp'      => true,
        'use_css_variables'   => true,
        'margin_top'          => '0',
        'margin_left'         => '0.25em',
        'allow_svg'           => true,
        'schema_enabled'      => false,
        'schema_mode'         => 'WebPageElement',
    );

    /**
     * Allowlists for sanitisation.
     *
     * @var array
     */
    private $allowlists = array(
        'type'           => array( 'symbol', 'image' ),
        'placement_mode' => array( 'last_paragraph', 'before_footnotes', 'after_footnotes', 'append', 'selector' ),
        'schema_mode'    => array( 'WebPageElement', 'CreativeWork' ),
    );

    /* ======================================================================
       Bootstrap
       ====================================================================== */

    /**
     * Get singleton instance.
     *
     * @return self
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor — registers hooks and instantiates sub-components.
     */
    private function __construct() {
        // Version migration.
        add_action( 'admin_init', array( $this, 'maybe_migrate_options' ) );

        // Sub-components.
        $this->admin    = new Endmark_Admin( $this );
        $this->frontend = new Endmark_Frontend( $this );
        $this->schema   = new Endmark_Schema( $this );
        $this->block    = new Endmark_Block( $this );
        $this->meta     = new Endmark_Meta( $this );
    }

    /* ======================================================================
       Text Domain
       ====================================================================== */

    /**
     * Load text domain for translations.
     */
    public function load_textdomain() {
        // The theme's own text domain is loaded by functions.php.
    }

    /* ======================================================================
       Settings API
       ====================================================================== */

    /**
     * Get merged settings (saved values + defaults).
     *
     * Handles runtime migration of legacy 'where' string to 'post_types' array.
     *
     * @return array
     */
    public function get_settings() {
        $settings = get_option( self::OPTION_SITE, array() );
        $merged   = wp_parse_args( $settings, $this->site_defaults );

        // Runtime migration: legacy 'where' string → 'post_types' array.
        if ( isset( $settings['where'] ) && ! isset( $settings['post_types'] ) ) {
            $merged['post_types'] = $this->convert_where_to_post_types( $settings['where'] );
        }

        // Ensure post_types is always an array.
        if ( ! is_array( $merged['post_types'] ) ) {
            $merged['post_types'] = array( 'post' );
        }

        return $merged;
    }

    /**
     * Get the site-level defaults array.
     *
     * @return array
     */
    public function get_site_defaults() {
        return $this->site_defaults;
    }

    /**
     * Get the allowlists array.
     *
     * @return array
     */
    public function get_allowlists() {
        return $this->allowlists;
    }

    /* ======================================================================
       Migration
       ====================================================================== */

    /**
     * Migrate options from older versions.
     */
    public function maybe_migrate_options() {
        $current_version = get_option( self::OPTION_VERSION, '0' );

        // Migrate from v3.x or earlier → v4.0 format.
        if ( version_compare( $current_version, '4.0', '<' ) ) {
            $old_settings = get_option( self::OPTION_SITE, array() );

            if ( ! empty( $old_settings ) ) {
                $new_settings = $this->site_defaults;

                if ( isset( $old_settings['type'] ) ) {
                    $new_settings['type'] = $old_settings['type'];
                }
                if ( isset( $old_settings['symbol'] ) ) {
                    $new_settings['symbol'] = $old_settings['symbol'];
                }
                if ( isset( $old_settings['image'] ) ) {
                    $new_settings['image_url'] = $old_settings['image'];
                }
                if ( isset( $old_settings['image_id'] ) ) {
                    $new_settings['image_id'] = $old_settings['image_id'];
                }
                if ( isset( $old_settings['image_size'] ) ) {
                    $new_settings['image_size_px'] = $old_settings['image_size'];
                }
                if ( isset( $old_settings['where'] ) ) {
                    $where_map = array(
                        'posts' => 'post',
                        'pages' => 'page',
                        'both'  => 'both',
                    );
                    $legacy_where = isset( $where_map[ $old_settings['where'] ] )
                        ? $where_map[ $old_settings['where'] ]
                        : 'post';
                    $new_settings['post_types'] = $this->convert_where_to_post_types( $legacy_where );
                }

                update_option( self::OPTION_SITE, $new_settings );
            }

            // Check for very old individual options.
            if ( false !== get_option( 'endmark_type' ) ) {
                $legacy_settings = array(
                    'type'       => get_option( 'endmark_type', 'symbol' ),
                    'symbol'     => get_option( 'endmark_symbol', '∎' ),
                    'image_url'  => get_option( 'endmark_image', '' ),
                    'image_id'   => 0,
                    'post_types' => $this->convert_where_to_post_types( get_option( 'endmark_where', 'post' ) ),
                );

                $new_settings = wp_parse_args( $legacy_settings, $this->site_defaults );
                update_option( self::OPTION_SITE, $new_settings );

                delete_option( 'endmark_type' );
                delete_option( 'endmark_symbol' );
                delete_option( 'endmark_image' );
                delete_option( 'endmark_where' );
            }

            update_option( self::OPTION_VERSION, self::VERSION );
        }

        // Migrate from v4.0–4.3 → v4.4 (where string → post_types array).
        if ( version_compare( $current_version, '4.4', '<' ) && version_compare( $current_version, '4.0', '>=' ) ) {
            $settings = get_option( self::OPTION_SITE, array() );

            if ( isset( $settings['where'] ) && ! isset( $settings['post_types'] ) ) {
                $settings['post_types'] = $this->convert_where_to_post_types( $settings['where'] );
                unset( $settings['where'] );
                update_option( self::OPTION_SITE, $settings );
            }

            update_option( self::OPTION_VERSION, self::VERSION );
        }
    }

    /**
     * Convert legacy 'where' string to 'post_types' array.
     *
     * @param  string $where Legacy value: 'post', 'page', or 'both'.
     * @return array
     */
    private function convert_where_to_post_types( $where ) {
        switch ( $where ) {
            case 'page':
                return array( 'page' );
            case 'both':
                return array( 'post', 'page' );
            case 'post':
            default:
                return array( 'post' );
        }
    }
}
