
<script type="text/javascript">

var show_msg = function(type, msg) {
    if (type == 'success') {
        var classnames = 'fm-message-success conf confirm';
    }
    if (type == 'error') {
        var classnames = 'fm-message-error error';
    }
    var html = $('<div class="'+classnames+'"><p>'+msg+'</p></div>');
    $('#fm-container').prepend(html);
    setTimeout(function(){
        html.fadeOut(400, function(){
            html.remove();
        });
    }, 8000);
};

$(document).ready(function() {

    $('.fm-form.orders').live('submit', function(e){
        e.preventDefault();
        $('.fm-loading-overlay').show();

        $.ajax({
            type: 'POST',
            url: '{$path}backoffice/service.php',
            data: {literal}{'action': 'get_orders'}{/literal},
            dataType: 'json',
        }).always(function(data){
            if ($.isPlainObject(data) && ('fm-service-status' in data)) {
                if (data['fm-service-status'] == 'error') {
                    show_msg('error', 'Error when calling service: ' + data['message']);
                }
                if (data['fm-service-status'] == 'success') {
                    show_msg('success', 'Yippie');
                }
            } else {
                show_msg('error', 'Error: Invalid response from service');
            }

            $('.fm-loading-overlay').hide();
        });
    });
});

</script>
