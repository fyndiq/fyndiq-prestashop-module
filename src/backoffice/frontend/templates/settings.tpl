<link href='http://fonts.googleapis.com/css?family=Montserrat:400,700' rel='stylesheet' type='text/css'>
<style type="text/css">
    {fetch file="$server_path/backoffice/frontend/css/main.css"}
</style>
<div class="fm-container {$version}">

    {include file='./header.tpl' current='settings' buttons=true}

    <div class="fm-content-wrapper">
        <div class="fm-panel">
            <div class="fm-panel-header text-center">{fi18n s='Settings'}</div>
            <div class="fm-panel-body text-center">
                <form action="" method="post" class="form-horizontal">
                    <p>
                        {fi18n s='In order to use this module, you have to select which language you will be using'}.<br>
                        {fi18n s='The language, you select, will be used when exporting products to Fyndiq'}.<br>
                        {fi18n s='Make sure you select a language that contains Swedish product info!'}<br>
                    </p>
                    <h2>{fi18n s='Localization'}</h2>

                    <div class="form-group">
                        <label for="fm-language-choice">{fi18n s='Language'}</label>

                        <select name="language_id" id="fm-language-choice">
                            {foreach $languages as $language}
                                <option
                                    value="{$language.id_lang}"
                                    {if $language.id_lang == $selected_language}
                                        selected="selected"
                                    {/if}
                                    >{$language.name}</option>
                            {/foreach}
                        </select>
                    </div>

                    <h2>{fi18n s='System'}</h2>
                    <b>{fi18n s='Percentage of price'}</b>
                    <p>{fi18n s='This percentage is the percentage of the price that will be cut off your price, if 10% percentage it will be 27 SEK of 30 SEK (10% of 30 SEK is 3 SEK)'}.</p>
                    <div class="form-group">
                        <label for="fm-price_percentage">{fi18n s='Percentage in numbers only'}</label>
                        <input type="number" name="price_percentage" id="fm-price_percentage"
                                {if $price_percentage}
                                    value="{$price_percentage}"
                                {/if}
                                >
                    </div>

                    <h2>{fi18n s='Orders'}</h2>

                    <div class="form-group">
                        <label for="fm-order-import-state">{fi18n s='Import state'}</label>
                        <select name="order_import_state" id="fm-order-import-state">
                            {foreach $order_states as $order_state}
                                <option value="{$order_state.id_order_state}"
                                        {if $order_state.id_order_state == $order_import_state}
                                            selected="selected"
                                        {/if}
                                        >{$order_state.name}</option>
                            {/foreach}
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="fm-order-done-state">{fi18n s='Done state'}</label>
                        <select name="order_done_state" id="fm-order-done-state">
                            {foreach $order_states as $order_state}
                                <option value="{$order_state.id_order_state}"
                                        {if $order_state.id_order_state == $order_done_state}
                                            selected="selected"
                                        {/if}
                                        >{$order_state.name}</option>
                            {/foreach}
                        </select>
                    </div>

                    <button class="btn btn-green" type="submit" name="submit_save_settings">{fi18n s='Save Settings'}</button>
                </form>
                <div class="text-right">
                    <a href="{$path}&action=disconnect" class="btn btn-red">{fi18n s='Disconnect Account'}</a>
                </div>
            </div>
        </div>
    </div>
    <br class="clear" />
</div>
