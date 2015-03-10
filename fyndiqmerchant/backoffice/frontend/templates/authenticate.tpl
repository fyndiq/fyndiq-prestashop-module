<link href='http://fonts.googleapis.com/css?family=Montserrat:400,700' rel='stylesheet' type='text/css'>
<style type="text/css">
    {fetch file="$server_path/backoffice/frontend/css/main.css"}
</style>
<div class="fm-container">

    {include file='./header.tpl' buttons=false}

    <div class="fm-content-wrapper">

        <div class="fm-panel">
            <div class="fm-panel-header text-center">{l s='Authentication' mod='fyndiqmerchant'}</div>
            <div class="fm-panel-body text-center">
                <form action="" method="post" class="form-horizontal fm-form authenticate">
                    <fieldset>
                        <div class="form-group">
                            <label for="fm-auth-username">{l s='Username' mod='fyndiqmerchant'}</label>
                            <input type="text" name="username" id="fm-auth-username">
                        </div>

                        <div class="form-group">
                            <label for="fm-auth-api-token">{l s='API Token' mod='fyndiqmerchant'}</label>
                            <input type="text" name="api_token" id="fm-auth-api-token">
                        </div>
                        <p>
                            {l s='By authenticating you will create a permanent connection to your Fyndiq merchant account.' mod='fyndiqmerchant'}<br>
                            {l s='You will not have to authenticate again when coming here next time.' mod='fyndiqmerchant'}<br>
                        </p>

                        <button class="fm-button fyndiq green" type="submit" name="submit_authenticate">{l s='Authenticate' mod='fyndiqmerchant'}</button>
                    </fieldset>
                </form>
            </div>
        </div>
    </div>
</div>
