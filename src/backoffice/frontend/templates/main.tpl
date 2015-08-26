{include './common.tpl'}
<script type="text/javascript" src="{$shared_path}frontend/js/main.js"></script>

<div class="fm-container {$version}">

    {include file='./header.tpl' current='main' buttons=true}

    <div class="fm-content-wrapper">
        <div class="fm-update-message-container"></div>
        <div class="fm-left-sidebar">
            <div class="fm-panel">
                <div class="fm-panel-header">{fi18n s="Categories"}</div>
                <div class="fm-panel-body no-padding">
                    <ul class="fm-category-tree">
                        <li data-category_id="-1">
                            <a href="#" title="{fi18n s="All products"}">{fi18n s="All products"}</a>
                        </li>
                    </ul>
                    <div class="fm-category-tree-container"></div>
                </div>
            </div>
        </div>

        <div class="fm-product-panel">
            <div class="fm-panel">
                <div class="fm-panel-header">
                    {fi18n s='Products'}: <span id="categoryname"></span>
                    <div class="legend"><i class="icon on"></i> {fi18n s='On Fyndiq'}  <i class="icon pending"></i> {fi18n s='Pending'} <i class="icon noton"></i> {fi18n s='Not On Fyndiq'} </div>
                </div>
                <div class="fm-panel-body no-padding">
                    <form action="" method="post" class="fm-form products">
                        <p class="info">{fi18n s='By using this form, you can export products from the local webshop into Fyndiq.'}</p>
                        <div class="fm-product-list-container"></div>
                    </form>
                </div>
            </div>
        </div>
        <br class="clear" />
        <div class="fm-content-wrapper fm-footer muted text-right">
            <img class="fm-update-check" style="display:none" src="{$shared_path}frontend/images/update-loader.gif" />
            {$module_version}
        </div>
    </div>
    <br class="clear" />
</div>
