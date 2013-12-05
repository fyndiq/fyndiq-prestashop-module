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

    public static function api_connection_exists($module=null) {
        $ret = true;
        $ret = $ret && FmConfig::get('username') !== false;
        $ret = $ret && FmConfig::get('api_token') !== false;
        return $ret;
    }

    public static function all_settings_exist($module=null) {
        $ret = true;
        $ret = $ret && FmConfig::get('language') !== false;
        $ret = $ret && FmConfig::get('currency') !== false;
        return $ret;
    }

    ## wrappers around FyndiqAPI
    # uses stored connection credentials for authentication
    public static function call_api($method, $path, $data=array()) {
        $username = FmConfig::get('username');
        $api_token = FmConfig::get('api_token');

        return FmHelpers::call_api_raw($username, $api_token, $method, $path, $data);
    }

    # handles custom exceptions, fetches proper error messages, and throws new generic exception
    public static function call_api_raw($username, $api_token, $method, $path, $data=array()) {
        $module = Module::getInstanceByName('fyndiqmerchant');

        try {
            return FyndiqAPI::call($module->user_agent, $username, $api_token, $method, $path, $data);

        } catch (FyndiqAPIConnectionFailed $e) {
            throw new Exception(FmMessages::get('api-network-error').': '.$e->getMessage());

        } catch (FyndiqAPIDataInvalid $e) {
            throw new Exception(FmMessages::get('api-invalid-data').': '.$e->getMessage());

        } catch (FyndiqAPIAuthorizationFailed $e) {
            throw new Exception(FmMessages::get('api-incorrect-credentials'));

        } catch (FyndiqAPITooManyRequests $e) {
            throw new Exception(FmMessages::get('api-too-many-requests'));

        } catch (FyndiqAPIUnsupportedStatus $e) {
            throw new Exception(FmMessages::get('api-unknown-error'));

        } catch (Exception $e) {
            throw new Exception(FmMessages::get('api-unknown-error'));
        }
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
