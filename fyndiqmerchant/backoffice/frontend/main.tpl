
{include './js_templates.tpl'}
{include './js.tpl'}
{include './css.tpl'}

<div id="fm-container">
    <div id="fm-message-boxes"></div>

    <div class="fm-loading-overlay">
        <img src="{$module_path}backoffice/frontend/images/ajax-loader.gif" alt="Loading animation">
    </div>

    <img id="fm-logo" src="{$module_path}backoffice/frontend/images/logo.png" alt="Fyndiq logotype">

    <div id="fm-main-panel">
        {include './products.tpl'}
    </div>

    <div id="fm-sidebar">

        {* Import orders form *}
        <form action="" method="post" class="fm-form orders">
            <fieldset>
                <legend>Orders</legend>
                <p>By clicking this button, you can import all orders from Fyndiq into the local webshop.</p>
                <input class="submit important-action" type="submit" value="Import orders">
            </fieldset>
        </form>

        {* Choose different language form *}
        <form action="" method="post" class="fm-form language">
            <fieldset>
                <legend>Language</legend>
                <p>Current language: <b>{$language->name}</b>.</p>
                <p>By clicking this button, you can switch to a different language to use when exporting products.</p>
                <input class="submit" type="submit" name="submit_switch_language" value="Switch language"
                    onclick="return confirm('{FmMessages::get('switch-language-confirm')}');">
            </fieldset>
        </form>

        {* Disconnect account form *}
        <form action="" method="post" class="fm-form disconnect">
            <fieldset>
                <legend>Account</legend>
                <p>Current user: <b>{$username}</b>.</p>
                <p>By clicking this button, you can disconnect from your Fyndiq merchant account.</p>
                <input class="submit" type="submit" name="submit_disconnect" value="Disconnect account"
                    onclick="return confirm('{FmMessages::get('disconnect-confirm')}');">
            </fieldset>
        </form>
    </div>
</div>
