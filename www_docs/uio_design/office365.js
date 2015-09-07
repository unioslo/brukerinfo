$(document).ready(function() {
    if (document.getElementById('consent-checkbox').checked) {
        $('#consent-checkbox').change(function() {
            if (this.checked) {
                $('#consent-submit').attr('disabled', true);
            }
            else {
                $('#consent-submit').attr('disabled', false);
            }
        });
    }
    else {
        $('#consent-checkbox').change(function() {
            if (this.checked) {
                $('#consent-submit').attr('disabled', false);
            }
            else {
                $('#consent-submit').attr('disabled', true);
            }
        });
    }
});