/* global $, Handlebars, FmCtrl, FmGui*/

(function(window, $) {
    'use strict';

    // prec-ompile handlebars partials
    $('script.handlebars-partial').each(function (k, v) {
        Handlebars.registerPartial($(v).attr('id'), $(v).html());
    });

    // pre-compile handlebars templates
    window.tpl = {};
    $('script.handlebars-template').each(function (k, v) {
        window.tpl[$(v).attr('id').substring(3)] = Handlebars.compile($(v).html());
    });

    $(document).ready(function () {

        FmGui.show_load_screen(function () {
            FmCtrl.bind_order_event_handlers();

            var page = $('div.pages > ol > li.current').html();
            if (page === 'undefined') {
                page = 1;
            }
            // load all orders
            FmCtrl.load_orders(page, function () {
                FmGui.hide_load_screen();
            });
        });
    });
})(window, $, FmGui, FmCtrl);
