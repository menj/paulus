<?php
/**
 * Admin Settings Page Template
 *
 * Variables available from Paulus_Mark_Admin::render_settings_page():
 *   $settings   — merged plugin settings
 *   $categories — all categories (term objects)
 *   $tags       — all tags (term objects)
 *   $plugin     — Paulus_Mark instance
 *
 * @package End mark
 * @since   4.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap paulus-mark-wrap-page">
    <!-- Header -->
    <div class="paulus-mark-header">
        <div class="paulus-mark-header-content">
            <div class="paulus-mark-header-left">
                <h1><?php esc_html_e( 'End mark', 'paulus' ); ?></h1>
                <p><?php esc_html_e( 'Professional typographic end marks for your content', 'paulus' ); ?></p>
            </div>
            <div class="paulus-mark-header-right">
                <span class="paulus-mark-version">v<?php echo esc_html( Paulus_Mark::VERSION ); ?></span>
                <div class="paulus-mark-master-toggle">
                    <span><?php esc_html_e( 'Active', 'paulus' ); ?></span>
                    <div class="paulus-mark-toggle">
                        <input type="checkbox" form="paulus-mark-settings-form" name="<?php echo esc_attr( Paulus_Mark::OPTION_SITE ); ?>[enabled]" id="paulus_mark_enabled" value="1" <?php checked( $settings['enabled'] ); ?>>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form method="post" action="options.php" id="paulus-mark-settings-form">
        <?php settings_fields( 'paulus_mark_settings_group' ); ?>

        <div class="paulus-mark-body">
            <!-- Tab Navigation -->
            <div class="paulus-mark-tabs">
                <button type="button" class="paulus-mark-tab active" data-tab="placement">
                    <span class="dashicons dashicons-location"></span>
                    <?php esc_html_e( 'Placement', 'paulus' ); ?>
                </button>
                <button type="button" class="paulus-mark-tab" data-tab="exclusions">
                    <span class="dashicons dashicons-hidden"></span>
                    <?php esc_html_e( 'Exclusions', 'paulus' ); ?>
                </button>
                <button type="button" class="paulus-mark-tab" data-tab="advanced">
                    <span class="dashicons dashicons-admin-generic"></span>
                    <?php esc_html_e( 'Advanced', 'paulus' ); ?>
                </button>
            </div>

            <!-- ============================================================
                 TAB: Placement
                 ============================================================ -->
            <div id="paulus-mark-tab-placement" class="paulus-mark-tab-content active">

                <!-- Live Preview -->
                <div class="paulus-mark-preview-container">
                    <div class="paulus-mark-preview-label">
                        <span class="dashicons dashicons-visibility"></span>
                        <?php esc_html_e( 'Live Preview', 'paulus' ); ?>
                    </div>
                    <div class="paulus-mark-preview-box">
                        <?php esc_html_e( 'The mark is fixed: the cameo of Paul. This screen controls where and when it appears, not what it shows.', 'paulus' ); ?><span id="paulus-mark-live-mark"><?php echo $plugin->frontend->render_mark_html( $settings ); ?></span>
                    </div>
                </div>


                <div class="paulus-mark-section">
                    <h3 class="paulus-mark-section-title"><?php esc_html_e( 'Content Types', 'paulus' ); ?></h3>
                    <p class="paulus-mark-section-desc"><?php esc_html_e( 'Select which post types should display the end mark.', 'paulus' ); ?></p>

                    <div class="paulus-mark-field">
                        <div class="paulus-mark-post-types">
                            <?php
                            $public_types   = get_post_types( array( 'public' => true ), 'objects' );
                            $enabled_types  = isset( $settings['post_types'] ) ? (array) $settings['post_types'] : array( 'post' );
                            $excluded_types = array( 'attachment' );

                            foreach ( $public_types as $pt_slug => $pt_obj ) :
                                if ( in_array( $pt_slug, $excluded_types, true ) ) {
                                    continue;
                                }
                            ?>
                            <label class="paulus-mark-post-type-item">
                                <input type="checkbox"
                                       name="<?php echo esc_attr( Paulus_Mark::OPTION_SITE ); ?>[post_types][]"
                                       value="<?php echo esc_attr( $pt_slug ); ?>"
                                       <?php checked( in_array( $pt_slug, $enabled_types, true ) ); ?>>
                                <span class="paulus-mark-pt-label"><?php echo esc_html( $pt_obj->labels->name ); ?></span>
                                <span class="paulus-mark-pt-slug"><?php echo esc_html( $pt_slug ); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <p class="hint"><?php esc_html_e( 'At least one post type must be selected. Custom post types registered by themes or plugins appear here automatically.', 'paulus' ); ?></p>
                    </div>
                </div>

                <div class="paulus-mark-divider"></div>

                <div class="paulus-mark-section">
                    <h3 class="paulus-mark-section-title"><?php esc_html_e( 'Insertion Point', 'paulus' ); ?></h3>
                    <p class="paulus-mark-section-desc"><?php esc_html_e( 'Control exactly where the end mark appears within your content.', 'paulus' ); ?></p>

                    <div class="paulus-mark-field">
                        <label for="paulus_mark_placement_mode"><?php esc_html_e( 'Insert end mark', 'paulus' ); ?></label>
                        <select name="<?php echo esc_attr( Paulus_Mark::OPTION_SITE ); ?>[placement_mode]" id="paulus_mark_placement_mode">
                            <option value="last_paragraph" <?php selected( $settings['placement_mode'], 'last_paragraph' ); ?>><?php esc_html_e( 'At end of last paragraph', 'paulus' ); ?></option>
                            <option value="before_footnotes" <?php selected( $settings['placement_mode'], 'before_footnotes' ); ?>><?php esc_html_e( 'Before footnotes', 'paulus' ); ?></option>
                            <option value="after_footnotes" <?php selected( $settings['placement_mode'], 'after_footnotes' ); ?>><?php esc_html_e( 'After footnotes (end of content)', 'paulus' ); ?></option>
                            <option value="append" <?php selected( $settings['placement_mode'], 'append' ); ?>><?php esc_html_e( 'Append to end', 'paulus' ); ?></option>
                            <option value="selector" <?php selected( $settings['placement_mode'], 'selector' ); ?>><?php esc_html_e( 'Before CSS selector', 'paulus' ); ?></option>
                        </select>
                    </div>

                    <div class="paulus-mark-field paulus-mark-selector-field" style="margin-top: 20px; <?php echo 'selector' !== $settings['placement_mode'] ? 'display:none;' : ''; ?>">
                        <label for="paulus_mark_placement_selector"><?php esc_html_e( 'CSS Selector', 'paulus' ); ?></label>
                        <input type="text" name="<?php echo esc_attr( Paulus_Mark::OPTION_SITE ); ?>[placement_selector]" id="paulus_mark_placement_selector" value="<?php echo esc_attr( $settings['placement_selector'] ); ?>" placeholder=".my-class or #my-id" maxlength="200" style="width: 100%; max-width: 400px;">
                        <p class="hint"><?php esc_html_e( 'Enter a class (.classname) or ID (#idname) selector. End mark will be inserted before this element.', 'paulus' ); ?></p>
                    </div>
                </div>

                <div class="paulus-mark-divider"></div>

                <div class="paulus-mark-section">
                    <h3 class="paulus-mark-section-title"><?php esc_html_e( 'Manual Placement', 'paulus' ); ?></h3>
                    <p class="paulus-mark-section-desc"><?php esc_html_e( 'For precise control, you can manually place the end mark using shortcodes or blocks.', 'paulus' ); ?></p>

                    <div class="paulus-mark-info">
                        <span class="dashicons dashicons-info"></span>
                        <p>
                            <?php printf( esc_html__( 'Use the shortcode %1$s or the %2$s block in the editor. When manually placed, automatic insertion is disabled for that post.', 'paulus' ), '<code>[endmark]</code>', '<code>End mark</code>' ); ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- ============================================================
                 TAB: Exclusions
                 ============================================================ -->
            <div id="paulus-mark-tab-exclusions" class="paulus-mark-tab-content">

                <div class="paulus-mark-section">
                    <h3 class="paulus-mark-section-title"><?php esc_html_e( 'Content Requirements', 'paulus' ); ?></h3>
                    <p class="paulus-mark-section-desc"><?php esc_html_e( 'Set minimum content length for end mark display.', 'paulus' ); ?></p>

                    <div class="paulus-mark-field">
                        <label for="paulus_mark_min_word_count"><?php esc_html_e( 'Minimum Word Count', 'paulus' ); ?></label>
                        <input type="number" name="<?php echo esc_attr( Paulus_Mark::OPTION_SITE ); ?>[min_word_count]" id="paulus_mark_min_word_count" value="<?php echo esc_attr( $settings['min_word_count'] ); ?>" min="0" max="50000" style="width: 140px;">
                        <p class="hint"><?php esc_html_e( 'Only show end mark on posts with at least this many words. Set to 0 to disable.', 'paulus' ); ?></p>
                    </div>
                </div>

                <?php if ( ! empty( $categories ) || ! empty( $tags ) ) : ?>
                <div class="paulus-mark-divider"></div>

                <div class="paulus-mark-section">
                    <h3 class="paulus-mark-section-title"><?php esc_html_e( 'Taxonomy Exclusions', 'paulus' ); ?></h3>
                    <p class="paulus-mark-section-desc"><?php esc_html_e( 'Exclude posts in specific categories or with certain tags from displaying end marks.', 'paulus' ); ?></p>

                    <div class="paulus-mark-grid">
                        <?php if ( ! empty( $categories ) ) : ?>
                        <div class="paulus-mark-field">
                            <label for="paulus_mark_exclude_categories"><?php esc_html_e( 'Exclude Categories', 'paulus' ); ?></label>
                            <select name="<?php echo esc_attr( Paulus_Mark::OPTION_SITE ); ?>[exclude_categories][]" id="paulus_mark_exclude_categories" multiple class="paulus-mark-multiselect" style="width: 100%;">
                                <?php foreach ( $categories as $cat ) : ?>
                                    <option value="<?php echo esc_attr( $cat->term_id ); ?>" <?php selected( in_array( $cat->term_id, $settings['exclude_categories'] ) ); ?>><?php echo esc_html( $cat->name ); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="hint"><?php esc_html_e( 'Hold Ctrl/Cmd to select multiple.', 'paulus' ); ?></p>
                        </div>
                        <?php endif; ?>

                        <?php if ( ! empty( $tags ) ) : ?>
                        <div class="paulus-mark-field">
                            <label for="paulus_mark_exclude_tags"><?php esc_html_e( 'Exclude Tags', 'paulus' ); ?></label>
                            <select name="<?php echo esc_attr( Paulus_Mark::OPTION_SITE ); ?>[exclude_tags][]" id="paulus_mark_exclude_tags" multiple class="paulus-mark-multiselect" style="width: 100%;">
                                <?php foreach ( $tags as $tag ) : ?>
                                    <option value="<?php echo esc_attr( $tag->term_id ); ?>" <?php selected( in_array( $tag->term_id, $settings['exclude_tags'] ) ); ?>><?php echo esc_html( $tag->name ); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="hint"><?php esc_html_e( 'Hold Ctrl/Cmd to select multiple.', 'paulus' ); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="paulus-mark-divider"></div>

                <div class="paulus-mark-section">
                    <h3 class="paulus-mark-section-title"><?php esc_html_e( 'Specific Posts', 'paulus' ); ?></h3>
                    <p class="paulus-mark-section-desc"><?php esc_html_e( 'Exclude specific posts by their IDs.', 'paulus' ); ?></p>

                    <div class="paulus-mark-field">
                        <label for="paulus_mark_exclude_post_ids"><?php esc_html_e( 'Exclude Post IDs', 'paulus' ); ?></label>
                        <input type="text" name="<?php echo esc_attr( Paulus_Mark::OPTION_SITE ); ?>[exclude_post_ids]" id="paulus_mark_exclude_post_ids" value="<?php echo esc_attr( implode( ', ', $settings['exclude_post_ids'] ) ); ?>" style="width: 100%; max-width: 500px;" placeholder="123, 456, 789">
                        <p class="hint"><?php esc_html_e( 'Comma-separated list of post IDs to exclude.', 'paulus' ); ?></p>
                    </div>
                </div>
            </div>

            <!-- ============================================================
                 TAB: Advanced
                 ============================================================ -->
            <div id="paulus-mark-tab-advanced" class="paulus-mark-tab-content">

                <div class="paulus-mark-section">
                    <h3 class="paulus-mark-section-title"><?php esc_html_e( 'Display Options', 'paulus' ); ?></h3>
                    <p class="paulus-mark-section-desc"><?php esc_html_e( 'Control visibility on mobile devices and AMP pages.', 'paulus' ); ?></p>

                    <div class="paulus-mark-toggle-group">
                        <div class="paulus-mark-toggle-item">
                            <div class="paulus-mark-toggle">
                                <input type="checkbox" name="<?php echo esc_attr( Paulus_Mark::OPTION_SITE ); ?>[hide_on_mobile]" id="paulus_mark_hide_on_mobile" value="1" <?php checked( $settings['hide_on_mobile'] ); ?>>
                            </div>
                            <div class="toggle-content">
                                <div class="toggle-label"><?php esc_html_e( 'Hide on mobile devices', 'paulus' ); ?></div>
                                <div class="toggle-desc"><?php esc_html_e( 'End mark will not display on phones and tablets.', 'paulus' ); ?></div>
                            </div>
                        </div>

                        <div class="paulus-mark-toggle-item">
                            <div class="paulus-mark-toggle">
                                <input type="checkbox" name="<?php echo esc_attr( Paulus_Mark::OPTION_SITE ); ?>[disable_on_amp]" id="paulus_mark_disable_on_amp" value="1" <?php checked( $settings['disable_on_amp'] ); ?>>
                            </div>
                            <div class="toggle-content">
                                <div class="toggle-label"><?php esc_html_e( 'Disable on AMP pages', 'paulus' ); ?></div>
                                <div class="toggle-desc"><?php esc_html_e( 'Skip end mark insertion on Accelerated Mobile Pages.', 'paulus' ); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="paulus-mark-divider"></div>

                <div class="paulus-mark-section">
                    <h3 class="paulus-mark-section-title"><?php esc_html_e( 'CSS Styling', 'paulus' ); ?></h3>
                    <p class="paulus-mark-section-desc"><?php esc_html_e( 'Fine-tune the spacing and styling of your end mark.', 'paulus' ); ?></p>

                    <div class="paulus-mark-inline-fields">
                        <div class="paulus-mark-inline-field">
                            <label for="paulus_mark_margin_top"><?php esc_html_e( 'Margin Top', 'paulus' ); ?></label>
                            <input type="text" name="<?php echo esc_attr( Paulus_Mark::OPTION_SITE ); ?>[margin_top]" id="paulus_mark_margin_top" value="<?php echo esc_attr( $settings['margin_top'] ); ?>">
                        </div>
                        <div class="paulus-mark-inline-field">
                            <label for="paulus_mark_margin_left"><?php esc_html_e( 'Margin Left', 'paulus' ); ?></label>
                            <input type="text" name="<?php echo esc_attr( Paulus_Mark::OPTION_SITE ); ?>[margin_left]" id="paulus_mark_margin_left" value="<?php echo esc_attr( $settings['margin_left'] ); ?>">
                        </div>
                    </div>

                    <div class="paulus-mark-toggle-item" style="margin-top: 20px;">
                        <div class="paulus-mark-toggle">
                            <input type="checkbox" name="<?php echo esc_attr( Paulus_Mark::OPTION_SITE ); ?>[use_css_variables]" id="paulus_mark_use_css_variables" value="1" <?php checked( $settings['use_css_variables'] ); ?>>
                        </div>
                        <div class="toggle-content">
                            <div class="toggle-label"><?php esc_html_e( 'Use CSS variables', 'paulus' ); ?></div>
                            <div class="toggle-desc"><?php esc_html_e( 'Enable CSS custom properties for advanced theme customization.', 'paulus' ); ?></div>
                        </div>
                    </div>
                </div>

                <div class="paulus-mark-divider"></div>

                <div class="paulus-mark-section">
                    <h3 class="paulus-mark-section-title"><?php esc_html_e( 'Schema.org Structured Data', 'paulus' ); ?></h3>
                    <p class="paulus-mark-section-desc"><?php esc_html_e( 'Add structured data markup for search engines.', 'paulus' ); ?></p>

                    <div class="paulus-mark-toggle-item">
                        <div class="paulus-mark-toggle">
                            <input type="checkbox" name="<?php echo esc_attr( Paulus_Mark::OPTION_SITE ); ?>[schema_enabled]" id="paulus_mark_schema_enabled" value="1" <?php checked( $settings['schema_enabled'] ); ?>>
                        </div>
                        <div class="toggle-content">
                            <div class="toggle-label"><?php esc_html_e( 'Output JSON-LD schema markup', 'paulus' ); ?></div>
                            <div class="toggle-desc"><?php esc_html_e( 'Add semantic markup identifying the end mark element.', 'paulus' ); ?></div>
                        </div>
                    </div>

                    <div class="paulus-mark-schema-mode-field <?php echo empty( $settings['schema_enabled'] ) ? 'disabled' : ''; ?>">
                        <div class="paulus-mark-field">
                            <label for="paulus_mark_schema_mode"><?php esc_html_e( 'Schema Type', 'paulus' ); ?></label>
                            <select name="<?php echo esc_attr( Paulus_Mark::OPTION_SITE ); ?>[schema_mode]" id="paulus_mark_schema_mode">
                                <option value="WebPageElement" <?php selected( $settings['schema_mode'], 'WebPageElement' ); ?>>WebPageElement</option>
                                <option value="CreativeWork" <?php selected( $settings['schema_mode'], 'CreativeWork' ); ?>>CreativeWork</option>
                            </select>
                            <p class="hint"><?php esc_html_e( 'Only WebPageElement and CreativeWork are allowed to prevent conflicts with other plugins.', 'paulus' ); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="paulus-mark-footer">
            <span class="paulus-mark-footer-hint"><?php esc_html_e( 'Changes apply immediately to all pages.', 'paulus' ); ?></span>
            <?php submit_button( __( 'Save Changes', 'paulus' ), 'primary', 'submit', false ); ?>
        </div>
    </form>
</div>
