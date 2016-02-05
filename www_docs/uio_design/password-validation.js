$(document).ready(function() {

    function getFields() {
        feedback = $('#password-feedback');
        feedback_confirm = $('#password-feedback-confirm');
        new_password = $('#new_pass').val();
        new_password2 = $('#new_pass2').val();
    }

    function checkEqual() {
        getFields();

        if (new_password2.length == 0) {
            feedback_confirm.hide()
            return;
        }

        if (new_password !== new_password2) {
            feedback_confirm.html(password_validation.error_match);
            feedback_confirm.removeClass("password-ok");
            feedback_confirm.addClass("password-bad");
        } else {
            feedback_confirm.html(password_validation.ok_match);
            feedback_confirm.removeClass("password-bad");
            feedback_confirm.addClass("password-ok");
        }

        feedback_confirm.show();
    }

    function checkPassword() {
        getFields();

        if (new_password.length == 0) {
            feedback.hide();
            return;
        }

        // Remove QuickForm error elements
        $( "span.error" ).each( function( index, element ) {
            $(this).hide();
        });

        $.post(password_validation.endpoint,
            { password: new_password } ,
            function(data, textStatus) {
                if (textStatus !== "success" || data.valid === undefined || data.valid === null) {
                    feedback.hide();
                    return;
                }
                feedback.html(data.message);
                feedback.show();
                if (data.valid === true) {
                    feedback.addClass("password-ok");
                    feedback.removeClass("password-bad");
                } else {
                    feedback.removeClass("password-ok");
                    feedback.addClass("password-bad");
                }
            },
            "json"
        ).fail(function(data, textStatus) {
            feedback.hide();
        });
    };

    $('#new_pass').keyup(function() {
        clearTimeout($.data(this, 'timer'));
        var wait = setTimeout(checkPassword, 300);
        $(this).data('timer', wait);
        checkEqual();
    });

    $('#new_pass2').keyup(function() {
        checkEqual();
    });

});
