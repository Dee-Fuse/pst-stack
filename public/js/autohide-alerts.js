$(function () {
    $('.alert-dismissable:not(.alert-danger)').fadeTo(5000, 500).slideUp(500, function () {
        $(this).slideUp(500);
    });
});