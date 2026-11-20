/**
 * Hreflang – Dismiss Notices
 *
 * @package Hreflang
 * @since   3.0.0
 */
(function ($) {
    'use strict';
    $(function () {
        $(document).on('click', '.paulus-hreflang-notice .notice-dismiss, .notice[data-dismiss-url] .notice-dismiss', function () {
            var url = $(this).closest('.notice').attr('data-dismiss-url');
            if (url) {
                $.get(url);
            }
        });
    });
})(jQuery);
