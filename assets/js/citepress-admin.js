/**
 * Cite Plugin - Admin JavaScript
 * Version: 2.8.0
 * 
 * Handles admin interface interactions including:
 * - Tab navigation
 * - Radio card selections
 * - Citation preview formatting (20 formats)
 * - Preview display mode live-switching (dropdown/tabs/buttons)
 * - Format selection management
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        /**
         * Tab Switching
         * Handles navigation between different settings tabs.
         * Also keeps the hidden citepress_active_tab field and the form action
         * URL in sync so Save Changes returns to the same tab.
         */
        $('.citepress-tab-button').on('click', function(e) {
            e.preventDefault();
            var tab = $(this).data('tab');
            $('.citepress-tab-button').removeClass('active');
            $('.citepress-tab-content').removeClass('active');
            $(this).addClass('active');
            $('#' + tab + '-tab').addClass('active');

            // Keep hidden field up to date so the POST carries the active tab.
            $('#citepress_active_tab').val(tab);

            // Update the URL query string so the WordPress redirect target
            // includes ?citepress_tab=<tab> and lands back on the right tab.
            // options.php redirects to the referer it is sent, so the tab goes
            // into the _wp_http_referer field, not into the form's action.
            var $form = $('#citepress_active_tab').closest('form');
            var $referer = $form.find('input[name="_wp_http_referer"]');
            if ($referer.length) {
                var ref = $referer.val() || '';
                var param = 'citepress_tab=' + encodeURIComponent(tab);
                ref = /([?&])citepress_tab=[^&#]*/.test(ref)
                    ? ref.replace(/([?&])citepress_tab=[^&#]*/, '$1' + param)
                    : ref + (ref.indexOf('?') === -1 ? '?' : '&') + param;
                $referer.val(ref);
            }
        });
        
        /**
         * Radio Card Selection
         * Adds visual selection state to radio button cards
         */
        $('.citepress-radio-card input[type="radio"]').on('change', function() {
            $(this).closest('.citepress-radio-group').find('.citepress-radio-card').removeClass('selected');
            $(this).closest('.citepress-radio-card').addClass('selected');
        });

        /**
         * Color Scheme Live Preview
         * Syncs the preview box data-theme attribute with the Color Scheme radio
         * so the preview reflects the chosen setting immediately without saving.
         */
        $('input.citepress-color-scheme-radio').on('change', function() {
            var scheme = $(this).val(); // 'auto', 'light', or 'dark'
            var $box = $('.citepress-preview-box');
            if (scheme === 'auto') {
                $box.removeAttr('data-theme');
            } else {
                $box.attr('data-theme', scheme);
            }
        });

        /**
         * Box Style Live Preview
         * Syncs the preview box data-box-style attribute with the Box Style radio.
         */
        $('input.citepress-box-style-radio').on('change', function() {
            $('.citepress-preview-box').attr('data-box-style', $(this).val());
        });

        /**
         * Sharp Corners Live Preview
         * Syncs the preview box data-corners attribute with the Corner Style toggle.
         */
        $('input.citepress-sharp-corners-toggle').on('change', function() {
            var $box = $('.citepress-preview-box');
            if ($(this).is(':checked')) {
                $box.attr('data-corners', 'sharp');
            } else {
                $box.removeAttr('data-corners');
            }
        });
        
        /**
         * Citation Preview Formatting
         * Updates preview based on selected citation style
         */
        if (typeof citepressPreviewData !== 'undefined') {
            
            /**
             * Format citation based on style
             * @param {string} style - Citation style identifier
             * @return {string} Formatted citation HTML
             */
            function formatPreviewCitation(style) {
                var a = citepressPreviewData.author;
                var d = citepressPreviewData.date;
                var dDMY = citepressPreviewData.dateDMY;
                var year = (String(d).match(/\b\d{4}\b/) || [''])[0]; // The four-digit year, whatever the date format
                var t = citepressPreviewData.title;
                var s = '<em>' + citepressPreviewData.site + '</em>';
                var u = citepressPreviewData.url;
                
                switch(style) {
                    // Original 10 formats
                    case 'apa':
                        return a + '. (' + d + '). ' + t + '. ' + s + '. ' + u;
                    case 'mla':
                        return a + '. "' + t + '." ' + s + ', ' + d + ', ' + u;
                    case 'chicago':
                        return a + '. "' + t + '." ' + s + '. Last modified ' + d + '. ' + u;
                    case 'harvard':
                        return a + '. (' + d + '). ' + t + '. ' + s + '. Available at: ' + u + ' (Accessed: ' + d + ')';
                    case 'vancouver':
                        return a + '. ' + t + '. ' + s + '. ' + d + '; [cited ' + d + ']. Available from: ' + u;
                    case 'ieee':
                        return a + ', "' + t + '," ' + s + ', ' + d + '. [Online]. Available: ' + u + '. [Accessed: ' + d + ']';
                    case 'ama':
                        return a + '. ' + t + '. ' + s + '. Published ' + d + '. Accessed ' + d + '. ' + u;
                    case 'asa':
                        return a + '. ' + d + '. "' + t + '." ' + s + '. Retrieved ' + d + ' (' + u + ')';
                    case 'turabian':
                        return a + '. "' + t + '." ' + s + '. ' + d + '. ' + u;
                    case 'wikipedia':
                        return '<code class="citepress-wikipedia-code">{{cite web |url=' + u + ' |title=' + t + ' |website=' + citepressPreviewData.site + ' |author=' + a + ' |date=' + dDMY + ' |access-date=' + dDMY + ' |language=' + citepressPreviewData.language + '}}</code>';
                    
                    // New 10 formats (v2.4.0)
                    case 'acs':
                        return a + '. ' + t + '. ' + s + ' [Online] ' + d + '. ' + u + ' (accessed ' + d + ').';
                    case 'aip':
                        return a + ', "' + t + '," ' + s + ' ' + year + '. [Online]. Available: ' + u;
                    case 'nlm':
                        return a + '. ' + t + '. ' + s + ' [Internet]. ' + d + ' [cited ' + d + ']. Available from: ' + u;
                    case 'aaa':
                        return a + ' ' + d + ' ' + t + '. ' + s + ', electronic document, ' + u + ', accessed ' + d + '.';
                    case 'apsa':
                        return a + '. ' + d + '. "' + t + '." ' + s + '. ' + u + ' (accessed ' + d + ').';
                    case 'oscola':
                        return a + ', \'' + t + '\' (' + s + ', ' + d + ') &lt;' + u + '&gt; accessed ' + d;
                    case 'nature':
                        return a + '. ' + t + '. ' + s + ' ' + u + ' (' + d + ').';
                    case 'acm':
                        return a + '. ' + d + '. ' + t + '. ' + s + '. Retrieved ' + d + ' from ' + u;
                    case 'bluebook':
                        return a + ', <em>' + t + '</em>, ' + citepressPreviewData.site + ' (' + d + '), ' + u + '.';
                    case 'iso690':
                        return a + '. ' + t + '. ' + s + ' [online]. ' + d + ' [viewed ' + d + ']. Available from: ' + u;
                    
                    default:
                        return a + '. (' + d + '). ' + t + '. ' + s + '. ' + u;
                }
            }
            
            /**
             * Handle preview style change — dropdown
             */
            $('.citepress-preview-select').on('change', function() {
                var style = $(this).val();
                updatePreviewCitation(style);
            });
            
            /**
             * Handle preview style change — tabs
             */
            $(document).on('click', '.citepress-preview-box .citepress-style-tab', function(e) {
                e.preventDefault();
                var $tabs = $(this).closest('.citepress-style-tabs');
                $tabs.find('.citepress-style-tab').removeClass('citepress-style-tab--active').attr('aria-selected', 'false');
                $(this).addClass('citepress-style-tab--active').attr('aria-selected', 'true');
                updatePreviewCitation($(this).data('style'));
            });
            
            /**
             * Handle preview style change — buttons
             */
            $(document).on('click', '.citepress-preview-box .citepress-style-btn', function(e) {
                e.preventDefault();
                var $buttons = $(this).closest('.citepress-style-buttons');
                $buttons.find('.citepress-style-btn').removeClass('citepress-style-btn--active').attr('aria-checked', 'false');
                $(this).addClass('citepress-style-btn--active').attr('aria-checked', 'true');
                updatePreviewCitation($(this).data('style'));
            });
            
            /**
             * Update preview citation text
             */
            function updatePreviewCitation(style) {
                var citation = formatPreviewCitation(style);
                $('.citepress-preview-citation').html(citation).data('style', style);
            }
        }
        
        /**
         * Live-switch preview selector when Format Selector Style radios change.
         * Uses class selector to avoid jQuery bracket-escaping issues with
         * name="citepress_setting[format_display_mode]".
         */
        $(document).on('change', '.citepress-display-mode-radio', function() {
            var mode = $(this).val();
            $('.citepress-preview-selector').hide();
            var $shown = $('.citepress-preview-selector[data-mode="' + mode + '"]').show();
            // The control now shown marks the format the preview is showing.
            var current = $('.citepress-preview-citation').first().data('style');
            if (current) {
                $shown.find('select').val(current);
                $shown.find('.citepress-style-tab').each(function() {
                    var on = $(this).data('style') === current;
                    $(this).toggleClass('citepress-style-tab--active', on).attr('aria-selected', on ? 'true' : 'false');
                });
                $shown.find('.citepress-style-btn').each(function() {
                    var on = $(this).data('style') === current;
                    $(this).toggleClass('citepress-style-btn--active', on).attr('aria-checked', on ? 'true' : 'false');
                });
            }
        });
        
        /**
         * Preview box interactive buttons
         */
        // Toggle button — collapse/expand citation content
        $(document).on('click', '.citepress-preview-box .toggle-button', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $content = $btn.closest('.citepress-citation-box').find('.citation-content');
            var isCollapsed = $content.hasClass('collapsed');
            
            $content.toggleClass('collapsed');
            $btn.toggleClass('collapsed');
            $btn.attr('aria-expanded', isCollapsed ? 'true' : 'false');
        });
        
        // Copy button — copy citation text to clipboard
        $(document).on('click', '.citepress-preview-box .copy-button', function(e) {
            e.preventDefault();
            var $box = $(this).closest('.citepress-citation-box');
            var text = $box.find('.citepress-preview-citation').text().trim();
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function() {
                    var $notif = $box.find('.copy-notification');
                    $notif.addClass('show');
                    setTimeout(function() { $notif.removeClass('show'); }, 1500);
                });
            }
        });
        
        // Export button — toggle dropdown visibility
        $(document).on('click', '.citepress-preview-box .export-button', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var $dropdown = $(this).siblings('.export-dropdown');
            var isOpen = $dropdown.hasClass('show');
            $dropdown.toggleClass('show');
            $(this).attr('aria-expanded', !isOpen ? 'true' : 'false');
        });
        
        // Close export dropdown on outside click
        $(document).on('click', function() {
            $('.citepress-preview-box .export-dropdown').removeClass('show');
            $('.citepress-preview-box .export-button').attr('aria-expanded', 'false');
        });
        
        /**
         * Live-sync export format checkboxes with preview export dropdown
         */
        $(document).on('change', '.citepress-export-format-toggle', function() {
            var format = $(this).val();
            var checked = $(this).is(':checked');
            $('.citepress-preview-export-option[data-export-format="' + format + '"]').toggle(checked);
        });
        
    });
    
})(jQuery);

