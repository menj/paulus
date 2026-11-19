<?php
/**
 * HrefMaster – Bulk Editor Tab
 *
 * @package HrefMaster
 * @since   3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$items = hrefmaster_get_all_tagged_items();
global $hrefmaster_languages;
?>

<div class="hrefmaster-card">
    <div class="hrefmaster-card__header">
        <h2><?php esc_html_e( 'Bulk Editor', 'paulus' ); ?></h2>
        <div class="hrefmaster-card__actions">
            <button type="button" class="button hrefmaster-btn hrefmaster-btn--primary" id="hrefmaster-bulk-save">
                <span class="dashicons dashicons-saved"></span>
                <?php esc_html_e( 'Save All Changes', 'paulus' ); ?>
            </button>
        </div>
    </div>
    <p class="hrefmaster-description"><?php esc_html_e( 'Edit hreflang tags across all your content from one place. Make your changes, then click "Save All Changes".', 'paulus' ); ?></p>

    <?php if ( empty( $items ) ) : ?>
        <div class="hrefmaster-empty">
            <span class="dashicons dashicons-info-outline"></span>
            <p><?php esc_html_e( 'No content found. Enable content types on the Dashboard tab first, then add hreflang tags to your posts or pages.', 'paulus' ); ?></p>
        </div>
    <?php else : ?>
        <div class="hrefmaster-filter">
            <input type="text" id="hrefmaster-bulk-filter" placeholder="<?php esc_attr_e( 'Filter by title…', 'paulus' ); ?>" class="hrefmaster-input" />
        </div>

        <div class="hrefmaster-bulk-table-wrap">
            <table class="hrefmaster-table" id="hrefmaster-bulk-table">
                <thead>
                    <tr>
                        <th class="hrefmaster-table__col--title"><?php esc_html_e( 'Title', 'paulus' ); ?></th>
                        <th class="hrefmaster-table__col--type"><?php esc_html_e( 'Type', 'paulus' ); ?></th>
                        <th class="hrefmaster-table__col--tags"><?php esc_html_e( 'Hreflang Tags', 'paulus' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $items as $item ) : ?>
                    <tr class="hrefmaster-bulk-row"
                        data-object-id="<?php echo intval( $item['id'] ); ?>"
                        data-object-type="<?php echo esc_attr( $item['type'] ); ?>">
                        <td class="hrefmaster-table__col--title">
                            <strong><?php echo esc_html( $item['title'] ); ?></strong>
                            <div class="hrefmaster-row-meta">
                                <?php if ( ! empty( $item['edit_url'] ) ) : ?>
                                    <a href="<?php echo esc_url( $item['edit_url'] ); ?>" target="_blank"><?php esc_html_e( 'Edit', 'paulus' ); ?></a>
                                <?php endif; ?>
                                <?php if ( ! empty( $item['permalink'] ) && ! is_wp_error( $item['permalink'] ) ) : ?>
                                    | <a href="<?php echo esc_url( $item['permalink'] ); ?>" target="_blank"><?php esc_html_e( 'View', 'paulus' ); ?></a>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="hrefmaster-table__col--type">
                            <span class="hrefmaster-badge"><?php echo esc_html( $item['type'] === 'post' ? ( $item['post_type'] ?? 'post' ) : ( $item['taxonomy'] ?? 'term' ) ); ?></span>
                        </td>
                        <td class="hrefmaster-table__col--tags">
                            <div class="hrefmaster-inline-editor" data-languages='<?php echo esc_attr( wp_json_encode( array_map( function( $lang, $data ) {
                                return array( 'code' => $lang, 'name' => $data['english_name'] ?? $lang );
                            }, array_keys( $hrefmaster_languages ), array_values( $hrefmaster_languages ) ) ) ); ?>'>
                                <?php if ( ! empty( $item['tags'] ) ) : ?>
                                    <?php foreach ( $item['tags'] as $tag ) : ?>
                                        <div class="hrefmaster-inline-tag">
                                            <select class="hrefmaster-inline-lang">
                                                <option value=""><?php esc_html_e( 'Select', 'paulus' ); ?></option>
                                                <?php foreach ( $hrefmaster_languages as $code => $data ) : ?>
                                                    <option value="<?php echo esc_attr( $code ); ?>"<?php selected( $code, $tag['lang'] ); ?>><?php echo esc_html( $data['english_name'] ?? $code ); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="url" class="hrefmaster-inline-href" value="<?php echo esc_attr( $tag['href'] ); ?>" placeholder="https://" />
                                            <button type="button" class="button hrefmaster-inline-remove" title="<?php esc_attr_e( 'Remove', 'paulus' ); ?>"><span class="dashicons dashicons-no-alt"></span></button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <button type="button" class="button hrefmaster-inline-add"><?php esc_html_e( '+ Add Tag', 'paulus' ); ?></button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
