$(document).ready(function() {
    $(function() {
        //$("#demo-pick").flatpickr();
        flatpickr("#groupDatePicker", {
            altInput: true,
            altFormat: "F j, Y",
            'maxDate': new Date(new Date().setFullYear(new Date().getFullYear() + 1)),
        });
    });

    $('[name="datepicker-subm"]').click(function() {
        let date = $("#groupDatePicker").attr("value")
        let inputVal = $('[name="_qf__setExpire"]')
        inputVal.attr("value", date)
    })
});
