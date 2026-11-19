<?php
/**
 * HrefMaster – Settings Tab
 *
 * @package HrefMaster
 * @since   3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$current_scheme   = get_option( 'paulus_hreflang_scheme', 'ocean' );
$admin_bar        = get_option( 'paulus_hreflang_admin_bar', 'false' );
$html_lang        = get_option( 'paulus_hreflang_html_lang', 'false' );
$woo_shop         = get_option( 'paulus_hreflang_woo_shop', 'false' );
$woo_active       = in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) ), true );
?>

<form action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" method="post">
    <?php settings_fields( 'hrefmaster-settings' ); ?>

    <!-- Hidden: preserve content types from dashboard. -->
    <?php
    $saved_types = get_option( 'paulus_hreflang_post_types', array() );
    if ( is_array( $saved_types ) ) {
        foreach ( $saved_types as $type ) {
            echo '<input type="hidden" name="paulus_hreflang_post_types[]" value="' . esc_attr( $type ) . '" />';
        }
    }
    ?>

    <div class="hrefmaster-card">
        <h2><?php esc_html_e( 'Appearance', 'paulus' ); ?></h2>
        <p class="hrefmaster-description"><?php esc_html_e( 'Choose a colour scheme for the HrefMaster admin interface.', 'paulus' ); ?></p>

        <div class="hrefmaster-scheme-grid">
            <?php
            $schemes = array(
                'ocean'    => array( 'label' => __( 'Ocean', 'paulus' ),    'colors' => array( '#0f5e8a', '#f97316' ) ),
                'slate'    => array( 'label' => __( 'Slate', 'paulus' ),    'colors' => array( '#475569', '#6366f1' ) ),
                'emerald'  => array( 'label' => __( 'Emerald', 'paulus' ),  'colors' => array( '#065f46', '#d97706' ) ),
                'midnight' => array( 'label' => __( 'Midnight', 'paulus' ), 'colors' => array( '#7c3aed', '#ec4899' ) ),
            );
            foreach ( $schemes as $key => $scheme ) :
            ?>
                <label class="hrefmaster-scheme-option <?php echo esc_attr( $current_scheme === $key ? 'hrefmaster-scheme-option--active' : '' ); ?>">
                    <input type="radio" name="paulus_hreflang_scheme" value="<?php echo esc_attr( $key ); ?>"<?php checked( $current_scheme, $key ); ?> />
                    <span class="hrefmaster-scheme-preview">
                        <span class="hrefmaster-scheme-swatch" style="background:<?php echo esc_attr( $scheme['colors'][0] ); ?>"></span>
                        <span class="hrefmaster-scheme-swatch" style="background:<?php echo esc_attr( $scheme['colors'][1] ); ?>"></span>
                    </span>
                    <span class="hrefmaster-scheme-label"><?php echo esc_html( $scheme['label'] ); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="hrefmaster-card">
        <h2><?php esc_html_e( 'General', 'paulus' ); ?></h2>

        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Admin Bar Link', 'paulus' ); ?></th>
                <td>
                    <label class="hrefmaster-toggle">
                        <input type="hidden" name="paulus_hreflang_admin_bar" value="false" />
                        <input type="checkbox" name="paulus_hreflang_admin_bar" value="true"<?php checked( $admin_bar, 'true' ); ?> />
                        <span class="hrefmaster-toggle__slider"></span>
                    </label>
                    <p class="description"><?php esc_html_e( 'Show a quick-access HrefMaster link in the WordPress admin bar.', 'paulus' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'HTML Lang Attribute', 'paulus' ); ?></th>
                <td>
                    <label class="hrefmaster-toggle">
                        <input type="hidden" name="paulus_hreflang_html_lang" value="false" />
                        <input type="checkbox" name="paulus_hreflang_html_lang" value="true"<?php checked( $html_lang, 'true' ); ?> />
                        <span class="hrefmaster-toggle__slider"></span>
                    </label>
                    <p class="description"><?php esc_html_e( 'Enable per-post/page override of the <html lang=""> attribute for improved SEO.', 'paulus' ); ?></p>
                </td>
            </tr>
            <?php if ( $woo_active ) : ?>
            <tr>
                <th scope="row"><?php esc_html_e( 'WooCommerce Shop Page', 'paulus' ); ?></th>
                <td>
                    <label class="hrefmaster-toggle">
                        <input type="hidden" name="paulus_hreflang_woo_shop" value="false" />
                        <input type="checkbox" name="paulus_hreflang_woo_shop" value="true"<?php checked( $woo_shop, 'true' ); ?> />
                        <span class="hrefmaster-toggle__slider"></span>
                    </label>
                    <p class="description"><?php esc_html_e( 'Output hreflang tags on the WooCommerce main shop page.', 'paulus' ); ?></p>
                </td>
            </tr>
            <?php endif; ?>
        </table>
    </div>

    <?php submit_button( __( 'Save Settings', 'paulus' ), 'primary hrefmaster-btn' ); ?>
</form>
