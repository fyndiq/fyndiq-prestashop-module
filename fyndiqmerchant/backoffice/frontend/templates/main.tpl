<link href='http://fonts.googleapis.com/css?family=Montserrat:400,700' rel='stylesheet' type='text/css'>
<style type="text/css">
    {fetch file="$server_path/backoffice/frontend/css/main.css"}
</style>
{include './js_templates.tpl'}

<script type="text/javascript">
    var module_path = '{$module_path}';
    var messages = {};
    {foreach $messages as $k => $v}
        messages['{$k}'] = '{$v}';
    {/foreach}
</script>
<script type="text/javascript" src="{$module_path}backoffice/frontend/js/handlebars-v1.1.2.js"></script>
<script type="text/javascript" src="{$module_path}backoffice/frontend/js/FmGui.js"></script>
<script type="text/javascript" src="{$module_path}backoffice/frontend/js/FmCtrl.js"></script>
<script type="text/javascript" src="{$module_path}backoffice/frontend/js/main.js"></script>

<div class="fm-container">

    {include file='./header.tpl' current='settings'}

    <div class="fm-content-wrapper">
        <div class="fm-left-sidebar">
            <div class="fm-panel">
                <div class="fm-panel-header">Categories</div>
                <div class="fm-panel-body fm-category-tree-container no-padding"></div>
            </div>
        </div>

        <div class="fm-product-panel">
            <div class="fm-panel">
                <div class="fm-panel-header">
                    Products: <span id="categoryname"></span>
                    <div class="legend"><i class="icon on"></i> On Fyndiq  <i class="icon pending"></i> Pending <i class="icon noton"></i> Not On Fyndiq </div>
                </div>
                <div class="fm-panel-body no-padding">
                    <form action="" method="post" class="fm-form products">
                        <p class="info">By using this form, you can export products from the local webshop into Fyndiq.</p>

                        <div class="fm-product-list-container"></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
