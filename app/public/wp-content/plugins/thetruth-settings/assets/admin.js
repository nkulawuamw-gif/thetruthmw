(function ($) {
    'use strict';

    var frame;

    $('#tts-upload-logo').on('click', function (e) {
        e.preventDefault();

        if (frame) {
            frame.open();
            return;
        }

        frame = wp.media({
            title: ttsAdmin.title,
            button: { text: ttsAdmin.button },
            multiple: false,
            library: { type: 'image' },
        });

        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            $('#tts_site_logo').val(attachment.id);
            $('#tts-logo-preview')
                .html('<img src="' + attachment.url + '" alt="' + attachment.alt + '" style="max-width:200px;height:auto;" />')
                .show();
            $('#tts-remove-logo').show();
            updatePreview();
        });

        frame.open();
    });

    $('#tts-remove-logo').on('click', function (e) {
        e.preventDefault();
        $('#tts_site_logo').val('');
        $('#tts-logo-preview').hide().empty();
        $(this).hide();
        updatePreview();
    });

    function updatePreview() {
        var name = $('#tts_site_name').val() || 'Your Website Name';
        var tagline = $('#tts_site_tagline').val() || 'Your Tagline Here';
        var logoUrl = $('#tts-logo-preview img').attr('src') || '';

        var $preview = $('.tts-preview-header');
        $preview.empty();

        if (logoUrl) {
            $preview.append(
                $('<img>').attr({
                    src: logoUrl,
                    alt: 'Logo',
                    class: 'tts-preview-logo',
                })
            );
        }

        var $textWrap = $('<div>').addClass('tts-preview-text');
        $textWrap.append(
            $('<strong>').addClass('tts-preview-name').text(name)
        );
        $textWrap.append(
            $('<span>').addClass('tts-preview-tagline').text(tagline)
        );
        $preview.append($textWrap);
    }

    $('#tts_site_name, #tts_site_tagline').on('input', updatePreview);

    $('.tts-color-picker').wpColorPicker({
        change: function (event, ui) {
            updateColorPreview();
            $(this).closest('.tts-color-item').find('.tts-color-hex').text(ui.color.toString());
        },
    });

    function updateColorPreview() {
        var colors = {};
        $('.tts-color-picker').each(function () {
            colors[$(this).attr('name')] = $(this).val();
        });

        var $preview = $('.tts-preview-card');
        $preview.css({
            'background-color': colors.tts_color_background || '#FFFFFF',
            color: colors.tts_color_text || '#111111',
            'border-color': colors.tts_color_secondary || '#686868',
        });
        $preview.find('h3').css('color', colors.tts_color_primary || '#111111');
        $preview.find('a').css('color', colors.tts_color_link || '#503AA8');

        $preview.find('.tts-sample-button').css({
            'background-color': colors.tts_button_bg || '#1565c0',
            color: colors.tts_button_text || '#ffffff',
        });
        $preview.find('.tts-sample-badge').css({
            'background-color': colors.tts_color_accent || '#FFEE58',
            color: colors.tts_color_primary || '#111111',
        });
    }

    $('.tts-color-picker').each(function () {
        var val = $(this).val();
        $(this).closest('.tts-color-item').find('.tts-color-hex').text(val);
    });

    $('#tts-reset-colors').on('click', function () {
        if (!confirm('Reset all colors to their default values?')) {
            return;
        }

        $('.tts-color-picker').each(function () {
            var defaultColor = $(this).data('default-color') || '';
            $(this).wpColorPicker('color', defaultColor);
            $(this).closest('.tts-color-item').find('.tts-color-hex').text(defaultColor);
        });

        updateColorPreview();
    });

    $('#tts_site_name, #tts_site_tagline').on('input', function () {
        var name = $('#tts_site_name').val() || 'Your Website Name';
        var tagline = $('#tts_site_tagline').val() || 'Your Tagline Here';
        $('.tts-preview-name').text(name);
        $('.tts-preview-tagline').text(tagline);
    });

})(jQuery);
