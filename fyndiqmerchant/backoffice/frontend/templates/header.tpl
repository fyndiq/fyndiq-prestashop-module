<style type="text/css">
    a i.menu-icon.comp {
        background-image: url('{$module_path}/backoffice/frontend/images/icons/comp_red.png');
    }
    a.active i.menu-icon.comp,a:hover i.menu-icon.comp {
        background-image: url('{$module_path}/backoffice/frontend/images/icons/comp_white.png');
    }
    a i.menu-icon.boxes {
        background-image: url('{$module_path}/backoffice/frontend/images/icons/box_red.png');
    }
    a.active i.menu-icon.boxes,a:hover i.menu-icon.boxes {
        background-image: url('{$module_path}/backoffice/frontend/images/icons/box_white.png');
    }
    a i.menu-icon.cog {
        background-image: url('{$module_path}/backoffice/frontend/images/icons/cog_red.png');
    }
    a.active i.menu-icon.cog,a:hover i.menu-icon.cog {
        background-position: 0px -1px;
        background-image: url('{$module_path}/backoffice/frontend/images/icons/cog_white.png');
    }
</style>
<div class="fm-header">
    <div class="fm-header-wrapper">
        <img class="navbar-brand" src="{$module_path}/backoffice/frontend/images/logo.png" alt="Fyndiq logotype">
        {if $buttons}
        <div class="navbar-right">
            <a href="{$path}" {if $current == "main"}class="btn btn-nav active"{else}class="btn btn-nav"{/if}><i class="menu-icon comp"></i> {l s='Export Products' mod='fyndiqmerchant'}</a>
            <a href="{$path}&order=1" {if $current == "order"}class="btn btn-nav active"{else}class="btn btn-nav"{/if}><i class="menu-icon boxes"></i> {l s='Imported Orders' mod='fyndiqmerchant'}</a>
            <a href="{$path}&submit_show_settings=1" {if $current == "settings"}class="btn btn-nav active"{else}class="btn btn-nav"{/if}><i class="menu-icon cog"></i> {l s='Settings' mod='fyndiqmerchant'}</a>
        </div>
        {/if}
    </div>
</div>