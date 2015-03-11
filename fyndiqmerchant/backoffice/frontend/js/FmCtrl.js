/* global $, FmGui, module_path, messages, tpl, urlpath0 */

var FmCtrl = {
    call_service: function (action, args, callback) {
        'use strict';
        $.ajax({
            type: 'POST',
            url: module_path + 'backoffice/service.php',
            data: {'action': action, 'args': args},
            dataType: 'json'
        }).always(function (data) {
            var status = 'error';
            var result = null;
            if ($.isPlainObject(data) && ('fm-service-status' in data)) {
                if (data['fm-service-status'] === 'error') {
                    FmGui.show_message('error', data.title, data.message);
                }
                if (data['fm-service-status'] === 'success') {
                    status = 'success';
                    result = data.data;
                }
            } else {
                FmGui.show_message('error', messages['unhandled-error-title'],
                    messages['unhandled-error-message']);
            }
            if (callback) {
                callback(status, result);
            }
        });
    },

    load_categories: function (category_id, $container, callback) {
        'use strict';
        FmCtrl.call_service('get_categories', {category_id: category_id}, function (status, categories) {
            if (status === 'success') {
                $(tpl['category-tree']({
                    'categories': categories
                })).appendTo($container);
            }

            if ($.isFunction(callback)) {
                callback();
            }
        });
    },

    load_products: function (category_id, page, callback) {
        'use strict';
        // unset active class on previously selected category
        $('.fm-category-tree li').removeClass('active');

        FmCtrl.call_service('get_products', {'category': category_id, 'page': page}, function (status, products) {
            if (status === 'success') {
                $('.fm-product-list-container').html(tpl['product-list']({
                    'module_path': module_path,
                    'products': products.products,
                    'pagination': products.pagination
                }));

                // set active class on selected category
                $('.fm-category-tree li[data-category_id=' + category_id + ']').addClass('active');

                // http://stackoverflow.com/questions/5943994/jquery-slidedown-snap-back-issue
                // set correct height on combinations to fix jquery slideDown jump issue
                $('.fm-product-list .combinations').each(function (k, v) {
                    $(v).css('height', $(v).height());
                    $(v).hide();
                });
            }

            if (callback) {
                callback();
            }
        });
    },

    update_product: function (product, percentage, callback) {
        'use strict';
        FmCtrl.call_service('update_product', {'product': product, 'percentage': percentage}, function (status) {
            if (callback) {
                callback(status);
            }
        });
    },

    load_orders: function (callback) {
        'use strict';
        FmCtrl.call_service('load_orders', {}, function (status, orders) {
            if (status === 'success') {
                $('.fm-order-list-container').html('');
                $('.fm-order-list-container').html(tpl['orders-list']({
                    'module_path': module_path,
                    'orders': orders
                }));
            }

            if (callback) {
                callback();
            }
        });
    },

    import_orders: function (callback) {
        'use strict';
        FmCtrl.call_service('import_orders', {}, function (status, orders) {
            if (status === 'success') {
                FmGui.show_message('success', messages['orders-imported-title'],
                    messages['orders-imported-message']);
            }
            if (callback) {
                callback();
            }
        });
    },

    export_products: function (products, callback) {
        'use strict';
        FmCtrl.call_service('export_products', {'products': products}, function (status, data) {
            if (status === 'success') {
                FmGui.show_message('success', messages['products-exported-title'],
                    messages['products-exported-message']);

                // reload category to ensure that everything is reset properly
                var category = $('.fm-category-tree li.active').attr('data-category_id');
                FmCtrl.load_products(category, function () {
                    if (callback) {
                        callback();
                    }
                });
            } else {
                if (callback) {
                    callback();
                }
            }
        });
    },


    products_delete: function (products, callback) {
        'use strict';
        FmCtrl.call_service('delete_exported_products', {'products': products}, function (status, data) {
            if (status === 'success') {
                FmGui.show_message('success', messages['products-deleted-title'],
                    messages['products-deleted-message']);
            }
            if (callback) {
                callback();
            }
        });
    },

    bind_event_handlers: function () {
        'use strict';
        // import orders submit button
        $(document).on('submit', '.fm-form.orders', function (e) {
            e.preventDefault();
            FmGui.show_load_screen();
            FmCtrl.import_orders(function () {
                FmGui.hide_load_screen();
            });
        });

        // When clicking category in tree, load its products
        $(document).on('click', '.fm-category-tree a', function (e) {
            var $li = $(this).parent();
            e.preventDefault();
            var category_id = parseInt($li.attr('data-category_id'), 10);
            FmGui.show_load_screen(function () {
                if (!$li.data('expanded')) {
                    FmCtrl.load_categories(category_id, $li, function() {
                        $li.data('expanded', true);
                        FmCtrl.load_products(category_id, function () {
                            FmGui.hide_load_screen();
                        });
                    });
                } else {
                    FmCtrl.load_products(category_id, function () {
                        FmGui.hide_load_screen();
                    });
                }
            });
        });

        $(document).on('click', 'div.pages > ol > li > a', function (e) {
            e.preventDefault();

            var category = $('.fm-category-tree li.active').attr('data-category_id');
            FmGui.show_load_screen(function () {
                var page = $(e.target).attr('data-page');
                FmCtrl.load_products(category, page, function () {
                    FmGui.hide_load_screen();
                });
            });
        });

        // when clicking select all products checkbox, set checked on all product's checkboxes
        $(document).on('click', '#select-all', function (e) {
            if ($(this).is(':checked')) {
                $('.fm-product-list tr .select input').each(function () {
                    $(this).prop('checked', true);
                    $('.fm-delete-products').removeClass('disabled').addClass('red');
                });

            } else {
                $('.fm-product-list tr .select input').each(function () {
                    $(this).prop('checked', false);
                    $('.fm-delete-products').removeClass('red').addClass('disabled');
                });
            }
        });

        // When clicking select on one product, check if any other is select and make delete button red.
        $(document).on('click', '.fm-product-list > tr', function () {
            var red = false;
            $('.fm-product-list .select input').each(function () {
                var active = $(this).prop('checked');
                if (active) {
                    red = true;
                }
            });
            if (red) {
                $('.fm-delete-products').removeClass('disabled').addClass('red');
            }
            else {
                $('.fm-delete-products').removeClass('red').addClass('disabled');
            }
        });

        var savetimeout;
        $(document).on('keyup', '.fyndiq_dicsount', function () {
            var discount = parseFloat($(this).val());
            var $product = $(this).closest('.product');
            var product_id = $product.attr('data-id');

            if (discount > 100) {
                discount = 100;
            }
            else if(discount < 0) {
                discount = 0;
            }

            var price = $product.attr('data-price');
            var field = $(this).closest('.prices').find('.price_preview_price');

            var counted = price - ((discount / 100) * price);
            if (isNaN(counted)) {
                counted = price;
            }

            field.text(counted.toFixed(2));

            clearTimeout(savetimeout);
            var ajaxdiv = $(this).parent().parent().find('#ajaxFired');
            ajaxdiv.html('Typing...').show();
            savetimeout = setTimeout(function () {
                FmCtrl.update_product(product_id, discount, function (status) {
                    if (status === 'success') {
                        ajaxdiv.html('Saved').delay(1000).fadeOut();
                    }
                    else {
                        ajaxdiv.html('Error').delay(1000).fadeOut();
                    }
                });
            }, 1000);
        });

        // when clicking the export products submit buttons, export products
        $(document).on('click', '.fm-export-products', function (e) {
            e.preventDefault();

            var products = [];

            // find all products
            $('.fm-product-list > tr').each(function (k, v) {

                // check if product is selected
                var active = $(this).find('.select input').prop('checked');
                if (active) {


                    // store product id and combinations
                    var price = $(this).find('td.prices > div.price > input').val();
                    var fyndiq_percentage = $(this).find('.fyndiq_dicsount').val();
                    products.push({
                        'product': {
                            'id': $(this).data('id'),
                            'fyndiq_percentage': fyndiq_percentage
                        }
                    });
                }
            });

            // if no products selected, show info message
            if (products.length === 0) {
                FmGui.show_message('info', messages['products-not-selected-title'],
                    messages['products-not-selected-message']);

            } else {

                // helper function that does the actual product export
                var export_products = function (products) {
                    FmGui.show_load_screen(function () {
                        FmCtrl.export_products(products, function () {
                            FmGui.hide_load_screen();
                        });
                    });
                };

                // export the products
                export_products(products);
            }
        });

        //Deleting selected products from export table
        $(document).on('click', '.fm-delete-products', function (e) {
            e.preventDefault();
            if ($(this).hasClass('disabled')) {
                return;
            }
            FmGui.show_load_screen(function () {
                var products = [];

                // find all products
                $('.fm-product-list .select input:checked').each(function (k, v) {
                    products.push({
                        'product': {
                            'id': $(this).parent().parent().data('id')
                        }
                    });
                });

                // if no products selected, show info message
                if (products.length === 0) {
                    FmGui.show_message('info', messages['products-not-selected-title'],
                        messages['products-not-selected-message']);
                    FmGui.hide_load_screen();

                } else {
                    // delete selected products
                    FmCtrl.products_delete(products, function () {
                        // reload category to ensure that everything is reset properly
                        var category = $('.fm-category-tree li.active').attr('data-category_id');
                        var page = $('div.pages > ol > li.current').html();
                        if (page === 'undefined') {
                            page = 1;
                        }
                        FmCtrl.load_products(category, page, function () {
                            FmGui.hide_load_screen();
                        });

                    });

                }
            });

        });
    },
    bind_order_event_handlers: function () {
        'use strict';
        // import orders submit button
        $(document).on('click', '#fm-import-orders', function (e) {
            e.preventDefault();
            FmGui.show_load_screen();
            FmCtrl.import_orders(function () {
                FmCtrl.load_orders(function () {
                    FmGui.hide_load_screen();
                });
            });
        });

        // when clicking select all orders checkbox, set checked on all order's checkboxes
        $(document).on('click', '#select-all', function (e) {
            if ($(this).is(':checked')) {
                $('.fm-orders-list tr .select input').each(function () {
                    $(this).prop('checked', true);
                });

            } else {
                $('.fm-orders-list tr .select input').each(function () {
                    $(this).prop('checked', false);
                });
            }
        });

        $(document).on('click', '.getdeliverynote', function (e) {
            if ($('.fm-orders-list > tr .select input:checked').length === 0) {
                e.preventDefault();
                FmGui.show_message('info', messages['orders-not-selected-title'],
                    messages['orders-not-selected-message']);
            }
        });
    }
};
