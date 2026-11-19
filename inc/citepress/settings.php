<?php
/**
 * Settings — registration, sanitization, and admin UI pages (Settings + Analytics).
 * No bug fixes in this file; content is verbatim from v2.8.0.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action('admin_init', 'citepress_register_setting');

function citepress_register_setting() {
    register_setting('paulus_citepress', 'paulus_citepress', 'citepress_sanitize_settings');
}

// Sanitize settings with enhanced validation.
// Nonce verification is handled by register_setting() / settings_fields() — PHPCS false positive.
// phpcs:disable WordPress.Security.NonceVerification.Recommended
function citepress_sanitize_settings( $input ) {
    $sanitized = array();
    
    $sanitized['auto_display'] = (isset($input['auto_display']) && in_array($input['auto_display'], array('yes', 'no'), true)) ? $input['auto_display'] : 'no';
    $sanitized['display_position'] = (isset($input['display_position']) && in_array($input['display_position'], array('top', 'bottom'), true)) ? $input['display_position'] : 'bottom';
    $sanitized['show_toggle'] = (isset($input['show_toggle']) && $input['show_toggle'] === 'yes') ? 'yes' : 'no';
    $sanitized['enable_analytics'] = (isset($input['enable_analytics']) && in_array($input['enable_analytics'], array('yes', 'no'), true)) ? $input['enable_analytics'] : 'no';
    $sanitized['require_consent_for_analytics'] = (isset($input['require_consent_for_analytics']) && $input['require_consent_for_analytics'] === 'yes') ? 'yes' : 'no';
    $sanitized['analytics_cooldown_mode'] = (isset($input['analytics_cooldown_mode']) && in_array($input['analytics_cooldown_mode'], array('session', 'ip_hash', 'none'), true)) ? $input['analytics_cooldown_mode'] : 'session';
    $sanitized['analytics_retention_days'] = (isset($input['analytics_retention_days']) && in_array($input['analytics_retention_days'], array('0', '30', '90', '180', '365'), true)) ? $input['analytics_retention_days'] : '90';
    $sanitized['show_google_scholar'] = (isset($input['show_google_scholar']) && $input['show_google_scholar'] === 'yes') ? 'yes' : 'no';
    $sanitized['show_jsonld'] = (isset($input['show_jsonld']) && $input['show_jsonld'] === 'yes') ? 'yes' : 'no';
    $sanitized['show_og_academic'] = (isset($input['show_og_academic']) && $input['show_og_academic'] === 'yes') ? 'yes' : 'no';
    
    if (isset($input['post_types']) && is_array($input['post_types'])) {
        $valid_post_types = get_post_types(array('public' => true));
        $sanitized['post_types'] = array_intersect($input['post_types'], $valid_post_types);
    } else {
        $sanitized['post_types'] = array('post');
    }
    
    if (isset($input['excluded_posts']) && !empty($input['excluded_posts'])) {
        $excluded_ids = array_map('trim', explode(',', $input['excluded_posts']));
        $excluded_ids = array_map('absint', $excluded_ids);
        $excluded_ids = array_filter($excluded_ids);
        $sanitized['excluded_posts'] = implode(',', $excluded_ids);
    } else {
        $sanitized['excluded_posts'] = '';
    }
    
    $threshold = isset($input['et_al_threshold']) ? absint($input['et_al_threshold']) : 3;
    $sanitized['et_al_threshold'] = max(1, min(999, $threshold));
    
    $sanitized['format_display_mode'] = (isset($input['format_display_mode']) && in_array($input['format_display_mode'], array('dropdown', 'tabs', 'buttons'), true)) ? $input['format_display_mode'] : 'dropdown';
    $sanitized['box_style']     = ( isset( $input['box_style'] ) && in_array( $input['box_style'], array( 'classic', 'academic', 'minimal' ), true ) ) ? $input['box_style'] : 'classic';
    $sanitized['sharp_corners'] = ( isset( $input['sharp_corners'] ) && $input['sharp_corners'] === 'yes' ) ? 'yes' : 'no';
    
    $sanitized['color_scheme'] = (isset($input['color_scheme']) && in_array($input['color_scheme'], array('auto', 'light', 'dark'), true)) ? $input['color_scheme'] : 'auto';
    
    // NEW (v2.7.0): Validate enabled_export_formats
    $all_export_formats = array('bibtex', 'ris', 'endnote', 'csl-json', 'cff', 'dublin-core');
    if (isset($input['enabled_export_formats']) && is_array($input['enabled_export_formats'])) {
        $sanitized['enabled_export_formats'] = array();
        foreach ($input['enabled_export_formats'] as $ef) {
            $ef = sanitize_text_field($ef);
            if (in_array($ef, $all_export_formats, true)) {
                $sanitized['enabled_export_formats'][] = $ef;
            }
        }
        if (empty($sanitized['enabled_export_formats'])) {
            add_settings_error(
                'citepress_messages',
                'citepress_export_format_error',
                __('At least one export format must be enabled. BibTeX has been enabled by default.', 'paulus'),
                'error'
            );
            $sanitized['enabled_export_formats'] = array('bibtex');
        }
    } else {
        $sanitized['enabled_export_formats'] = $all_export_formats;
    }
    
    // NEW (v2.4.0): Validate enabled_formats
    if (isset($input['enabled_formats']) && is_array($input['enabled_formats'])) {
        $all_formats = array_keys(citepress_get_citation_styles());
        $sanitized['enabled_formats'] = array();
        
        foreach ($input['enabled_formats'] as $format) {
            $format = sanitize_text_field($format);
            if (in_array($format, $all_formats, true)) {
                $sanitized['enabled_formats'][] = $format;
            }
        }
        
        // Ensure at least one format is enabled
        if (empty($sanitized['enabled_formats'])) {
            add_settings_error(
                'citepress_messages',
                'citepress_format_error',
                __('At least one citation format must be enabled. APA format has been enabled by default.', 'paulus'),
                'error'
            );
            $sanitized['enabled_formats'] = array('apa');
        }
    } else {
        // If no formats submitted, enable all by default
        $sanitized['enabled_formats'] = array_keys(citepress_get_citation_styles());
    }
    
    return $sanitized;
}
// phpcs:enable WordPress.Security.NonceVerification.Recommended

// Adding settings page in wp menu
add_action('admin_menu', 'citepress_setting_menu');

function citepress_setting_menu() {
    // Minimalist citation/quote icon as SVG data URI
    $icon_svg = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="#a7aaad"><path d="M8.5 5.5C8.5 4.12 7.38 3 6 3S3.5 4.12 3.5 5.5c0 1.03.63 1.92 1.52 2.3-.15 1.52-.78 2.87-1.87 3.93a.5.5 0 0 0 .35.85c2.14 0 3.92-1.15 4.67-2.85.23-.53.33-1.1.33-1.73V5.5zm8 0C16.5 4.12 15.38 3 14 3s-2.5 1.12-2.5 2.5c0 1.03.63 1.92 1.52 2.3-.15 1.52-.78 2.87-1.87 3.93a.5.5 0 0 0 .35.85c2.14 0 3.92-1.15 4.67-2.85.23-.53.33-1.1.33-1.73V5.5z"/></svg>');
    
    add_menu_page(
        __('CitePress Settings', 'paulus'),
        __('CitePress', 'paulus'),
        'manage_options',
        'citepress',
        'citepress_setting_page',
        $icon_svg,
        55
    );
    
    add_submenu_page(
        'citepress',
        __('Analytics', 'paulus'),
        __('Analytics', 'paulus'),
        'manage_options',
        'citepress-analytics',
        'citepress_analytics_page'
    );
}

// Display admin page
function citepress_setting_page() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'paulus'));
    }
    
    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- both $_GET values are set by WordPress core
    // after options.php and citepress_handle_analytics_purge() have already verified their respective nonces.
    if (isset($_GET['settings-updated'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- set by WP core after options.php/purge handler verified nonce
        add_settings_error('citepress_messages', 'citepress_message', __('Settings Saved', 'paulus'), 'updated');
    }
    
    if (isset($_GET['citepress_purged'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- set by WP core after options.php/purge handler verified nonce
        add_settings_error('citepress_messages', 'citepress_purged', __('All analytics data has been purged.', 'paulus'), 'updated');
    }
    // phpcs:enable WordPress.Security.NonceVerification.Recommended
    
    settings_errors('citepress_messages');
    
    ?>
    <div class="wrap">
        <div class="citepress-page-header">
            <div class="citepress-page-header-content">
                <h1 class="citepress-page-title">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                        <polyline points="10 9 9 9 8 9"/>
                    </svg>
                    <?php echo esc_html(get_admin_page_title()); ?>
                </h1>
                <span class="citepress-version-badge"><?php echo 'v' . esc_html(CITEPRESS_VERSION); ?></span>
            </div>
            <p class="citepress-page-description"><?php esc_html_e('Professional academic citation management with multiple formats and export options', 'paulus'); ?></p>
        </div>
        
        <div class="citepress-admin-container">
            <form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
                <input type="hidden" name="citepress_active_tab" id="citepress_active_tab" value="<?php echo esc_attr( isset( $_GET['citepress_tab'] ) ? sanitize_key( $_GET['citepress_tab'] ) : 'general' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only tab routing parameter; no data is processed ?>" />
                <?php
                settings_fields('paulus_citepress');
                citepress_admin_form();
                submit_button(__('Save Changes', 'paulus'));
                ?>
            </form>
        </div>
    </div>
    <?php
}

// Admin form display function
function citepress_admin_form() {
    global $citepress_setting;
    $post_types = get_post_types(array('public' => true), 'objects');
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only tab routing parameter; sanitize_key applied; no data is processed
    $active_tab = isset( $_GET['citepress_tab'] ) ? sanitize_key( $_GET['citepress_tab'] ) : 'general';
    $valid_tabs = array( 'general', 'formats', 'display', 'metadata', 'analytics', 'preview' );
    if ( ! in_array( $active_tab, $valid_tabs, true ) ) { $active_tab = 'general'; }
    ?>
    <div class="citepress-admin">
        <div class="citepress-tabs">
            <button type="button" class="citepress-tab-button <?php echo $active_tab === 'general'  ? 'active' : ''; ?>" data-tab="general">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 1v6m0 6v10M1 12h6m6 0h10"/></svg>
                <?php esc_html_e('General', 'paulus'); ?>
            </button>
            <button type="button" class="citepress-tab-button <?php echo $active_tab === 'formats'  ? 'active' : ''; ?>" data-tab="formats">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                <?php esc_html_e('Formats', 'paulus'); ?>
            </button>
            <button type="button" class="citepress-tab-button <?php echo $active_tab === 'display'  ? 'active' : ''; ?>" data-tab="display">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/></svg>
                <?php esc_html_e('Display', 'paulus'); ?>
            </button>
            <button type="button" class="citepress-tab-button <?php echo $active_tab === 'metadata' ? 'active' : ''; ?>" data-tab="metadata">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6m-4 5H8m8 4H8m2-8H8"/></svg>
                <?php esc_html_e('Metadata & SEO', 'paulus'); ?>
            </button>
            <button type="button" class="citepress-tab-button <?php echo $active_tab === 'analytics' ? 'active' : ''; ?>" data-tab="analytics">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
                <?php esc_html_e('Analytics', 'paulus'); ?>
            </button>
            <button type="button" class="citepress-tab-button <?php echo $active_tab === 'preview'  ? 'active' : ''; ?>" data-tab="preview">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <?php esc_html_e('Preview', 'paulus'); ?>
            </button>
        </div>
        
        <!-- General Settings Tab -->
        <div class="citepress-tab-content <?php echo $active_tab === 'general'  ? 'active' : ''; ?>" id="general-tab">
            <div class="citepress-section-header">
                <h2><?php esc_html_e('Citation Formats', 'paulus'); ?></h2>
                <p><?php esc_html_e('20 professional citation formats available. Users can switch formats using the dropdown.', 'paulus'); ?></p>
            </div>
            
            <div class="citepress-formats-grid">
                <div class="citepress-format-card">
                    <div class="citepress-format-icon">📚</div>
                    <div class="citepress-format-info">
                        <h4>APA</h4>
                        <span>American Psychological Association</span>
                    </div>
                </div>
                <div class="citepress-format-card">
                    <div class="citepress-format-icon">📖</div>
                    <div class="citepress-format-info">
                        <h4>MLA</h4>
                        <span>Modern Language Association</span>
                    </div>
                </div>
                <div class="citepress-format-card">
                    <div class="citepress-format-icon">⚡</div>
                    <div class="citepress-format-info">
                        <h4>IEEE</h4>
                        <span>Engineering & Electronics</span>
                    </div>
                </div>
                <div class="citepress-format-card">
                    <div class="citepress-format-icon">🎓</div>
                    <div class="citepress-format-info">
                        <h4>Harvard</h4>
                        <span>Harvard Referencing</span>
                    </div>
                </div>
                <div class="citepress-format-card">
                    <div class="citepress-format-icon">📜</div>
                    <div class="citepress-format-info">
                        <h4>Chicago</h4>
                        <span>Chicago Manual of Style</span>
                    </div>
                </div>
                <div class="citepress-format-card">
                    <div class="citepress-format-icon">🏥</div>
                    <div class="citepress-format-info">
                        <h4>Vancouver</h4>
                        <span>Medical & Health Sciences</span>
                    </div>
                </div>
                <div class="citepress-format-card">
                    <div class="citepress-format-icon">⚕️</div>
                    <div class="citepress-format-info">
                        <h4>AMA</h4>
                        <span>American Medical Association</span>
                    </div>
                </div>
                <div class="citepress-format-card">
                    <div class="citepress-format-icon">👥</div>
                    <div class="citepress-format-info">
                        <h4>ASA</h4>
                        <span>American Sociological Association</span>
                    </div>
                </div>
                <div class="citepress-format-card">
                    <div class="citepress-format-icon">✍️</div>
                    <div class="citepress-format-info">
                        <h4>Turabian</h4>
                        <span>Turabian Style</span>
                    </div>
                </div>
                <div class="citepress-format-card">
                    <div class="citepress-format-icon">🌐</div>
                    <div class="citepress-format-info">
                        <h4>Wikipedia</h4>
                        <span>{{cite web}} Template</span>
                    </div>
                </div>
                <!-- NEW FORMAT CARDS (v2.4.0) -->
                <div class="citepress-format-card">
                    <div class="citepress-format-icon">🧪</div>
                    <div class="citepress-format-info">
                        <h4>ACS</h4>
                        <span>American Chemical Society</span>
                    </div>
                </div>
                <div class="citepress-format-card">
                    <div class="citepress-format-icon">⚛️</div>
                    <div class="citepress-format-info">
                        <h4>AIP</h4>
                        <span>American Institute of Physics</span>
                    </div>
                </div>
                <div class="citepress-format-card">
                    <div class="citepress-format-icon">🏥</div>
                    <div class="citepress-format-info">
                        <h4>NLM</h4>
                        <span>National Library of Medicine</span>
                    </div>
                </div>
                <div class="citepress-format-card">
                    <div class="citepress-format-icon">🌍</div>
                    <div class="citepress-format-info">
                        <h4>AAA</h4>
                        <span>American Anthropological Assoc.</span>
                    </div>
                </div>
                <div class="citepress-format-card">
                    <div class="citepress-format-icon">🗳️</div>
                    <div class="citepress-format-info">
                        <h4>APSA</h4>
                        <span>American Political Science Assoc.</span>
                    </div>
                </div>
                <div class="citepress-format-card">
                    <div class="citepress-format-icon">⚖️</div>
                    <div class="citepress-format-info">
                        <h4>OSCOLA</h4>
                        <span>Oxford Legal Citations</span>
                    </div>
                </div>
                <div class="citepress-format-card">
                    <div class="citepress-format-icon">🔬</div>
                    <div class="citepress-format-info">
                        <h4>Nature</h4>
                        <span>Nature Journal Style</span>
                    </div>
                </div>
                <div class="citepress-format-card">
                    <div class="citepress-format-icon">💻</div>
                    <div class="citepress-format-info">
                        <h4>ACM</h4>
                        <span>Association for Computing Machinery</span>
                    </div>
                </div>
                <div class="citepress-format-card">
                    <div class="citepress-format-icon">⚖️</div>
                    <div class="citepress-format-info">
                        <h4>Bluebook</h4>
                        <span>US Legal Citation</span>
                    </div>
                </div>
                <div class="citepress-format-card">
                    <div class="citepress-format-icon">🌐</div>
                    <div class="citepress-format-info">
                        <h4>ISO 690</h4>
                        <span>International Standard</span>
                    </div>
                </div>
            </div>
            
            <div class="citepress-card">
                <div class="citepress-card-header">
                    <h3><?php esc_html_e('Multiple Authors', 'paulus'); ?></h3>
                </div>
                <div class="citepress-card-body">
                    <label class="citepress-inline-label">
                        <?php esc_html_e('Show "et al." after', 'paulus'); ?>
                        <input type="number" name="paulus_citepress[et_al_threshold]" value="<?php echo esc_attr($citepress_setting['et_al_threshold']); ?>" min="1" max="999" class="citepress-input-small" />
                        <?php esc_html_e('authors', 'paulus'); ?>
                    </label>
                    <p class="citepress-help-text"><?php esc_html_e('For Co-Authors Plus compatibility. Enter 999 to always show all authors.', 'paulus'); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Formats Tab (NEW in v2.4.0) -->
        <div class="citepress-tab-content <?php echo $active_tab === 'formats'  ? 'active' : ''; ?>" id="formats-tab">
            <div class="citepress-section-header">
                <h2><?php esc_html_e('Available Citation Formats', 'paulus'); ?></h2>
                <p><?php esc_html_e('Select which citation formats should be available to users. Disabled formats will not appear in the citation dropdown. At least one format must remain enabled.', 'paulus'); ?></p>
            </div>
            
            <?php
            $format_metadata = citepress_get_format_metadata();
            $enabled_formats = citepress_get_enabled_formats();
            
            // Group formats by category
            $categories = array(
                'general' => array('title' => __('General Purpose', 'paulus'), 'formats' => array()),
                'sciences' => array('title' => __('Sciences', 'paulus'), 'formats' => array()),
                'medical' => array('title' => __('Medical & Health', 'paulus'), 'formats' => array()),
                'social-sciences' => array('title' => __('Social Sciences', 'paulus'), 'formats' => array()),
                'engineering' => array('title' => __('Engineering & Technology', 'paulus'), 'formats' => array()),
                'humanities' => array('title' => __('Humanities', 'paulus'), 'formats' => array()),
                'legal' => array('title' => __('Legal', 'paulus'), 'formats' => array()),
                'web' => array('title' => __('Web & Digital', 'paulus'), 'formats' => array())
            );
            
            // Organize formats into categories
            foreach ($format_metadata as $format_id => $metadata) {
                $category = $metadata['category'];
                if (isset($categories[$category])) {
                    $categories[$category]['formats'][$format_id] = $metadata;
                }
            }
            ?>
            
            <div class="citepress-formats-selection">
                <div class="citepress-bulk-actions">
                    <button type="button" class="citepress-btn citepress-btn-secondary citepress-select-all">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 11l3 3L22 4"/>
                            <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
                        </svg>
                        <?php esc_html_e('Select All', 'paulus'); ?>
                    </button>
                    <button type="button" class="citepress-btn citepress-btn-secondary citepress-select-none">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                        </svg>
                        <?php esc_html_e('Clear All', 'paulus'); ?>
                    </button>
                </div>
                
                <?php foreach ($categories as $category_id => $category_data): ?>
                    <?php if (!empty($category_data['formats'])): ?>
                        <div class="citepress-card citepress-format-category">
                            <div class="citepress-card-header">
                                <h3><?php echo esc_html($category_data['title']); ?></h3>
                                <span class="citepress-format-count">
                                    <?php 
                                    $category_enabled = count(array_intersect(array_keys($category_data['formats']), $enabled_formats));
                                    $category_total = count($category_data['formats']);
                                    printf(
                                        /* translators: 1: number of enabled formats, 2: total number of formats */
                                        esc_html__('%1$d of %2$d enabled', 'paulus'),
                                        absint($category_enabled),
                                        absint($category_total)
                                    );
                                    ?>
                                </span>
                            </div>
                            <div class="citepress-card-body">
                                <div class="citepress-format-checkboxes">
                                    <?php foreach ($category_data['formats'] as $format_id => $metadata): ?>
                                        <label class="citepress-format-checkbox">
                                            <input 
                                                type="checkbox" 
                                                name="paulus_citepress[enabled_formats][]" 
                                                value="<?php echo esc_attr($format_id); ?>"
                                                <?php checked(in_array($format_id, $enabled_formats)); ?>
                                                class="citepress-format-toggle"
                                            />
                                            <span class="citepress-format-checkbox-content">
                                                <span class="citepress-format-icon"><?php echo esc_html($metadata['icon']); ?></span>
                                                <span class="citepress-format-info">
                                                    <strong><?php echo esc_html($metadata['name']); ?></strong>
                                                    <span class="citepress-format-description"><?php echo esc_html($metadata['full_name']); ?></span>
                                                </span>
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            
            <div class="citepress-notice citepress-notice-info" style="margin-top: 24px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="16" x2="12" y2="12"/>
                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                </svg>
                <div>
                    <strong><?php esc_html_e('Note:', 'paulus'); ?></strong>
                    <?php esc_html_e('Disabling a format will hide it from the citation dropdown on your site. Users will only see enabled formats. At least one format must remain enabled.', 'paulus'); ?>
                </div>
            </div>
        </div>
        
        <!-- Display Options Tab -->
        <div class="citepress-tab-content <?php echo $active_tab === 'display'  ? 'active' : ''; ?>" id="display-tab">
            <div class="citepress-section-header">
                <h2><?php esc_html_e('Display Settings', 'paulus'); ?></h2>
                <p><?php esc_html_e('Control how and where the citation box appears on your site.', 'paulus'); ?></p>
            </div>
            
            <div class="citepress-card">
                <div class="citepress-card-header">
                    <h3><?php esc_html_e('Auto Display', 'paulus'); ?></h3>
                </div>
                <div class="citepress-card-body">
                    <label class="citepress-toggle">
                        <input type="checkbox" name="paulus_citepress[auto_display]" value="yes" <?php checked($citepress_setting['auto_display'], 'yes'); ?> />
                        <span class="citepress-toggle-slider"></span>
                        <span class="citepress-toggle-label"><?php esc_html_e('Automatically display citation box', 'paulus'); ?></span>
                    </label>
                    <p class="citepress-help-text"><?php esc_html_e('When enabled, the citation box appears automatically without using a shortcode.', 'paulus'); ?></p>
                </div>
            </div>
            
            <div class="citepress-card">
                <div class="citepress-card-header">
                    <h3><?php esc_html_e('Position', 'paulus'); ?></h3>
                </div>
                <div class="citepress-card-body">
                    <div class="citepress-radio-group">
                        <label class="citepress-radio-card <?php echo $citepress_setting['display_position'] === 'top' ? 'selected' : ''; ?>">
                            <input type="radio" name="paulus_citepress[display_position]" value="top" <?php checked($citepress_setting['display_position'], 'top'); ?> />
                            <span class="citepress-radio-icon">↑</span>
                            <span class="citepress-radio-text"><?php esc_html_e('Top of content', 'paulus'); ?></span>
                        </label>
                        <label class="citepress-radio-card <?php echo $citepress_setting['display_position'] === 'bottom' ? 'selected' : ''; ?>">
                            <input type="radio" name="paulus_citepress[display_position]" value="bottom" <?php checked($citepress_setting['display_position'], 'bottom'); ?> />
                            <span class="citepress-radio-icon">↓</span>
                            <span class="citepress-radio-text"><?php esc_html_e('Bottom of content', 'paulus'); ?></span>
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="citepress-card">
                <div class="citepress-card-header">
                    <h3><?php esc_html_e('Post Types', 'paulus'); ?></h3>
                </div>
                <div class="citepress-card-body">
                    <div class="citepress-checkbox-grid">
                        <?php foreach ($post_types as $post_type): ?>
                        <label class="citepress-checkbox-card">
                            <input type="checkbox" name="paulus_citepress[post_types][]" value="<?php echo esc_attr($post_type->name); ?>" 
                                <?php checked(in_array($post_type->name, (array)$citepress_setting['post_types'])); ?> />
                            <span class="citepress-checkbox-indicator"></span>
                            <span class="citepress-checkbox-text"><?php echo esc_html($post_type->label); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="citepress-card">
                <div class="citepress-card-header">
                    <h3><?php esc_html_e('Exclude Posts', 'paulus'); ?></h3>
                </div>
                <div class="citepress-card-body">
                    <textarea name="paulus_citepress[excluded_posts]" rows="2" class="citepress-textarea" placeholder="1, 5, 10"><?php echo esc_textarea($citepress_setting['excluded_posts']); ?></textarea>
                    <p class="citepress-help-text"><?php esc_html_e('Enter post IDs to exclude, separated by commas.', 'paulus'); ?></p>
                </div>
            </div>
            
            <div class="citepress-card">
                <div class="citepress-card-header">
                    <h3><?php esc_html_e('Toggle Button', 'paulus'); ?></h3>
                </div>
                <div class="citepress-card-body">
                    <label class="citepress-toggle">
                        <input type="checkbox" name="paulus_citepress[show_toggle]" value="yes" <?php checked($citepress_setting['show_toggle'], 'yes'); ?> />
                        <span class="citepress-toggle-slider"></span>
                        <span class="citepress-toggle-label"><?php esc_html_e('Show expand/collapse toggle', 'paulus'); ?></span>
                    </label>
                    <p class="citepress-help-text"><?php esc_html_e('Allow users to hide/show the citation box.', 'paulus'); ?></p>
                </div>
            </div>
            
            <div class="citepress-card">
                <div class="citepress-card-header">
                    <h3>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="16" rx="2"/><path d="M2 7h20"/></svg>
                        <?php esc_html_e('Box Style', 'paulus'); ?>
                    </h3>
                </div>
                <div class="citepress-card-body">
                    <?php $current_box_style = isset( $citepress_setting['box_style'] ) ? $citepress_setting['box_style'] : 'classic'; ?>
                    <div class="citepress-radio-group citepress-style-cards">

                        <label class="citepress-radio-card citepress-style-card <?php echo $current_box_style === 'classic' ? 'selected' : ''; ?>">
                            <input type="radio" class="citepress-box-style-radio" name="paulus_citepress[box_style]" value="classic" <?php checked( $current_box_style, 'classic' ); ?> />
                            <span class="citepress-style-thumb" aria-hidden="true">
                                <svg viewBox="0 0 80 56" xmlns="http://www.w3.org/2000/svg">
                                    <rect x="1" y="1" width="78" height="54" rx="4" fill="#fff" stroke="#dde3ec" stroke-width="1"/>
                                    <rect x="1" y="1" width="4" height="54" rx="2" fill="#3b6ef5"/>
                                    <rect x="1" y="1" width="78" height="16" rx="4" fill="#f7f9fc" stroke="#dde3ec" stroke-width="1"/>
                                    <rect x="1" y="1" width="78" height="1" fill="#f7f9fc"/><!-- hide top of left bar inside header -->
                                    <rect x="10" y="6" width="30" height="3" rx="1" fill="#3b6ef5" opacity=".7"/>
                                    <rect x="44" y="6" width="16" height="3" rx="1.5" fill="#3b6ef5" opacity=".3"/>
                                    <rect x="62" y="6" width="12" height="3" rx="1.5" fill="#3b6ef5" opacity=".3"/>
                                    <rect x="10" y="23" width="58" height="2.5" rx="1" fill="#1a202c" opacity=".15"/>
                                    <rect x="10" y="29" width="48" height="2.5" rx="1" fill="#1a202c" opacity=".1"/>
                                    <rect x="10" y="35" width="52" height="2.5" rx="1" fill="#1a202c" opacity=".1"/>
                                    <rect x="10" y="41" width="36" height="2.5" rx="1" fill="#1a202c" opacity=".08"/>
                                </svg>
                            </span>
                            <span class="citepress-radio-text"><?php esc_html_e('Classic', 'paulus'); ?></span>
                            <span class="citepress-radio-desc"><?php esc_html_e('Left bar · shadow · tinted header', 'paulus'); ?></span>
                        </label>

                        <label class="citepress-radio-card citepress-style-card <?php echo $current_box_style === 'academic' ? 'selected' : ''; ?>">
                            <input type="radio" class="citepress-box-style-radio" name="paulus_citepress[box_style]" value="academic" <?php checked( $current_box_style, 'academic' ); ?> />
                            <span class="citepress-style-thumb" aria-hidden="true">
                                <svg viewBox="0 0 80 56" xmlns="http://www.w3.org/2000/svg">
                                    <rect x="1" y="1" width="78" height="54" rx="4" fill="#faf8f4" stroke="#d6cfc4" stroke-width="1"/>
                                    <rect x="1" y="1" width="78" height="3" rx="1" fill="#3b6ef5"/>
                                    <rect x="1" y="1" width="78" height="17" rx="4" fill="#f0ece4" stroke="#d6cfc4" stroke-width="1"/>
                                    <rect x="1" y="4" width="78" height="14" fill="#f0ece4"/><!-- square off bottom of header -->
                                    <rect x="10" y="7" width="28" height="3" rx="1" fill="#5a4a2f" opacity=".5"/>
                                    <rect x="42" y="7" width="14" height="3" rx="1.5" fill="#5a4a2f" opacity=".25"/>
                                    <rect x="59" y="7" width="12" height="3" rx="1.5" fill="#5a4a2f" opacity=".25"/>
                                    <rect x="10" y="25" width="58" height="2.5" rx="1" fill="#3d3020" opacity=".14"/>
                                    <rect x="10" y="32" width="48" height="2.5" rx="1" fill="#3d3020" opacity=".09"/>
                                    <rect x="10" y="39" width="52" height="2.5" rx="1" fill="#3d3020" opacity=".09"/>
                                    <rect x="10" y="46" width="36" height="2.5" rx="1" fill="#3d3020" opacity=".07"/>
                                </svg>
                            </span>
                            <span class="citepress-radio-text"><?php esc_html_e('Academic', 'paulus'); ?></span>
                            <span class="citepress-radio-desc"><?php esc_html_e('Top rule · warm background · document feel', 'paulus'); ?></span>
                        </label>

                        <label class="citepress-radio-card citepress-style-card <?php echo $current_box_style === 'minimal' ? 'selected' : ''; ?>">
                            <input type="radio" class="citepress-box-style-radio" name="paulus_citepress[box_style]" value="minimal" <?php checked( $current_box_style, 'minimal' ); ?> />
                            <span class="citepress-style-thumb" aria-hidden="true">
                                <svg viewBox="0 0 80 56" xmlns="http://www.w3.org/2000/svg">
                                    <rect x="1" y="1" width="78" height="54" rx="4" fill="none" stroke="#c8cdd6" stroke-width="1"/>
                                    <line x1="1" y1="17" x2="79" y2="17" stroke="#c8cdd6" stroke-width="1"/>
                                    <rect x="10" y="6" width="24" height="3" rx="1" fill="#1a202c" opacity=".4"/>
                                    <rect x="10" y="24" width="58" height="2.5" rx="1" fill="#1a202c" opacity=".13"/>
                                    <rect x="10" y="30" width="48" height="2.5" rx="1" fill="#1a202c" opacity=".09"/>
                                    <rect x="10" y="36" width="52" height="2.5" rx="1" fill="#1a202c" opacity=".09"/>
                                    <rect x="10" y="42" width="36" height="2.5" rx="1" fill="#1a202c" opacity=".07"/>
                                    <rect x="38" y="4" width="18" height="6" rx="3" fill="none" stroke="#c8cdd6" stroke-width="1"/>
                                    <rect x="59" y="4" width="14" height="6" rx="3" fill="none" stroke="#c8cdd6" stroke-width="1"/>
                                </svg>
                            </span>
                            <span class="citepress-radio-text"><?php esc_html_e('Minimal', 'paulus'); ?></span>
                            <span class="citepress-radio-desc"><?php esc_html_e('Hairline border · transparent · ghost buttons', 'paulus'); ?></span>
                        </label>

                    </div>
                    <p class="citepress-help-text"><?php esc_html_e('Choose the visual style of the citation box. All styles respect the Color Scheme setting below.', 'paulus'); ?></p>
                </div>
            </div>

            <div class="citepress-card">
                <div class="citepress-card-header">
                    <h3>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="0"/><path d="M2 2 l4 0 0 4" opacity=".4"/><path d="M22 2 l-4 0 0 4" opacity=".4"/><path d="M2 22 l4 0 0 -4" opacity=".4"/><path d="M22 22 l-4 0 0 -4" opacity=".4"/></svg>
                        <?php esc_html_e('Corner Style', 'paulus'); ?>
                    </h3>
                </div>
                <div class="citepress-card-body">
                    <?php $sharp_corners = isset( $citepress_setting['sharp_corners'] ) ? $citepress_setting['sharp_corners'] : 'no'; ?>
                    <label class="citepress-toggle">
                        <input type="checkbox" class="citepress-sharp-corners-toggle" name="paulus_citepress[sharp_corners]" value="yes" <?php checked( $sharp_corners, 'yes' ); ?> />
                        <span class="citepress-toggle-slider"></span>
                        <span class="citepress-toggle-label"><?php esc_html_e('Sharp corners', 'paulus'); ?></span>
                    </label>
                    <p class="citepress-help-text"><?php esc_html_e('When off, corners are rounded (default). When on, corners are square — better for themes with a strict grid or editorial aesthetic.', 'paulus'); ?></p>
                </div>
            </div>

            <div class="citepress-card">
                <div class="citepress-card-header">
                    <h3>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        <?php esc_html_e('Format Selector Style', 'paulus'); ?>
                    </h3>
                </div>
                <div class="citepress-card-body">
                    <div class="citepress-radio-group">
                        <label class="citepress-radio-card <?php echo $citepress_setting['format_display_mode'] === 'dropdown' ? 'selected' : ''; ?>">
                            <input type="radio" class="citepress-display-mode-radio" name="paulus_citepress[format_display_mode]" value="dropdown" <?php checked($citepress_setting['format_display_mode'], 'dropdown'); ?> />
                            <span class="citepress-radio-icon">▾</span>
                            <span class="citepress-radio-text"><?php esc_html_e('Dropdown', 'paulus'); ?></span>
                            <span class="citepress-radio-desc"><?php esc_html_e('Compact menu, best for many formats', 'paulus'); ?></span>
                        </label>
                        <label class="citepress-radio-card <?php echo $citepress_setting['format_display_mode'] === 'tabs' ? 'selected' : ''; ?>">
                            <input type="radio" class="citepress-display-mode-radio" name="paulus_citepress[format_display_mode]" value="tabs" <?php checked($citepress_setting['format_display_mode'], 'tabs'); ?> />
                            <span class="citepress-radio-icon">☰</span>
                            <span class="citepress-radio-text"><?php esc_html_e('Tabs', 'paulus'); ?></span>
                            <span class="citepress-radio-desc"><?php esc_html_e('Connected horizontal tabs, best for 3–8 formats', 'paulus'); ?></span>
                        </label>
                        <label class="citepress-radio-card <?php echo $citepress_setting['format_display_mode'] === 'buttons' ? 'selected' : ''; ?>">
                            <input type="radio" class="citepress-display-mode-radio" name="paulus_citepress[format_display_mode]" value="buttons" <?php checked($citepress_setting['format_display_mode'], 'buttons'); ?> />
                            <span class="citepress-radio-icon">●</span>
                            <span class="citepress-radio-text"><?php esc_html_e('Pill Buttons', 'paulus'); ?></span>
                            <span class="citepress-radio-desc"><?php esc_html_e('Modern pill-style, best for 3–6 formats', 'paulus'); ?></span>
                        </label>
                    </div>
                    <p class="citepress-help-text"><?php esc_html_e('Choose how citation format options appear on the frontend. Dropdown is recommended when many formats are enabled.', 'paulus'); ?></p>
                </div>
            </div>

            <div class="citepress-card">
                <div class="citepress-card-header">
                    <h3>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32 1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41M18.66 5.34l1.41-1.41"/></svg>
                        <?php esc_html_e('Color Scheme', 'paulus'); ?>
                    </h3>
                </div>
                <div class="citepress-card-body">
                    <div class="citepress-radio-group">
                        <label class="citepress-radio-card <?php echo ($citepress_setting['color_scheme'] ?? 'auto') === 'auto' ? 'selected' : ''; ?>">
                            <input type="radio" class="citepress-color-scheme-radio" name="paulus_citepress[color_scheme]" value="auto" <?php checked(($citepress_setting['color_scheme'] ?? 'auto'), 'auto'); ?> />
                            <span class="citepress-radio-icon">⬤</span>
                            <span class="citepress-radio-text"><?php esc_html_e('Auto', 'paulus'); ?></span>
                            <span class="citepress-radio-desc"><?php esc_html_e('Follows visitor\'s OS preference', 'paulus'); ?></span>
                        </label>
                        <label class="citepress-radio-card <?php echo ($citepress_setting['color_scheme'] ?? 'auto') === 'light' ? 'selected' : ''; ?>">
                            <input type="radio" class="citepress-color-scheme-radio" name="paulus_citepress[color_scheme]" value="light" <?php checked(($citepress_setting['color_scheme'] ?? 'auto'), 'light'); ?> />
                            <span class="citepress-radio-icon">☀️</span>
                            <span class="citepress-radio-text"><?php esc_html_e('Always Light', 'paulus'); ?></span>
                            <span class="citepress-radio-desc"><?php esc_html_e('Force light mode for all visitors', 'paulus'); ?></span>
                        </label>
                        <label class="citepress-radio-card <?php echo ($citepress_setting['color_scheme'] ?? 'auto') === 'dark' ? 'selected' : ''; ?>">
                            <input type="radio" class="citepress-color-scheme-radio" name="paulus_citepress[color_scheme]" value="dark" <?php checked(($citepress_setting['color_scheme'] ?? 'auto'), 'dark'); ?> />
                            <span class="citepress-radio-icon">🌙</span>
                            <span class="citepress-radio-text"><?php esc_html_e('Always Dark', 'paulus'); ?></span>
                            <span class="citepress-radio-desc"><?php esc_html_e('Force dark mode for all visitors', 'paulus'); ?></span>
                        </label>
                    </div>
                    <p class="citepress-help-text"><?php esc_html_e('Override the citation box color scheme. "Auto" respects the visitor\'s system dark/light mode preference.', 'paulus'); ?></p>
                </div>
            </div>
            
            <div class="citepress-card">
                <div class="citepress-card-header">
                    <h3>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        <?php esc_html_e('Export Formats', 'paulus'); ?>
                    </h3>
                </div>
                <div class="citepress-card-body">
                    <p class="citepress-help-text" style="margin-top:0;margin-bottom:16px;"><?php esc_html_e('Select which export formats are available in the citation box dropdown.', 'paulus'); ?></p>
                    <?php
                    $export_format_labels = array(
                        'bibtex'      => array('name' => 'BibTeX',      'ext' => '.bib',  'desc' => __('LaTeX / Overleaf', 'paulus')),
                        'ris'         => array('name' => 'RIS',         'ext' => '.ris',  'desc' => __('Universal legacy format', 'paulus')),
                        'endnote'     => array('name' => 'EndNote',     'ext' => '.enw',  'desc' => __('EndNote desktop', 'paulus')),
                        'csl-json'    => array('name' => 'CSL-JSON',    'ext' => '.json', 'desc' => __('Modern reference managers (native)', 'paulus')),
                        'cff'         => array('name' => 'CFF',         'ext' => '.cff',  'desc' => __('GitHub CITATION.cff', 'paulus')),
                        'dublin-core' => array('name' => 'Dublin Core', 'ext' => '.xml',  'desc' => __('Institutional repositories', 'paulus')),
                    );
                    $enabled_exports = isset($citepress_setting['enabled_export_formats']) ? $citepress_setting['enabled_export_formats'] : array_keys($export_format_labels);
                    ?>
                    <div class="citepress-export-format-grid">
                        <?php foreach ($export_format_labels as $ef_id => $ef_data): ?>
                        <label class="citepress-export-format-item">
                            <input type="checkbox" 
                                name="paulus_citepress[enabled_export_formats][]" 
                                value="<?php echo esc_attr($ef_id); ?>" 
                                class="citepress-export-format-toggle"
                                <?php checked(in_array($ef_id, $enabled_exports)); ?> />
                            <span class="citepress-export-format-info">
                                <span class="citepress-export-format-label">
                                    <span class="citepress-export-format-name"><?php echo esc_html($ef_data['name']); ?></span>
                                    <span class="citepress-export-format-ext"><?php echo esc_html($ef_data['ext']); ?></span>
                                </span>
                                <span class="citepress-export-format-desc"><?php echo esc_html($ef_data['desc']); ?></span>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Metadata & SEO Tab -->
        <div class="citepress-tab-content <?php echo $active_tab === 'metadata' ? 'active' : ''; ?>" id="metadata-tab">
            <div class="citepress-section-header">
                <h2><?php esc_html_e('Metadata & SEO', 'paulus'); ?></h2>
                <p><?php esc_html_e('Improve discoverability and tracking for your academic content.', 'paulus'); ?></p>
            </div>
            
            <div class="citepress-card">
                <div class="citepress-card-header">
                    <h3>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                        <?php esc_html_e('Google Scholar', 'paulus'); ?>
                    </h3>
                </div>
                <div class="citepress-card-body">
                    <label class="citepress-toggle">
                        <input type="checkbox" name="paulus_citepress[show_google_scholar]" value="yes" <?php checked($citepress_setting['show_google_scholar'], 'yes'); ?> />
                        <span class="citepress-toggle-slider"></span>
                        <span class="citepress-toggle-label"><?php esc_html_e('Add Google Scholar meta tags', 'paulus'); ?></span>
                    </label>
                    
                    <div class="citepress-feature-box">
                        <h4><?php esc_html_e('Meta tags added:', 'paulus'); ?></h4>
                        <div class="citepress-meta-tags">
                            <code>citation_title</code>
                            <code>citation_author</code>
                            <code>citation_publication_date</code>
                            <code>citation_journal_title</code>
                            <code>citation_fulltext_html_url</code>
                            <code>citation_author_orcid</code>
                        </div>
                        <ul class="citepress-benefits">
                            <li><?php esc_html_e('Improves discoverability in Google Scholar', 'paulus'); ?></li>
                            <li><?php esc_html_e('Helps researchers find and cite your work', 'paulus'); ?></li>
                            <li><?php esc_html_e('Invisible to visitors - only for search engines', 'paulus'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="citepress-card">
                <div class="citepress-card-header">
                    <h3>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><path d="M9 14l2 2 4-4"/></svg>
                        <?php esc_html_e('JSON-LD Structured Data', 'paulus'); ?>
                    </h3>
                </div>
                <div class="citepress-card-body">
                    <label class="citepress-toggle">
                        <input type="checkbox" name="paulus_citepress[show_jsonld]" value="yes" <?php checked($citepress_setting['show_jsonld'], 'yes'); ?> />
                        <span class="citepress-toggle-slider"></span>
                        <span class="citepress-toggle-label"><?php esc_html_e('Output ScholarlyArticle schema in JSON-LD format', 'paulus'); ?></span>
                    </label>
                    <p class="description"><?php esc_html_e('Paulus prints its own JSON-LD graph, so this switch changes nothing while the theme is active.', 'paulus'); ?></p>
                    
                    <div class="citepress-feature-box">
                        <h4><?php esc_html_e('Schema properties output:', 'paulus'); ?></h4>
                        <div class="citepress-meta-tags">
                            <code>@type: ScholarlyArticle</code>
                            <code>headline</code>
                            <code>author</code>
                            <code>datePublished</code>
                            <code>dateModified</code>
                            <code>publisher</code>
                            <code>mainEntityOfPage</code>
                        </div>
                        <ul class="citepress-benefits">
                            <li><?php esc_html_e('Google strongly prefers JSON-LD for rich results eligibility', 'paulus'); ?></li>
                            <li><?php esc_html_e('Enables enhanced search snippets for academic content', 'paulus'); ?></li>
                            <li><?php esc_html_e('Complementary to Google Scholar meta tags', 'paulus'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="citepress-card">
                <div class="citepress-card-header">
                    <h3>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                        <?php esc_html_e('Open Graph Academic Tags', 'paulus'); ?>
                    </h3>
                </div>
                <div class="citepress-card-body">
                    <label class="citepress-toggle">
                        <input type="checkbox" name="paulus_citepress[show_og_academic]" value="yes" <?php checked($citepress_setting['show_og_academic'], 'yes'); ?> />
                        <span class="citepress-toggle-slider"></span>
                        <span class="citepress-toggle-label"><?php esc_html_e('Add Open Graph article meta tags', 'paulus'); ?></span>
                    </label>
                    <p class="description"><?php esc_html_e('Paulus prints its own Open Graph tags, so this switch changes nothing while the theme is active.', 'paulus'); ?></p>
                    
                    <div class="citepress-feature-box">
                        <h4><?php esc_html_e('OG tags added:', 'paulus'); ?></h4>
                        <div class="citepress-meta-tags">
                            <code>og:type</code>
                            <code>article:published_time</code>
                            <code>article:modified_time</code>
                            <code>article:author</code>
                            <code>article:section</code>
                        </div>
                        <ul class="citepress-benefits">
                            <li><?php esc_html_e('Enhances link previews on social media platforms', 'paulus'); ?></li>
                            <li><?php esc_html_e('Provides publication metadata for content aggregators', 'paulus'); ?></li>
                            <li><?php esc_html_e('Compatible with existing SEO plugins (no duplication)', 'paulus'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="citepress-card">
                <div class="citepress-card-body">
                    <div class="citepress-inline-notice">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
                        <span>
                            <?php
                            printf(
                                /* translators: %s: link to the Analytics settings tab */
                                wp_kses_post( __( 'Analytics settings have moved to the %s tab.', 'paulus' ) ),
                                '<a href="' . esc_url( admin_url( 'admin.php?page=citepress&citepress_tab=analytics' ) ) . '">' . esc_html__( 'Analytics', 'paulus' ) . '</a>'
                            );
                            ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Analytics Tab -->
        <div class="citepress-tab-content <?php echo $active_tab === 'analytics' ? 'active' : ''; ?>" id="analytics-tab">
            <div class="citepress-section-header">
                <h2><?php esc_html_e('Analytics', 'paulus'); ?></h2>
                <p><?php esc_html_e('Configure how citation interactions are tracked and how long data is retained.', 'paulus'); ?></p>
            </div>

            <?php
            // Quick stat summary — only when analytics is enabled and table exists
            $analytics_enabled_for_tab = isset( $citepress_setting['enable_analytics'] ) && $citepress_setting['enable_analytics'] === 'yes';
            if ( $analytics_enabled_for_tab ) {
                global $wpdb;
                $tbl_check = $wpdb->prefix . 'paulus_citepress_analytics';
                $tbl_check = preg_replace( '/[^A-Za-z0-9_]/', '', $tbl_check );
                $tbl_exists = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $tbl_check ) ) === $tbl_check ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- admin-only existence check; no WP API equivalent
                if ( $tbl_exists ) {
                    $tab_cache = wp_cache_get( 'citepress_analytics_stats', 'paulus' );
                    if ( false === $tab_cache ) {
                        $tbl_q = $tbl_check;
                        $tab_views   = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM `' . $tbl_q . '` WHERE action_type = %s', 'view'   ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter -- $tbl_q constrained to [A-Za-z0-9_]
                        $tab_copies  = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM `' . $tbl_q . '` WHERE action_type = %s', 'copy'   ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter -- $tbl_q constrained to [A-Za-z0-9_]
                        $tab_exports = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM `' . $tbl_q . '` WHERE action_type = %s', 'export' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter -- $tbl_q constrained to [A-Za-z0-9_]
                    } else {
                        $tab_views   = (int) ( $tab_cache['total_views']   ?? 0 );
                        $tab_copies  = (int) ( $tab_cache['total_copies']  ?? 0 );
                        $tab_exports = (int) ( $tab_cache['total_exports'] ?? 0 );
                    }
                    ?>
                    <div class="citepress-stats-overview citepress-stats-overview--compact">
                        <div class="citepress-stat-card">
                            <div class="citepress-stat-icon citepress-stat-views">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </div>
                            <div class="citepress-stat-info">
                                <span class="citepress-stat-number"><?php echo number_format( $tab_views ); ?></span>
                                <span class="citepress-stat-label"><?php esc_html_e( 'Views', 'paulus' ); ?></span>
                            </div>
                        </div>
                        <div class="citepress-stat-card">
                            <div class="citepress-stat-icon citepress-stat-copies">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                            </div>
                            <div class="citepress-stat-info">
                                <span class="citepress-stat-number"><?php echo number_format( $tab_copies ); ?></span>
                                <span class="citepress-stat-label"><?php esc_html_e( 'Copies', 'paulus' ); ?></span>
                            </div>
                        </div>
                        <div class="citepress-stat-card">
                            <div class="citepress-stat-icon citepress-stat-exports">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            </div>
                            <div class="citepress-stat-info">
                                <span class="citepress-stat-number"><?php echo number_format( $tab_exports ); ?></span>
                                <span class="citepress-stat-label"><?php esc_html_e( 'Exports', 'paulus' ); ?></span>
                            </div>
                        </div>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=citepress-analytics' ) ); ?>" class="citepress-btn citepress-btn-secondary citepress-stat-dashboard-link">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
                            <?php esc_html_e( 'Full Dashboard', 'paulus' ); ?>
                        </a>
                    </div>
                    <?php
                }
            }
            ?>

            <!-- Tracking -->
            <div class="citepress-card">
                <div class="citepress-card-header">
                    <h3>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
                        <?php esc_html_e( 'Tracking', 'paulus' ); ?>
                    </h3>
                </div>
                <div class="citepress-card-body">
                    <label class="citepress-toggle">
                        <input type="checkbox" name="paulus_citepress[enable_analytics]" value="yes" <?php checked( $citepress_setting['enable_analytics'], 'yes' ); ?> />
                        <span class="citepress-toggle-slider"></span>
                        <span class="citepress-toggle-label"><?php esc_html_e( 'Enable citation analytics', 'paulus' ); ?></span>
                    </label>
                    <p class="citepress-help-text"><?php esc_html_e( 'Records citation views, copies, and file exports per post.', 'paulus' ); ?></p>

                    <label class="citepress-toggle" style="margin-top: 16px;">
                        <input type="checkbox" name="paulus_citepress[require_consent_for_analytics]" value="yes" <?php checked( $citepress_setting['require_consent_for_analytics'], 'yes' ); ?> />
                        <span class="citepress-toggle-slider"></span>
                        <span class="citepress-toggle-label"><?php esc_html_e( 'Require user consent before tracking', 'paulus' ); ?></span>
                    </label>
                    <?php
                    $consent_plugins_detected = (
                        isset( $_COOKIE['cookieyes-consent'] ) ||
                        isset( $_COOKIE['cmplz_statistics'] ) ||
                        isset( $_COOKIE['CookieConsent'] ) ||
                        isset( $_COOKIE['moove_gdpr_popup'] ) ||
                        isset( $_COOKIE['borlabs-cookie'] ) ||
                        isset( $_COOKIE['cookielawinfo-checkbox-analytics'] ) ||
                        isset( $_COOKIE['cookie_notice_accepted'] ) ||
                        isset( $_COOKIE['euconsent-v2'] )
                    );
                    ?>
                    <p class="citepress-help-text" style="margin-top: 6px;">
                        <?php if ( $consent_plugins_detected ) : ?>
                            <span class="citepress-consent-detected">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                <?php esc_html_e( 'Cookie consent plugin detected — consent will be read from it automatically.', 'paulus' ); ?>
                            </span>
                        <?php else : ?>
                            <?php echo wp_kses(
                                __( '<strong>No consent plugin detected.</strong> This toggle only takes effect when a supported plugin is active (CookieYes, Complianz, CookieBot, Moove GDPR, or Borlabs). Without one, analytics fires regardless of this toggle.', 'paulus' ),
                                array( 'strong' => array() )
                            ); ?>
                        <?php endif; ?>
                    </p>

                    <div class="citepress-feature-box" style="margin-top: 20px;">
                        <h4><?php esc_html_e( 'What gets tracked:', 'paulus' ); ?></h4>
                        <ul class="citepress-benefits">
                            <li><?php esc_html_e( 'Citation format selections (APA, MLA, Wikipedia, etc.)', 'paulus' ); ?></li>
                            <li><?php esc_html_e( 'Copy to clipboard actions', 'paulus' ); ?></li>
                            <li><?php esc_html_e( 'Export downloads (BibTeX, RIS, EndNote, CSL-JSON, CFF, Dublin Core)', 'paulus' ); ?></li>
                            <li><?php esc_html_e( 'Per-post citation statistics', 'paulus' ); ?></li>
                        </ul>
                        <p class="citepress-privacy-note" style="margin-top: 8px;"><?php esc_html_e( 'All data is stored locally in your WordPress database and is never shared with third parties.', 'paulus' ); ?></p>
                    </div>
                </div>
            </div>

            <!-- Cooldown Mode -->
            <div class="citepress-card">
                <div class="citepress-card-header">
                    <h3>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <?php esc_html_e( 'Cooldown Mode', 'paulus' ); ?>
                    </h3>
                </div>
                <div class="citepress-card-body">
                    <div class="citepress-radio-group">
                        <label class="citepress-radio-card <?php echo $citepress_setting['analytics_cooldown_mode'] === 'session' ? 'selected' : ''; ?>">
                            <input type="radio" name="paulus_citepress[analytics_cooldown_mode]" value="session" <?php checked( $citepress_setting['analytics_cooldown_mode'], 'session' ); ?> />
                            <span class="citepress-radio-icon">🔑</span>
                            <span class="citepress-radio-text"><?php esc_html_e( 'Session', 'paulus' ); ?></span>
                            <span class="citepress-radio-desc"><?php esc_html_e( 'One count per browser session. GDPR-friendly — no persistent identifier stored.', 'paulus' ); ?></span>
                        </label>
                        <label class="citepress-radio-card <?php echo $citepress_setting['analytics_cooldown_mode'] === 'ip_hash' ? 'selected' : ''; ?>">
                            <input type="radio" name="paulus_citepress[analytics_cooldown_mode]" value="ip_hash" <?php checked( $citepress_setting['analytics_cooldown_mode'], 'ip_hash' ); ?> />
                            <span class="citepress-radio-icon">🌐</span>
                            <span class="citepress-radio-text"><?php esc_html_e( 'IP Hash', 'paulus' ); ?></span>
                            <span class="citepress-radio-desc"><?php esc_html_e( 'Deduplicates by hashed IP address. Legacy mode — may require consent disclosure.', 'paulus' ); ?></span>
                        </label>
                        <label class="citepress-radio-card <?php echo $citepress_setting['analytics_cooldown_mode'] === 'none' ? 'selected' : ''; ?>">
                            <input type="radio" name="paulus_citepress[analytics_cooldown_mode]" value="none" <?php checked( $citepress_setting['analytics_cooldown_mode'], 'none' ); ?> />
                            <span class="citepress-radio-icon">⚡</span>
                            <span class="citepress-radio-text"><?php esc_html_e( 'None', 'paulus' ); ?></span>
                            <span class="citepress-radio-desc"><?php esc_html_e( 'Every interaction is counted. Best for raw event totals, not unique visitor counts.', 'paulus' ); ?></span>
                        </label>
                    </div>
                    <p class="citepress-help-text"><?php esc_html_e( 'Controls how repeat interactions from the same visitor are handled. Session is recommended for most sites.', 'paulus' ); ?></p>
                </div>
            </div>

            <!-- Data Retention -->
            <div class="citepress-card">
                <div class="citepress-card-header">
                    <h3>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                        <?php esc_html_e( 'Data Retention', 'paulus' ); ?>
                    </h3>
                </div>
                <div class="citepress-card-body">
                    <div class="citepress-inline-label">
                        <span><?php esc_html_e( 'Delete records older than:', 'paulus' ); ?></span>
                        <select name="paulus_citepress[analytics_retention_days]" class="citepress-select" style="min-width: 160px;">
                            <option value="0"   <?php selected( $citepress_setting['analytics_retention_days'], '0'   ); ?>><?php esc_html_e( 'Keep forever', 'paulus' ); ?></option>
                            <option value="30"  <?php selected( $citepress_setting['analytics_retention_days'], '30'  ); ?>><?php esc_html_e( '30 days',      'paulus' ); ?></option>
                            <option value="90"  <?php selected( $citepress_setting['analytics_retention_days'], '90'  ); ?>><?php esc_html_e( '90 days',      'paulus' ); ?></option>
                            <option value="180" <?php selected( $citepress_setting['analytics_retention_days'], '180' ); ?>><?php esc_html_e( '180 days',     'paulus' ); ?></option>
                            <option value="365" <?php selected( $citepress_setting['analytics_retention_days'], '365' ); ?>><?php esc_html_e( '1 year',       'paulus' ); ?></option>
                        </select>
                    </div>
                    <p class="citepress-help-text"><?php esc_html_e( 'Older records are automatically removed by a daily background task. Helps satisfy GDPR storage limitation requirements.', 'paulus' ); ?></p>
                </div>
            </div>

        </div>

        <!-- Preview Tab -->
        <div class="citepress-tab-content <?php echo $active_tab === 'preview'  ? 'active' : ''; ?>" id="preview-tab">
            <div class="citepress-section-header">
                <h2><?php esc_html_e('Preview', 'paulus'); ?></h2>
                <p><?php esc_html_e('See how the citation box will appear on your site.', 'paulus'); ?></p>
            </div>
            
            <div class="citepress-preview-wrapper">
                <div class="citepress-preview-header">
                    <span class="citepress-preview-dot"></span>
                    <span class="citepress-preview-dot"></span>
                    <span class="citepress-preview-dot"></span>
                    <span class="citepress-preview-title"><?php esc_html_e('Live Preview', 'paulus'); ?></span>
                </div>
                <div class="citepress-preview-content">
                    <?php
                    $sample_author = wp_get_current_user()->display_name ?: 'Author Name';
                    $sample_date = date_i18n(get_option('date_format'));
                    $sample_title = __('Sample Article Title', 'paulus');
                    $sample_site = get_bloginfo('name');
                    $sample_url = home_url('/sample-article/');
                    ?>
                    <?php
                    $preview_scheme      = isset( $citepress_setting['color_scheme'] ) ? $citepress_setting['color_scheme'] : 'auto';
                    $preview_box_style   = isset( $citepress_setting['box_style'] ) ? $citepress_setting['box_style'] : 'classic';
                    $preview_sharp       = isset( $citepress_setting['sharp_corners'] ) && $citepress_setting['sharp_corners'] === 'yes';
                    $preview_theme_attr  = ( $preview_scheme === 'auto' ) ? '' : ' data-theme="' . esc_attr( $preview_scheme ) . '"';
                    $preview_style_attr  = ' data-box-style="' . esc_attr( $preview_box_style ) . '"';
                    $preview_corner_attr = $preview_sharp ? ' data-corners="sharp"' : '';
                    ?>
                    <div class="citepress-citation-box citepress-preview-box" role="region" aria-label="<?php esc_attr_e('Citation Preview', 'paulus'); ?>"<?php echo $preview_theme_attr . $preview_style_attr . $preview_corner_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- all values escaped above ?>>

                        <div class="citation-header">
                            <div class="citation-title">
                                <svg class="citepress-cite-icon" width="20" height="20" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M8.5 5.5C8.5 4.12 7.38 3 6 3S3.5 4.12 3.5 5.5c0 1.03.63 1.92 1.52 2.3-.15 1.52-.78 2.87-1.87 3.93a.5.5 0 0 0 .35.85c2.14 0 3.92-1.15 4.67-2.85.23-.53.33-1.1.33-1.73V5.5zm8 0C16.5 4.12 15.38 3 14 3s-2.5 1.12-2.5 2.5c0 1.03.63 1.92 1.52 2.3-.15 1.52-.78 2.87-1.87 3.93a.5.5 0 0 0 .35.85c2.14 0 3.92-1.15 4.67-2.85.23-.53.33-1.1.33-1.73V5.5z"/></svg>
                                <span><?php esc_html_e('Cite This As:', 'paulus'); ?></span>
                                <?php
                                $enabled_formats = citepress_get_enabled_formats();
                                $format_metadata = citepress_get_format_metadata();
                                $first_format = !empty($enabled_formats) ? $enabled_formats[0] : 'apa';
                                ?>
                                <?php
                                $preview_display_mode = isset($citepress_setting['format_display_mode']) ? $citepress_setting['format_display_mode'] : 'dropdown';
                                ?>
                                <!-- Dropdown mode -->
                                <select class="citation-style-select citepress-preview-select citepress-preview-selector" data-mode="dropdown" aria-label="<?php esc_attr_e('Select citation style', 'paulus'); ?>"<?php echo $preview_display_mode !== 'dropdown' ? ' style="display:none"' : ''; ?>>
                                    <?php foreach ($enabled_formats as $format_id): ?>
                                        <?php if (isset($format_metadata[$format_id])): ?>
                                            <option value="<?php echo esc_attr($format_id); ?>" <?php selected($format_id, $first_format); ?>>
                                                <?php echo esc_html($format_metadata[$format_id]['name']); ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                                <!-- Tabs mode -->
                                <div class="citepress-style-tabs citepress-preview-selector" data-mode="tabs" role="tablist"<?php echo $preview_display_mode !== 'tabs' ? ' style="display:none"' : ''; ?>>
                                    <?php foreach ($enabled_formats as $i => $format_id): ?>
                                        <?php if (isset($format_metadata[$format_id])): ?>
                                            <button type="button" class="citepress-style-tab<?php echo $i === 0 ? ' citepress-style-tab--active' : ''; ?>" role="tab" data-style="<?php echo esc_attr($format_id); ?>" aria-selected="<?php echo $i === 0 ? 'true' : 'false'; ?>"><?php echo esc_html($format_metadata[$format_id]['name']); ?></button>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                                <!-- Buttons mode -->
                                <div class="citepress-style-buttons citepress-preview-selector" data-mode="buttons" role="radiogroup"<?php echo $preview_display_mode !== 'buttons' ? ' style="display:none"' : ''; ?>>
                                    <?php foreach ($enabled_formats as $i => $format_id): ?>
                                        <?php if (isset($format_metadata[$format_id])): ?>
                                            <button type="button" class="citepress-style-btn<?php echo $i === 0 ? ' citepress-style-btn--active' : ''; ?>" role="radio" data-style="<?php echo esc_attr($format_id); ?>" aria-checked="<?php echo $i === 0 ? 'true' : 'false'; ?>"><?php echo esc_html($format_metadata[$format_id]['name']); ?></button>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                                <button class="copy-button" type="button" title="<?php esc_attr_e('Copy citation', 'paulus'); ?>" aria-label="<?php esc_attr_e('Copy citation to clipboard', 'paulus'); ?>">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path d="M13.5 2H6.5C5.67 2 5 2.67 5 3.5V10.5C5 11.33 5.67 12 6.5 12H13.5C14.33 12 15 11.33 15 10.5V3.5C15 2.67 14.33 2 13.5 2ZM13.5 10.5H6.5V3.5H13.5V10.5ZM2.5 6H4V7.5H2.5V12.5H9.5V11H11V12.5C11 13.33 10.33 14 9.5 14H2.5C1.67 14 1 13.33 1 12.5V7.5C1 6.67 1.67 6 2.5 6Z" fill="currentColor"/>
                                    </svg>
                                </button>
                                <div class="export-menu">
                                    <button class="export-button" type="button" title="<?php esc_attr_e('Export citation', 'paulus'); ?>" aria-label="<?php esc_attr_e('Export citation', 'paulus'); ?>" aria-expanded="false" aria-haspopup="true">
                                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                            <path d="M8 12L3 7L4.4 5.6L7 8.2V0H9V8.2L11.6 5.6L13 7L8 12ZM2 16C1.45 16 0.979 15.804 0.587 15.412C0.195 15.02 -0.000667 14.5493 7.78539e-07 14V11H2V14H14V11H16V14C16 14.55 15.804 15.021 15.412 15.413C15.02 15.805 14.5493 16.0007 14 16H2Z" fill="currentColor"/>
                                        </svg>
                                    </button>
                                    <div class="export-dropdown" role="menu" aria-label="<?php esc_attr_e('Export options', 'paulus'); ?>">
                                        <?php
                                        $preview_export_labels = array(
                                            'bibtex'      => __('Export as BibTeX', 'paulus'),
                                            'ris'         => __('Export as RIS', 'paulus'),
                                            'endnote'     => __('Export as EndNote', 'paulus'),
                                            'csl-json'    => __('Export as CSL-JSON', 'paulus'),
                                            'cff'         => __('Export as CFF', 'paulus'),
                                            'dublin-core' => __('Export as Dublin Core', 'paulus'),
                                        );
                                        $preview_enabled_exports = isset($citepress_setting['enabled_export_formats']) ? $citepress_setting['enabled_export_formats'] : array_keys($preview_export_labels);
                                        foreach ($preview_export_labels as $pef_id => $pef_label):
                                            $pef_visible = in_array($pef_id, $preview_enabled_exports);
                                        ?>
                                        <button class="export-option citepress-preview-export-option" type="button" data-export-format="<?php echo esc_attr($pef_id); ?>" role="menuitem"<?php echo !$pef_visible ? ' style="display:none"' : ''; ?>><?php echo esc_html($pef_label); ?></button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <button class="toggle-button" type="button" aria-label="<?php esc_attr_e('Toggle citation visibility', 'paulus'); ?>" aria-expanded="true">
                                    <svg class="toggle-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path d="M8 11L3 6L4.4 4.6L8 8.2L11.6 4.6L13 6L8 11Z" fill="currentColor"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="citation-content">
                            <div class="citation-output citepress-preview-citation">
                                <?php echo esc_html($sample_author); ?>. (<?php echo esc_html($sample_date); ?>). <?php echo esc_html($sample_title); ?>. <em><?php echo esc_html($sample_site); ?></em>. <?php echo esc_url($sample_url); ?>
                            </div>
                        </div>
                        <div class="copy-notification" role="status" aria-live="polite"><?php esc_html_e('Citation copied!', 'paulus'); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="citepress-shortcode-info">
                <h4><?php esc_html_e('Shortcode Usage', 'paulus'); ?></h4>
                <div class="citepress-code-block">
                    <code>[cite]</code>
                    <span class="citepress-code-desc"><?php esc_html_e('Basic usage', 'paulus'); ?></span>
                </div>
                <div class="citepress-code-block">
                    <code>[cite style="wikipedia"]</code>
                    <span class="citepress-code-desc"><?php esc_html_e('Default to Wikipedia format', 'paulus'); ?></span>
                </div>
                <div class="citepress-code-block">
                    <code>[cite mode="inline"]</code>
                    <span class="citepress-code-desc"><?php esc_html_e('Inline parenthetical citation', 'paulus'); ?></span>
                </div>
                <div class="citepress-code-block">
                    <code>[cite mode="inline" page="42"]</code>
                    <span class="citepress-code-desc"><?php esc_html_e('Inline with page number', 'paulus'); ?></span>
                </div>
                <div class="citepress-code-block">
                    <code>[cite formats="apa,mla,chicago"]</code>
                    <span class="citepress-code-desc"><?php esc_html_e('Show only specific formats', 'paulus'); ?></span>
                </div>
                <div class="citepress-code-block">
                    <code>[cite exclude_formats="wikipedia"]</code>
                    <span class="citepress-code-desc"><?php esc_html_e('Hide specific formats', 'paulus'); ?></span>
                </div>
                <div class="citepress-code-block">
                    <code>[cite show_export="false"]</code>
                    <span class="citepress-code-desc"><?php esc_html_e('Hide export buttons', 'paulus'); ?></span>
                </div>
                <div class="citepress-code-block">
                    <code>[cite link="false"]</code>
                    <span class="citepress-code-desc"><?php esc_html_e('Disable clickable links in inline citations', 'paulus'); ?></span>
                </div>
                <div class="citepress-code-block">
                    <code>[cite_bibliography]</code>
                    <span class="citepress-code-desc"><?php esc_html_e('Numbered reference list', 'paulus'); ?></span>
                </div>
                <div class="citepress-code-block">
                    <code>[cite_bibliography heading="Sources" numbered="false"]</code>
                    <span class="citepress-code-desc"><?php esc_html_e('Custom heading, unnumbered', 'paulus'); ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    // Localize preview data for JavaScript
    $preview_data = array(
        'author' => $sample_author,
        'date' => $sample_date,
        'dateDMY' => citepress_format_date_dmy(),
        'title' => $sample_title,
        'site' => $sample_site,
        'url' => $sample_url,
        'language' => citepress_get_language_code()
    );
    wp_localize_script('citepress-admin', 'citepressPreviewData', $preview_data);
    ?>
    <?php
}

