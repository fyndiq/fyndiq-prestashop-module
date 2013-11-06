
{include './css.tpl'}
{include './js.tpl'}

<div id="fm-container">
    <div class="fm-loading-overlay" style="display: none;">
        <img src="{$path}backoffice/templates/ajax-loader.gif" alt="Loading animation">
    </div>

    {include './orders.tpl'}
    {include './products.tpl'}
    {include './disconnect.tpl'}
</div>
