<style type="text/css">
    a i.menu-icon.comp {
        background-image: url('{$shared_path}/frontend/images/icons/comp_red.png');
    }
    a.active i.menu-icon.comp,a:hover i.menu-icon.comp {
        background-image: url('{$shared_path}/frontend/images/icons/comp_white.png');
    }
    a i.menu-icon.boxes {
        background-image: url('{$shared_path}/frontend/images/icons/box_red.png');
    }
    a.active i.menu-icon.boxes,a:hover i.menu-icon.boxes {
        background-image: url('{$shared_path}/frontend/images/icons/box_white.png');
    }
    a i.menu-icon.cog {
        background-image: url('{$shared_path}/frontend/images/icons/cog_red.png');
    }
    a.active i.menu-icon.cog,a:hover i.menu-icon.cog {
        background-image: url('{$shared_path}/frontend/images/icons/cog_white.png');
        background-position: 0px -1px;
    }
</style>
<div class="fm-header">
    <div class="fm-header-wrapper">
        <img class="navbar-brand" src="{$shared_path}/frontend/images/logo.png" alt="Fyndiq logotype">
        {if $buttons}
        <div class="navbar-right">
            <a href="{$path}" {if $current == "main"}class="btn btn-nav active"{else}class="btn btn-nav"{/if}><i class="menu-icon comp"></i> {fi18n s='Export Products'}</a>
            <a href="{$path}&action=orders" {if $current == "order"}class="btn btn-nav active"{else}class="btn btn-nav"{/if}><i class="menu-icon boxes"></i> {fi18n s='Imported Orders'}</a>
            <a href="{$path}&action=settings" {if $current == "settings"}class="btn btn-nav active"{else}class="btn btn-nav"{/if}><i class="menu-icon cog"></i> {fi18n s='Settings'}</a>
        </div>
        {/if}
    </div>
</div>
