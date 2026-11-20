<?php
/**
 * Hreflang – Bulk Editor Tab
 *
 * @package Hreflang
 * @since   3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$items = paulus_hreflang_get_all_tagged_items();
global $paulus_hreflang_languages;
?>

<div class="paulus-hreflang-card">
    <div class="paulus-hreflang-card__header">
        <h2><?php esc_html_e( 'Bulk Editor', 'paulus' ); ?></h2>
        <div class="paulus-hreflang-card__actions">
            <button type="button" class="button paulus-hreflang-btn paulus-hreflang-btn--primary" id="paulus-hreflang-bulk-save">
                <span class="dashicons dashicons-saved"></span>
                <?php esc_html_e( 'Save All Changes', 'paulus' ); ?>
            </button>
        </div>
    </div>
    <p class="paulus-hreflang-description"><?php esc_html_e( 'Edit hreflang tags across all your content from one place. Make your changes, then click "Save All Changes".', 'paulus' ); ?></p>

    <?php if ( empty( $items ) ) : ?>
        <div class="paulus-hreflang-empty">
            <span class="dashicons dashicons-info-outline"></span>
            <p><?php esc_html_e( 'No content found. Enable content types on the Dashboard tab first, then add hreflang tags to your posts or pages.', 'paulus' ); ?></p>
        </div>
    <?php else : ?>
        <div class="paulus-hreflang-filter">
            <input type="text" id="paulus-hreflang-bulk-filter" placeholder="<?php esc_attr_e( 'Filter by title…', 'paulus' ); ?>" class="paulus-hreflang-input" />
        </div>

        <div class="paulus-hreflang-bulk-table-wrap">
            <table class="paulus-hreflang-table" id="paulus-hreflang-bulk-table">
                <thead>
                    <tr>
                        <th class="paulus-hreflang-table__col--title"><?php esc_html_e( 'Title', 'paulus' ); ?></th>
                        <th class="paulus-hreflang-table__col--type"><?php esc_html_e( 'Type', 'paulus' ); ?></th>
                        <th class="paulus-hreflang-table__col--tags"><?php esc_html_e( 'Hreflang Tags', 'paulus' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $items as $item ) : ?>
                    <tr class="paulus-hreflang-bulk-row"
                        data-object-id="<?php echo intval( $item['id'] ); ?>"
                        data-object-type="<?php echo esc_attr( $item['type'] ); ?>">
                        <td class="paulus-hreflang-table__col--title">
                            <strong><?php echo esc_html( $item['title'] ); ?></strong>
                            <div class="paulus-hreflang-row-meta">
                                <?php if ( ! empty( $item['edit_url'] ) ) : ?>
                                    <a href="<?php echo esc_url( $item['edit_url'] ); ?>" target="_blank"><?php esc_html_e( 'Edit', 'paulus' ); ?></a>
                                <?php endif; ?>
                                <?php if ( ! empty( $item['permalink'] ) && ! is_wp_error( $item['permalink'] ) ) : ?>
                                    | <a href="<?php echo esc_url( $item['permalink'] ); ?>" target="_blank"><?php esc_html_e( 'View', 'paulus' ); ?></a>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="paulus-hreflang-table__col--type">
                            <span class="paulus-hreflang-badge"><?php echo esc_html( $item['type'] === 'post' ? ( $item['post_type'] ?? 'post' ) : ( $item['taxonomy'] ?? 'term' ) ); ?></span>
                        </td>
                        <td class="paulus-hreflang-table__col--tags">
                            <div class="paulus-hreflang-inline-editor" data-languages='<?php echo esc_attr( wp_json_encode( array_map( function( $lang, $data ) {
                                return array( 'code' => $lang, 'name' => $data['english_name'] ?? $lang );
                            }, array_keys( $paulus_hreflang_languages ), array_values( $paulus_hreflang_languages ) ) ) ); ?>'>
                                <?php if ( ! empty( $item['tags'] ) ) : ?>
                                    <?php foreach ( $item['tags'] as $tag ) : ?>
                                        <div class="paulus-hreflang-inline-tag">
                                            <select class="paulus-hreflang-inline-lang">
                                                <option value=""><?php esc_html_e( 'Select', 'paulus' ); ?></option>
                                                <?php foreach ( $paulus_hreflang_languages as $code => $data ) : ?>
                                                    <option value="<?php echo esc_attr( $code ); ?>"<?php selected( $code, $tag['lang'] ); ?>><?php echo esc_html( $data['english_name'] ?? $code ); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="url" class="paulus-hreflang-inline-href" value="<?php echo esc_attr( $tag['href'] ); ?>" placeholder="https://" />
                                            <button type="button" class="button paulus-hreflang-inline-remove" title="<?php esc_attr_e( 'Remove', 'paulus' ); ?>"><span class="dashicons dashicons-no-alt"></span></button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <button type="button" class="button paulus-hreflang-inline-add"><?php esc_html_e( '+ Add Tag', 'paulus' ); ?></button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
