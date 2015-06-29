<link href='http://fonts.googleapis.com/css?family=Montserrat:400,700' rel='stylesheet' type='text/css'>
<style type="text/css">
    {fetch file="$server_path/backoffice/frontend/css/main.css"}
</style>

<div class="fm-container {$version}">
    {include file='./header.tpl' buttons=false}
    <div class="fm-content-wrapper">
        <div class="fm-panel">
            <div class="fm-panel-header text-center">{fi18n s='Error'}</div>
            <div class="fm-panel-body text-center">
                <p>
                    {fi18n s='Unfortunately, Fyndiq API is currently not available.'}
                </p>
                <p>
                    {fi18n s='If this problem persists, please contact integration support, and attach the error message shown below.'}
                </p>
                <p>
                    {fi18n s='Error message'}: <strong>{$message}</strong>
                </p>
            </div>
        </div>
    </div>
</div>
