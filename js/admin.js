/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

jQuery(function() {
    var $ = jQuery;

    // Activate help icons on the settings page
    $('.settings-help-icon').each(function() {
        var $helpIcon = $(this);

        var tooltip = new jBox('Tooltip', {
            attach: $helpIcon,
            trigger: 'mouseenter',
            getContent: 'data-help-text',
            offset: { x: 30, y: 0 },
            position: { x: 'left', y: 'center' },
            closeOnClick: 'body'
        });
    });

    // Activate clear Bible cache button. Only needed in WP admin
    $('.clear-bible-cache-button').on('click', function() {
        var $t = $(this);
        $t.prop('disabled', true);
        $.ajax({
            url: '/wp-admin/admin-ajax.php',
            dataType: 'json',
            data: { action: 'clear_bible_cache_button' },
            success: function(response) {
                console.log('clear bible cache: success');
                $('.clear-bible-cache-result').text(response.result);
                $t.prop('disabled', false);
            },
            error: function() {
                console.log('clear bible cache: error');
                $('.clear-bible-cache-result').text('Error!');
                $t.prop('disabled', false);
            }
        });
    });
});

