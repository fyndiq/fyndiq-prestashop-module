<?php

require_once('./service_init.php');
require_once('./FmUtils.php');
require_once('./models/FmModel.php');
require_once('./FmPrestashop.php');
require_once('./FmOutput.php');
require_once('./models/FmProductExport.php');
require_once('./models/FmCategory.php');
require_once('./models/FmProduct.php');
require_once('./models/FmProductInfo.php');
require_once('./models/FmApiModel.php');
require_once('./FmConfig.php');
require_once('./models/FmOrder.php');
require_once('./models/order_fetch.php');
require_once('./FmServiceController.php');

$cookie = new Cookie('psAdmin');
if (!$cookie->id_employee) {
    header('HTTP/1.0 401 Unauthorized');
}

$fmPrestashop = new FmPrestashop(FmUtils::MODULE_NAME);
$fmOutput = new FmOutput($fmPrestashop, null, null);
$fmConfig = new FmConfig($fmPrestashop);
$fmApiModel = new FmApiModel($fmConfig->get('username'), $fmConfig->get('api_token'));
$ajaxService = new FmServiceController($fmPrestashop, $fmOutput, $fmConfig, $fmApiModel);
$ajaxService->handleRequest($_POST);
