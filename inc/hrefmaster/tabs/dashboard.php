<?php
/**
 * HrefMaster – Dashboard Tab
 *
 * @package HrefMaster
 * @since   3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="hrefmaster-card">
    <h2><?php esc_html_e( 'Content Types', 'paulus' ); ?></h2>
    <p class="hrefmaster-description"><?php esc_html_e( 'Select which content types should display the HrefMaster meta box for hreflang tag editing.', 'paulus' ); ?></p>

    <form action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" method="post">
        <?php settings_fields( 'hrefmaster-settings' ); ?>

        <div class="hrefmaster-checkbox-grid">
            <?php
            $post_types = get_post_types( array( 'public' => true, 'exclude_from_search' => false ), 'objects' );
            $saved      = get_option( 'paulus_hreflang_post_types', array() );
            if ( ! is_array( $saved ) ) { $saved = array(); }

            if ( is_array( $post_types ) ) {
                foreach ( $post_types as $pt ) {
                    $checked = in_array( $pt->name, $saved, true ) ? ' checked' : '';
                    echo '<label class="hrefmaster-checkbox">';
                    echo '<input type="checkbox" name="paulus_hreflang_post_types[]" value="' . esc_attr( $pt->name ) . '"' . $checked . ' />';
                    echo '<span>' . esc_html( $pt->label ) . '</span>';
                    echo '</label>';
                }
            }

            $taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
            if ( is_array( $taxonomies ) ) {
                foreach ( $taxonomies as $tax ) {
                    $checked = in_array( $tax->name, $saved, true ) ? ' checked' : '';
                    echo '<label class="hrefmaster-checkbox">';
                    echo '<input type="checkbox" name="paulus_hreflang_post_types[]" value="' . esc_attr( $tax->name ) . '"' . $checked . ' />';
                    echo '<span>' . esc_html( $tax->label ) . '</span>';
                    echo '</label>';
                }
            }
            ?>
        </div>

        <?php submit_button( __( 'Save Changes', 'paulus' ), 'primary hrefmaster-btn' ); ?>
    </form>
</div>

<div class="hrefmaster-card hrefmaster-card--muted">
    <h2><?php esc_html_e( 'Quick Stats', 'paulus' ); ?></h2>
    <?php
    $items      = hrefmaster_get_all_tagged_items();
    $with_tags  = array_filter( $items, function( $item ) { return ! empty( $item['tags'] ); } );
    $total_tags = array_sum( array_map( function( $item ) { return count( $item['tags'] ); }, $with_tags ) );
    ?>
    <div class="hrefmaster-stats">
        <div class="hrefmaster-stat">
            <span class="hrefmaster-stat__number"><?php echo intval( count( $items ) ); ?></span>
            <span class="hrefmaster-stat__label"><?php esc_html_e( 'Total Items', 'paulus' ); ?></span>
        </div>
        <div class="hrefmaster-stat">
            <span class="hrefmaster-stat__number"><?php echo intval( count( $with_tags ) ); ?></span>
            <span class="hrefmaster-stat__label"><?php esc_html_e( 'With Tags', 'paulus' ); ?></span>
        </div>
        <div class="hrefmaster-stat">
            <span class="hrefmaster-stat__number"><?php echo intval( $total_tags ); ?></span>
            <span class="hrefmaster-stat__label"><?php esc_html_e( 'Total Tags', 'paulus' ); ?></span>
        </div>
    </div>
</div>

<div class="hrefmaster-card hrefmaster-card--danger">
    <h2><?php esc_html_e( 'Danger Zone', 'paulus' ); ?></h2>
    <p class="hrefmaster-description"><?php esc_html_e( 'Permanently delete all hreflang tags from every post, page, and taxonomy. This cannot be undone.', 'paulus' ); ?></p>
    <button type="button" class="button hrefmaster-btn hrefmaster-btn--danger hrefmaster-erase-all">
        <?php esc_html_e( 'Erase All Hreflang Tags', 'paulus' ); ?>
    </button>
</div>
