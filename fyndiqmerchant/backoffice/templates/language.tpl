
{include './css.tpl'}

<div id="fm-container">

    <img id="fm-logo" src="{$module_path}backoffice/templates/images/logo.png" alt="Fyndiq logotype">

    <h2>Almost there!</h2>

    <form action="" method="post" class="fm-form choose-language">
        <fieldset>
            <legend>Choose language</legend>

            <div>
                <label>Username</label>
            </div>
            <div>
                <input type="text" name="username">
            </div>

            <p>
                By authenticating you will create a permanent connection to your Fyndiq merchant account.<br>
                You will not have to authenticate again when coming here next time.
            </p>

            <input class="submit" type="submit" name="submit_authenticate" value="Authenticate">
        </fieldset>
    </form>
</div>
