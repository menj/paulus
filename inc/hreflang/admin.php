<?php
/**
 * Hreflang – Admin Page
 *
 * @package Hreflang
 * @since   3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$active_tab   = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'dashboard';
$color_scheme = get_option( 'paulus_hreflang_scheme', 'ocean' );

$tabs = array(
    'dashboard'       => __( 'Dashboard', 'paulus' ),
    'bulk_editor'     => __( 'Bulk Editor', 'paulus' ),
    'generator'       => __( 'Code Generator', 'paulus' ),
    'validation_tool' => __( 'Validation', 'paulus' ),
    'settings'        => __( 'Settings', 'paulus' ),
);
?>
<div class="wrap paulus-hreflang-wrap" data-scheme="<?php echo esc_attr( $color_scheme ); ?>">

    <div class="paulus-hreflang-header">
        <div class="paulus-hreflang-header-inner">
            <span class="dashicons dashicons-translation paulus-hreflang-logo-icon"></span>
            <h1>Hreflang
                <span class="paulus-hreflang-version">v<?php echo esc_html( PAULUS_HREFLANG_VERSION ); ?></span>
            </h1>
        </div>
    </div>

    <nav class="paulus-hreflang-tabs">
        <?php foreach ( $tabs as $slug => $label ) : ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=paulus-hreflang&tab=' . $slug ) ); ?>"
               class="paulus-hreflang-tab <?php echo esc_attr( $active_tab === $slug ? 'paulus-hreflang-tab--active' : '' ); ?>">
                <?php echo esc_html( $label ); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="paulus-hreflang-body">
        <?php
        $tab_file = PAULUS_HREFLANG_PATH . 'tabs/' . str_replace( '_', '-', $active_tab ) . '.php';
        if ( file_exists( $tab_file ) ) {
            include_once $tab_file;
        } else {
            echo '<div class="paulus-hreflang-card"><p>' . esc_html__( 'Tab not found.', 'paulus' ) . '</p></div>';
        }
        ?>
    </div>

    <div class="paulus-hreflang-footer">
        <p><?php
            printf(
                /* translators: %s: version number */
                esc_html__( 'Hreflang %s — Open-source hreflang management for WordPress.', 'paulus' ),
                esc_html( PAULUS_HREFLANG_VERSION )
            );
        ?></p>
    </div>

</div>
