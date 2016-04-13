<?php

require_once('./service_init.php');
require_once('./models/FmModel.php');
require_once('./FmOutput.php');
require_once('./models/FmProductExport.php');
require_once('./models/FmCategory.php');
require_once('./models/FmProduct.php');
require_once('./models/FmApiModel.php');
require_once('./FmConfig.php');
require_once('./models/FmOrder.php');
require_once('./FmOrderFetch.php');
require_once('./FmServiceController.php');


// TODO: Fix security for 1.4
// Introduce new cookie for 1.4 which ca authenticate against the token
$cookie = new Cookie('psAdmin');

if ($fmPrestashop->isPs1516()) {
    if (!$cookie->id_employee) {
        header('HTTP/1.0 401 Unauthorized');
        die();
    }
} else {
    $fyCookie = new Cookie(FmUtils::MODULE_NAME);
    if ($fyCookie->id_currency) {
        $cookie->id_currency = $fyCookie->id_currency;
    }
    if ($fyCookie->id_lang) {
        $cookie->id_lang = $fyCookie->id_lang;
    }
    unset($cookie->id_country);
    if ($fyCookie->id_country) {
        $cookie->id_country = $fyCookie->id_country;
    }
    if ($fyCookie->id_employee) {
        $cookie->id_employee = $fyCookie->id_employee;
    }
}
$storeId = $fmPrestashop->getStoreId();
$fmOutput = new FmOutput($fmPrestashop, null, null);
$fmConfig = new FmConfig($fmPrestashop);
$fmApiModel = new FmApiModel($fmPrestashop, $fmConfig, $storeId);
$ajaxService = new FmServiceController($fmPrestashop, $fmOutput, $fmConfig, $fmApiModel);
$ajaxService->handleRequest($_POST);
