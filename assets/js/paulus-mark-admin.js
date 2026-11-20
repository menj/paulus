/**
 * End mark Admin Scripts
 * 
 * @package End mark
 * @version 4.3
 */

(function($) {
    'use strict';

    /**
     * Initialize when DOM is ready
     */
    $(document).ready(function() {
        initTabs();
        initTypeSelection();
        initSymbolButtons();
        initMediaUploader();
        initSizeSlider();
        initPlacementMode();
        initSchemaToggle();
        initLivePreview();
    });

    /**
     * Tab Navigation
     */
    function initTabs() {
        $('.paulus-mark-tab').on('click', function() {
            var tabId = $(this).data('tab');
            
            // Update tab states
            $('.paulus-mark-tab').removeClass('active');
            $(this).addClass('active');
            
            // Update content states
            $('.paulus-mark-tab-content').removeClass('active');
            $('#paulus-mark-tab-' + tabId).addClass('active');
        });
    }

    /**
     * Type Card Selection (Symbol vs Image)
     */
    function initTypeSelection() {
        // Handle click on type option cards
        $('.paulus-mark-type-option').on('click', function() {
            var input = $(this).find('input');
            input.prop('checked', true);
            
            // Update selected states
            $('.paulus-mark-type-option').removeClass('selected');
            $(this).addClass('selected');
            
            // Show corresponding options panel
            $('.paulus-mark-options-panel').removeClass('active');
            $('#paulus-mark-' + input.val() + '-options').addClass('active');
            
            updatePreview();
        });

        // Initialize selected state on page load
        $('.paulus-mark-type-option input:checked').closest('.paulus-mark-type-option').addClass('selected');
        
        // Show the correct options panel
        var initType = $('input[name="' + paulusMarkAdmin.optionName + '[type]"]:checked').val();
        $('#paulus-mark-' + initType + '-options').addClass('active');
    }

    /**
     * Symbol Quick-Select Buttons
     */
    function initSymbolButtons() {
        $('.paulus-mark-sym-btn').on('click', function() {
            $('#paulus_mark_symbol').val($(this).data('symbol'));
            updatePreview();
        });
    }

    /**
     * Media Library Uploader
     */
    function initMediaUploader() {
        var frame;

        // Upload button
        $('#paulus-mark-upload-btn').on('click', function(e) {
            e.preventDefault();
            
            // If frame already exists, reopen it
            if (frame) {
                frame.open();
                return;
            }
            
            // Create new media frame
            frame = wp.media({
                title: paulusMarkAdmin.i18n.selectImage,
                button: {
                    text: paulusMarkAdmin.i18n.useImage
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });
            
            // Handle image selection
            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                var sizes = attachment.sizes;
                var paulusMarkUrl = attachment.url;
                
                // Prefer thumbnail size if available
                if (sizes && sizes.thumbnail) {
                    paulusMarkUrl = sizes.thumbnail.url;
                }
                
                // Update form fields
                $('#paulus_mark_image_url').val(paulusMarkUrl);
                $('#paulus_mark_image_id').val(attachment.id);
                
                // Update preview box
                $('.paulus-mark-image-box')
                    .addClass('has-image')
                    .html('<img src="' + paulusMarkUrl + '">');
                
                // Show remove button
                $('#paulus-mark-remove-btn').show();
                
                updatePreview();
            });
            
            frame.open();
        });

        // Remove image button
        $('#paulus-mark-remove-btn').on('click', function(e) {
            e.preventDefault();
            
            // Clear form fields
            $('#paulus_mark_image_url, #paulus_mark_image_id').val('');
            
            // Reset preview box
            $('.paulus-mark-image-box')
                .removeClass('has-image')
                .html('<span class="dashicons dashicons-format-image"></span>');
            
            // Hide remove button
            $(this).hide();
            
            updatePreview();
        });
    }

    /**
     * Image Size Slider
     */
    function initSizeSlider() {
        $('#paulus_mark_image_size_px').on('input', function() {
            $('#paulus-mark-size-value').text($(this).val() + 'px');
            updatePreview();
        });
    }

    /**
     * Placement Mode Selector Toggle
     */
    function initPlacementMode() {
        $('#paulus_mark_placement_mode').on('change', function() {
            var mode = $(this).val();
            
            if (mode === 'selector') {
                $('.paulus-mark-selector-field').slideDown(200);
            } else {
                $('.paulus-mark-selector-field').slideUp(200);
            }
        });
        
        // Initialize visibility on page load
        if ($('#paulus_mark_placement_mode').val() !== 'selector') {
            $('.paulus-mark-selector-field').hide();
        }
    }

    /**
     * Schema Enabled Toggle
     */
    function initSchemaToggle() {
        $('#paulus_mark_schema_enabled').on('change', function() {
            if ($(this).is(':checked')) {
                $('.paulus-mark-schema-mode-field').removeClass('disabled');
            } else {
                $('.paulus-mark-schema-mode-field').addClass('disabled');
            }
        });
        
        // Initialize state on page load
        if (!$('#paulus_mark_schema_enabled').is(':checked')) {
            $('.paulus-mark-schema-mode-field').addClass('disabled');
        }
    }

    /**
     * Live Preview
     */
    function initLivePreview() {
        // Update on symbol input
        $('#paulus_mark_symbol').on('input', updatePreview);
        
        // Initial preview render
        updatePreview();
    }

    /**
     * Update the live preview display
     */
    function updatePreview() {
        // The screen has no symbol or image fields (the mark is fixed), so the
        // preview the server drew stays as it is.
        if ( ! $('#paulus_mark_symbol').length && ! $('#paulus_mark_image_url').length ) {
            return;
        }
        var type = $('input[name="' + paulusMarkAdmin.optionName + '[type]"]:checked').val();
        var html = '';
        
        if (type === 'symbol') {
            var symbol = $('#paulus_mark_symbol').val() || '∎';
            // Escape HTML entities for security
            var escaped = $('<div>').text(symbol).html();
            html = '<span style="margin-left:0.25em;">' + escaped + '</span>';
        } else {
            var url = $('#paulus_mark_image_url').val();
            var size = $('#paulus_mark_image_size_px').val() || 16;
            
            if (url) {
                html = '<span style="margin-left:0.25em;">' +
                       '<img src="' + url + '" style="height:' + size + 'px;width:auto;vertical-align:baseline;">' +
                       '</span>';
            }
        }
        
        $('#paulus-mark-live-mark').html(html);
    }

})(jQuery);
