
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
                <label>PrestaShop Language</label>
            </div>
            <div>
                <select name="language_id">
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
            </div>

            <p>
                <input class="submit" type="submit" name="submit_language" value="Save">
            </p>

        </fieldset>
    </form>
</div>
