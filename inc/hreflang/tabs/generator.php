<?php
/**
 * Hreflang – HTML Code Generator Tab
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $paulus_hreflang_languages;
?>

<div class="paulus-hreflang-card">
    <h2><?php esc_html_e( 'HTML Code Generator', 'paulus' ); ?></h2>
    <p class="paulus-hreflang-description"><?php esc_html_e( 'Generate hreflang HTML tags for non-WordPress sites. Add your language/URL pairs below, then copy the generated code.', 'paulus' ); ?></p>

    <div id="paulus-hreflang-generator">
        <div class="paulus-hreflang-generator-rows">
            <div class="paulus-hreflang-generator-row">
                <label><?php esc_html_e( 'Language / Region', 'paulus' ); ?></label>
                <select class="paulus-hreflang-gen-lang">
                    <option value=""><?php esc_html_e( 'Select language…', 'paulus' ); ?></option>
                    <?php foreach ( $paulus_hreflang_languages as $code => $data ) : ?>
                        <option value="<?php echo esc_attr( str_replace( '_', '-', $code ) ); ?>">
                            <?php echo esc_html( $data['english_name'] ?? $code ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label><?php esc_html_e( 'URL', 'paulus' ); ?></label>
                <input type="url" class="paulus-hreflang-gen-url paulus-hreflang-input" placeholder="https://example.com/page" />
                <button type="button" class="button paulus-hreflang-gen-remove" title="<?php esc_attr_e( 'Remove', 'paulus' ); ?>">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
        </div>

        <div class="paulus-hreflang-generator-actions">
            <button type="button" class="button" id="paulus-hreflang-gen-add-row">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php esc_html_e( 'Add Another Language', 'paulus' ); ?>
            </button>
            <button type="button" class="button paulus-hreflang-btn paulus-hreflang-btn--primary" id="paulus-hreflang-gen-generate">
                <span class="dashicons dashicons-editor-code"></span>
                <?php esc_html_e( 'Generate Code', 'paulus' ); ?>
            </button>
        </div>

        <div id="paulus-hreflang-gen-output" class="paulus-hreflang-code-output" style="display:none;">
            <div class="paulus-hreflang-code-header">
                <h3><?php esc_html_e( 'Generated HTML', 'paulus' ); ?></h3>
                <button type="button" class="button" id="paulus-hreflang-gen-copy">
                    <span class="dashicons dashicons-clipboard"></span>
                    <?php esc_html_e( 'Copy to Clipboard', 'paulus' ); ?>
                </button>
            </div>
            <pre><code id="paulus-hreflang-gen-code"></code></pre>
            <p class="paulus-hreflang-description"><?php esc_html_e( 'Place this code inside the <head> section of each page listed above.', 'paulus' ); ?></p>
        </div>
    </div>
</div>
