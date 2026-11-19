<?php
/**
 * Endmark Schema
 *
 * Handles optional JSON-LD structured data output.
 *
 * @package Endmark
 * @since   4.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Endmark_Schema {

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

        add_action( 'wp_head', array( $this, 'output_schema_json_ld' ), 99 );
    }

    /**
     * Output schema.org JSON-LD for the endmark.
     *
     * Only fires when schema_enabled is true, the endmark was actually
     * rendered, and the schema_mode is an allowed (non-forbidden) type.
     */
    public function output_schema_json_ld() {
        // fail_safe: schema_failure = skip_output
        try {
            $settings = $this->plugin->get_settings();

            if ( empty( $settings['schema_enabled'] ) ) {
                return;
            }

            if ( ! $this->plugin->frontend->was_rendered() ) {
                return;
            }

            if ( ! is_singular() ) {
                return;
            }

            $schema_mode = $settings['schema_mode'];

            if ( ! in_array( $schema_mode, Endmark_Plugin::ALLOWED_SCHEMA_TYPES, true ) ) {
                return;
            }
            if ( in_array( $schema_mode, Endmark_Plugin::FORBIDDEN_SCHEMA_TYPES, true ) ) {
                return;
            }

            $permalink = get_permalink();
            if ( ! $permalink ) {
                return;
            }

            $schema = array(
                '@context'    => 'https://schema.org',
                '@type'       => $schema_mode,
                '@id'         => $permalink . '#endmark',
                'name'        => __( 'Endmark', 'paulus' ),
                'description' => __( 'End-of-content marker', 'paulus' ),
                'isPartOf'    => array(
                    '@id' => $permalink,
                ),
            );

            // Conditional image fields.
            if ( 'image' === $settings['type'] && ! empty( $settings['image_url'] ) ) {
                $mime_type = $this->derive_mime_type( $settings['image_url'] );
                if ( $mime_type ) {
                    $schema['image'] = array(
                        'contentUrl'     => $settings['image_url'],
                        'encodingFormat' => $mime_type,
                    );
                }
            }

            $schema = apply_filters( 'endmark_schema_payload', $schema, $settings, $permalink );

            // Post-filter safety: reject forbidden types.
            if ( isset( $schema['@type'] ) && in_array( $schema['@type'], Endmark_Plugin::FORBIDDEN_SCHEMA_TYPES, true ) ) {
                return;
            }

            $json = wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
            if ( $json ) {
                if ( function_exists( 'wp_print_inline_script_tag' ) ) {
                    wp_print_inline_script_tag( $json, array( 'type' => 'application/ld+json' ) );
                } else {
                    echo '<script type="application/ld+json">' . $json . '</script>' . "\n";
                }
            }
        } catch ( Exception $e ) {
            return;
        }
    }

    /**
     * Derive MIME type from an image URL.
     *
     * @param  string $url Image URL.
     * @return string|false
     */
    private function derive_mime_type( $url ) {
        $path = wp_parse_url( $url, PHP_URL_PATH );
        if ( ! $path ) {
            return false;
        }

        $extension = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );

        $mime_map = array(
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            'svg'  => 'image/svg+xml',
            'ico'  => 'image/x-icon',
            'bmp'  => 'image/bmp',
        );

        return isset( $mime_map[ $extension ] ) ? $mime_map[ $extension ] : false;
    }
}
