
{include './css.tpl'}

<div id="fm-container">

    <img id="fm-logo" src="{$module_path}backoffice/templates/images/logo.png" alt="Fyndiq logotype">

    <div class="fm-api-unavailable">
    {if $exception_type == 'FyndiqAPITooManyRequests'}
        <p>
            You have sent too many requests to the Fyndiq API. Please calm down!
        </p>
    {else}
        <p>
            Unfortunately, Fyndiq API is currently not available.
        </p>
        <p>
            If this problem persists, please contact integration support, and attach the error message shown below.
        </p>
        <p>
            {$exception_type}{if $error_message}: {$error_message}{/if}
        </p>
    {/if}
    </div>
</div>
