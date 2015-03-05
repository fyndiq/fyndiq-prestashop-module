"use strict";

var FmCtrl = {
    call_service: function (action, args, callback) {
        $.ajax({
            type: 'POST',
            url: module_path + 'backoffice/service.php',
            data: {'action': action, 'args': args},
            dataType: 'json'
        }).always(function (data) {
            var status = 'error';
            var result = null;
            if ($.isPlainObject(data) && ('fm-service-status' in data)) {
                if (data['fm-service-status'] == 'error') {
                    FmGui.show_message('error', data['title'], data['message']);
                }
                if (data['fm-service-status'] == 'success') {
                    status = 'success';
                    result = data['data'];
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

    load_categories: function (callback) {
        FmCtrl.call_service('get_categories', {}, function (status, categories) {
            if (status == 'success') {
                $('.fm-category-tree-container').html(tpl['category-tree']({
                    'categories': categories
                }));
            }

            if (callback) {
                callback();
            }
        });
    },

    load_products: function (category_id, page, callback) {
        // unset active class on previously selected category
        $('.fm-category-tree li').removeClass('active');

        FmCtrl.call_service('get_products', {'category': category_id, 'page': page}, function (status, products) {
            if (status == 'success') {
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
        FmCtrl.call_service('update_product', {'product': product, 'percentage': percentage}, function (status) {
            if (callback) {
                callback(status);
            }
        });
    },

    load_orders: function (callback) {
        FmCtrl.call_service('load_orders', {}, function (status, orders) {
            if (status == 'success') {
                $('.fm-order-list-container').html("");
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
        FmCtrl.call_service('import_orders', {}, function (status, orders) {
            if (status == 'success') {
                FmGui.show_message('success', messages['orders-imported-title'],
                    messages['orders-imported-message']);
            }
            if (callback) {
                callback();
            }
        });
    },

    export_products: function (products, callback) {
        FmCtrl.call_service('export_products', {'products': products}, function (status, data) {
            if (status == 'success') {
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

        // import orders submit button
        $(document).on('submit', '.fm-form.orders', function (e) {
            e.preventDefault();
            FmGui.show_load_screen();
            FmCtrl.import_orders(function () {
                FmGui.hide_load_screen();
            });
        });

        // when clicking category in tree, load its products
        $(document).on('click', '.fm-category-tree a', function (e) {
            e.preventDefault();
            var category_id = $(this).parent().attr('data-category_id');
            FmGui.show_load_screen(function () {
                FmCtrl.load_products(category_id, function () {
                    FmGui.hide_load_screen();
                });
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
                $(".fm-product-list tr .select input").each(function () {
                    $(this).prop("checked", true);
                    $('#delete-products').removeClass('disabled').addClass('red');
                });

            } else {
                $(".fm-product-list tr .select input").each(function () {
                    $(this).prop("checked", false);
                    $('#delete-products').removeClass('red').addClass('disabled');
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
                $('#delete-products').removeClass('disabled').addClass('red');
            }
            else {
                $('#delete-products').removeClass('red').addClass('disabled');
            }
        });

        var savetimeout;
        $(document).on('keyup', '.prices .fyndiq_price .inputdiv .fyndiq_dicsount', function () {
            var discount = $(this).val();
            var product = $(this).parent().parent().parent().parent().attr('data-id');

            if (discount > 100) {
                discount = 100;
            }

            var price = $(this).parent().parent().parent().parent().attr('data-price');
            var field = $(this).parent().parent().find('.price_preview_price');

            var counted = price - ((discount / 100) * price);
            if (isNaN(counted)) {
                counted = price;
            }

            field.text(counted.toFixed(2));

            clearTimeout(savetimeout);
            var ajaxdiv = $(this).parent().parent().find('#ajaxFired');
            ajaxdiv.html('Typing...').show();
            savetimeout = setTimeout(function () {
                FmCtrl.update_product(product, discount, function (status) {
                    if (status == "success") {
                        ajaxdiv.html('Saved').delay(1000).fadeOut();
                    }
                    else {
                        ajaxdiv.html('Error').delay(1000).fadeOut();
                    }
                });
            }, 1000);
        });

        // when clicking the export products submit buttons, export products
        $(document).on('click', '#export-products', function (e) {
            e.preventDefault();

            var products = [];

            // find all products
            $('.fm-product-list > tr').each(function (k, v) {

                // check if product is selected
                var active = $(this).find('.select input').prop('checked');
                if (active) {


                    // store product id and combinations
                    var price = $(this).find("td.prices > div.price > input").val();
                    var fyndiq_percentage = $(this).find(".fyndiq_price .inputdiv .fyndiq_dicsount").val();
                    console.log(fyndiq_percentage);
                    products.push({
                        'product': {
                            'id': $(this).data('id'),
                            'fyndiq_percentage': fyndiq_percentage
                        }
                    });
                }
            });

            // if no products selected, show info message
            if (products.length == 0) {
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
        $(document).on('click', '#delete-products', function (e) {
            e.preventDefault();
            if ($(this).hasClass("disabled")) {
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
                        if (page == 'undefined') {
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
                $(".fm-orders-list tr .select input").each(function () {
                    $(this).prop("checked", true);
                });

            } else {
                $(".fm-orders-list tr .select input").each(function () {
                    $(this).prop("checked", false);
                });
            }
        });
        $(document).on('click', '#getdeliverynote', function () {
            var orders = [];

            $('.fm-orders-list > tr').each(function (k, v) {
                // check if product is selected
                var active = $(this).find('.select input').prop('checked');
                if (active) {
                    orders.push($(this).data('fyndiqid'));
                }
            });

            FmGui.show_load_screen(function () {
                FmCtrl.get_delivery_notes(orders, function (status) {
                    FmGui.hide_load_screen();
                    if (status == 'success') {
                        var wins = window.open(urlpath0 + "fyndiq/files/deliverynote.pdf", '_blank');
                        if (wins) {
                            //Browser has allowed it to be opened
                            wins.focus();
                        }
                    }
                });
            });
        });
    }
};
