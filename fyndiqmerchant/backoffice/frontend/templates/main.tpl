
<style type="text/css">
    {fetch file="$server_path/backoffice/frontend/css/main.css"}
</style>

{include './js_templates.tpl'}

<script type="text/javascript" src="{$module_path}backoffice/frontend/js/handlebars-v1.1.2.js"></script>

<script type="text/javascript">
    var module_path = '{$module_path}';
    var messages = {};
    {foreach $messages as $k => $v}
        messages['{$k}'] = '{$v}';
    {/foreach}
</script>

<script type="text/javascript" src="{$module_path}backoffice/frontend/js/FmGui.js"></script>
<script type="text/javascript" src="{$module_path}backoffice/frontend/js/FmCtrl.js"></script>
<script type="text/javascript" src="{$module_path}backoffice/frontend/js/main.js"></script>


<div id="fm-container">

    <img class="fyndiqlogo" src="{$module_path}backoffice/frontend/images/logo.png" alt="Fyndiq logotype">

    <div id="fm-main-panel">

        {* Product list form *}
        <form action="" method="post" class="fm-form products">
            <fieldset>
                <legend>Products</legend>
                <p>By using this form, you can export products from the local webshop into Fyndiq.</p>

                <div class="fm-category-tree-container"></div>
                <div class="fm-product-list-container"></div>
            </fieldset>
        </form>
    </div>

    <div id="fm-sidebar">

        {* Import orders form *}
        <form action="" method="post" class="fm-form orders">
            <fieldset>
                <legend>Orders</legend>
                <p>By clicking this button, you can import all orders from Fyndiq into the local webshop.</p>
                <button class="fm-button fyndiq" name="import-orders">Import Orders</button>
            </fieldset>
        </form>

        {* Choose different settings form *}
        <form action="" method="post" class="fm-form settings">
            <fieldset>
                <legend>Settings</legend>
                <p>Language: <b>{$language->name}</b>.<br></p>

                <button class="fm-button" type="submit" name="submit_show_settings">Change Settings</button>
            </fieldset>
        </form>

        {* Disconnect account form *}
        <form action="" method="post" class="fm-form disconnect">
            <fieldset>
                <legend>Account</legend>
                <p>Current user: <b>{$username}</b>.</p>
                <p>By clicking this button, you can disconnect from your Fyndiq merchant account.</p>
                <button class="fm-button" type="submit" name="submit_disconnect"
                    onclick="return confirm('{FmMessages::get('disconnect-confirm')}');"
                >Disconnect Account</button>
            </fieldset>
        </form>
    </div>
</div>
