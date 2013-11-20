
String.prototype.repeat = function(times) {
    return (new Array(times + 1)).join(this);
};

var cl = function(v) {
    console.log(v)
};

// precompile handlebars partials
$('script.handlebars-partial').each(function(k, v) {
    Handlebars.registerPartial($(v).attr('id'), $(v).html());
});

// precompile handlebars templates
var tpl = {};
$('script.handlebars-template').each(function(k, v) {
    tpl[$(v).attr('id').substring(3)] = Handlebars.compile($(v).html());
});

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
        }, 6000);
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

FmCtrl = {
    call_service: function(action, args, callback) {
        $.ajax({
            type: 'POST',
            url: module_path+'backoffice/service.php',
            data: {'action': action, 'args': args},
            dataType: 'json'
        }).always(function(data) {
            if ($.isPlainObject(data) && ('fm-service-status' in data)) {
                if (data['fm-service-status'] == 'error') {
                    FmGui.show_message('error', messages['service-call-fail-head'], data['message']);
                }
                if (data['fm-service-status'] == 'success') {
                    callback(data['data']);
                }
            } else {
                FmGui.show_message('error', messages['service-call-fail-head'], messages['connection-failed']);
            }
        });
    },

    load_categories: function(callback) {
        FmCtrl.call_service('get_categories', {}, function(categories) {
            $('.fm-category-tree-container').html(tpl['category-tree']({
                'categories': categories
            }));

            if (callback) {
                callback();
            }
        });
    },

    load_products: function(category_id, callback) {
        // unset active class on previously selected category
        $('.fm-category-tree li').removeClass('active');

        FmCtrl.call_service('get_products', {'category': category_id}, function(products) {
            $('.fm-product-list-container').html(tpl['product-list']({
                'module_path': module_path,
                'products': products
            }));

            // set active class on selected category
            $('.fm-category-tree li[data-category_id='+category_id+']').addClass('active');

            // http://stackoverflow.com/questions/5943994/jquery-slidedown-snap-back-issue
            // set correct height on combinations to fix jquery slideDown jump issue
            $('.fm-product-list .combinations').each(function(k, v) {
                $(v).css('height', $(v).height());
                $(v).hide();
            });

            if (callback) {
                callback();
            }
        });
    },

    import_orders: function(callback) {
        FmCtrl.call_service('import_orders', {}, function() {
            if (callback) {
                callback();
            }
        });
    },

    bind_event_handlers: function() {

        // import orders submit button
        $(document).on('submit', '.fm-form.orders', function(e) {
            e.preventDefault();
            FmGui.show_load_screen();
            FmCtrl.import_orders(function() {
                FmGui.hide_load_screen();
            });
        });

        // when clicking category in tree, load its products
        $(document).on('click', '.fm-category-tree a', function(e) {
            e.preventDefault();
            var self = this;
            FmGui.show_load_screen(function(){
                FmCtrl.load_products($(self).parent().attr('data-category_id'), function() {
                    FmGui.hide_load_screen();
                });
            });
        });

        // when clicking product's expand icon, show its combinations
        $(document).on('click', '.fm-product-list .product .expand a', function(e) {
            e.preventDefault();
            $(this).parents('li').find('.combinations').slideToggle(250);
        });

        // when clicking product's checkbox, toggle checked on all its combination's checkboxes
        $(document).on('change', '.fm-product-list .product .select input', function(e) {
            var combination_checkboxes = $(this).parents('li').find('.combinations .select input');
            combination_checkboxes.prop('checked', $(this).prop('checked'));
        });

        // when clicking a combination's checkbox, set checked on its parent product's checkbox
        $(document).on('change', '.fm-product-list .combinations .select input', function(e) {
            $(this).parents('li').find('.product .select input').prop('checked', true);
        });

        // when clicking select all products checkbox, set checked on all product's checkboxes
        $(document).on('click', '.fm-product-list-controls .select input', function(e) {
            e.preventDefault();
            if ($(this).attr('name') == 'select-all') {
                $('.fm-product-list .product .select input').prop('checked', true).change();
            }
            if ($(this).attr('name') == 'deselect-all') {
                $('.fm-product-list .product .select input').prop('checked', false).change();
            }
        });
    }
};

$(document).ready(function() {



    FmGui.show_load_screen(function(){
        FmCtrl.bind_event_handlers();

        // load all categories
        FmCtrl.load_categories(function() {

            // load products from second category
            var category_id = $('.fm-category-tree a').eq(1).parent().attr('data-category_id');
            FmCtrl.load_products(category_id, function() {
                FmGui.hide_load_screen();
            });
        });
    });
});
