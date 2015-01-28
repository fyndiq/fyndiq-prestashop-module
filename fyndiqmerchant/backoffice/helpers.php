<?php

class FyndiqAPIDataInvalid extends Exception{}

class FyndiqAPIConnectionFailed extends Exception{}

class FyndiqAPIPageNotFound extends Exception{}

class FyndiqAPIAuthorizationFailed extends Exception{}

class FyndiqAPITooManyRequests extends Exception{}

class FyndiqAPIServerError extends Exception{}

class FyndiqAPIBadRequest extends Exception{}

class FyndiqAPIUnsupportedStatus extends Exception{}

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

# FyndiqMerchant PrestaShop Version 1.4|1.5|1.6
define('FMPSV14', 'FMPSV14');
define('FMPSV15', 'FMPSV15');
define('FMPSV16', 'FMPSV16');
if (startswith(_PS_VERSION_, '1.4.')) {
    define('FMPSV', FMPSV14);
}
if (startswith(_PS_VERSION_, '1.5.')) {
    define('FMPSV', FMPSV15);
}
if (startswith(_PS_VERSION_, '1.6.')) {
    define('FMPSV', FMPSV16);
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

    # add descriptive error messages for common errors, and re throw same exception
    public static function call_api_raw($username, $api_token, $method, $path, $data=array()) {
        $module = Module::getInstanceByName('fyndiqmerchant');

        $response = FyndiqAPI::call($module->user_agent, $username, $api_token, $method, $path, $data);



        if ($response['status'] == 404) {
            throw new FyndiqAPIPageNotFound('Not Found: ' . $path);
        }

        if ($response['status'] == 401) {
            throw new FyndiqAPIAuthorizationFailed('Unauthorized');
        }

        if ($response['status'] == 429) {
            throw new FyndiqAPITooManyRequests('Too Many Requests');
        }

        if ($response['status'] == 500) {
            throw new FyndiqAPIServerError('Server Error');
        }
        // if json_decode failed
        if (json_last_error() != JSON_ERROR_NONE) {
            throw new FyndiqAPIDataInvalid('Error in response data');
        }

        // 400 may contain error messages intended for the user
        if ($response['status'] == 400) {
            $message = '';

            // if there are any error messages, save them to class static member
            if (property_exists($response["data"], 'error_messages')) {
                $error_messages = $response["data"]->error_messages;

                // if it contains several messages as an array
                if (is_array($error_messages)) {

                    foreach ($response["data"]->error_messages as $error_message) {
                        self::$error_messages[] = $error_message;
                    }

                    // if it contains just one message as a string
                } else {
                    self::$error_messages[] = $error_messages;
                }
            }

            throw new FyndiqAPIBadRequest('Bad Request');
        }

        $success_http_statuses = array('200', '201');

        if (!in_array($response['status'], $success_http_statuses)) {
            throw new FyndiqAPIUnsupportedStatus('Unsupported HTTP status: ' . $response['status']);
        }

        $success_http_statuses = array('200', '201');

        if (!in_array($response['status'], $success_http_statuses)) {
            throw new FyndiqAPIUnsupportedStatus('Unsupported HTTP status: ' . $response['status']);
        }

        return $response;
    }

    public static function db_escape($value) {
        if (FMPSV == FMPSV15 OR FMPSV == FMPSV16) {
            return Db::getInstance()->_escape($value);
        }
        if (FMPSV == FMPSV14) {
            return pSQL($value);
        }
    }

    public static function get_shop_url($context) {
        if (FMPSV == FMPSV15 OR FMPSV == FMPSV16) {
            return $context->shop->getBaseURL();
        }
        if (FMPSV == FMPSV14) {
            // pd(Tools::getShopDomainSsl(true, false)) pd(__PS_BASE_URI__);
        }
    }

    public static function get_module_url() {
        $url = _PS_BASE_URL_.__PS_BASE_URI__.substr(strrchr(_PS_ADMIN_DIR_, '/'), 1)."/index.php?controller=AdminModules&configure=fyndiqmerchant&module_name=fyndiqmerchant";
        $url .= '&token='.Tools::getAdminTokenLite('AdminModules');
        return $url;
    }

}
