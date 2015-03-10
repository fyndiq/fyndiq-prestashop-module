<link href='http://fonts.googleapis.com/css?family=Montserrat:400,700' rel='stylesheet' type='text/css'>
<style type="text/css">
    {fetch file="$server_path/backoffice/frontend/css/main.css"}
</style>

<div class="fm-container">

    {include file='./header.tpl' buttons=false}

    <div class="fm-content-wrapper">

        <div class="fm-panel">
            <div class="fm-panel-body text-center">
                <p>
                    {l s='Unfortunately, Fyndiq API is currently not available.' mod='fyndiqmerchant'}
                </p>
                <p>
                    {l s='If this problem persists, please contact integration support, and attach the error message shown below.' mod='fyndiqmerchant'}
                </p>
                <p>
                    {l s='Error message' mod='fyndiqmerchant'}: <strong>{$message}</strong>
                </p>
            </div>
        </div>
    </div>
</div>
