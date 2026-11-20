/**
 * Hreflang – Admin JavaScript
 *
 * @package Hreflang
 * @since   3.0.0
 */
(function ($) {
    'use strict';

    var i18n = (window.paulusHreflang && window.paulusHreflang.i18n) || {};
    var ajaxUrl = (window.paulusHreflang && window.paulusHreflang.ajax_url) || '';
    var nonce = (window.paulusHreflang && window.paulusHreflang.nonce) || '';

    /* ── Meta Box: Add / Remove Tag Rows ─────────────────────────── */

    function bindRowButtons(context) {
        var ctx = context || 'body';

        // Generic handler for add buttons.
        $(ctx).on('click', '.add-new-hreflang-tag, [class*="add-new-"][class*="-hreflang-tag"]', function (e) {
            e.preventDefault();
            var $container = $(this).closest('.href-container, .href-container-cat, .term-hreflang-data');
            var $rows = $container.find('[id^="hreflang-"]');
            var $last = $rows.last();
            var idNum = parseInt(($last.prop('id') || '0').match(/\d+/) || [0], 10) + 1;
            var prefix = ($last.prop('id') || '').replace(/\d+$/, '');

            var $clone = $last.clone(true);
            $clone.prop('id', prefix + idNum);
            $clone.find('input[type="url"], input[type="text"]').val('');
            $clone.find('select').prop('selectedIndex', 0);

            // Remove buttons from previous last row, add to new.
            $last.find('.add-new-hreflang-tag, .remove-new-hreflang-tag, [class*="add-new-"][class*="-hreflang-tag"], [class*="remove-new-"][class*="-hreflang-tag"]').remove();

            $container.append($clone);
        });

        // Generic handler for remove buttons.
        $(ctx).on('click', '.remove-new-hreflang-tag, [class*="remove-new-"][class*="-hreflang-tag"]', function (e) {
            e.preventDefault();
            var $container = $(this).closest('.href-container, .href-container-cat, .term-hreflang-data');
            var $rows = $container.find('[id^="hreflang-"]');
            if ($rows.length <= 1) return;
            $rows.last().remove();

            // Add buttons back to new last row.
            var $newLast = $container.find('[id^="hreflang-"]').last();
            if (!$newLast.find('[class*="add-new"]').length) {
                var addClass = 'add-new-hreflang-tag';
                var removeClass = 'remove-new-hreflang-tag';
                $newLast.append(
                    ' <button type="button" class="button ' + addClass + '" title="Add"><span class="dashicons dashicons-plus-alt2"></span></button>' +
                    ' <button type="button" class="button ' + removeClass + '" title="Remove"><span class="dashicons dashicons-minus"></span></button>'
                );
            }
        });
    }

    /* ── Dashboard: Erase All ────────────────────────────────────── */

    function bindEraseAll() {
        $(document).on('click', '.paulus-hreflang-erase-all', function () {
            if (!confirm(i18n.confirm_erase || 'Are you sure?')) return;
            var $btn = $(this).prop('disabled', true);
            $.post(ajaxUrl, {
                action: 'paulus_hreflang_delete_all_data',
                nonce: nonce
            }, function (res) {
                $btn.prop('disabled', false);
                alert(res.success ? res.data.message : (res.data || i18n.error));
                if (res.success) location.reload();
            }).fail(function () {
                $btn.prop('disabled', false);
                alert(i18n.error);
            });
        });
    }

    /* ── Bulk Editor ─────────────────────────────────────────────── */

    function bindBulkEditor() {

        // Filter.
        $('#paulus-hreflang-bulk-filter').on('input', function () {
            var q = $(this).val().toLowerCase();
            $('#paulus-hreflang-bulk-table tbody tr').each(function () {
                var title = $(this).find('.paulus-hreflang-table__col--title strong').text().toLowerCase();
                $(this).toggle(title.indexOf(q) !== -1);
            });
        });

        // Add tag row.
        $(document).on('click', '.paulus-hreflang-inline-add', function () {
            var $editor = $(this).closest('.paulus-hreflang-inline-editor');
            var langs = $editor.data('languages') || [];
            var options = '<option value="">Select</option>';
            for (var i = 0; i < langs.length; i++) {
                options += '<option value="' + langs[i].code + '">' + langs[i].name + '</option>';
            }
            var $row = $('<div class="paulus-hreflang-inline-tag">' +
                '<select class="paulus-hreflang-inline-lang">' + options + '</select>' +
                '<input type="url" class="paulus-hreflang-inline-href" placeholder="https://" />' +
                '<button type="button" class="button paulus-hreflang-inline-remove" title="Remove"><span class="dashicons dashicons-no-alt"></span></button>' +
                '</div>');
            $(this).before($row);
        });

        // Remove tag row.
        $(document).on('click', '.paulus-hreflang-inline-remove', function () {
            $(this).closest('.paulus-hreflang-inline-tag').remove();
        });

        // Save all.
        $('#paulus-hreflang-bulk-save').on('click', function () {
            if (!confirm(i18n.confirm_bulk_save || 'Save all?')) return;

            var entries = [];
            $('#paulus-hreflang-bulk-table .paulus-hreflang-bulk-row').each(function () {
                var $row = $(this);
                var tags = [];
                $row.find('.paulus-hreflang-inline-tag').each(function () {
                    var lang = $(this).find('.paulus-hreflang-inline-lang').val();
                    var href = $(this).find('.paulus-hreflang-inline-href').val();
                    if (lang && href) {
                        tags.push({ lang: lang, href: href });
                    }
                });
                entries.push({
                    id: $row.data('object-id'),
                    type: $row.data('object-type'),
                    tags: tags
                });
            });

            var $btn = $(this).prop('disabled', true).text('Saving…');
            $.post(ajaxUrl, {
                action: 'paulus_hreflang_bulk_save',
                nonce: nonce,
                entries: entries
            }, function (res) {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Save All Changes');
                alert(res.success ? res.data.message : (res.data || i18n.error));
            }).fail(function () {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Save All Changes');
                alert(i18n.error);
            });
        });
    }

    /* ── Code Generator ──────────────────────────────────────────── */

    function bindGenerator() {

        // Add row.
        $('#paulus-hreflang-gen-add-row').on('click', function () {
            var $first = $('.paulus-hreflang-generator-row').first();
            var $clone = $first.clone();
            $clone.find('select').prop('selectedIndex', 0);
            $clone.find('input').val('');
            $('.paulus-hreflang-generator-rows').append($clone);
        });

        // Remove row.
        $(document).on('click', '.paulus-hreflang-gen-remove', function () {
            if ($('.paulus-hreflang-generator-row').length > 1) {
                $(this).closest('.paulus-hreflang-generator-row').remove();
            }
        });

        // Generate.
        $('#paulus-hreflang-gen-generate').on('click', function () {
            var lines = [];
            $('.paulus-hreflang-generator-row').each(function () {
                var lang = $(this).find('.paulus-hreflang-gen-lang').val();
                var url = $(this).find('.paulus-hreflang-gen-url').val();
                if (lang && url) {
                    lines.push('<link rel="alternate" href="' + escHtml(url) + '" hreflang="' + escHtml(lang) + '" />');
                }
            });

            if (lines.length === 0) {
                alert('Please fill in at least one language + URL pair.');
                return;
            }

            $('#paulus-hreflang-gen-code').text(lines.join('\n'));
            $('#paulus-hreflang-gen-output').slideDown(200);
        });

        // Copy.
        $('#paulus-hreflang-gen-copy').on('click', function () {
            var code = $('#paulus-hreflang-gen-code').text();
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(code).then(function () {
                    alert(i18n.copied || 'Copied!');
                });
            } else {
                // Fallback.
                var $tmp = $('<textarea>').val(code).appendTo('body').select();
                document.execCommand('copy');
                $tmp.remove();
                alert(i18n.copied || 'Copied!');
            }
        });
    }

    /* ── Validation Tool ─────────────────────────────────────────── */

    function bindValidation() {

        // Single URL validate.
        $('#paulus-hreflang-validate-btn').on('click', function () {
            var url = $('#paulus-hreflang-validate-url').val();
            if (!url) return;

            var $btn = $(this).prop('disabled', true).text(i18n.validating || 'Validating…');
            var $results = $('#paulus-hreflang-validate-results').show();

            $.post(ajaxUrl, {
                action: 'paulus_hreflang_validate_url',
                nonce: nonce,
                url: url
            }, function (res) {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-yes-alt"></span> Validate');

                if (!res.success) {
                    $('#paulus-hreflang-validate-status')
                        .attr('class', 'paulus-hreflang-status-bar paulus-hreflang-status-bar--error')
                        .text('Error: ' + (res.data || 'Unknown error'));
                    return;
                }

                var d = res.data;
                var tags = d.tags || [];
                var warnings = [];

                if (tags.length === 0) {
                    $('#paulus-hreflang-validate-status')
                        .attr('class', 'paulus-hreflang-status-bar paulus-hreflang-status-bar--warning')
                        .text('No hreflang tags found on this page (HTTP ' + d.status_code + ').');
                } else {
                    // Check for x-default.
                    var hasXDefault = tags.some(function (t) { return t.lang === 'x-default'; });
                    if (!hasXDefault) warnings.push('Missing x-default tag.');

                    // Check for duplicates.
                    var seen = {};
                    tags.forEach(function (t) {
                        if (seen[t.lang]) warnings.push('Duplicate language: ' + t.lang);
                        seen[t.lang] = true;
                    });

                    var cls = warnings.length ? 'warning' : 'success';
                    var msg = tags.length + ' hreflang tag(s) found (HTTP ' + d.status_code + ').';
                    if (warnings.length) msg += ' ' + warnings.length + ' warning(s).';

                    $('#paulus-hreflang-validate-status')
                        .attr('class', 'paulus-hreflang-status-bar paulus-hreflang-status-bar--' + cls)
                        .text(msg);
                }

                // Populate table.
                var $tbody = $('#paulus-hreflang-validate-table tbody').empty();
                tags.forEach(function (t) {
                    var valid = isValidLangCode(t.lang);
                    $tbody.append(
                        '<tr><td>' + escHtml(t.lang) + '</td>' +
                        '<td><a href="' + safeHref(t.href) + '" target="_blank" rel="noopener noreferrer">' + escHtml(t.href) + '</a></td>' +
                        '<td class="' + (valid ? 'paulus-hreflang-tag-valid' : 'paulus-hreflang-tag-invalid') + '">' + (valid ? (i18n.valid || 'Valid') : (i18n.invalid || 'Invalid')) + '</td></tr>'
                    );
                });

                // Warnings.
                var $warn = $('#paulus-hreflang-validate-warnings').empty();
                warnings.forEach(function (w) {
                    $warn.append('<p>⚠ ' + escHtml(w) + '</p>');
                });

            }).fail(function () {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-yes-alt"></span> Validate');
                alert(i18n.error);
            });
        });

        // Bulk validate.
        $('#paulus-hreflang-bulk-validate-btn').on('click', function () {
            var $btn = $(this).prop('disabled', true);
            var $progress = $('#paulus-hreflang-bulk-validate-progress').show();
            var $results = $('#paulus-hreflang-bulk-validate-results').hide();
            var $body = $('#paulus-hreflang-bv-results-body').empty();

            $('#paulus-hreflang-bv-progress-bar').css('width', '10%');
            $('#paulus-hreflang-bv-progress-text').text('Fetching content…');

            $.post(ajaxUrl, {
                action: 'paulus_hreflang_bulk_validate',
                nonce: nonce
            }, function (res) {
                $btn.prop('disabled', false);
                if (!res.success) {
                    alert(res.data || i18n.error);
                    $progress.hide();
                    return;
                }

                var items = res.data.items || [];
                if (items.length === 0) {
                    $('#paulus-hreflang-bv-progress-text').text('No items with hreflang tags found.');
                    return;
                }

                $('#paulus-hreflang-bv-progress-bar').css('width', '100%');
                $('#paulus-hreflang-bv-progress-text').text(items.length + ' item(s) scanned.');
                $results.show();

                items.forEach(function (item) {
                    var issues = [];
                    var hasXDefault = item.tags.some(function (t) { return t.lang === 'x-default'; });
                    if (!hasXDefault) issues.push('No x-default');

                    var seen = {};
                    item.tags.forEach(function (t) {
                        if (!isValidLangCode(t.lang)) issues.push('Invalid code: ' + t.lang);
                        if (seen[t.lang]) issues.push('Duplicate: ' + t.lang);
                        seen[t.lang] = true;
                    });

                    var issueHtml = issues.length === 0
                        ? '<span class="paulus-hreflang-bv-ok">✓ OK</span>'
                        : issues.map(function (i) { return '<div class="paulus-hreflang-bv-issue">⚠ ' + escHtml(i) + '</div>'; }).join('');

                    $body.append(
                        '<tr><td><strong>' + escHtml(item.title) + '</strong></td>' +
                        '<td>' + item.tags.length + '</td>' +
                        '<td class="paulus-hreflang-bv-issues">' + issueHtml + '</td></tr>'
                    );
                });

            }).fail(function () {
                $btn.prop('disabled', false);
                $progress.hide();
                alert(i18n.error);
            });
        });
    }

    /* ── Colour Scheme Picker ────────────────────────────────────── */

    function bindSchemePicker() {
        $(document).on('change', 'input[name="paulus_hreflang_scheme"]', function () {
            var scheme = $(this).val();
            $('.paulus-hreflang-wrap').attr('data-scheme', scheme);
            $('.paulus-hreflang-scheme-option').removeClass('paulus-hreflang-scheme-option--active');
            $(this).closest('.paulus-hreflang-scheme-option').addClass('paulus-hreflang-scheme-option--active');
        });
    }

    /* ── Helpers ──────────────────────────────────────────────────── */

    function escHtml(str) {
        var el = document.createElement('span');
        el.textContent = str || '';
        return el.innerHTML;
    }

    /**
     * Sanitise a URL for safe use in href attributes.
     * Blocks javascript:, data:, vbscript: and other dangerous protocols.
     */
    function safeHref(url) {
        if (!url || typeof url !== 'string') return '#';
        var trimmed = url.replace(/^\s+/, '').toLowerCase();
        if (/^(javascript|data|vbscript|blob)\s*:/i.test(trimmed)) return '#';
        return escHtml(url);
    }

    function isValidLangCode(code) {
        if (code === 'x-default') return true;
        return /^[a-z]{2,3}(-[A-Za-z]{2,4})?$/.test(code.replace(/_/g, '-'));
    }

    /* ── Init ────────────────────────────────────────────────────── */

    $(function () {
        bindRowButtons();
        bindEraseAll();
        bindBulkEditor();
        bindGenerator();
        bindValidation();
        bindSchemePicker();
    });

})(jQuery);
