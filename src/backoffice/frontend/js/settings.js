
$(function() {
    $('select[name="is_active_cron_task"]').change(function(){
        if($(this).val() == '1'){
            $('#fm_interval').removeAttr('disabled');
        }else{
            $('#fm_interval').attr("disabled", "disabled");
        }
    });
});
