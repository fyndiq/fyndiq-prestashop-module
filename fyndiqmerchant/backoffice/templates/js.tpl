
<script type="text/javascript" src="{$module_path}backoffice/templates/handlebars-v1.1.2.js"></script>
<script type="text/javascript">

// get smarty template variables before going into literal javascript block
var module_path = '{$module_path}';

{literal}

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
    tpl[$(v).attr('id')] = Handlebars.compile($(v).html());
});

var FmCtrl = {
    show_load_screen: function() {
        $('.fm-loading-overlay').fadeIn(300);
    },

    hide_load_screen: function() {
        setTimeout(function() {
            $('.fm-loading-overlay').fadeOut(300);
        }, 200);
    },

    show_msg: function(type, message) {
        var classnames = {
            'success': 'fm-message-success conf confirm',
            'error': 'fm-message-error error'
        };
        var box = $(tpl['message-box']({
            'classnames': classnames[type],
            'message': message
        }));
        $('#fm-message-boxes').append(box);
        setTimeout(function(){
            box.fadeOut(400, function(){
                this.remove();
            });
        }, 8000);
    },

    call_service: function(action, args, callback) {
        FmCtrl.show_load_screen();
        $.ajax({
            type: 'POST',
            url: module_path+'backoffice/service.php',
            data: {'action': action, 'args': args},
            dataType: 'json'
        }).always(function(data) {
            if ($.isPlainObject(data) && ('fm-service-status' in data)) {
                if (data['fm-service-status'] == 'error') {
                    FmCtrl.show_msg('error', 'Error when calling service: ' + data['message']);
                }
                if (data['fm-service-status'] == 'success') {
                    callback(data['data']);
                }
            } else {
                FmCtrl.show_msg('error', 'Error when calling service: Connection failed');
            }
            FmCtrl.hide_load_screen();
        });
    },

    load_categories: function(callback) {
        FmCtrl.call_service('get_categories', {}, function(categories) {
            $('.fm-category-tree-container').html(tpl['category-tree']({
                'categories': categories
            }));

            callback();
        });
    },

    load_products: function(category_id) {
        // unset active class on previously selected category
        $('.fm-category-tree a').removeClass('active');

        FmCtrl.call_service('get_products', {'category': category_id}, function(products) {
            $('.fm-product-list-container').html(tpl['product-list']({
                'module_path': module_path,
                'products': products
            }));

            // set active class on selected category
            $('.fm-category-tree a[data-category_id='+category_id+']').addClass('active');

            // http://stackoverflow.com/questions/5943994/jquery-slidedown-snap-back-issue
            // set correct height on combinations to fix jquery slideDown jump issue
            $('.fm-product-list .combinations').each(function(k, v) {
                $(v).css('height', $(v).height());
                $(v).hide();
            });
        });
    },

    import_orders: function() {
        FmCtrl.call_service('import_orders', {}, function() {

        });
    }
};

$(document).ready(function() {

    // event handlers
    $(document).on('submit', '.fm-form.orders', function(e){
        e.preventDefault();
        FmCtrl.import_orders();
    });

    $(document).on('click', '.fm-category-tree a', function(e) {
        e.preventDefault();
        FmCtrl.load_products($(this).attr('data-category_id'));
        return false;
    });

    $(document).on('click', '.fm-product-list .product .expand a', function(e) {
        e.preventDefault();
        $(this).parent().parent().parent().find('.combinations').slideToggle(250);
        return false;
    });

    $(document).on('change', '.fm-product-list .product .select input', function(e) {
        var combination_checkboxes = $(this).parents('li').find('.combinations .select input');
        combination_checkboxes.prop('checked', $(this).prop('checked'));
    });

    $(document).on('change', '.fm-product-list .combinations .select input', function(e) {
        $(this).parents('li').find('.product .select input').prop('checked', true);
    });

    // load all categories
    FmCtrl.load_categories(function(){

        // load products from second category
        var category_id = $('.fm-category-tree a').eq(1).attr('data-category_id');
        FmCtrl.load_products(category_id);

        FmCtrl.hide_load_screen();
    });
});

{/literal}

</script>
