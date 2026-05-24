(function ($) {
    'use strict';

    var searchTimer;
    var $searchForm = $('.wp-block-search');
    var $searchInput = $searchForm.find('input[type="search"]');
    var $suggestions = $('<div class="tts-search-suggestions"></div>');
    var $wrapper = $('<div class="tts-search-wrap"></div>');

    if (!$searchInput.length) {
        return;
    }

    $searchInput.wrap($wrapper);
    $searchInput.after($suggestions);

    function highlightText(text, term) {
        var escaped = $('<span>').text(text).html();
        var regex = new RegExp('(' + term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
        return escaped.replace(regex, '<span class="tts-suggestion-highlight">$1</span>');
    }

    $searchInput.on('input', function () {
        var term = $(this).val().trim();

        clearTimeout(searchTimer);

        if (term.length < 2) {
            $suggestions.empty().removeClass('active');
            return;
        }

        searchTimer = setTimeout(function () {
            $.getJSON(wpRestController.url + 'wp/v2/posts', {
                search: term,
                per_page: 6,
                _fields: 'id,title,url,date',
            })
                .done(function (data) {
                    $suggestions.empty();

                    if (!data.length) {
                        $suggestions.removeClass('active');
                        return;
                    }

                    $.each(data, function (i, post) {
                        var title = post.title.rendered;
                        var $item = $('<a class="tts-suggestion-item"></a>');
                        $item.attr('href', post.url);

                        var $titleSpan = $('<span class="tts-suggestion-title"></span>');
                        $titleSpan.html(highlightText(title, term));
                        $item.append($titleSpan);

                        $suggestions.append($item);
                    });

                    $suggestions.addClass('active');
                })
                .fail(function () {
                    $suggestions.empty().removeClass('active');
                });
        }, 300);
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('.tts-search-wrap').length) {
            $suggestions.empty().removeClass('active');
        }
    });

    $searchInput.on('blur', function () {
        setTimeout(function () {
            $suggestions.empty().removeClass('active');
        }, 200);
    });

})(jQuery);
