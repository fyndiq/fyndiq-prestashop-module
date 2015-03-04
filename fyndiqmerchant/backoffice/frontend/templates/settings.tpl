<style type="text/css">
    {fetch file="$server_path/backoffice/frontend/css/main.css"}
</style>
{include file='./menu.tpl' current='settings'}
<div class="fm-container">
    <div class="fm-main-panel">
        <div class="fm-subheader center">Settings</div>
        <div class="content">
            <form action="" method="post">
                <fieldset>
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

                    <div class="form-group">
                        <label for="fm-currency-choice"><b>Currency</b></label>
                        <select name="currency_id" id="fm-currency-choice">
                            {foreach $currencies as $currency}
                                <option
                                    value="{$currency.id_currency}"
                                    {if $currency.id_currency == $selected_currency}
                                        selected="selected"
                                    {/if}
                                    >{$currency.name}</option>
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

                    <button class="fm-button green" type="submit" name="submit_save_settings">Save Settings</button>
                </fieldset>
            </form>
        </div>
    </div>
</div>
