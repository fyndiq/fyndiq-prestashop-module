<?php

function pd($v) {
    echo '<pre>';
    var_dump($v);
    echo '</pre>';
}

class FyndiqMerchantHelpers {

    # wrapper around FyndiqAPI
    # uses stored connection credentials for authentication
    public static function call_api($path, $data=array()) {

        # get stored connection credentials
        $module = Module::getInstanceByName('fyndiqmerchant');
        $username = Configuration::get($module->config_name.'_username');
        $api_token = Configuration::get($module->config_name.'_api_token');

        # call API
        try {
            return FyndiqAPI::call($module->user_agent, $username, $api_token, $path, $data);
        } catch (Exception $e) {
            throw new Exception(FmMessages::get('api-call-error').': '.get_class($e).': '.$e->getMessage());
        }
    }
}
