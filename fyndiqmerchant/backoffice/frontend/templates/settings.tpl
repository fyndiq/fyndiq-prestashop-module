
<style type="text/css">
    {fetch file="$server_path/backoffice/frontend/css/main.css"}
</style>

<div class="fm-container">

    <img class="fyndiqlogo" src="{$module_path}backoffice/frontend/images/logo.png" alt="Fyndiq logotype">

    <form action="" method="post" class="fm-form choose-language">
        <fieldset>
            <legend>Choose language</legend>

            <p>
                In order to use this module, you have to select which language and currency you will be using.<br>
                The language and currency you select will be used when exporting products to Fyndiq.<br>
                Make sure you select a language that contains Swedish product info, and a currency that contains Swedish Krona (SEK)!<br>
            </p>

            <h2>Localization</h2>

            <label for="fm-language-choice"><b>Language</b></label>
            <p>
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
            </p>

            <label for="fm-currency-choice"><b>Currency</b></label>
            <p>
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
            </p>

            <h1>System</h1>
            <b>Automatic Order Import</b>
            <p>
                <input type="checkbox" name="auto_import" id="fm-auto-import"
                    {if $auto_import}
                        checked="checked"
                    {/if}
                >
                <label for="fm-auto-import">Enable</label>
            </p>
            <b>Automatic Quantity Export</b>
            <p>
                <input type="checkbox" name="auto_export" id="fm-auto-export"
                    {if $auto_export}
                        checked="checked"
                    {/if}
                >
                <label for="fm-auto-export">Enable</label>
            </p>
            <b>Precentage of price</b>
            <p>This precentage is the precentage of the price that will be cut off your price, if 10% precentage it will be 27 SEK of 30 SEK (10% of 30 SEK is 3 SEK).</p>
            <p>
                <input type="text" name="precentage" id="fm-auto-export"
                        {if $precentage}
                            value="{$precentage}"
                        {/if}
                        >
                <label for="fm-auto-export">Precentage in numbers only</label>
            </p>

            <button class="fm-button" type="submit" name="submit_save_settings">Save Settings</button>
        </fieldset>
    </form>
</div>
