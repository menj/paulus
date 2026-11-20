<?php
/**
 * Hreflang – Validation Tool Tab
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="paulus-hreflang-card">
    <h2><?php esc_html_e( 'Single URL Validation', 'paulus' ); ?></h2>
    <p class="paulus-hreflang-description"><?php esc_html_e( 'Enter a URL to check which hreflang tags are present on that page and whether they follow best practices.', 'paulus' ); ?></p>

    <div class="paulus-hreflang-validator-form">
        <input type="url" id="paulus-hreflang-validate-url" class="paulus-hreflang-input paulus-hreflang-input--wide" placeholder="https://example.com/page" />
        <button type="button" class="button paulus-hreflang-btn paulus-hreflang-btn--primary" id="paulus-hreflang-validate-btn">
            <span class="dashicons dashicons-yes-alt"></span>
            <?php esc_html_e( 'Validate', 'paulus' ); ?>
        </button>
    </div>

    <div id="paulus-hreflang-validate-results" style="display:none;">
        <div id="paulus-hreflang-validate-status" class="paulus-hreflang-status-bar"></div>
        <table class="paulus-hreflang-table" id="paulus-hreflang-validate-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Language', 'paulus' ); ?></th>
                    <th><?php esc_html_e( 'URL', 'paulus' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'paulus' ); ?></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
        <div id="paulus-hreflang-validate-warnings" class="paulus-hreflang-warnings"></div>
    </div>
</div>

<div class="paulus-hreflang-card">
    <h2><?php esc_html_e( 'Bulk Validation', 'paulus' ); ?></h2>
    <p class="paulus-hreflang-description"><?php esc_html_e( 'Scan all your content with hreflang tags and check for common issues: missing reciprocal links, invalid language codes, and unreachable URLs.', 'paulus' ); ?></p>

    <button type="button" class="button paulus-hreflang-btn paulus-hreflang-btn--primary" id="paulus-hreflang-bulk-validate-btn">
        <span class="dashicons dashicons-search"></span>
        <?php esc_html_e( 'Run Bulk Validation', 'paulus' ); ?>
    </button>

    <div id="paulus-hreflang-bulk-validate-progress" style="display:none;">
        <div class="paulus-hreflang-progress">
            <div class="paulus-hreflang-progress__bar" id="paulus-hreflang-bv-progress-bar"></div>
        </div>
        <p id="paulus-hreflang-bv-progress-text"></p>
    </div>

    <div id="paulus-hreflang-bulk-validate-results" style="display:none;">
        <table class="paulus-hreflang-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Page', 'paulus' ); ?></th>
                    <th><?php esc_html_e( 'Tags', 'paulus' ); ?></th>
                    <th><?php esc_html_e( 'Issues', 'paulus' ); ?></th>
                </tr>
            </thead>
            <tbody id="paulus-hreflang-bv-results-body"></tbody>
        </table>
    </div>
</div>
