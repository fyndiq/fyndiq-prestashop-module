<div class="fm-menu">
    <ul>
        <li><a href="{$path}"{if $current == "main"} class="active"{/if}>Export products</a></li>
        <li><a href="{$path}&exported_products"{if $current == "exported_products"} class="active"{/if}>Exported products</a></li>
        <li><a href="{$path}&imported_orders"{if $current == "imported_orders"} class="active"{/if}>Imported Orders</a></li>
        <li><a href="{$path}&submit_show_settings=1"{if $current == "settings"} class="active"{/if}>Settings</a></li>
    </ul>
    <ul class="right">
        <li><a href="#" onclick="return confirm('{FmMessages::get('disconnect-confirm')}">Disconnect Account</a></li>
    </ul>
</div>