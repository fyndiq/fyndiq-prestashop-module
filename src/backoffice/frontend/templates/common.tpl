<link href='http://fonts.googleapis.com/css?family=Montserrat:400,700' rel='stylesheet' type='text/css'>
<style type="text/css">
    {fetch file="$server_path/backoffice/frontend/css/main.css"}
</style>
<script type="text/javascript">
    var FmPaths = {
        module: '{$module_path}',
        shared: '{$shared_path}',
        service: '{$service_path}'
    };
    var messages = {$json_messages};
</script>
{include './js_templates.tpl'}
<script type="text/javascript" src="{$shared_path}frontend/js/handlebars-v1.1.2.js"></script>
<script type="text/javascript" src="{$shared_path}frontend/js/FmGui.js"></script>
<script type="text/javascript" src="{$shared_path}frontend/js/FmCtrl.js"></script>
<script type="text/javascript" src="{$shared_path}frontend/js/FmUpdate.js"></script>
