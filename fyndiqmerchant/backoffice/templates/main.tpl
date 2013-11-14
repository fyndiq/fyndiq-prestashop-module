
{include './js_templates.tpl'}
{include './js.tpl'}
{include './css.tpl'}

<div id="fm-container">
    <div id="fm-message-boxes"></div>

    <div class="fm-loading-overlay">
        <img src="{$module_path}backoffice/templates/images/ajax-loader.gif" alt="Loading animation">
    </div>

    <img id="fm-logo" src="{$module_path}backoffice/templates/images/logo.png" alt="Fyndiq logotype">

    <div id="fm-main-panel">
        {include './products.tpl'}
    </div>

    <div id="fm-sidebar">

        {* Import orders form *}
        <form action="" method="post" class="fm-form orders">
            <fieldset>
                <legend>Orders</legend>
                <p>By clicking this button, you can import all orders from Fyndiq into the local webshop.</p>
                <input class="submit" type="submit" value="Import orders">
            </fieldset>
        </form>

        {* Disconnect account form *}
        <form action="" method="post" class="fm-form disconnect">
            <fieldset>
                Currently authenticated as: <b>{$username}</b>.
                <legend>Account</legend>
                <p>By clicking this button, you can disconnect from your Fyndiq merchant account.</p>
                <input class="submit" type="submit" name="submit_disconnect" value="Disconnect account"
                    onclick="return confirm('{FmMessages::get('disconnect-confirm')}');">
            </fieldset>
        </form>
    </div>
</div>
