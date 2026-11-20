<?php
/**
 * End mark Schema
 *
 * Handles optional JSON-LD structured data output.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Paulus_Mark_Schema {

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

        add_action( 'wp_head', array( $this, 'output_schema_json_ld' ), 99 );
    }

    /**
     * Output schema.org JSON-LD for the end mark.
     *
     * Only fires when schema_enabled is true, the end mark was actually
     * rendered, and the schema_mode is an allowed (non-forbidden) type.
     */
    public function output_schema_json_ld() {
        // fail_safe: schema_failure = skip_output
        try {
            $settings = $this->plugin->get_settings();

            if ( empty( $settings['schema_enabled'] ) ) {
                return;
            }

            if ( ! is_singular() ) {
                return;
            }

            // wp_head runs before the content, so the mark cannot have been
            // rendered yet: decide from the item itself.
            $post_id = get_queried_object_id();
            if ( ! in_array( get_post_type( $post_id ), (array) $settings['post_types'], true )
                || get_post_meta( $post_id, Paulus_Mark::META_DISABLE, true )
                || in_array( $post_id, (array) $settings['exclude_post_ids'], true ) ) {
                return;
            }

            $schema_mode = $settings['schema_mode'];

            if ( ! in_array( $schema_mode, Paulus_Mark::ALLOWED_SCHEMA_TYPES, true ) ) {
                return;
            }
            if ( in_array( $schema_mode, Paulus_Mark::FORBIDDEN_SCHEMA_TYPES, true ) ) {
                return;
            }

            $permalink = get_permalink();
            if ( ! $permalink ) {
                return;
            }

            $schema = array(
                '@context'    => 'https://schema.org',
                '@type'       => $schema_mode,
                '@id'         => $permalink . '#end mark',
                'name'        => __( 'End mark', 'paulus' ),
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

            $schema = apply_filters( 'paulus_mark_schema_payload', $schema, $settings, $permalink );

            // Post-filter safety: reject forbidden types.
            if ( isset( $schema['@type'] ) && in_array( $schema['@type'], Paulus_Mark::FORBIDDEN_SCHEMA_TYPES, true ) ) {
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
