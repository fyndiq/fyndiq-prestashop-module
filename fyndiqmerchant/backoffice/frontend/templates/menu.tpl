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
<div class="fm-header-background">
    <div class="fm-header-container">
        <div class="fm-header">
            <img class="fyndiqlogo" src="{$module_path}/backoffice/frontend/images/logo.png" alt="Fyndiq logotype">
            <div class="right">
                <a href="{$path}"{if $current == "main"} class="active"{/if}><i class="menu-icon comp"></i> Export Products</a>
                <a href="{$path}&order=1"{if $current == "order"} class="active"{/if}><i class="menu-icon boxes"></i> Imported Orders</a>
                <a href="{$path}&submit_show_settings=1"{if $current == "settings"} class="active"{/if}><i class="menu-icon cog"></i> Settings</a>
            </div>
        </div>
    </div>
</div>