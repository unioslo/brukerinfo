$(document).ready(function() {
    var elements = $('input[name^="consent-"]');
    for (var i = 0; i < elements.length; i++) {
        (function(i) {
            var checked = elements[i].checked;
            $('#' + elements[i].name + '-checkbox').change(function() {
                if (this.checked) {
                    $('#' + elements[i].name + '-submit').attr('disabled', checked);
                }
                else {
                    $('#' + elements[i].name + '-submit').attr('disabled', !checked);
                }
            });
        }).call(this, i);
    }

    $('#consent tr').click(function() {
        var href = $(this).find("a").attr("href");
        if(href) {
            window.location = href;
        }
    })
});