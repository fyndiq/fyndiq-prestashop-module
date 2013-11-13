
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

// precompile templates
var tpl = {};
$('script[type="text/x-handlebars-template"]').each(function(k,v){
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
        FmCtrl.call_service('get_categories', {}, function(levels) {

            var categories = [];
            for (var i = 0; i < levels.length; i++) {
                var level = levels[i];

                for (var j in level) {
                    var category = level[j];
                    var c = category['infos'];
                    categories.push({'level': i, 'category': c});
                }
            }

            $('.fm-category-tree-container').html(tpl['category-tree']({
                'categories': categories
            }));

            callback();
        });
    },

    load_products: function(category_id) {
        FmCtrl.call_service('get_products', {'category': category_id}, function(products) {
            $('.fm-product-list-container').html(tpl['product-list']({
                'products': products
            }));

            // add active class to selected category
            $('.fm-category-tree a').removeClass('active');
            $('.fm-category-tree a[data-category_id='+category_id+']').addClass('active');
        });
    },

    import_orders: function() {
        FmCtrl.call_service('import_orders', {}, function() {

        });
    }
};

$(document).ready(function() {

    // event handlers
    $('.fm-form.orders').live('submit', function(e){
        e.preventDefault();
        FmCtrl.import_orders();
    });

    $('.fm-category-tree a').live('click', function(e) {
        e.preventDefault();
        FmCtrl.load_products($(this).attr('data-category_id'));
        return false;
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
