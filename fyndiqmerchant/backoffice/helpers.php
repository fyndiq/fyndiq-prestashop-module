<?php

class FyndiqProductSKUNotFound extends Exception{}

function pd($v)
{
    echo '<pre>';
    var_dump($v);
    echo '</pre>';
}

function startsWith($haystack, $needle)
{
    return $needle === '' || strpos($haystack, $needle) === 0;
}

function endsWith($haystack, $needle)
{
    return $needle === '' || substr($haystack, -strlen($needle)) === $needle;
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

class FmHelpers
{
    const EXPORT_FILE_NAME_PATTERN = 'feed-%d.csv';

    public static function api_connection_exists($module = null)
    {
        $ret = true;
        $ret = $ret && FmConfig::get('username') !== false;
        $ret = $ret && FmConfig::get('api_token') !== false;

        return $ret;
    }

    public static function all_settings_exist($module = null)
    {
        $ret = true;
        $ret = $ret && FmConfig::get('language') !== false;
        $ret = $ret && FmConfig::get('import_state') !== false;
        $ret = $ret && FmConfig::get('done_state') !== false;

        return $ret;
    }

    /**
     * Wrappers around FyndiqAPI -  uses stored connection credentials for authentication
     *
     * @param $method
     * @param $path
     * @param array $data
     * @return mixed
     * @throws FyndiqAPIAuthorizationFailed
     * @throws FyndiqAPIBadRequest
     * @throws FyndiqAPIDataInvalid
     * @throws FyndiqAPINoAPIClass
     * @throws FyndiqAPIPageNotFound
     * @throws FyndiqAPIServerError
     * @throws FyndiqAPITooManyRequests
     * @throws FyndiqAPIUnsupportedStatus
     */
    public static function call_api($method, $path, $data = array())
    {
        $username = FmConfig::get('username');
        $apiToken = FmConfig::get('api_token');
        $module = Module::getInstanceByName('fyndiqmerchant');
        $userAgent = $module->user_agent;

        return FyndiqAPICall::callApiRaw($userAgent, $username, $apiToken, $method, $path, $data,
            array('FyndiqAPI', 'call'));
    }

    public static function db_escape($value)
    {
        if (FMPSV == FMPSV15 OR FMPSV == FMPSV16) {
            return Db::getInstance()->_escape($value);
        }
        if (FMPSV == FMPSV14) {
            return pSQL($value);
        }
    }

    public static function get_module_url($withadminurl = true)
    {
        $url = _PS_BASE_URL_ . __PS_BASE_URI__;
        if ($withadminurl) {
            $url .= substr(strrchr(_PS_ADMIN_DIR_, '/'), 1);
            $url .= "/index.php?controller=AdminModules&configure=fyndiqmerchant&module_name=fyndiqmerchant";
            $url .= '&token=' . Tools::getAdminTokenLite('AdminModules');
        }

        return $url;
    }

    public static function get_shop_url()
    {
        if (Shop::getContext() === Shop::CONTEXT_SHOP) {
            $shop = new Shop(self::getCurrentShopId());
            return $shop->getBaseURL();
        }
        // fallback to globals if context is not shop
        return self::get_module_url(false);
    }

    /**
     * Returns export file name depending on the shop context
     *
     * @return string export file name
     */
    public static function getExportFileName()
    {
        if (Shop::getContext() === Shop::CONTEXT_SHOP) {
            return sprintf(self::EXPORT_FILE_NAME_PATTERN, self::getCurrentShopId());
        }
        // fallback to 0 for non-multistore setups
        return sprintf(self::EXPORT_FILE_NAME_PATTERN, 0);
    }

    /**
     * Returns the current shop id
     *
     * @return int
     */
    public static function getCurrentShopId()
    {
        $context = Context::getContext();
        if (Shop::isFeatureActive() && $context->cookie->shopContext) {
            $split = explode('-', $context->cookie->shopContext);
            if (count($split) === 2) {
                return intval($split[1]);
            }
        }
        return intval($context->shop->id);
    }
}
