/* global $, powernav */
$(function () {

    var powerNavShown = false;
    var results = [];
    var resultCount = 0;
    var focusedResult = 0;

    function throttle (cb, fps) {
        var dt = 1000 / fps, handle;
        return function () {
            var args = arguments;
            function actualCall () {
                cb.apply(undefined, args);
            }
            if (undefined !== handle) {
                window.clearTimeout(handle);
            }
            handle = window.setTimeout(actualCall, dt);
        };
    }

    function showPowerNav () {
        $('#powernav').show();
        $('#powernav-input').val('').focus();
        powerNavShown = true;
    }

    function hidePowerNav () {
        $('#powernav').hide();
        powerNavShown = false;
    }

    function request (action, data) {
        var url = powernav.controllerURL + '&ajax=1&action=' + encodeURIComponent(action);
        return $.ajax({
            'type': 'POST',
            'url': url,
            'data': data,
            'dataType': 'json'
        });
    }

    function powerNavQuery (query) {
        query = query.trim();
        if (!query) {
            return;
        }

        request('powerNavQuery', {
            query: query
        }).then(function (rows) {
            resultCount = rows.length;
            results = rows;
            var renderedResults = [];
            for (var i = 0, len = rows.length; i < len; ++i) {
                renderedResults.push(renderResult(rows[i], i));
            }
            $('#powernav-results').html(renderedResults.join(''));
            focusResult(0);
        });
    }

    $('#powernav-results').on('click', '.result', function () {
        var $this = $(this);
        if ($this.hasClass('focused')) {

        } else {
            focusResult(+$this.attr('data-offset'));
        }
    });

    var throttledQuery = throttle(powerNavQuery, 30);

    function renderResult (row, offset) {
        return [
            '<div class="result" data-offset="' + offset + '">',
                '<span class="score">', Math.round(100*row.score), '</span>',
                row.actionString,
            '</div>'
        ].join('');
    }

    function focusResult (offset) {

        if (offset < 0) {
            $('#powernav-input').focus();
            offset = 0;
        } else if (offset >= resultCount) {
            offset = resultCount - 1;
        }

        focusedResult = offset;

        var $previouslyFocused = $('#powernav .result.focused');
        if ($previouslyFocused.length > 0) {
            $previouslyFocused.removeClass('focused');
        }

        var $result = $('#powernav .result[data-offset="' + offset + '"]');

        $result.addClass('focused');
    }

    function activateFocusedResult () {
        var actionData = results[focusedResult].actionData;

        if ('updateLocation' === actionData.onActivate) {
            window.location = actionData.url;
        }
    }

    $(document).bind("keydown", function(e){
        if (e.ctrlKey && e.keyCode === 80 /* p key */){
            showPowerNav();
            return false;
        } else if (e.keyCode === 38 /* up arrow */) {
            focusResult(focusedResult - 1);
            return false;
        } else if (e.keyCode === 40 /* down arrow */) {
            focusResult(focusedResult + 1);
            return false;
        } else if (e.keyCode === 13 /* return key */) {
            activateFocusedResult();
            return false;
        }
    });

    $(document).bind("keydown", function(e){
        if (e.keyCode === 27) {

            if (powerNavShown) {

                hidePowerNav();

                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        }
    });

    var oldQuery = null;
    $('#powernav-input').on('keyup', function () {
        var query = $(this).val();
        if (query !== oldQuery) {
            oldQuery = query;
            throttledQuery(query);
        }
    });
});
