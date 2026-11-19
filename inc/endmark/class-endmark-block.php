<?php
/**
 * Endmark Block & Shortcode
 *
 * Registers the [endmark] shortcode and the endmark/endmark
 * Gutenberg block for manual placement.
 *
 * @package Endmark
 * @since   4.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Endmark_Block {

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

        add_action( 'init', array( $this, 'register_shortcode' ) );
        add_action( 'init', array( $this, 'register_block' ) );
    }

    /**
     * Register [endmark] shortcode.
     */
    public function register_shortcode() {
        add_shortcode( Endmark_Plugin::SHORTCODE_TAG, array( $this, 'render_shortcode' ) );
    }

    /**
     * Render [endmark] shortcode output.
     *
     * @param  array $atts Shortcode attributes (unused).
     * @return string
     */
    public function render_shortcode( $atts ) {
        $settings = $this->plugin->get_settings();
        return $this->plugin->frontend->render_endmark_html( $settings );
    }

    /**
     * Register the Gutenberg block.
     */
    public function register_block() {
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }

        register_block_type( Endmark_Plugin::BLOCK_NAME, array(
            'render_callback' => array( $this, 'render_block' ),
            'attributes'      => array(
                'useGlobalSettings' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
            ),
        ) );
    }

    /**
     * Render the Gutenberg block output.
     *
     * @param  array $attributes Block attributes.
     * @return string
     */
    public function render_block( $attributes ) {
        $settings = $this->plugin->get_settings();
        return $this->plugin->frontend->render_endmark_html( $settings );
    }
}
