<?php
/**
 * HrefMaster – Admin Page
 *
 * @package HrefMaster
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
<div class="wrap hrefmaster-wrap" data-scheme="<?php echo esc_attr( $color_scheme ); ?>">

    <div class="hrefmaster-header">
        <div class="hrefmaster-header-inner">
            <span class="dashicons dashicons-translation hrefmaster-logo-icon"></span>
            <h1>HrefMaster
                <span class="hrefmaster-version">v<?php echo esc_html( HREFMASTER_VERSION ); ?></span>
            </h1>
        </div>
    </div>

    <nav class="hrefmaster-tabs">
        <?php foreach ( $tabs as $slug => $label ) : ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=hrefmaster&tab=' . $slug ) ); ?>"
               class="hrefmaster-tab <?php echo esc_attr( $active_tab === $slug ? 'hrefmaster-tab--active' : '' ); ?>">
                <?php echo esc_html( $label ); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="hrefmaster-body">
        <?php
        $tab_file = HREFMASTER_PATH . 'tabs/' . str_replace( '_', '-', $active_tab ) . '.php';
        if ( file_exists( $tab_file ) ) {
            include_once $tab_file;
        } else {
            echo '<div class="hrefmaster-card"><p>' . esc_html__( 'Tab not found.', 'paulus' ) . '</p></div>';
        }
        ?>
    </div>

    <div class="hrefmaster-footer">
        <p><?php
            printf(
                /* translators: %s: version number */
                esc_html__( 'HrefMaster %s — Open-source hreflang management for WordPress.', 'paulus' ),
                esc_html( HREFMASTER_VERSION )
            );
        ?></p>
    </div>

</div>
