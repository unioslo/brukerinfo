$(document).ready(function() {

    function getCookie(cname) {
        var name = cname + "=";
        var ca = document.cookie.split(';');
        for(var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0)==' ') c = c.substring(1);
            if (c.indexOf(name) == 0) return c.substring(name.length,c.length);
        }
        return "";
    }

    var formContainer = $('#form');
    var passwordForm = $('#setPassword');
    passwordForm.appendTo(formContainer);
    var validationContainer = $('#validation');

    var postUrl = '/forgotten/password/validator.php';
    var passwordIsValidated = false;

    var passwordTypes = {
        'rigid': {
            'en': 'Type: Password',
            'nb': 'Type: Passord'
        },
        'phrase': {
            'en': 'Type: Passphrase',
            'nb': 'Type: Passfrase'
        }
    };

    var validationHeader = {
        'en': 'Validation status',
        'nb': 'Valideringsstatus'
    };

    var language = getCookie('chosenLang');
    // JSON from server uses 'nb' and not 'no', so we reassign here.
    if (language === 'no') {
        language = 'nb'
    }

    // Disable submit button until all checks are passed
    $('.submit').prop('disabled', true);

    // Trigger fetching the validation JSON and display the current rules active
    $.post(postUrl, { password: 'a' } ,
        function(data) {
            buildValidationFeedbackElements(data);
        }
    );

    // Validate the password as the user types it in (with a slight delay):
    $('#password').keyup(function() {
        clearTimeout($.data(this, 'timer'));
        var wait = setTimeout(checkPassword, 200);
        $(this).data('timer', wait);

        if ($('#confirm-password').val() !== '' && $('#password').val() !== $('#confirm-password').val()) {
            $('.submit').prop('disabled', true);
            $('#confirm-password-feedback').show();
        }
        else {
            $('.submit').prop('disabled', false);
            $('#confirm-password-feedback').hide();
        }
    });

    // Check if confirmed password field matches.
    $('#confirm-password').keyup(function() {
        if (passwordIsValidated && ($('#password').val() === $('#confirm-password').val())) {
            $('.submit').prop('disabled', false);
            $('#confirm-password-feedback').hide();
        }

        else {
            $('.submit').prop('disabled', true);
            $('#confirm-password-feedback').show();
        }
    });

    function checkPassword() {
        var pasw = $('#password').val();
        // If password is empty, send a dummy value to ensure a proper
        // response. This can happen if the user deletes an already typed-in
        // password.
        if (pasw === '') {
            pasw = 'a';
        }
        // Send the data using post and put the results in a div
        $.post(postUrl, { password: pasw},
            function(data) {
                buildValidationFeedbackElements(data);
                passwordIsValidated = data.passed;
                if (data.passed && ($('#password').val() === $('#confirm-password').val()))
                    $('.submit').prop('disabled', false);
                else
                    $('.submit').prop('disabled', true);
            }
        );
    }

    function buildValidationFeedbackElements(data) {
        validationContainer.empty();
        $('<div class="validation-header">' + validationHeader[language] + '</div>').appendTo(validationContainer);
        var tabsContainer = $('<ul class="tabs"></ul>').appendTo(validationContainer);
        var passwordStylesAllowed = data.allowed_style;
        var currentPasswordStyle = data.style;
        var currentPasswordStyleName;
        if (passwordStylesAllowed === 'mixed') {
            // Show other available password types as flaps if mixed mode is on.
            for (passwordType in data.checks) {
                currentPasswordStyleName = passwordTypes[passwordType][language];
                if (passwordType === currentPasswordStyle)
                    buildRuleChecklist(data, passwordType, currentPasswordStyleName, tabsContainer, validationContainer);
                else
                    $('<li>' + currentPasswordStyleName + '</li>').appendTo(tabsContainer);
            }
        }
        else {
            // Only display the activated ruleset
            currentPasswordStyleName = passwordTypes[passwordStylesAllowed][language];
            buildRuleChecklist(data, currentPasswordStyle, currentPasswordStyleName, tabsContainer, validationContainer)
        }


    }

    function buildRuleChecklist(data, passwordType, currentPasswordStyleName, tabsContainer, validationContainer) {
        var currentChecks = data.checks[passwordType];
        var currentChecksContainer;
        currentChecksContainer = $('<div class="tab-content current"></div>');
        $('<li class="current">' + currentPasswordStyleName + '</li>').appendTo(tabsContainer);
        for (check in currentChecks) {
            for (rule in currentChecks[check]) {
                var ruleElement;
                var ruleIcon;
                if (currentChecks[check][rule].passed) {
                    ruleElement = $('<div class="password-rule passed"></div>');
                    ruleIcon = $('<img src="/forgotten/uio_design/images/admonitions/checksign.png">');
                }
                else {
                    ruleElement = $('<div class="password-rule failed"></div>');
                    ruleIcon = $('<img src="/forgotten/uio_design/images/admonitions/errorsign.png">');
                }
                ruleIcon.appendTo(ruleElement);
                var ruleText = $('<p>' + currentChecks[check][rule]['requirement'][language] + '</p>');
                ruleText.appendTo(ruleElement);
                ruleElement.appendTo(currentChecksContainer);
            }
        }
        currentChecksContainer.appendTo(validationContainer);
    }
});

