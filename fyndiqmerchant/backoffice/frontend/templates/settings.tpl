
<style type="text/css">
    {fetch file="$server_path/backoffice/frontend/css/main.css"}
</style>

<div id="fm-container">

    <img class="fyndiqlogo" src="{$module_path}backoffice/frontend/images/logo.png" alt="Fyndiq logotype">

    <h2>Almost there!</h2>

    <form action="" method="post" class="fm-form choose-language">
        <fieldset>
            <legend>Choose language</legend>

            <p>
                In order to use this module, you have to select which language you will be using.<br>
                The language you select will be used when exporting products to Fyndiq.<br>
                Make sure you select a language that contains Swedish data only!<br>
            </p>

            <div>
                <label for="fm-language-choice">Language</label>
            </div>
            <p>
                <select name="language_id" id="fm-language-choice">
                    <option
                        {if !$selected_language}
                            selected="selected"
                        {/if}
                        >----</option>
                    {foreach $languages as $language}
                        <option
                            value="{$language.id_lang}"
                            {if $selected_language == $language.id_lang}
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
                    <option
                        {if !$selected_currency}
                            selected="selected"
                        {/if}
                        >----</option>
                    {foreach $currencies as $currency}
                        <option
                            value="{$currency.id_currency}"
                            {if $selected_currency == $currency.id_currency}
                                selected="selected"
                            {/if}
                            >{$currency.name}</option>
                    {/foreach}
                </select>
            </p>

            <button class="fm-button" type="submit" name="submit_save_settings">Save</button>
        </fieldset>
    </form>
</div>
