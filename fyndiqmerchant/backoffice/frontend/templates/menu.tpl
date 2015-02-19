<div class="fm-menu">
    <ul>
        <li><a href="{$path}"{if $current == "main"} class="active"{/if}>Export products</a></li>
        <li><a href="{$path}&order=1"{if $current == "order"} class="active"{/if}>Imported Orders</a></li>
        <li><a href="{$path}&submit_show_settings=1"{if $current == "settings"} class="active"{/if}>Settings</a></li>
    </ul>
    <ul class="right">
        <li><a href="{$path}&disconnect=1" onclick="return confirm('{FmMessages::get('disconnect-confirm')}');">Disconnect Account</a></li>
    </ul>
</div>