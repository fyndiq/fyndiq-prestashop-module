
{include './css.tpl'}

<div id="fm-container">

    <img id="fm-logo" src="{$module_path}backoffice/templates/logo.png" alt="Fyndiq logotype">

    <form action="" method="post" class="fm-form authenticate">
        <fieldset>
            <legend>Authentication</legend>

            <div>
                <label>Username</label>
            </div>
            <div>
                <input type="text" name="username">
            </div>

            <div>
                <label>API Token</label>
            </div>
            <div>
                <input type="text" name="api_token">
            </div>

            <p>
                By authenticating you will create a permanent connection to your Fyndiq merchant account.<br>
                You will not have to authenticate again when coming here next time.
            </p>

            <input class="submit" type="submit" name="submit_authenticate" value="Authenticate">
        </fieldset>
    </form>
</div>
