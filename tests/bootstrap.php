<?php

DEFINE('FYNDIQ_ROOT', './src/');

require_once(FYNDIQ_ROOT . 'backoffice/includes/shared/src/init.php');
require_once(FYNDIQ_ROOT . 'backoffice/FmPrestashop.php');
require_once(FYNDIQ_ROOT . 'backoffice/FmConfig.php');
require_once(FYNDIQ_ROOT . 'backoffice/FmOutput.php');
require_once(FYNDIQ_ROOT . 'backoffice/FmUtils.php');
require_once(FYNDIQ_ROOT . 'backoffice/FmController.php');
require_once(FYNDIQ_ROOT . 'backoffice/FmFormSetting.php');
require_once(FYNDIQ_ROOT . 'backoffice/FmServiceController.php');
require_once(FYNDIQ_ROOT . 'backoffice/models/FmModel.php');
require_once(FYNDIQ_ROOT . 'backoffice/models/FmApiModel.php');
require_once(FYNDIQ_ROOT . 'backoffice/models/FmOrder.php');
require_once(FYNDIQ_ROOT . 'backoffice/models/FmCategory.php');
require_once(FYNDIQ_ROOT . 'backoffice/models/FmProduct.php');
require_once(FYNDIQ_ROOT . 'backoffice/models/FmProductExport.php');
require_once(FYNDIQ_ROOT . 'backoffice/FmOrderFetch.php');
require_once(FYNDIQ_ROOT . 'backoffice/FmProductInfo.php');
require_once(FYNDIQ_ROOT . 'backoffice/FmFormSetting.php');

class PrestaShopException extends Exception
{
}
