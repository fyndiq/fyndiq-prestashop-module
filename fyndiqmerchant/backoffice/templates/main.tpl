
{include './css.tpl'}
{include './js.tpl'}

<div id="fm-container">
    <div class="fm-loading-overlay" style="display: none;">
        <img src="{$path}backoffice/templates/ajax-loader.gif" alt="Loading animation">
    </div>

    <img id="fm-logo" src="{$path}backoffice/templates/fyndiq_logo_100323.png" alt="Fyndiq logotype">

    <div id="fm-products">
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
                    onclick="return confirm('Are you sure you want to disconnect from your Fyndiq merchant account?');"
                >
            </fieldset>
        </form>
    </div>
</div>
