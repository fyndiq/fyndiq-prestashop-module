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
$shop_id = null;
if ($fmPrestashop->isPs1516()) {
    // Set the correct shop context
    $shop_id = null;
    Shop::setContext(Shop::CONTEXT_ALL);
    if (isset($context) && $context->cookie && $context->cookie->shopContext) {
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
    if (isset($_GET['store_id']) && is_numeric($_GET['store_id'])) {
        $shop_id = intval($_GET['store_id']);
    };
}
$fmPrestashop->setStoreId($shop_id);
