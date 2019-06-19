$(document).ready(function() {
    var checked = document.getElementById('consent-checkbox').checked;
    $('#consent-checkbox').change(function() {
        if (this.checked) {
            $('#consent-submit').attr('disabled', checked);
        }
        else {
            $('#consent-submit').attr('disabled', !checked);
        }
    });
});