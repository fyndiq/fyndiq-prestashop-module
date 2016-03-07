$(document).on('change', '#fyndiq_exported', function() {
    $("#fyndiq_title, #fyndiq_description").prop('disabled', !$('#fyndiq_exported').is(":checked"));
});
