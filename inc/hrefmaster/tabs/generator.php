<?php
/**
 * HrefMaster – HTML Code Generator Tab
 *
 * @package HrefMaster
 * @since   3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $hrefmaster_languages;
?>

<div class="hrefmaster-card">
    <h2><?php esc_html_e( 'HTML Code Generator', 'paulus' ); ?></h2>
    <p class="hrefmaster-description"><?php esc_html_e( 'Generate hreflang HTML tags for non-WordPress sites. Add your language/URL pairs below, then copy the generated code.', 'paulus' ); ?></p>

    <div id="hrefmaster-generator">
        <div class="hrefmaster-generator-rows">
            <div class="hrefmaster-generator-row">
                <label><?php esc_html_e( 'Language / Region', 'paulus' ); ?></label>
                <select class="hrefmaster-gen-lang">
                    <option value=""><?php esc_html_e( 'Select language…', 'paulus' ); ?></option>
                    <?php foreach ( $hrefmaster_languages as $code => $data ) : ?>
                        <option value="<?php echo esc_attr( str_replace( '_', '-', $code ) ); ?>">
                            <?php echo esc_html( $data['english_name'] ?? $code ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label><?php esc_html_e( 'URL', 'paulus' ); ?></label>
                <input type="url" class="hrefmaster-gen-url hrefmaster-input" placeholder="https://example.com/page" />
                <button type="button" class="button hrefmaster-gen-remove" title="<?php esc_attr_e( 'Remove', 'paulus' ); ?>">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
        </div>

        <div class="hrefmaster-generator-actions">
            <button type="button" class="button" id="hrefmaster-gen-add-row">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php esc_html_e( 'Add Another Language', 'paulus' ); ?>
            </button>
            <button type="button" class="button hrefmaster-btn hrefmaster-btn--primary" id="hrefmaster-gen-generate">
                <span class="dashicons dashicons-editor-code"></span>
                <?php esc_html_e( 'Generate Code', 'paulus' ); ?>
            </button>
        </div>

        <div id="hrefmaster-gen-output" class="hrefmaster-code-output" style="display:none;">
            <div class="hrefmaster-code-header">
                <h3><?php esc_html_e( 'Generated HTML', 'paulus' ); ?></h3>
                <button type="button" class="button" id="hrefmaster-gen-copy">
                    <span class="dashicons dashicons-clipboard"></span>
                    <?php esc_html_e( 'Copy to Clipboard', 'paulus' ); ?>
                </button>
            </div>
            <pre><code id="hrefmaster-gen-code"></code></pre>
            <p class="hrefmaster-description"><?php esc_html_e( 'Place this code inside the <head> section of each page listed above.', 'paulus' ); ?></p>
        </div>
    </div>
</div>
