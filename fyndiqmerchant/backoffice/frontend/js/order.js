"use strict";

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

$(document).ready(function() {

    FmGui.show_load_screen(function(){
        FmCtrl.bind_order_event_handlers();

        // load all categories
        FmCtrl.load_orders(function() {
            FmGui.hide_load_screen();
        });
    });
});
