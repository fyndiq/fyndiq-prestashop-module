<link href='http://fonts.googleapis.com/css?family=Montserrat:400,700' rel='stylesheet' type='text/css'>
<style type="text/css">
    {fetch file="$server_path/backoffice/frontend/css/main.css"}
</style>
<div class="fm-container">

    {include file='./header.tpl' current='settings'}

    <div class="fm-content-wrapper">
        <div class="fm-panel">
            <div class="fm-panel-header text-center">{l s='Settings' mod='fyndiqmerchant'}</div>
            <div class="fm-panel-body text-center">
                <form action="" method="post" class="form-horizontal">
                    <p>
                        {l s='In order to use this module, you have to select which language you will be using' mod='fyndiqmerchant'}.<br>
                        {l s='The language, you select, will be used when exporting products to Fyndiq' mod='fyndiqmerchant'}.<br>
                        {l s='Make sure you select a language that contains Swedish product info!' mod='fyndiqmerchant'}<br>
                    </p>
                    <h2>{l s='Localization' mod='fyndiqmerchant'}</h2>

                    <div class="form-group">
                        <label for="fm-language-choice">{l s='Language' mod='fyndiqmerchant'}</label>

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

                    <h2>{l s='System' mod='fyndiqmerchant'}</h2>
                    <b>{l s='Percentage of price' mod='fyndiqmerchant'}</b>
                    <p>{l s='This percentage is the percentage of the price that will be cut off your price, if 10%% percentage it will be 27 SEK of 30 SEK (10%% of 30 SEK is 3 SEK)' mod='fyndiqmerchant' sprintf=[]}.</p>
                    <div class="form-group">
                        <label for="fm-price_percentage">{l s='Percentage in numbers only' mod='fyndiqmerchant'}</label>
                        <input type="number" name="price_percentage" id="fm-price_percentage"
                                {if $price_percentage}
                                    value="{$price_percentage}"
                                {/if}
                                >
                    </div>

                    <button class="btn btn-green" type="submit" name="submit_save_settings">{l s='Save Settings' mod='fyndiqmerchant'}</button>
                </form>
                <div class="text-right">
                    <a href="{$path}&disconnect=1" class="btn btn-red">{l s='Disconnect Account' mod='fyndiqmerchant'}</a>
                </div>
            </div>
        </div>
    </div>
</div>
