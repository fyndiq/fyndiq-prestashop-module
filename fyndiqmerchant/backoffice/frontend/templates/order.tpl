
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
<script type="text/javascript" src="{$module_path}backoffice/frontend/js/order.js"></script>


<div class="fm-container">

    <img class="fyndiqlogo" src="{$module_path}backoffice/frontend/images/logo.png" alt="Fyndiq logotype">

    {include file='./menu.tpl' current='order'}

    <div class="fm-sidebar">
        <h3>Import Order</h3>

        <form action="" method="post" class="fm-form orders">
            <fieldset>
                <p>By clicking this button, you can import all orders from Fyndiq into the local webshop.</p>
                <button class="fm-button fyndiq" id="fm-import-orders">Import Orders</button>
            </fieldset>
        </form>
    </div>

    <div class="fm-main-panel">

        {* Product list form *}
        <form action="" method="post" class="fm-form products">
            <fieldset>
                <legend>Orders</legend>

                <div class="fm-order-list-container"></div>
            </fieldset>
        </form>
    </div>
</div>
