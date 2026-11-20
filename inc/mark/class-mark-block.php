<?php
/**
 * End mark Block & Shortcode
 *
 * Registers the [endmark] shortcode and the end mark/end mark
 * Gutenberg block for manual placement.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Paulus_Mark_Block {

    /**
     * Reference to the main plugin instance.
     *
     * @var Paulus_Mark
     */
    private $plugin;

    /**
     * Constructor.
     *
     * @param Paulus_Mark $plugin Main plugin instance.
     */
    public function __construct( Paulus_Mark $plugin ) {
        $this->plugin = $plugin;

        add_action( 'init', array( $this, 'register_shortcode' ) );
        add_action( 'init', array( $this, 'register_block' ) );
    }

    /**
     * Register [endmark] shortcode.
     */
    public function register_shortcode() {
        add_shortcode( Paulus_Mark::SHORTCODE_TAG, array( $this, 'render_shortcode' ) );
    }

    /**
     * Render [endmark] shortcode output.
     *
     * @param  array $atts Shortcode attributes (unused).
     * @return string
     */
    public function render_shortcode( $atts ) {
        $settings = $this->plugin->get_settings();
        return $this->plugin->frontend->render_mark_html( $settings );
    }

    /**
     * Register the Gutenberg block.
     */
    public function register_block() {
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }

        register_block_type( Paulus_Mark::BLOCK_NAME, array(
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
        return $this->plugin->frontend->render_mark_html( $settings );
    }
}
