
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


<div class="fm-container">

    <img class="fyndiqlogo" src="{$module_path}backoffice/frontend/images/logo.png" alt="Fyndiq logotype">

    <div class="fm-menu">
        <ul>
            <li><a href="#" class="active">Export products</a></li>
            <li><a href="#">Exported products</a></li>
            <li><a href="#">Imported Orders</a></li>
            <li><a href="#">Settings</a></li>
        </ul>
        <ul class="right">
            <li><a href="#" onclick="return confirm('{FmMessages::get('disconnect-confirm')}">Disconnect Account</a></li>
        </ul>
    </div>

    <div class="fm-sidebar">
        <div class="fm-category-tree-container"></div>
    </div>

    <div class="fm-main-panel">

        {* Product list form *}
        <form action="" method="post" class="fm-form products">
            <fieldset>
                <legend>Products</legend>
                <p>By using this form, you can export products from the local webshop into Fyndiq.</p>

                <div class="fm-product-list-container"></div>
            </fieldset>
        </form>
    </div>
</div>
