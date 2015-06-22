<?php

$storeRoot = dirname(dirname(dirname(dirname($_SERVER['SCRIPT_FILENAME']))));
require_once('./includes/shared/src/init.php');
require_once('./FmPrestashop.php');
$timer_start = microtime(true);

// NOTE: This root is wrong but config relies on these constants to be set to populate the proper context
if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', $storeRoot);
}
if (!defined('PS_ADMIN_DIR')) {
    define('PS_ADMIN_DIR', _PS_ADMIN_DIR_);
}

# import PrestaShop config, to enable use of PrestaShop classes, like Configuration
$configPath = $storeRoot . '/config/config.inc.php';

if (file_exists($configPath)) {
    require_once($configPath);
} else {
    exit;
}

require_once('./FmUtils.php');
require_once('./FmPrestashop.php');

$fmPrestashop = new FmPrestashop(FmUtils::MODULE_NAME);

if ($fmPrestashop->isPs1516()) {
    // Set the correct shop context
    $shop_id = '';
    Shop::setContext(Shop::CONTEXT_ALL);
    if ($context->cookie->shopContext) {
        $split = explode('-', $context->cookie->shopContext);
        if (count($split) == 2) {
            if ($split[0] == 'g') {
                if ($context->employee->hasAuthOnShopGroup($split[1])) {
                    Shop::setContext(Shop::CONTEXT_GROUP, $split[1]);
                } else {
                    $shop_id = $context->employee->getDefaultShopID();
                    Shop::setContext(Shop::CONTEXT_SHOP, $shop_id);
                }
            } elseif ($context->employee->hasAuthOnShop($split[1])) {
                $shop_id = $split[1];
                Shop::setContext(Shop::CONTEXT_SHOP, $shop_id);
            } else {
                $shop_id = $context->employee->getDefaultShopID();
                Shop::setContext(Shop::CONTEXT_SHOP, $shop_id);
            }
        }
    }
    // Replace existing shop if necessary
    if (!$shop_id) {
        $context->shop = new Shop(Configuration::get('PS_SHOP_DEFAULT'));
    } elseif ($context->shop->id != $shop_id) {
        $context->shop = new Shop($shop_id);
    }
}

