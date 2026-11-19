<?php
/**
 * Admin Settings Page Template
 *
 * Variables available from Endmark_Admin::render_settings_page():
 *   $settings   — merged plugin settings
 *   $categories — all categories (term objects)
 *   $tags       — all tags (term objects)
 *   $plugin     — Endmark_Plugin instance
 *
 * @package Endmark
 * @since   4.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap endmark-wrap-page">
    <!-- Header -->
    <div class="endmark-header">
        <div class="endmark-header-content">
            <div class="endmark-header-left">
                <h1><?php esc_html_e( 'Endmark', 'paulus' ); ?></h1>
                <p><?php esc_html_e( 'Professional typographic endmarks for your content', 'paulus' ); ?></p>
            </div>
            <div class="endmark-header-right">
                <span class="endmark-version">v<?php echo esc_html( Endmark_Plugin::VERSION ); ?></span>
                <div class="endmark-master-toggle">
                    <span><?php esc_html_e( 'Active', 'paulus' ); ?></span>
                    <div class="endmark-toggle">
                        <input type="checkbox" form="endmark-settings-form" name="<?php echo esc_attr( Endmark_Plugin::OPTION_SITE ); ?>[enabled]" id="endmark_enabled" value="1" <?php checked( $settings['enabled'] ); ?>>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form method="post" action="options.php" id="endmark-settings-form">
        <?php settings_fields( 'endmark_settings_group' ); ?>

        <div class="endmark-body">
            <!-- Tab Navigation -->
            <div class="endmark-tabs">
                <button type="button" class="endmark-tab active" data-tab="appearance">
                    <span class="dashicons dashicons-art"></span>
                    <?php esc_html_e( 'Appearance', 'paulus' ); ?>
                </button>
                <button type="button" class="endmark-tab" data-tab="placement">
                    <span class="dashicons dashicons-location"></span>
                    <?php esc_html_e( 'Placement', 'paulus' ); ?>
                </button>
                <button type="button" class="endmark-tab" data-tab="exclusions">
                    <span class="dashicons dashicons-hidden"></span>
                    <?php esc_html_e( 'Exclusions', 'paulus' ); ?>
                </button>
                <button type="button" class="endmark-tab" data-tab="advanced">
                    <span class="dashicons dashicons-admin-generic"></span>
                    <?php esc_html_e( 'Advanced', 'paulus' ); ?>
                </button>
            </div>

            <!-- ============================================================
                 TAB: Appearance
                 ============================================================ -->
            <div id="endmark-tab-appearance" class="endmark-tab-content active">

                <div class="endmark-section">
                    <h3 class="endmark-section-title"><?php esc_html_e( 'Endmark Type', 'paulus' ); ?></h3>
                    <p class="endmark-section-desc"><?php esc_html_e( 'Choose between a typographic symbol or a custom image for your endmark.', 'paulus' ); ?></p>

                    <div class="endmark-type-grid">
                        <label class="endmark-type-option">
                            <input type="radio" name="<?php echo esc_attr( Endmark_Plugin::OPTION_SITE ); ?>[type]" value="symbol" <?php checked( $settings['type'], 'symbol' ); ?>>
                            <div class="type-icon"><span class="dashicons dashicons-editor-textcolor"></span></div>
                            <div class="type-label"><?php esc_html_e( 'Symbol', 'paulus' ); ?></div>
                            <div class="type-desc"><?php esc_html_e( 'Unicode character', 'paulus' ); ?></div>
                        </label>

                        <label class="endmark-type-option">
                            <input type="radio" name="<?php echo esc_attr( Endmark_Plugin::OPTION_SITE ); ?>[type]" value="image" <?php checked( $settings['type'], 'image' ); ?>>
                            <div class="type-icon"><span class="dashicons dashicons-format-image"></span></div>
                            <div class="type-label"><?php esc_html_e( 'Image', 'paulus' ); ?></div>
                            <div class="type-desc"><?php esc_html_e( 'Custom graphic', 'paulus' ); ?></div>
                        </label>
                    </div>

                    <!-- Symbol Options Panel -->
                    <div id="endmark-symbol-options" class="endmark-options-panel">
                        <div class="endmark-field">
                            <label for="endmark_symbol"><?php esc_html_e( 'Symbol Character', 'paulus' ); ?></label>
                            <input type="text" name="<?php echo esc_attr( Endmark_Plugin::OPTION_SITE ); ?>[symbol]" id="endmark_symbol" value="<?php echo esc_attr( $settings['symbol'] ); ?>" maxlength="12">
                            <div class="endmark-symbols">
                                <?php
                                $symbols = array( '∎', '◆', '❧', '■', '●', '※', '❖', '◾', '▪', '#' );
                                foreach ( $symbols as $sym ) {
                                    printf( '<button type="button" class="endmark-sym-btn" data-symbol="%s">%s</button>', esc_attr( $sym ), esc_html( $sym ) );
                                }
                                ?>
                            </div>
                            <p class="hint"><?php esc_html_e( 'Click a symbol above or type your own (max 12 characters).', 'paulus' ); ?></p>
                        </div>
                    </div>

                    <!-- Image Options Panel -->
                    <div id="endmark-image-options" class="endmark-options-panel">
                        <div class="endmark-field">
                            <label><?php esc_html_e( 'Endmark Image', 'paulus' ); ?></label>
                            <div class="endmark-image-upload">
                                <div class="endmark-image-box <?php echo ! empty( $settings['image_url'] ) ? 'has-image' : ''; ?>">
                                    <?php if ( ! empty( $settings['image_url'] ) ) : ?>
                                        <img src="<?php echo esc_url( $settings['image_url'] ); ?>" alt="">
                                    <?php else : ?>
                                        <span class="dashicons dashicons-format-image"></span>
                                    <?php endif; ?>
                                </div>
                                <div class="endmark-image-actions">
                                    <button type="button" id="endmark-upload-btn" class="button button-primary">
                                        <span class="dashicons dashicons-upload"></span>
                                        <?php esc_html_e( 'Upload Image', 'paulus' ); ?>
                                    </button>
                                    <button type="button" id="endmark-remove-btn" class="button button-secondary" style="<?php echo empty( $settings['image_url'] ) ? 'display:none;' : ''; ?>">
                                        <?php esc_html_e( 'Remove', 'paulus' ); ?>
                                    </button>
                                </div>
                            </div>
                            <input type="hidden" name="<?php echo esc_attr( Endmark_Plugin::OPTION_SITE ); ?>[image_url]" id="endmark_image_url" value="<?php echo esc_url( $settings['image_url'] ); ?>">
                            <input type="hidden" name="<?php echo esc_attr( Endmark_Plugin::OPTION_SITE ); ?>[image_id]" id="endmark_image_id" value="<?php echo esc_attr( $settings['image_id'] ); ?>">
                        </div>

                        <div class="endmark-field" style="margin-top: 24px;">
                            <label for="endmark_image_size_px"><?php esc_html_e( 'Image Size', 'paulus' ); ?></label>
                            <div class="endmark-size-control">
                                <input type="range" name="<?php echo esc_attr( Endmark_Plugin::OPTION_SITE ); ?>[image_size_px]" id="endmark_image_size_px" min="12" max="32" value="<?php echo esc_attr( $settings['image_size_px'] ); ?>">
                                <span id="endmark-size-value"><?php echo esc_html( $settings['image_size_px'] ); ?>px</span>
                            </div>
                            <p class="hint"><?php esc_html_e( 'Adjust the display size of the endmark image (12–32 px).', 'paulus' ); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Live Preview -->
                <div class="endmark-preview-container">
                    <div class="endmark-preview-label">
                        <span class="dashicons dashicons-visibility"></span>
                        <?php esc_html_e( 'Live Preview', 'paulus' ); ?>
                    </div>
                    <div class="endmark-preview-box">
                        <?php esc_html_e( 'This is how your endmark will appear at the end of your content, giving your articles a professional, polished finish.', 'paulus' ); ?><span id="endmark-live-mark"><?php echo $plugin->frontend->render_endmark_html( $settings ); ?></span>
                    </div>
                </div>
            </div>

            <!-- ============================================================
                 TAB: Placement
                 ============================================================ -->
            <div id="endmark-tab-placement" class="endmark-tab-content">

                <div class="endmark-section">
                    <h3 class="endmark-section-title"><?php esc_html_e( 'Content Types', 'paulus' ); ?></h3>
                    <p class="endmark-section-desc"><?php esc_html_e( 'Select which post types should display the endmark.', 'paulus' ); ?></p>

                    <div class="endmark-field">
                        <div class="endmark-post-types">
                            <?php
                            $public_types   = get_post_types( array( 'public' => true ), 'objects' );
                            $enabled_types  = isset( $settings['post_types'] ) ? (array) $settings['post_types'] : array( 'post' );
                            $excluded_types = array( 'attachment' );

                            foreach ( $public_types as $pt_slug => $pt_obj ) :
                                if ( in_array( $pt_slug, $excluded_types, true ) ) {
                                    continue;
                                }
                            ?>
                            <label class="endmark-post-type-item">
                                <input type="checkbox"
                                       name="<?php echo esc_attr( Endmark_Plugin::OPTION_SITE ); ?>[post_types][]"
                                       value="<?php echo esc_attr( $pt_slug ); ?>"
                                       <?php checked( in_array( $pt_slug, $enabled_types, true ) ); ?>>
                                <span class="endmark-pt-label"><?php echo esc_html( $pt_obj->labels->name ); ?></span>
                                <span class="endmark-pt-slug"><?php echo esc_html( $pt_slug ); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <p class="hint"><?php esc_html_e( 'At least one post type must be selected. Custom post types registered by themes or plugins appear here automatically.', 'paulus' ); ?></p>
                    </div>
                </div>

                <div class="endmark-divider"></div>

                <div class="endmark-section">
                    <h3 class="endmark-section-title"><?php esc_html_e( 'Insertion Point', 'paulus' ); ?></h3>
                    <p class="endmark-section-desc"><?php esc_html_e( 'Control exactly where the endmark appears within your content.', 'paulus' ); ?></p>

                    <div class="endmark-field">
                        <label for="endmark_placement_mode"><?php esc_html_e( 'Insert endmark', 'paulus' ); ?></label>
                        <select name="<?php echo esc_attr( Endmark_Plugin::OPTION_SITE ); ?>[placement_mode]" id="endmark_placement_mode">
                            <option value="last_paragraph" <?php selected( $settings['placement_mode'], 'last_paragraph' ); ?>><?php esc_html_e( 'At end of last paragraph', 'paulus' ); ?></option>
                            <option value="before_footnotes" <?php selected( $settings['placement_mode'], 'before_footnotes' ); ?>><?php esc_html_e( 'Before footnotes', 'paulus' ); ?></option>
                            <option value="after_footnotes" <?php selected( $settings['placement_mode'], 'after_footnotes' ); ?>><?php esc_html_e( 'After footnotes (end of content)', 'paulus' ); ?></option>
                            <option value="append" <?php selected( $settings['placement_mode'], 'append' ); ?>><?php esc_html_e( 'Append to end', 'paulus' ); ?></option>
                            <option value="selector" <?php selected( $settings['placement_mode'], 'selector' ); ?>><?php esc_html_e( 'Before CSS selector', 'paulus' ); ?></option>
                        </select>
                    </div>

                    <div class="endmark-field endmark-selector-field" style="margin-top: 20px; <?php echo 'selector' !== $settings['placement_mode'] ? 'display:none;' : ''; ?>">
                        <label for="endmark_placement_selector"><?php esc_html_e( 'CSS Selector', 'paulus' ); ?></label>
                        <input type="text" name="<?php echo esc_attr( Endmark_Plugin::OPTION_SITE ); ?>[placement_selector]" id="endmark_placement_selector" value="<?php echo esc_attr( $settings['placement_selector'] ); ?>" placeholder=".my-class or #my-id" maxlength="200" style="width: 100%; max-width: 400px;">
                        <p class="hint"><?php esc_html_e( 'Enter a class (.classname) or ID (#idname) selector. Endmark will be inserted before this element.', 'paulus' ); ?></p>
                    </div>
                </div>

                <div class="endmark-divider"></div>

                <div class="endmark-section">
                    <h3 class="endmark-section-title"><?php esc_html_e( 'Manual Placement', 'paulus' ); ?></h3>
                    <p class="endmark-section-desc"><?php esc_html_e( 'For precise control, you can manually place the endmark using shortcodes or blocks.', 'paulus' ); ?></p>

                    <div class="endmark-info">
                        <span class="dashicons dashicons-info"></span>
                        <p>
                            <?php printf( esc_html__( 'Use the shortcode %1$s or the %2$s block in the editor. When manually placed, automatic insertion is disabled for that post.', 'paulus' ), '<code>[endmark]</code>', '<code>Endmark</code>' ); ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- ============================================================
                 TAB: Exclusions
                 ============================================================ -->
            <div id="endmark-tab-exclusions" class="endmark-tab-content">

                <div class="endmark-section">
                    <h3 class="endmark-section-title"><?php esc_html_e( 'Content Requirements', 'paulus' ); ?></h3>
                    <p class="endmark-section-desc"><?php esc_html_e( 'Set minimum content length for endmark display.', 'paulus' ); ?></p>

                    <div class="endmark-field">
                        <label for="endmark_min_word_count"><?php esc_html_e( 'Minimum Word Count', 'paulus' ); ?></label>
                        <input type="number" name="<?php echo esc_attr( Endmark_Plugin::OPTION_SITE ); ?>[min_word_count]" id="endmark_min_word_count" value="<?php echo esc_attr( $settings['min_word_count'] ); ?>" min="0" max="50000" style="width: 140px;">
                        <p class="hint"><?php esc_html_e( 'Only show endmark on posts with at least this many words. Set to 0 to disable.', 'paulus' ); ?></p>
                    </div>
                </div>

                <?php if ( ! empty( $categories ) || ! empty( $tags ) ) : ?>
                <div class="endmark-divider"></div>

                <div class="endmark-section">
                    <h3 class="endmark-section-title"><?php esc_html_e( 'Taxonomy Exclusions', 'paulus' ); ?></h3>
                    <p class="endmark-section-desc"><?php esc_html_e( 'Exclude posts in specific categories or with certain tags from displaying endmarks.', 'paulus' ); ?></p>

                    <div class="endmark-grid">
                        <?php if ( ! empty( $categories ) ) : ?>
                        <div class="endmark-field">
                            <label for="endmark_exclude_categories"><?php esc_html_e( 'Exclude Categories', 'paulus' ); ?></label>
                            <select name="<?php echo esc_attr( Endmark_Plugin::OPTION_SITE ); ?>[exclude_categories][]" id="endmark_exclude_categories" multiple class="endmark-multiselect" style="width: 100%;">
                                <?php foreach ( $categories as $cat ) : ?>
                                    <option value="<?php echo esc_attr( $cat->term_id ); ?>" <?php selected( in_array( $cat->term_id, $settings['exclude_categories'] ) ); ?>><?php echo esc_html( $cat->name ); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="hint"><?php esc_html_e( 'Hold Ctrl/Cmd to select multiple.', 'paulus' ); ?></p>
                        </div>
                        <?php endif; ?>

                        <?php if ( ! empty( $tags ) ) : ?>
                        <div class="endmark-field">
                            <label for="endmark_exclude_tags"><?php esc_html_e( 'Exclude Tags', 'paulus' ); ?></label>
                            <select name="<?php echo esc_attr( Endmark_Plugin::OPTION_SITE ); ?>[exclude_tags][]" id="endmark_exclude_tags" multiple class="endmark-multiselect" style="width: 100%;">
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

                <div class="endmark-divider"></div>

                <div class="endmark-section">
                    <h3 class="endmark-section-title"><?php esc_html_e( 'Specific Posts', 'paulus' ); ?></h3>
                    <p class="endmark-section-desc"><?php esc_html_e( 'Exclude specific posts by their IDs.', 'paulus' ); ?></p>

                    <div class="endmark-field">
                        <label for="endmark_exclude_post_ids"><?php esc_html_e( 'Exclude Post IDs', 'paulus' ); ?></label>
                        <input type="text" name="<?php echo esc_attr( Endmark_Plugin::OPTION_SITE ); ?>[exclude_post_ids]" id="endmark_exclude_post_ids" value="<?php echo esc_attr( implode( ', ', $settings['exclude_post_ids'] ) ); ?>" style="width: 100%; max-width: 500px;" placeholder="123, 456, 789">
                        <p class="hint"><?php esc_html_e( 'Comma-separated list of post IDs to exclude.', 'paulus' ); ?></p>
                    </div>
                </div>
            </div>

            <!-- ============================================================
                 TAB: Advanced
                 ============================================================ -->
            <div id="endmark-tab-advanced" class="endmark-tab-content">

                <div class="endmark-section">
                    <h3 class="endmark-section-title"><?php esc_html_e( 'Display Options', 'paulus' ); ?></h3>
                    <p class="endmark-section-desc"><?php esc_html_e( 'Control visibility on mobile devices and AMP pages.', 'paulus' ); ?></p>

                    <div class="endmark-toggle-group">
                        <div class="endmark-toggle-item">
                            <div class="endmark-toggle">
                                <input type="checkbox" name="<?php echo esc_attr( Endmark_Plugin::OPTION_SITE ); ?>[hide_on_mobile]" id="endmark_hide_on_mobile" value="1" <?php checked( $settings['hide_on_mobile'] ); ?>>
                            </div>
                            <div class="toggle-content">
                                <div class="toggle-label"><?php esc_html_e( 'Hide on mobile devices', 'paulus' ); ?></div>
                                <div class="toggle-desc"><?php esc_html_e( 'Endmark will not display on phones and tablets.', 'paulus' ); ?></div>
                            </div>
                        </div>

                        <div class="endmark-toggle-item">
                            <div class="endmark-toggle">
                                <input type="checkbox" name="<?php echo esc_attr( Endmark_Plugin::OPTION_SITE ); ?>[disable_on_amp]" id="endmark_disable_on_amp" value="1" <?php checked( $settings['disable_on_amp'] ); ?>>
                            </div>
                            <div class="toggle-content">
                                <div class="toggle-label"><?php esc_html_e( 'Disable on AMP pages', 'paulus' ); ?></div>
                                <div class="toggle-desc"><?php esc_html_e( 'Skip endmark insertion on Accelerated Mobile Pages.', 'paulus' ); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="endmark-divider"></div>

                <div class="endmark-section">
                    <h3 class="endmark-section-title"><?php esc_html_e( 'SVG Support', 'paulus' ); ?></h3>
                    <p class="endmark-section-desc"><?php esc_html_e( 'Allow SVG images to be used as endmarks.', 'paulus' ); ?></p>

                    <div class="endmark-toggle-item">
                        <div class="endmark-toggle">
                            <input type="checkbox" name="<?php echo esc_attr( Endmark_Plugin::OPTION_SITE ); ?>[allow_svg]" id="endmark_allow_svg" value="1" <?php checked( $settings['allow_svg'] ); ?>>
                        </div>
                        <div class="toggle-content">
                            <div class="toggle-label"><?php esc_html_e( 'Allow SVG endmarks', 'paulus' ); ?></div>
                            <div class="toggle-desc"><?php esc_html_e( 'When disabled, SVG images fall back to the default ∎ symbol for security.', 'paulus' ); ?></div>
                        </div>
                    </div>
                </div>

                <div class="endmark-divider"></div>

                <div class="endmark-section">
                    <h3 class="endmark-section-title"><?php esc_html_e( 'CSS Styling', 'paulus' ); ?></h3>
                    <p class="endmark-section-desc"><?php esc_html_e( 'Fine-tune the spacing and styling of your endmark.', 'paulus' ); ?></p>

                    <div class="endmark-inline-fields">
                        <div class="endmark-inline-field">
                            <label for="endmark_margin_top"><?php esc_html_e( 'Margin Top', 'paulus' ); ?></label>
                            <input type="text" name="<?php echo esc_attr( Endmark_Plugin::OPTION_SITE ); ?>[margin_top]" id="endmark_margin_top" value="<?php echo esc_attr( $settings['margin_top'] ); ?>">
                        </div>
                        <div class="endmark-inline-field">
                            <label for="endmark_margin_left"><?php esc_html_e( 'Margin Left', 'paulus' ); ?></label>
                            <input type="text" name="<?php echo esc_attr( Endmark_Plugin::OPTION_SITE ); ?>[margin_left]" id="endmark_margin_left" value="<?php echo esc_attr( $settings['margin_left'] ); ?>">
                        </div>
                    </div>

                    <div class="endmark-toggle-item" style="margin-top: 20px;">
                        <div class="endmark-toggle">
                            <input type="checkbox" name="<?php echo esc_attr( Endmark_Plugin::OPTION_SITE ); ?>[use_css_variables]" id="endmark_use_css_variables" value="1" <?php checked( $settings['use_css_variables'] ); ?>>
                        </div>
                        <div class="toggle-content">
                            <div class="toggle-label"><?php esc_html_e( 'Use CSS variables', 'paulus' ); ?></div>
                            <div class="toggle-desc"><?php esc_html_e( 'Enable CSS custom properties for advanced theme customization.', 'paulus' ); ?></div>
                        </div>
                    </div>
                </div>

                <div class="endmark-divider"></div>

                <div class="endmark-section">
                    <h3 class="endmark-section-title"><?php esc_html_e( 'Schema.org Structured Data', 'paulus' ); ?></h3>
                    <p class="endmark-section-desc"><?php esc_html_e( 'Add structured data markup for search engines.', 'paulus' ); ?></p>

                    <div class="endmark-toggle-item">
                        <div class="endmark-toggle">
                            <input type="checkbox" name="<?php echo esc_attr( Endmark_Plugin::OPTION_SITE ); ?>[schema_enabled]" id="endmark_schema_enabled" value="1" <?php checked( $settings['schema_enabled'] ); ?>>
                        </div>
                        <div class="toggle-content">
                            <div class="toggle-label"><?php esc_html_e( 'Output JSON-LD schema markup', 'paulus' ); ?></div>
                            <div class="toggle-desc"><?php esc_html_e( 'Add semantic markup identifying the endmark element.', 'paulus' ); ?></div>
                        </div>
                    </div>

                    <div class="endmark-schema-mode-field <?php echo empty( $settings['schema_enabled'] ) ? 'disabled' : ''; ?>">
                        <div class="endmark-field">
                            <label for="endmark_schema_mode"><?php esc_html_e( 'Schema Type', 'paulus' ); ?></label>
                            <select name="<?php echo esc_attr( Endmark_Plugin::OPTION_SITE ); ?>[schema_mode]" id="endmark_schema_mode">
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
        <div class="endmark-footer">
            <span class="endmark-footer-hint"><?php esc_html_e( 'Changes apply immediately to all pages.', 'paulus' ); ?></span>
            <?php submit_button( __( 'Save Changes', 'paulus' ), 'primary', 'submit', false ); ?>
        </div>
    </form>
</div>
