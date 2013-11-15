<?php

function pd($v) {
    echo '<pre>';
    var_dump($v);
    echo '</pre>';
}

function startsWith($haystack, $needle) {
    return $needle === "" || strpos($haystack, $needle) === 0;
}
function endsWith($haystack, $needle) {
    return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
}

# FyndiqMerchant PrestaShop Version 1.4|1.5
define('FMPSV14', 'FMPSV14');
define('FMPSV15', 'FMPSV15');
if (startswith(_PS_VERSION_, '1.4.')) {
    define('FMPSV', FMPSV14);
}
if (startswith(_PS_VERSION_, '1.5.')) {
    define('FMPSV', FMPSV15);
}

class FmHelpers {

    # wrapper around FyndiqAPI
    # uses stored connection credentials for authentication
    public static function call_api($path, $data=array()) {

        # get stored connection credentials
        $module = Module::getInstanceByName('fyndiqmerchant');
        $username = Configuration::get($module->config_name.'_username');
        $api_token = Configuration::get($module->config_name.'_api_token');

        # call API
        return FyndiqAPI::call($module->user_agent, $username, $api_token, $path, $data);
    }

    public static function db_escape($value) {
        if (FMPSV == FMPSV15) {
            return Db::getInstance()->_escape($value);
        }
        if (FMPSV == FMPSV14) {
            return pSQL($value);
        }
    }
}
