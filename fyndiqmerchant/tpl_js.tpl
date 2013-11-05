
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
};

$(document).ready(function() {

    $('.fm-form.orders').live('submit', function(e){
        e.preventDefault();
        $('.fm-loading-overlay').show();

        $.ajax({
            url: '{$path}ajax.php',
            dataType: 'json',
        }).always(function(data){
            if (!('status' in data)) {
                show_msg('error', 'Unexpected error: Invalid ajax response');
            }
            if (data['status'] == 'error') {
                show_message('error', 'Unexpected error from server: ' + data['message']);
            }
            if (data['status'] == 'success') {
                show_msg('success', 'Yippie');
                console.log(data['data'])
            }
            $('.fm-loading-overlay').hide();
        });
    });
});

</script>
