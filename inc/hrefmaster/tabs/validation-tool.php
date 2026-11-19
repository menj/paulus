<?php
/**
 * HrefMaster – Validation Tool Tab
 *
 * @package HrefMaster
 * @since   3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="hrefmaster-card">
    <h2><?php esc_html_e( 'Single URL Validation', 'paulus' ); ?></h2>
    <p class="hrefmaster-description"><?php esc_html_e( 'Enter a URL to check which hreflang tags are present on that page and whether they follow best practices.', 'paulus' ); ?></p>

    <div class="hrefmaster-validator-form">
        <input type="url" id="hrefmaster-validate-url" class="hrefmaster-input hrefmaster-input--wide" placeholder="https://example.com/page" />
        <button type="button" class="button hrefmaster-btn hrefmaster-btn--primary" id="hrefmaster-validate-btn">
            <span class="dashicons dashicons-yes-alt"></span>
            <?php esc_html_e( 'Validate', 'paulus' ); ?>
        </button>
    </div>

    <div id="hrefmaster-validate-results" style="display:none;">
        <div id="hrefmaster-validate-status" class="hrefmaster-status-bar"></div>
        <table class="hrefmaster-table" id="hrefmaster-validate-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Language', 'paulus' ); ?></th>
                    <th><?php esc_html_e( 'URL', 'paulus' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'paulus' ); ?></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
        <div id="hrefmaster-validate-warnings" class="hrefmaster-warnings"></div>
    </div>
</div>

<div class="hrefmaster-card">
    <h2><?php esc_html_e( 'Bulk Validation', 'paulus' ); ?></h2>
    <p class="hrefmaster-description"><?php esc_html_e( 'Scan all your content with hreflang tags and check for common issues: missing reciprocal links, invalid language codes, and unreachable URLs.', 'paulus' ); ?></p>

    <button type="button" class="button hrefmaster-btn hrefmaster-btn--primary" id="hrefmaster-bulk-validate-btn">
        <span class="dashicons dashicons-search"></span>
        <?php esc_html_e( 'Run Bulk Validation', 'paulus' ); ?>
    </button>

    <div id="hrefmaster-bulk-validate-progress" style="display:none;">
        <div class="hrefmaster-progress">
            <div class="hrefmaster-progress__bar" id="hrefmaster-bv-progress-bar"></div>
        </div>
        <p id="hrefmaster-bv-progress-text"></p>
    </div>

    <div id="hrefmaster-bulk-validate-results" style="display:none;">
        <table class="hrefmaster-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Page', 'paulus' ); ?></th>
                    <th><?php esc_html_e( 'Tags', 'paulus' ); ?></th>
                    <th><?php esc_html_e( 'Issues', 'paulus' ); ?></th>
                </tr>
            </thead>
            <tbody id="hrefmaster-bv-results-body"></tbody>
        </table>
    </div>
</div>
