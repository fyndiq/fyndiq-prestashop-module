
<style type="text/css">
    {fetch file="$server_path/backoffice/frontend/css/main.css"}
</style>

<div class="fm-container">

    <img class="fyndiqlogo" src="{$module_path}backoffice/frontend/images/logo.png" alt="Fyndiq logotype">

    <form action="" method="post" class="fm-form authenticate">
        <fieldset>
            <legend>Authentication</legend>

            <div>
                <label for="fm-auth-username">Username</label>
            </div>
            <div>
                <input type="text" name="username" id="fm-auth-username">
            </div>

            <div>
                <label for="fm-auth-api-token">API Token</label>
            </div>
            <div>
                <input type="text" name="api_token" id="fm-auth-api-token">
            </div>

            <p>
                By authenticating you will create a permanent connection to your Fyndiq merchant account.<br>
                You will not have to authenticate again when coming here next time.
            </p>

            <button class="fm-button fyndiq" type="submit" name="submit_authenticate">Authenticate</button>
        </fieldset>
    </form>
</div>
