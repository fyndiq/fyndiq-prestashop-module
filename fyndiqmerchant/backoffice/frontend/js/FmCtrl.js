"use strict";

var FmCtrl = {
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
                FmGui.show_message('error', messages['service-call-fail-head'],
                    messages['service-call-fail-message']);
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

    export_products: function(products, callback) {
        FmCtrl.call_service('export_products', {'products': products}, function(data) {
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

        // when clicking the export products submit buttons, send product id's via ajax
        $(document).on('click', '.fm-product-list-controls .submit[name=submit_export]', function(e) {
            e.preventDefault();

            var products = [];

            // find all products
            $('.fm-product-list > li').each(function(k, v) {

                // check if product is selected
                var active = $(this).find('.product .select input').prop('checked');
                if (active) {

                    var combinations = [];

                    // find all combinations
                    $(this).find('.combinations > li').each(function(k, v) {

                        // check if combination is selected
                        var active = $(this).find('> .select input').prop('checked');
                        if (active) {

                            // store combination id
                            combinations.push($(this).attr('data-combination_id'));
                        }
                    });

                    // store product id and combinations
                    products.push({
                        'product': $(this).attr('data-product_id'),
                        'combinations': combinations
                    });
                }
            });

            if (products.length > 0) {
                FmGui.show_load_screen(function() {

                    FmCtrl.export_products(products, function() {

                        FmGui.show_message('success', messages['products-exported-title'],
                            messages['products-exported-message']);

                        var category = $('.fm-category-tree li.active').attr('data-category_id');
                        FmCtrl.load_products(category, function() {
                            FmGui.hide_load_screen();
                        });
                    });
                });
            } else {
                FmGui.show_message('info', messages['products-not-selected-title'],
                    messages['products-not-selected-message']);
            }
        });
    }
};
