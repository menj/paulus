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
         * Also keeps the hidden paulus_cite_active_tab field and the form action
         * URL in sync so Save Changes returns to the same tab.
         */
        $('.paulus-cite-tab-button').on('click', function(e) {
            e.preventDefault();
            var tab = $(this).data('tab');
            $('.paulus-cite-tab-button').removeClass('active');
            $('.paulus-cite-tab-content').removeClass('active');
            $(this).addClass('active');
            $('#' + tab + '-tab').addClass('active');

            // Keep hidden field up to date so the POST carries the active tab.
            $('#paulus_cite_active_tab').val(tab);

            // Update the URL query string so the WordPress redirect target
            // includes ?paulus_cite_tab=<tab> and lands back on the right tab.
            // options.php redirects to the referer it is sent, so the tab goes
            // into the _wp_http_referer field, not into the form's action.
            var $form = $('#paulus_cite_active_tab').closest('form');
            var $referer = $form.find('input[name="_wp_http_referer"]');
            if ($referer.length) {
                var ref = $referer.val() || '';
                var param = 'paulus_cite_tab=' + encodeURIComponent(tab);
                ref = /([?&])paulus_cite_tab=[^&#]*/.test(ref)
                    ? ref.replace(/([?&])paulus_cite_tab=[^&#]*/, '$1' + param)
                    : ref + (ref.indexOf('?') === -1 ? '?' : '&') + param;
                $referer.val(ref);
            }
        });
        
        /**
         * Radio Card Selection
         * Adds visual selection state to radio button cards
         */
        $('.paulus-cite-radio-card input[type="radio"]').on('change', function() {
            $(this).closest('.paulus-cite-radio-group').find('.paulus-cite-radio-card').removeClass('selected');
            $(this).closest('.paulus-cite-radio-card').addClass('selected');
        });

        /**
         * Color Scheme Live Preview
         * Syncs the preview box data-theme attribute with the Color Scheme radio
         * so the preview reflects the chosen setting immediately without saving.
         */
        $('input.paulus-cite-color-scheme-radio').on('change', function() {
            var scheme = $(this).val(); // 'auto', 'light', or 'dark'
            var $box = $('.paulus-cite-preview-box');
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
        $('input.paulus-cite-box-style-radio').on('change', function() {
            $('.paulus-cite-preview-box').attr('data-box-style', $(this).val());
        });

        /**
         * Sharp Corners Live Preview
         * Syncs the preview box data-corners attribute with the Corner Style toggle.
         */
        $('input.paulus-cite-sharp-corners-toggle').on('change', function() {
            var $box = $('.paulus-cite-preview-box');
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
        if (typeof paulusCitePreviewData !== 'undefined') {
            
            /**
             * Format citation based on style
             * @param {string} style - Citation style identifier
             * @return {string} Formatted citation HTML
             */
            function formatPreviewCitation(style) {
                var a = paulusCitePreviewData.author;
                var d = paulusCitePreviewData.date;
                var dDMY = paulusCitePreviewData.dateDMY;
                var year = (String(d).match(/\b\d{4}\b/) || [''])[0]; // The four-digit year, whatever the date format
                var t = paulusCitePreviewData.title;
                var s = '<em>' + paulusCitePreviewData.site + '</em>';
                var u = paulusCitePreviewData.url;
                
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
                        return '<code class="paulus-cite-wikipedia-code">{{cite web |url=' + u + ' |title=' + t + ' |website=' + paulusCitePreviewData.site + ' |author=' + a + ' |date=' + dDMY + ' |access-date=' + dDMY + ' |language=' + paulusCitePreviewData.language + '}}</code>';
                    
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
                        return a + ', <em>' + t + '</em>, ' + paulusCitePreviewData.site + ' (' + d + '), ' + u + '.';
                    case 'iso690':
                        return a + '. ' + t + '. ' + s + ' [online]. ' + d + ' [viewed ' + d + ']. Available from: ' + u;
                    
                    default:
                        return a + '. (' + d + '). ' + t + '. ' + s + '. ' + u;
                }
            }
            
            /**
             * Handle preview style change — dropdown
             */
            $('.paulus-cite-preview-select').on('change', function() {
                var style = $(this).val();
                updatePreviewCitation(style);
            });
            
            /**
             * Handle preview style change — tabs
             */
            $(document).on('click', '.paulus-cite-preview-box .paulus-cite-style-tab', function(e) {
                e.preventDefault();
                var $tabs = $(this).closest('.paulus-cite-style-tabs');
                $tabs.find('.paulus-cite-style-tab').removeClass('paulus-cite-style-tab--active').attr('aria-selected', 'false');
                $(this).addClass('paulus-cite-style-tab--active').attr('aria-selected', 'true');
                updatePreviewCitation($(this).data('style'));
            });
            
            /**
             * Handle preview style change — buttons
             */
            $(document).on('click', '.paulus-cite-preview-box .paulus-cite-style-btn', function(e) {
                e.preventDefault();
                var $buttons = $(this).closest('.paulus-cite-style-buttons');
                $buttons.find('.paulus-cite-style-btn').removeClass('paulus-cite-style-btn--active').attr('aria-checked', 'false');
                $(this).addClass('paulus-cite-style-btn--active').attr('aria-checked', 'true');
                updatePreviewCitation($(this).data('style'));
            });
            
            /**
             * Update preview citation text
             */
            function updatePreviewCitation(style) {
                var citation = formatPreviewCitation(style);
                $('.paulus-cite-preview-citation').html(citation).data('style', style);
            }
        }
        
        /**
         * Live-switch preview selector when Format Selector Style radios change.
         * Uses class selector to avoid jQuery bracket-escaping issues with
         * name="paulus_cite_setting[format_display_mode]".
         */
        $(document).on('change', '.paulus-cite-display-mode-radio', function() {
            var mode = $(this).val();
            $('.paulus-cite-preview-selector').hide();
            var $shown = $('.paulus-cite-preview-selector[data-mode="' + mode + '"]').show();
            // The control now shown marks the format the preview is showing.
            var current = $('.paulus-cite-preview-citation').first().data('style');
            if (current) {
                $shown.find('select').val(current);
                $shown.find('.paulus-cite-style-tab').each(function() {
                    var on = $(this).data('style') === current;
                    $(this).toggleClass('paulus-cite-style-tab--active', on).attr('aria-selected', on ? 'true' : 'false');
                });
                $shown.find('.paulus-cite-style-btn').each(function() {
                    var on = $(this).data('style') === current;
                    $(this).toggleClass('paulus-cite-style-btn--active', on).attr('aria-checked', on ? 'true' : 'false');
                });
            }
        });
        
        /**
         * Preview box interactive buttons
         */
        // Toggle button — collapse/expand citation content
        $(document).on('click', '.paulus-cite-preview-box .toggle-button', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $content = $btn.closest('.paulus-cite-citation-box').find('.citation-content');
            var isCollapsed = $content.hasClass('collapsed');
            
            $content.toggleClass('collapsed');
            $btn.toggleClass('collapsed');
            $btn.attr('aria-expanded', isCollapsed ? 'true' : 'false');
        });
        
        // Copy button — copy citation text to clipboard
        $(document).on('click', '.paulus-cite-preview-box .copy-button', function(e) {
            e.preventDefault();
            var $box = $(this).closest('.paulus-cite-citation-box');
            var text = $box.find('.paulus-cite-preview-citation').text().trim();
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function() {
                    var $notif = $box.find('.copy-notification');
                    $notif.addClass('show');
                    setTimeout(function() { $notif.removeClass('show'); }, 1500);
                });
            }
        });
        
        // Export button — toggle dropdown visibility
        $(document).on('click', '.paulus-cite-preview-box .export-button', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var $dropdown = $(this).siblings('.export-dropdown');
            var isOpen = $dropdown.hasClass('show');
            $dropdown.toggleClass('show');
            $(this).attr('aria-expanded', !isOpen ? 'true' : 'false');
        });
        
        // Close export dropdown on outside click
        $(document).on('click', function() {
            $('.paulus-cite-preview-box .export-dropdown').removeClass('show');
            $('.paulus-cite-preview-box .export-button').attr('aria-expanded', 'false');
        });
        
        /**
         * Live-sync export format checkboxes with preview export dropdown
         */
        $(document).on('change', '.paulus-cite-export-format-toggle', function() {
            var format = $(this).val();
            var checked = $(this).is(':checked');
            $('.paulus-cite-preview-export-option[data-export-format="' + format + '"]').toggle(checked);
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
        var selectAllBtn = document.querySelector('.paulus-cite-select-all');
        var selectNoneBtn = document.querySelector('.paulus-cite-select-none');
        var formatCheckboxes = document.querySelectorAll('.paulus-cite-format-toggle');
        
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
            var categories = document.querySelectorAll('.paulus-cite-format-category');
            
            categories.forEach(function(category) {
                var checkboxes = category.querySelectorAll('.paulus-cite-format-toggle');
                var countSpan = category.querySelector('.paulus-cite-format-count');
                
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
