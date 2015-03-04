<link href='http://fonts.googleapis.com/css?family=Montserrat:400,700' rel='stylesheet' type='text/css'>
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


{include file='./menu.tpl' current='main'}

<div class="fm-container">

    <div class="fm-left-sidebar">
        <div class="fm-subheader">Categories</div>
        <div class="fm-category-tree-container"></div>
    </div>

    <div class="fm-product-panel">
        <div class="fm-subheader" style="margin-bottom: 5px;">
            Products: <span id="categoryname"></span>
            <div class="right"><i class="icon on"></i> On Fyndiq  <i class="icon pending"></i> Pending <i class="icon noton"></i> Not On Fyndiq </div>
        </div>
        <form action="" method="post" class="fm-form products">
            <p class="info">By using this form, you can export products from the local webshop into Fyndiq.</p>


            <div class="fm-product-list-container"></div>
        </form>
    </div>
</div>
