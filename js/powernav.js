/* global $, powernav */
$(function () {

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

    var powerNavShown = false;

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
            var results = [];
            for (var i = 0, len = rows.length; i < len; ++i) {
                results.push(renderResult(rows[i]), i);
            }
            $('#powernav-results').html(results.join(''));
        });
    }

    var throttledQuery = throttle(powerNavQuery, 30);

    function renderResult (row, offset) {
        return [
            '<div class="result">',
                '<span class="score" data-offset="' + offset + '">', Math.round(100*row.score), '</span>',
                row.actionString,
            '</div>'
        ].join('');
    }

    function focusResult (offset) {

    }

    $(document).bind("keyup keydown", function(e){
        if (e.ctrlKey && e.keyCode === 80 /* p key */){
            showPowerNav();
            return false;
        } else if (e.keyCode === 38 /* up arrow */) {
            focusResult(-1);
            return false;
        } else if (e.keyCode === 40 /* down arrow */) {
            focusResult(+1);
            return false;
        }
    });

    $(document).bind("keyup keydown", function(e){
        if (e.keyCode === 27) {

            if (powerNavShown) {

                hidePowerNav();

                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        }
    });

    $('#powernav-input').on('keyup', function () {
        var query = $(this).val();
        throttledQuery(query);
    });
});
