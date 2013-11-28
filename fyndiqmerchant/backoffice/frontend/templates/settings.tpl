
<style type="text/css">
    {fetch file="$server_path/backoffice/frontend/css/main.css"}
</style>

<div id="fm-container">

    <img class="fyndiqlogo" src="{$module_path}backoffice/frontend/images/logo.png" alt="Fyndiq logotype">

    <form action="" method="post" class="fm-form choose-language">
        <fieldset>
            <legend>Choose language</legend>

            <p>
                In order to use this module, you have to select which language and currency you will be using.<br>
                The language and currency you select will be used when exporting products to Fyndiq.<br>
                Make sure you select a language that contains Swedish product info, and a currency that contains Swedish Krona (SEK)!<br>
            </p>

            <div>
                <label for="fm-language-choice">Language</label>
            </div>
            <p>
                <select name="language_id" id="fm-language-choice">
                    {foreach $languages as $language}
                        <option
                            value="{$language.id_lang}"
                            {if $language.id_lang == $selected_language or $language.id_lang == $default_language}
                                selected="selected"
                            {/if}
                            >{$language.name}</option>
                    {/foreach}
                </select>
            </p>

            <div>
                <label for="fm-currency-choice">Currency</label>
            </div>
            <p>
                <select name="currency_id" id="fm-currency-choice">
                    {foreach $currencies as $currency}
                        <option
                            value="{$currency.id_currency}"
                            {if $currency.id_currency == $selected_currency or $currency.id_currency == $default_currency->id}
                                selected="selected"
                            {/if}
                            >{$currency.name}</option>
                    {/foreach}
                </select>
            </p>

            <button class="fm-button" type="submit" name="submit_save_settings">Save Settings</button>
        </fieldset>
    </form>
</div>
