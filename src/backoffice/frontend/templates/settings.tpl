{include './common.tpl'}
<div class="fm-container {$version}">

    {include file='./header.tpl' current='settings' buttons=true}

    <div class="fm-content-wrapper">
        <div class="fm-update-message-container"></div>
        <div class="fm-panel">
            <div class="fm-panel-header text-center">{fi18n s='Settings'}</div>
            <div class="fm-panel-body text-center">
                {foreach $message as $msg}
                    <p class="text-warning">{$msg}</p>
                {/foreach}
                <form action="" method="post" class="form-horizontal">
                    <p>
                        {fi18n s='In order to use this module, you have to select which language you will be using'}.<br>
                        {fi18n s='The language, you select, will be used when exporting products to Fyndiq'}.<br>
                        {fi18n s='Make sure you select a language that contains Swedish product info!'}<br>
                    </p>
                    <h2>{fi18n s='Localization'}</h2>

                    <div class="form-group">
                        <label for="fm-language-choice">{fi18n s='Language'}</label>

                        <select name="language_id" id="fm-language-choice">
                            {foreach $languages as $language}
                                <option
                                    value="{$language.id_lang}"
                                    {if $language.id_lang == $selected_language}
                                        selected="selected"
                                    {/if}
                                    >{$language.name}</option>
                            {/foreach}
                        </select>
                    </div>

                    <h2>{fi18n s='System'}</h2>
                    <b>{fi18n s='Percentage of price'}</b>
                    <p>{fi18n s='This percentage is the percentage of the price that will be cut off your price, if 10% percentage it will be 27 SEK of 30 SEK (10% of 30 SEK is 3 SEK)'}.</p>
                    <div class="form-group">
                        <label for="fm-price_percentage">{fi18n s='Percentage in numbers only'}</label>
                        <input type="number" name="price_percentage" id="fm-price_percentage"
                                {if $price_percentage}
                                    value="{$price_percentage}"
                                {/if}
                                >
                    </div>
                    <div class="form-group">
                        <label for="fm-stock-min">{fi18n s='Lowest quantity to send to Fyndiq'}</label>
                        <input type="number" name="stock_min" id="fm-stock-min"
                                {if $stock_min}
                                    value="{$stock_min}"
                                {/if}
                                >
                    </div>
                    <div class="form-group">
                        <label for="fm-order-done-state">{fi18n s='Description to use'}</label>
                        <select name="description_type" id="fm-description-type">
                            {foreach $description_types as $description_type}
                                <option value="{$description_type.id}"
                                        {if $description_type.id == $description_type_id}
                                            selected="selected"
                                        {/if}
                                        >{$description_type.name}</option>
                            {/foreach}
                        </select>
                    </div>


                    {if $orders_enabled}
                    <h2>{fi18n s='Orders'}</h2>

                    <div class="form-group">
                        <label for="fm-order-import-state">{fi18n s='Import state'}</label>
                        <select name="order_import_state" id="fm-order-import-state">
                            {foreach $order_states as $order_state}
                                <option value="{$order_state.id_order_state}"
                                        {if $order_state.id_order_state == $order_import_state}
                                            selected="selected"
                                        {/if}
                                        >{$order_state.name}</option>
                            {/foreach}
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="fm-order-done-state">{fi18n s='Done state'}</label>
                        <select name="order_done_state" id="fm-order-done-state">
                            {foreach $order_states as $order_state}
                                <option value="{$order_state.id_order_state}"
                                        {if $order_state.id_order_state == $order_done_state}
                                            selected="selected"
                                        {/if}
                                        >{$order_state.name}</option>
                            {/foreach}
                        </select>
                    </div>
                    {/if}

                    <button class="btn btn-green" type="submit" name="submit_save_settings">{fi18n s='Save Settings'}</button>
                </form>
                <div class="text-right">
                    <a href="{$path}&action=disconnect" class="btn btn-red">{fi18n s='Disconnect Account'}</a>
                </div>
                <div class="text-left">
                    <a href="#" class="fm-check-module">{fi18n s='Check module'}</a>
                    <ul class="fm-module-check"></ul>
                </div>
            </div>
        </div>
        <br class="clear" />
        <div class="fm-content-wrapper fm-footer muted text-right">
            <img class="fm-update-check" style="display:none" src="{$shared_path}frontend/images/update-loader.gif" />
            {$module_version}
        </div>
    </div>
</div>

<script>
(function($, window, serviceURL, formKey){

    var probes = {$probes};

    function setupProbe($container, probe) {
        var $probeLine = $([
                '<li class="fm-probe fm-probe-' + probe.action +'">',
                '<h3>',
                '<img class="trobber" src="{$shared_path}frontend/images/update-loader.gif" /> ',
                probe.label,
                '</h3>',
                '<div class="fm-probe-result"></div>',
                '</li>'
            ].join(''));
        $element = $container.append($probeLine);
        $.ajax({
            url: serviceURL,
            method: 'POST',
            data: {
                action : probe.action
            }
        })
            .done(function(data) {
                var errorMessage = data;
                if (data.hasOwnProperty('fm-service-status')) {
                    if (data['fm-service-status'] === 'success') {
                        $probeLine.addClass('fm-probe-success');
                        $probeLine.find('.fm-probe-result').html(data.data);
                        return;
                    }
                    errorMessage = data.message;
                }
                // Failure show the result
                $probeLine.addClass('fm-probe-error');
                $probeLine.find('.fm-probe-result').html(errorMessage);
            })
            .fail(function(jqXHR, textStatus) {
                $probeLine.addClass('fm-probe-error');
                $probeLine.find('.fm-probe-result').html(textStatus);
            })
            .always(function() {
                $probeLine.find('img').remove();
            });
    }

    $('document').ready(function() {

        var $moduleCheckList = $('.fm-module-check');
        var fresh = true;

        $('.fm-check-module').click(function(event){
            event.preventDefault();
            if (fresh) {
                fresh = false;
                probes.forEach(function(probe, i){
                    setTimeout(function() {
                        setupProbe($moduleCheckList, probe);
                    }, i * 500);
                });
            }
        });
    });
})(window.jQuery, window, window.FmPaths.service);
</script>
