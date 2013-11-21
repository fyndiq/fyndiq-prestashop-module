"use strict";

var FmGui = {
    messages_z_index_counter: 1,

    show_load_screen: function(callback) {
        var overlay = tpl['loading-overlay']({
            'module_path': module_path
        });
        $(overlay).hide().prependTo($('#fm-container'));
        var attached_overlay = $('.fm-loading-overlay');

        var top = $(document).scrollTop() + 100;
        attached_overlay.find('img').css({'marginTop': top+'px'});

        attached_overlay.fadeIn(300, function() {
            if (callback) {
                callback();
            }
        });
    },

    hide_load_screen: function(callback) {
        setTimeout(function() {
            $('.fm-loading-overlay').fadeOut(300, function() {
                $('.fm-loading-overlay').remove();
                if (callback) {
                    callback();
                }
            });
        }, 200);
    },

    show_message: function(type, title, message) {
        var overlay = $(tpl['message-overlay']({
            'module_path': module_path,
            'type': type,
            'title': title,
            'message': message
        }));

        overlay.hide()
            .css({'z-index': 999+FmGui.messages_z_index_counter++})
            .prependTo($('#fm-container'));

        var attached_overlay = $('.fm-message-overlay');
        attached_overlay.slideDown(300);

        attached_overlay.find('.close').bind('click', function(){
            $(this).parent().slideUp(200, function() {
                $(this).remove();
            });
        });

        setTimeout(function() {
            attached_overlay.find('.close').click();
        }, 10000);
    },

    show_modal: function(type, tpl_name, callback) {
        var buttons = {
            'cancel': {'type': 'cancel', 'label': 'Cancel'},
            'accept': {'type': 'accept', 'label': 'OK'}
        };
        var modal_args = {};
        if (type == 'confirm') {
            modal_args['buttons'] = [buttons['cancel'], buttons['accept']];
        }
        var overlay = $(tpl['modal-overlay'](modal_args));

        overlay.hide().prependTo($('#fm-container'));
        var attached_overlay = $('.fm-modal-overlay');

        var content = tpl[tpl_name]({});
        attached_overlay.find('.content').html(content);

        var top = $(document).scrollTop() + 50;
        attached_overlay.find('.container').css({'marginTop': top+'px'});

        attached_overlay.fadeIn(300);

        attached_overlay.find('.controls button').bind('click', function(e) {
            e.preventDefault();
            attached_overlay.remove();
            if (callback) {
                callback($(this).attr('data-modal_type'));
            }
        });
    }
};
