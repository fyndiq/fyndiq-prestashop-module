<link href='http://fonts.googleapis.com/css?family=Montserrat:400,700' rel='stylesheet' type='text/css'>
<style type="text/css">
    {fetch file="$server_path/backoffice/frontend/css/main.css"}
</style>
{include './js_templates.tpl'}

<script type="text/javascript">
    var FmPaths = {
        module: '{$module_path}',
        shared: '{$shared_path}',
        service: '{$service_path}'
    };
    var messages = {$json_messages};
</script>
<script type="text/javascript" src="{$shared_path}frontend/js/handlebars-v1.1.2.js"></script>
<script type="text/javascript" src="{$shared_path}frontend/js/FmGui.js"></script>
<script type="text/javascript" src="{$shared_path}frontend/js/FmCtrl.js"></script>
<script type="text/javascript" src="{$shared_path}frontend/js/order.js"></script>

<div class="fm-container">
    {include file='./header.tpl' current='order' buttons=true}

    <div class="fm-content-wrapper">
        <div class="fm-orderlist-panel">
            <div class="fm-panel">
                <div class="fm-panel-header">Imported Orders</div>
                <div class="fm-panel-body no-padding">
                    <form action="{$service_path}" method="post" class="fm-form orders-form">
                        <input type="hidden" name="action" value="get_delivery_notes" />
                        <input type="hidden" name="isService" value="1" />
                        <div class="fm-order-list-container"></div>
                    </form>
                </div>
            </div>
        </div>

        <div class="fm-sidebar">
            <div class="fm-panel">
                <div class="fm-panel-header">Manual Order Import</div>
                <div class="fm-panel-body">
                    <p>By clicking this button, you can import all orders from Fyndiq into the local webshop.</p>
                    <div id="fm-order-import-date">
                        {if $import_date}
                        <div class="lastupdated">
                            <img src="{$shared_path}frontend/images/icons/refresh.png" />
                            <span class="last-header">Latest Import</span>
                            {if $isToday}
                                Today {$import_time}
                            {else}
                                {$import_date}
                            {/if}
                        </div>
                        {/if}
                    </div>
                    <a class="fm-button green" id="fm-import-orders">Import Orders</a>
                </div>
            </div>
        </div>
    </div>
    <br class="clear" />
</div>
