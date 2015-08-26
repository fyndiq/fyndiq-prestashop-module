{include './common.tpl'}

<div class="fm-container {$version}">
    {include file='./header.tpl' buttons=false}
    <div class="fm-content-wrapper">
        <div class="fm-update-message-container"></div>
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
    <div class="fm-content-wrapper fm-footer muted text-right">
        <img class="fm-update-check" style="display:none" src="{$shared_path}frontend/images/update-loader.gif" />
        {$module_version}
    </div>
</div>