// Analytics page
function citepress_analytics_page() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'paulus'));
    }
    
    global $wpdb, $citepress_setting;
    $tbl = $wpdb->prefix . 'paulus_citepress_analytics';
    $tbl = preg_replace( '/[^A-Za-z0-9_]/', '', $tbl ); // Constrain identifier before SQL concatenation.

    // Check if analytics is disabled
    $analytics_disabled = !isset($citepress_setting['enable_analytics']) || $citepress_setting['enable_analytics'] !== 'yes';

    // SHOW TABLES has no WP core API equivalent. This is a read-only admin-only
    // existence check; $tbl is already constrained to [A-Za-z0-9_] above.
    if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $tbl ) ) !== $tbl ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- no WP API exists for SHOW TABLES; admin-only read; $tbl hardened above
        echo '<div class="wrap citepress-admin-container"><div class="citepress-admin"><div class="citepress-tab-content active">';
        echo '<div class="citepress-section-header"><h2>' . esc_html__('Citation Analytics', 'paulus') . '</h2>';
        echo '<p>' . esc_html__('Analytics table not found. Please deactivate and reactivate the plugin.', 'paulus') . '</p></div>';
        echo '</div></div></div>';
        return;
    }

    // Use object cache so repeated admin page loads don't hammer the DB.
    $cache_key   = 'citepress_analytics_stats';
    $cache_group = 'citepress';
    $cached      = wp_cache_get( $cache_key, $cache_group );

    if ( false === $cached ) {
        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        // $tbl is constrained to [A-Za-z0-9_] above; identifier cannot use prepare() placeholders.
        // These are admin-only aggregate reads with no practical WP API equivalent.
        $style_stats  = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter -- $tbl constrained to [A-Za-z0-9_]; identifier cannot use prepare() placeholders
            'SELECT citation_style, COUNT(*) as count FROM `' . $tbl . '` WHERE action_type IN (%s, %s) GROUP BY citation_style ORDER BY count DESC',
            'view', 'copy'
        ) );
        $post_stats   = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter -- $tbl constrained to [A-Za-z0-9_]; identifier cannot use prepare() placeholders
            'SELECT post_id, COUNT(*) as count FROM `' . $tbl . '` WHERE action_type IN (%s, %s) GROUP BY post_id ORDER BY count DESC LIMIT 10',
            'view', 'copy'
        ) );
        $export_stats = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter -- $tbl constrained to [A-Za-z0-9_]; identifier cannot use prepare() placeholders
            'SELECT citation_style as format, COUNT(*) as count FROM `' . $tbl . '` WHERE action_type = %s GROUP BY citation_style ORDER BY count DESC',
            'export'
        ) );
        $total_views   = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM `' . $tbl . '` WHERE action_type = %s', 'view' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter -- $tbl constrained to [A-Za-z0-9_]; identifier cannot use prepare() placeholders
        $total_copies  = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM `' . $tbl . '` WHERE action_type = %s', 'copy' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter -- $tbl constrained to [A-Za-z0-9_]; identifier cannot use prepare() placeholders
        $total_exports = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM `' . $tbl . '` WHERE action_type = %s', 'export' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter -- $tbl constrained to [A-Za-z0-9_]; identifier cannot use prepare() placeholders
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        wp_cache_set( $cache_key, compact( 'style_stats', 'post_stats', 'export_stats', 'total_views', 'total_copies', 'total_exports' ), $cache_group, 5 * MINUTE_IN_SECONDS );
    } else {
        extract( $cached, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
    }
    
    ?>
    <div class="wrap">
        <div class="citepress-page-header">
            <div class="citepress-page-header-content">
                <h1 class="citepress-page-title">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 20V10M12 20V4M6 20v-6"/>
                    </svg>
                    <?php esc_html_e('Citation Analytics', 'paulus'); ?>
                </h1>
                <span class="citepress-version-badge"><?php echo 'v' . esc_html(CITEPRESS_VERSION); ?></span>
            </div>
            <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                <p class="citepress-page-description" style="margin:0;"><?php esc_html_e('Track how visitors interact with your citations and monitor engagement metrics', 'paulus'); ?></p>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=citepress&citepress_tab=analytics' ) ); ?>" class="citepress-btn citepress-btn-secondary" style="white-space:nowrap; flex-shrink:0;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 1v6m0 6v10M1 12h6m6 0h10"/></svg>
                    <?php esc_html_e( 'Analytics Settings', 'paulus' ); ?>
                </a>
            </div>
        </div>
        
        <div class="citepress-admin-container">
            <div class="citepress-admin">
                <div class="citepress-tab-content active" style="display: block;">
                
                <?php if ($analytics_disabled): ?>
                <div class="citepress-analytics-notice">
                    <div class="citepress-analytics-notice-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="16" x2="12" y2="12"/>
                            <line x1="12" y1="8" x2="12.01" y2="8"/>
                        </svg>
                    </div>
                    <div class="citepress-analytics-notice-content">
                        <h4><?php esc_html_e('Analytics Tracking is Currently Disabled', 'paulus'); ?></h4>
                        <p>
                            <?php
                            printf(
                                /* translators: %s: link to the Analytics settings tab */
                                wp_kses_post( __( 'Citation analytics tracking is disabled. Enable it in %s to start collecting data.', 'paulus' ) ),
                                '<a href="' . esc_url( admin_url( 'admin.php?page=citepress&citepress_tab=analytics' ) ) . '">' . esc_html__( 'Settings → Analytics', 'paulus' ) . '</a>'
                            );
                            ?>
                        </p>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Stats Overview -->
                <div class="citepress-stats-overview">
                    <div class="citepress-stat-card">
                        <div class="citepress-stat-icon citepress-stat-views">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </div>
                        <div class="citepress-stat-info">
                            <span class="citepress-stat-number"><?php echo number_format(intval($total_views)); ?></span>
                            <span class="citepress-stat-label"><?php esc_html_e('Total Views', 'paulus'); ?></span>
                        </div>
                    </div>
                    <div class="citepress-stat-card">
                        <div class="citepress-stat-icon citepress-stat-copies">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                        </div>
                        <div class="citepress-stat-info">
                            <span class="citepress-stat-number"><?php echo number_format(intval($total_copies)); ?></span>
                            <span class="citepress-stat-label"><?php esc_html_e('Citations Copied', 'paulus'); ?></span>
                        </div>
                    </div>
                    <div class="citepress-stat-card">
                        <div class="citepress-stat-icon citepress-stat-exports">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        </div>
                        <div class="citepress-stat-info">
                            <span class="citepress-stat-number"><?php echo number_format(intval($total_exports)); ?></span>
                            <span class="citepress-stat-label"><?php esc_html_e('File Exports', 'paulus'); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Analytics Grid -->
                <div class="citepress-analytics-grid">
                    <div class="citepress-card">
                        <div class="citepress-card-header">
                            <h3>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
                                <?php esc_html_e('Popular Citation Styles', 'paulus'); ?>
                            </h3>
                        </div>
                        <div class="citepress-card-body citepress-card-body-table">
                            <?php if (!empty($style_stats)): ?>
                            <table class="citepress-table">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Style', 'paulus'); ?></th>
                                        <th><?php esc_html_e('Usage', 'paulus'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($style_stats as $stat): ?>
                                    <tr>
                                        <td>
                                            <span class="citepress-style-badge"><?php echo esc_html(strtoupper($stat->citation_style)); ?></span>
                                        </td>
                                        <td>
                                            <span class="citepress-count"><?php echo number_format(absint($stat->count)); ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php else: ?>
                            <div class="citepress-empty-state">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
                                <p><?php esc_html_e('No data available yet.', 'paulus'); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="citepress-card">
                        <div class="citepress-card-header">
                            <h3>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                                <?php esc_html_e('Most Cited Posts', 'paulus'); ?>
                            </h3>
                        </div>
                        <div class="citepress-card-body citepress-card-body-table">
                            <?php if (!empty($post_stats)): ?>
                            <table class="citepress-table">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Post', 'paulus'); ?></th>
                                        <th><?php esc_html_e('Citations', 'paulus'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($post_stats as $stat): ?>
                                        <?php $post_title = get_the_title($stat->post_id); ?>
                                        <?php if ($post_title): ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo esc_url(get_edit_post_link($stat->post_id)); ?>" class="citepress-post-link"><?php echo esc_html(wp_trim_words($post_title, 8)); ?></a>
                                            </td>
                                            <td>
                                                <span class="citepress-count"><?php echo number_format(absint($stat->count)); ?></span>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php else: ?>
                            <div class="citepress-empty-state">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                                <p><?php esc_html_e('No data available yet.', 'paulus'); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="citepress-card">
                        <div class="citepress-card-header">
                            <h3>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                <?php esc_html_e('Export Statistics', 'paulus'); ?>
                            </h3>
                        </div>
                        <div class="citepress-card-body citepress-card-body-table">
                            <?php if (!empty($export_stats)): ?>
                            <table class="citepress-table">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Format', 'paulus'); ?></th>
                                        <th><?php esc_html_e('Downloads', 'paulus'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($export_stats as $stat): ?>
                                    <tr>
                                        <td>
                                            <span class="citepress-format-badge"><?php echo esc_html(strtoupper($stat->format)); ?></span>
                                        </td>
                                        <td>
                                            <span class="citepress-count"><?php echo number_format(absint($stat->count)); ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php else: ?>
                            <div class="citepress-empty-state">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                <p><?php esc_html_e('No export data available yet.', 'paulus'); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Privacy & Data Management -->
                <div class="citepress-privacy-management-section">
                    <div class="citepress-section-header" style="margin-top: 40px;">
                        <h2><?php esc_html_e('Data Management', 'paulus'); ?></h2>
                        <p><?php esc_html_e('Manage your citation analytics data and privacy settings', 'paulus'); ?></p>
                    </div>
                    
                    <div class="citepress-analytics-grid">
                        <!-- Data Overview Card -->
                        <div class="citepress-card">
                            <div class="citepress-card-header">
                                <h3>
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                    </svg>
                                    <?php esc_html_e('Data Privacy', 'paulus'); ?>
                                </h3>
                            </div>
                            <div class="citepress-card-body">
                                <div class="citepress-privacy-info">
                                    <h4><?php esc_html_e('What Gets Tracked', 'paulus'); ?></h4>
                                    <ul>
                                        <li><?php esc_html_e('Citation format selections (APA, MLA, Wikipedia, etc.)', 'paulus'); ?></li>
                                        <li><?php esc_html_e('Copy to clipboard actions', 'paulus'); ?></li>
                                        <li><?php esc_html_e('Export downloads (BibTeX, RIS, EndNote, CSL-JSON, CFF, Dublin Core)', 'paulus'); ?></li>
                                        <li><?php esc_html_e('Per-post citation statistics', 'paulus'); ?></li>
                                    </ul>
                                    <p class="citepress-privacy-note">
                                        <?php esc_html_e('All data is stored locally in your WordPress database and never shared with third parties.', 'paulus'); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Purge Data Card -->
                        <div class="citepress-card citepress-card-danger">
                            <div class="citepress-card-header">
                                <h3>
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3 6 5 6 21 6"/>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                        <line x1="10" y1="11" x2="10" y2="17"/>
                                        <line x1="14" y1="11" x2="14" y2="17"/>
                                    </svg>
                                    <?php esc_html_e('Purge Analytics Data', 'paulus'); ?>
                                </h3>
                            </div>
                            <div class="citepress-card-body">
                                <div class="citepress-purge-info">
                                    <p class="citepress-purge-warning">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                            <line x1="12" y1="9" x2="12" y2="13"/>
                                            <line x1="12" y1="17" x2="12.01" y2="17"/>
                                        </svg>
                                        <?php esc_html_e('Permanently delete all recorded analytics data from your database. This action cannot be undone.', 'paulus'); ?>
                                    </p>
                                    <form method="post" action="" class="citepress-purge-form-analytics">
                                        <?php wp_nonce_field('citepress_purge_analytics_action', 'citepress_purge_nonce'); ?>
                                        <button type="submit" name="citepress_purge_analytics" value="1" class="citepress-btn citepress-btn-danger-solid" onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete ALL analytics data? This action cannot be undone.', 'paulus'); ?>');">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="3 6 5 6 21 6"/>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                                <line x1="10" y1="11" x2="10" y2="17"/>
                                                <line x1="14" y1="11" x2="14" y2="17"/>
                                            </svg>
                                            <?php esc_html_e('Purge All Data', 'paulus'); ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
        </div>
    </div>
    <?php
}

