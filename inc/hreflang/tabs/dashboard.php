<?php
/**
 * Hreflang – Dashboard Tab
 *
 * @package Hreflang
 * @since   3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="paulus-hreflang-card">
    <h2><?php esc_html_e( 'Content Types', 'paulus' ); ?></h2>
    <p class="paulus-hreflang-description"><?php esc_html_e( 'Select which content types should display the Hreflang meta box for hreflang tag editing.', 'paulus' ); ?></p>

    <form action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" method="post">
        <?php settings_fields( 'paulus-hreflang-settings' ); ?>

        <div class="paulus-hreflang-checkbox-grid">
            <?php
            $post_types = get_post_types( array( 'public' => true, 'exclude_from_search' => false ), 'objects' );
            $saved      = get_option( 'paulus_hreflang_post_types', array() );
            if ( ! is_array( $saved ) ) { $saved = array(); }

            // An empty value, so that unchecking every type is saved as none.
            echo '<input type="hidden" name="paulus_hreflang_post_types[]" value="" />';
            if ( is_array( $post_types ) ) {
                foreach ( $post_types as $pt ) {
                    $checked = in_array( $pt->name, $saved, true ) ? ' checked' : '';
                    echo '<label class="paulus-hreflang-checkbox">';
                    echo '<input type="checkbox" name="paulus_hreflang_post_types[]" value="' . esc_attr( $pt->name ) . '"' . $checked . ' />';
                    echo '<span>' . esc_html( $pt->label ) . '</span>';
                    echo '</label>';
                }
            }

            $taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
            if ( is_array( $taxonomies ) ) {
                foreach ( $taxonomies as $tax ) {
                    $checked = in_array( $tax->name, $saved, true ) ? ' checked' : '';
                    echo '<label class="paulus-hreflang-checkbox">';
                    echo '<input type="checkbox" name="paulus_hreflang_post_types[]" value="' . esc_attr( $tax->name ) . '"' . $checked . ' />';
                    echo '<span>' . esc_html( $tax->label ) . '</span>';
                    echo '</label>';
                }
            }
            ?>
        </div>

        <?php submit_button( __( 'Save Changes', 'paulus' ), 'primary paulus-hreflang-btn' ); ?>
    </form>
</div>

<div class="paulus-hreflang-card paulus-hreflang-card--muted">
    <h2><?php esc_html_e( 'Quick Stats', 'paulus' ); ?></h2>
    <?php
    $items      = paulus_hreflang_get_all_tagged_items();
    $with_tags  = array_filter( $items, function( $item ) { return ! empty( $item['tags'] ); } );
    $total_tags = array_sum( array_map( function( $item ) { return count( $item['tags'] ); }, $with_tags ) );
    ?>
    <div class="paulus-hreflang-stats">
        <div class="paulus-hreflang-stat">
            <span class="paulus-hreflang-stat__number"><?php echo intval( count( $items ) ); ?></span>
            <span class="paulus-hreflang-stat__label"><?php esc_html_e( 'Total Items', 'paulus' ); ?></span>
        </div>
        <div class="paulus-hreflang-stat">
            <span class="paulus-hreflang-stat__number"><?php echo intval( count( $with_tags ) ); ?></span>
            <span class="paulus-hreflang-stat__label"><?php esc_html_e( 'With Tags', 'paulus' ); ?></span>
        </div>
        <div class="paulus-hreflang-stat">
            <span class="paulus-hreflang-stat__number"><?php echo intval( $total_tags ); ?></span>
            <span class="paulus-hreflang-stat__label"><?php esc_html_e( 'Total Tags', 'paulus' ); ?></span>
        </div>
    </div>
</div>

<div class="paulus-hreflang-card paulus-hreflang-card--danger">
    <h2><?php esc_html_e( 'Danger Zone', 'paulus' ); ?></h2>
    <p class="paulus-hreflang-description"><?php esc_html_e( 'Permanently delete all hreflang tags from every post, page, and taxonomy. This cannot be undone.', 'paulus' ); ?></p>
    <button type="button" class="button paulus-hreflang-btn paulus-hreflang-btn--danger paulus-hreflang-erase-all">
        <?php esc_html_e( 'Erase All Hreflang Tags', 'paulus' ); ?>
    </button>
</div>
