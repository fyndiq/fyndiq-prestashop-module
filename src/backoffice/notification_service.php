<?php
/*
This file handles incoming requests from the automated notification system at Fyndiq.
*/

require_once('./service_init.php');
require_once('./FmUtils.php');
require_once('./FmConfig.php');
require_once('./FmOutput.php');
require_once('./FmPrestashop.php');
require_once('./models/FmApiModel.php');
require_once('./models/FmModel.php');
require_once('./models/FmOrder.php');
require_once('./models/FmProduct.php');
require_once('./models/FmProductExport.php');
require_once('./FmProductInfo.php');
require_once('./includes/fyndiqAPI/fyndiqAPI.php');

class FmNotificationService
{

    public function __construct($fmPrestashop, $fmConfig, $fmOutput, $fmApiModel)
    {
        $this->fmPrestashop = $fmPrestashop;
        $this->fmConfig = $fmConfig;
        $this->fmOutput = $fmOutput;
        $this->fmApiModel = $fmApiModel;
    }

    /**
     * Handle request
     *
     * @param array $params GET Params
     * @return mixed
     */
    public function handleRequest($params)
    {
        $eventName = isset($params['event']) ? $params['event'] : false;
        if ($eventName) {
            if ($eventName[0] != '_' && method_exists($this, $eventName)) {
                return $this->$eventName($params);
            }
        }
        return $this->fmOutput->showError(400, 'Bad Request', 400 Bad Request);
    }

    /**
     * Processes new order notifications
     *
     * @param array $params
     * @return bool
     */
    private function order_created($params)
    {
        $orderId = isset($params['order_id']) && is_numeric($params['order_id']) ? $params['order_id'] : 0;
        if ($orderId) {
            $url = 'orders/' . $orderId . '/';
            try {
                $ret = $this->fmApiModel->callApi('GET', $url);
                $order = $ret['data'];
                $fmOrder = new FmOrder($this->fmPrestashop, $this->fmConfig);
                $idOrderState = $this->fmConfig->get('import_state');
                $taxAddressType = $this->fmPrestashop->getTaxAddressType();
                if (!$fmOrder->orderExists($order->id)) {
                    $fmOrder->create($order, $idOrderState, $taxAddressType);
                }
            } catch (Exception $e) {
                return $this->fmOutput->showError(500, 'Internal Server Error', $e->getMessage());
            }
            return $this->fmOutput->output('OK');
        }
        return $this->fmOutput->showError(400, 'Bad Request', '400 Bad Request');
    }

    /**
     * Generate feed
     *
     * @param $params
     */
    private function ping($params)
    {
        $token = isset($params['token']) ? $params['token'] : null;
        if (is_null($token) || $token != $this->fmConfig->get('ping_token')) {
            return $this->fmOutput->showError(400, 'Bad Request', 'Invalid token');
        }

        $this->fmOutput->flushHeader('OK');

        $locked = false;
        $lastPing = $this->fmConfig->get('ping_time');
        if ($lastPing && $lastPing > strtotime('9 minutes ago')) {
            $locked = true;
        }
        if (!$locked) {
            $this->fmConfig->set('ping_time', time());
            $filePath = $this->fmPrestashop->getExportPath() . $this->fmPrestashop->getExportFileName();
            try {
                $file = fopen($filePath, 'w+');
                $feedWriter = FmUtils::getFileWriter($file);
                $fmProductExport = new FmProductExport($this->fmPrestashop, $this->fmConfig);
                $languageId = $this->fmConfig->get('language');
                $fmProductExport->saveFile($languageId, $feedWriter);
                fclose($file);
                return $this->_update_product_info();
            } catch (Exception $e) {
                return $this->fmOutput->showError(500, 'Internal Server Error', $e->getMessage());
            }
        }
    }

    private function _update_product_info()
    {
        $module = $this->fmPrestashop->moduleGetInstanceByName(FmUtils::MODULE_NAME);
        $tableName = $module->config_name . '_products';
        $fmProduct = new FmProduct($this->fmPrestashop, $this->fmConfig);
        $productInfo = new FmProductInfo($fmProduct, $this->fmApiModel, $tableName);
        return $productInfo->getAll();
    }
}

$fmPrestashop = new FmPrestashop(FmUtils::MODULE_NAME);
$fmConfig = new FmConfig($fmPrestashop);
$fmOutput = new FmOutput($fmPrestashop, null, null);
$fmApiModel = new FmApiModel($fmConfig->get('username'), $fmConfig->get('api_token'));

$notifications = new FmNotificationService($fmPrestashop, $fmConfig, $fmOutput, $fmApiModel);
$notifications->handleRequest($_GET);
