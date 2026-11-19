<?php
/**
 * Endmark Post Meta
 *
 * Adds a per-post "Disable Endmark" checkbox via a classic meta box.
 * Works in both the Classic Editor and the Block Editor (rendered
 * below the content area as a standard meta box panel).
 *
 * @package Endmark
 * @since   4.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Endmark_Meta {

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

        add_action( 'init', array( $this, 'register_meta' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
        add_action( 'save_post', array( $this, 'save_meta' ), 10, 2 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_meta_styles' ) );
    }

    /**
     * Register the post meta key for REST API access (block editor).
     */
    public function register_meta() {
        $post_types = $this->get_enabled_post_types();

        foreach ( $post_types as $post_type ) {
            register_post_meta( $post_type, Endmark_Plugin::META_DISABLE, array(
                'type'              => 'boolean',
                'single'            => true,
                'default'           => false,
                'show_in_rest'      => true,
                'sanitize_callback' => 'rest_sanitize_boolean',
                'auth_callback'     => function () {
                    return current_user_can( 'edit_posts' );
                },
            ) );
        }
    }

    /**
     * Add the meta box to all enabled post types.
     */
    public function add_meta_box() {
        $post_types = $this->get_enabled_post_types();

        foreach ( $post_types as $post_type ) {
            add_meta_box(
                'endmark-post-options',
                __( 'Endmark', 'paulus' ),
                array( $this, 'render_meta_box' ),
                $post_type,
                'side',
                'low'
            );
        }
    }

    /**
     * Render the meta box content.
     *
     * @param WP_Post $post Current post object.
     */
    public function render_meta_box( $post ) {
        $disabled = (bool) get_post_meta( $post->ID, Endmark_Plugin::META_DISABLE, true );

        wp_nonce_field( 'endmark_meta_nonce', 'endmark_meta_nonce_field' );
        ?>
        <label class="endmark-meta-label">
            <input type="checkbox"
                   name="endmark_disable"
                   value="1"
                   <?php checked( $disabled ); ?>>
            <?php esc_html_e( 'Disable endmark on this post', 'paulus' ); ?>
        </label>
        <p class="endmark-meta-hint">
            <?php esc_html_e( 'When checked, no endmark will be displayed regardless of global settings.', 'paulus' ); ?>
        </p>
        <?php
    }

    /**
     * Save the meta box value.
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post    Post object.
     */
    public function save_meta( $post_id, $post ) {
        // Verify nonce.
        if ( ! isset( $_POST['endmark_meta_nonce_field'] )
            || ! wp_verify_nonce( $_POST['endmark_meta_nonce_field'], 'endmark_meta_nonce' ) ) {
            return;
        }

        // Check autosave.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check permissions.
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Check post type is one we manage.
        $post_types = $this->get_enabled_post_types();
        if ( ! in_array( $post->post_type, $post_types, true ) ) {
            return;
        }

        // Save or delete the meta.
        $disabled = ! empty( $_POST['endmark_disable'] );

        if ( $disabled ) {
            update_post_meta( $post_id, Endmark_Plugin::META_DISABLE, true );
        } else {
            delete_post_meta( $post_id, Endmark_Plugin::META_DISABLE );
        }
    }

    /**
     * Enqueue lightweight meta box styles on post edit screens.
     *
     * @param string $hook The current admin page hook.
     */
    public function enqueue_meta_styles( $hook ) {
        if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
            return;
        }

        $css = '
            .endmark-meta-label {
                display: flex;
                align-items: center;
                gap: 8px;
                font-size: 13px;
                font-weight: 500;
                color: #1e293b;
                cursor: pointer;
                padding: 4px 0;
            }
            .endmark-meta-label input[type="checkbox"] {
                margin: 0;
            }
            .endmark-meta-hint {
                color: #64748b;
                font-size: 12px;
                margin: 6px 0 0;
                line-height: 1.4;
            }
        ';

        wp_register_style( 'endmark-meta', false, array(), Endmark_Plugin::VERSION );
        wp_enqueue_style( 'endmark-meta' );
        wp_add_inline_style( 'endmark-meta', $css );
    }

    /**
     * Get the list of enabled post types from settings.
     *
     * @return array
     */
    private function get_enabled_post_types() {
        $settings   = $this->plugin->get_settings();
        $post_types = isset( $settings['post_types'] ) ? (array) $settings['post_types'] : array( 'post' );

        return $post_types;
    }
}
