/* global $, Handlebars, FmGui, FmCtrl */

(function(context) {
    'use strict';

    // Pre-compile handlebars templates
    context.tpl = {};

    // Pre-compile handlebars partials
    $('script.handlebars-partial').each(function(k, v) {
        Handlebars.registerPartial($(v).attr('id'), $(v).html());
    });

    $('script.handlebars-template').each(function(k, v) {
        context.tpl[$(v).attr('id').substring(3)] = Handlebars.compile($(v).html());
    });

    // Setup page
    $(document).ready(function() {
        FmGui.show_load_screen(function(){
            FmCtrl.bind_event_handlers();

            // load all parent categories
            FmCtrl.load_categories(0, $('.fm-category-tree-container'), function() {

                // load products from first category
                var category_id = $('.fm-category-tree a').eq(0).parent().attr('data-category_id');
                FmCtrl.load_products(category_id, 1, function() {
                    FmGui.hide_load_screen();
                });
            });
        });
    });
})(window);
