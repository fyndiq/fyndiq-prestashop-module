
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

{include file='./menu.tpl' current='order'}

<div class="fm-container">

    <div class="fm-orderlist-panel">
        <div class="fm-subheader">Imported Orders</div>

        <form action="" method="post" class="fm-form orders-form">

            <div class="fm-order-list-container"></div>
        </form>
    </div>

    <div class="fm-sidebar">
        <div class="fm-subheader">Manual Order Import</div>
        <div class="content">
            <p>By clicking this button, you can import all orders from Fyndiq into the local webshop.</p>
                <div class="lastupdated">
                    <img src="{$module_path}/backoffice/frontend/images/icons/refresh.png" />
                    <span class="last-header">Latest Import</span>
                    Today 14:20:12
                </div>
            <a class="fm-button green block" id="fm-import-orders">Import Orders</a>
        </div>
    </div>
</div>