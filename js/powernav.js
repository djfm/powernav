/* global $, powernav */
$(function () {

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
                results.push(renderResult(rows[i]));
            }
            $('#powernav-results').html(results.join(''));
        });
    }

    function renderResult(row) {
        return [
            '<div class="result">',
                '<span class="score">', Math.round(100*row.score), '</span>',
                row.actionString,
            '</div>'
        ].join('');
    }

    $(document).bind("keyup keydown", function(e){
        if (e.ctrlKey && e.keyCode === 80 /* p key */){

            showPowerNav();

            e.preventDefault();
            e.stopPropagation();
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
        powerNavQuery(query);
    });
});