/**
 * Format Selection Tab Functionality (v2.4.0)
 */
(function() {
    'use strict';
    
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFormatSelection);
    } else {
        initFormatSelection();
    }
    
    function initFormatSelection() {
        var selectAllBtn = document.querySelector('.citepress-select-all');
        var selectNoneBtn = document.querySelector('.citepress-select-none');
        var formatCheckboxes = document.querySelectorAll('.citepress-format-toggle');
        
        if (!selectAllBtn || !selectNoneBtn || !formatCheckboxes.length) {
            return;
        }
        
        // Select all formats
        selectAllBtn.addEventListener('click', function(e) {
            e.preventDefault();
            formatCheckboxes.forEach(function(checkbox) {
                checkbox.checked = true;
            });
            updateFormatCounts();
        });
        
        // Clear all formats (but ensure at least one remains)
        selectNoneBtn.addEventListener('click', function(e) {
            e.preventDefault();
            var checkedCount = 0;
            formatCheckboxes.forEach(function(checkbox) {
                if (checkbox.checked) checkedCount++;
            });
            
            // At least one format must stay enabled: an empty list is not
            // submitted, and the setting would then fall back to every format.
            // So Clear All always leaves the first one checked.
            formatCheckboxes.forEach(function(checkbox, index) {
                checkbox.checked = (index === 0);
            });
            if (checkedCount > 1) {
                alert('At least one citation format must remain enabled.');
            }
            updateFormatCounts();
        });
        
        // Update counts when checkboxes change
        formatCheckboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                validateMinimumSelection();
                updateFormatCounts();
            });
        });
        
        // Prevent unchecking the last checkbox
        function validateMinimumSelection() {
            var checkedCount = 0;
            var lastChecked = null;
            
            formatCheckboxes.forEach(function(checkbox) {
                if (checkbox.checked) {
                    checkedCount++;
                    lastChecked = checkbox;
                }
            });
            
            if (checkedCount === 0 && lastChecked) {
                lastChecked.checked = true;
                alert('At least one citation format must remain enabled.');
            }
        }
        
        // Update format counts in category headers
        function updateFormatCounts() {
            var categories = document.querySelectorAll('.citepress-format-category');
            
            categories.forEach(function(category) {
                var checkboxes = category.querySelectorAll('.citepress-format-toggle');
                var countSpan = category.querySelector('.citepress-format-count');
                
                if (!countSpan) return;
                
                var total = checkboxes.length;
                var checked = 0;
                
                checkboxes.forEach(function(checkbox) {
                    if (checkbox.checked) checked++;
                });
                
                countSpan.textContent = checked + ' of ' + total + ' enabled';
            });
        }
        
        // Initialize counts on page load
        updateFormatCounts();
    }
})();
