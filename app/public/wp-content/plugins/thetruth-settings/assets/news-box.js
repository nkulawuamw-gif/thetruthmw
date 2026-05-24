(function ($) {
    'use strict';

    function fixReadMoreLinks() {
        $('.tts-news-box').each(function () {
            var $box = $(this);
            var $titleLink = $box.find('.wp-block-post-title a').first();
            var postUrl = $titleLink.attr('href');
            var $btn = $box.find('.tts-read-more a');

            if (postUrl && $btn.length) {
                $btn.attr('href', postUrl);
            }
        });
    }

    function addAudioBadges() {
        if (!window.ttsAudioData || !ttsAudioData.postIds || !ttsAudioData.postIds.length) {
            return;
        }

        var ids = ttsAudioData.postIds;
        var label = ttsAudioData.label || 'Listen to Audio';

        $('.tts-news-box').each(function () {
            var $box = $(this);
            var classes = $box.attr('class') || '';
            var match = classes.match(/post-(\d+)/);

            if (match && match[1]) {
                var postId = parseInt(match[1], 10);
                if (ids.indexOf(postId) !== -1) {
                    if (!$box.find('.tts-audio-badge').length) {
                        var $badge = $('<span class="tts-audio-badge">\uD83D\uDD0A ' + label + '</span>');
                        $box.find('.wp-block-post-featured-image').after($badge);
                    }
                }
            }
        });
    }

    $(document).ready(function () {
        fixReadMoreLinks();
        addAudioBadges();
    });
    $(document).on('post-load', function () {
        fixReadMoreLinks();
        addAudioBadges();
    });

})(jQuery);
