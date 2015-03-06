<link href='http://fonts.googleapis.com/css?family=Montserrat:400,700' rel='stylesheet' type='text/css'>
<style type="text/css">
    {fetch file="$server_path/backoffice/frontend/css/main.css"}
</style>
<div class="fm-container">

    {include file='./header.tpl' current='settings'}

    <div class="fm-content-wrapper">
        <div class="fm-panel">
            <div class="fm-panel-header text-center">Settings</div>
            <div class="fm-panel-body text-center">
                <form action="" method="post" class="form-horizontal">
                    <p>
                        In order to use this module, you have to select which language and currency you will be using.<br>
                        The language and currency you select will be used when exporting products to Fyndiq.<br>
                        Make sure you select a language that contains Swedish product info, and a currency that contains Swedish Krona (SEK)!<br>
                    </p>
                    <h2>Localization</h2>

                    <div class="form-group">
                        <label for="fm-language-choice">Language</label>

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

                    <h2>System</h2>
                    <b>Percentage of price</b>
                    <p>This percentage is the percentage of the price that will be cut off your price, if 10% percentage it will be 27 SEK of 30 SEK (10% of 30 SEK is 3 SEK).</p>
                    <div class="form-group">
                        <label for="fm-price_percentage">Percentage in numbers only</label>
                        <input type="text" name="price_percentage" id="fm-price_percentage"
                                {if $price_percentage}
                                    value="{$price_percentage}"
                                {/if}
                                >

                    </div>

                    <button class="btn btn-green" type="submit" name="submit_save_settings">Save Settings</button>
                </form>
                <div class="text-right">
                    <a href="{$path}&disconnect=1" class="btn btn-red">{l s='Disconnect Account' mod='fyndiqmerchant'}</a>
                </div>
            </div>
        </div>
    </div>
</div>

