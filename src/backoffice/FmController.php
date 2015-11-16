<?php

class FmController
{

    const DEFAULT_DISCOUNT_PERCENTAGE = 10;
    const DEFAULT_ORDER_IMPORT_STATE = 3;
    const DEFAULT_ORDER_DONE_STATE = 4;

    private $data = array();
    private $fmOutput;
    private $fmConfig;
    private $fmPrestashop;
    private $storeId = null;

    public function __construct($fmPrestashop, $fmOutput, $fmConfig, $fmApiModel)
    {
        $this->fmOutput = $fmOutput;
        $this->fmConfig = $fmConfig;
        $this->fmPrestashop = $fmPrestashop;
        $this->fmApiModel = $fmApiModel;
        $this->storeId = $this->fmPrestashop->getStoreId();
        $importOrdersStatus = $this->fmConfig->get('disable_orders', $this->storeId);

        $path = $fmPrestashop->getModuleUrl();
        $this->data = array(
            'json_messages' => json_encode(FyndiqTranslation::getAll()),
            'messages' => FyndiqTranslation::getAll(),
            'path' => $path,
            'orders_enabled' => $importOrdersStatus == FmUtils::ORDERS_ENABLED,
        );
    }

    protected function serviceIsOperational($action)
    {
        try {
            $this->fmApiModel->callApi('GET', 'settings/');
            return $action;
        } catch (Exception $e) {
            if ($e->getMessage() == 'Unauthorized') {
                return 'authenticate';
            }
            $this->data['message'] = $e->getMessage();
            return 'api_unavailable';
        }
    }

    public function handleRequest()
    {
        $action = $this->fmPrestashop->toolsGetValue('action');
        $action = $action ? $action : 'main';

        // Force authorize if not authorized
        $action = $this->fmConfig->isAuthorized($this->storeId) ? $action : 'authenticate';
        // Force setup if not set up
        $action = $this->fmConfig->isSetUp($this->storeId) ? $action : 'settings';
        $action = $action != 'authenticate' ? $this->serviceIsOperational($action) : $action;

        switch ($action) {
            case 'api_unavailable':
                return $this->apiUnavailable();
            case 'authenticate':
                return $this->authenticate();
            case 'main':
                return $this->main();
            case 'settings':
                return $this->settings();
            case 'orders':
                return $this->orders();
            case 'disconnect':
                return $this->disconnect();
            default:
                return $this->fmOutput->showModuleError(FyndiqTranslation::get('Page not found'));
        }
    }

    private function apiUnavailable()
    {
        return $this->fmOutput->render('api_unavailable', $this->data);
    }

    // TODO: Remove me once beta merchants are patched
    private function patchProductsTable()
    {
        try {
            $tableName = $this->fmPrestashop->getTableName(FmUtils::MODULE_NAME, '_products', true);
            $sql = 'ALTER TABLE ' . $tableName . ' ADD COLUMN store_id int(10) unsigned DEFAULT 1 AFTER id';
            $this->fmPrestashop->dbGetInstance()->ExecuteS($sql);
        } catch (Exception $e) {
            // be discrete
        }
    }

    private function authenticate()
    {
        if ($this->fmPrestashop->toolsIsSubmit('submit_authenticate')) {
            $username = strval($this->fmPrestashop->toolsGetValue('username'));
            $apiToken = strval($this->fmPrestashop->toolsGetValue('api_token'));
            $importOrdersStatus = strval($this->fmPrestashop->toolsGetValue('import_orders_disabled'))
                ? FmUtils::ORDERS_DISABLED
                : FmUtils::ORDERS_ENABLED;

            // validate parameters
            if (empty($username) || empty($apiToken)) {
                return $this->fmOutput->showModuleError(FyndiqTranslation::get('empty-username-token'));
            }
            $base = $this->fmPrestashop->getBaseModuleUrl();
            $pingToken = $this->fmPrestashop->toolsEncrypt(time());
            $this->fmConfig->set('ping_token', $pingToken, $this->storeId);
            $this->fmConfig->set('username', $username, $this->storeId);
            $this->fmConfig->set('api_token', $apiToken, $this->storeId);
            $this->fmConfig->set('disable_orders', $importOrdersStatus, $this->storeId);
            $updateData = array(
                FyndiqUtils::NAME_PRODUCT_FEED_URL =>
                    $base . 'modules/fyndiqmerchant/backoffice/filePage.php?store_id=' . $this->storeId,
                FyndiqUtils::NAME_PING_URL =>
                    $base . 'modules/fyndiqmerchant/backoffice/notification_service.php?event=ping&token=' . $pingToken . '&store_id=' . $this->storeId,
            );
            if ($importOrdersStatus == FmUtils::ORDERS_ENABLED) {
                $updateData[FyndiqUtils::NAME_NOTIFICATION_URL] =
                    $base . 'modules/fyndiqmerchant/backoffice/notification_service.php?event=order_created&store_id=' . $this->storeId;
            }
            try {
                $this->fmApiModel->callApi('PATCH', 'settings/', $updateData, $username, $apiToken);

                // TODO: Remove me once beta merchants are patched
                $this->patchProductsTable();

                $this->fmPrestashop->sleep(1);
                return $this->fmOutput->redirect($this->fmPrestashop->getModuleUrl());
            } catch (Exception $e) {
                $this->fmConfig->delete('username', $this->storeId);
                $this->fmConfig->delete('api_token', $this->storeId);
                return $this->fmOutput->render('authenticate', $this->data, $e->getMessage());
            }
        }
        return $this->fmOutput->render('authenticate', $this->data);
    }

    private function main()
    {
        return $this->fmOutput->render('main', $this->data);
    }

    private function settings()
    {
        $showSKUSelect = intval($this->fmPrestashop->toolsGetValue('set_sku')) === 1;
        if ($this->fmPrestashop->toolsIsSubmit('submit_save_settings')) {
            $languageId = intval($this->fmPrestashop->toolsGetValue('language_id'));
            $pricePercentage = intval($this->fmPrestashop->toolsGetValue('price_percentage'));
            $orderImportState = intval($this->fmPrestashop->toolsGetValue('order_import_state'));
            $orderDoneState = intval($this->fmPrestashop->toolsGetValue('order_done_state'));
            $stockMin = intval($this->fmPrestashop->toolsGetValue('stock_min'));
            $stockMin = $stockMin < 0 ? 0 : $stockMin;
            $descriptionType = intval($this->fmPrestashop->toolsGetValue('description_type'));
            $skuTypeId = $this->fmPrestashop->toolsGetValue('sku_type_id');
            $skuTypeId = $skuTypeId ? $skuTypeId : FmUtils::SKU_DEFAULT;

            $this->config->set('sku_type_id', intval($post['sku_type_id']));

            if ($this->fmConfig->set('language', $languageId, $this->storeId) &&
                $this->fmConfig->set('price_percentage', $pricePercentage, $this->storeId) &&
                $this->fmConfig->set('import_state', $orderImportState, $this->storeId) &&
                $this->fmConfig->set('done_state', $orderDoneState, $this->storeId) &&
                $this->fmConfig->set('stock_min', $stockMin, $this->storeId) &&
                $this->fmConfig->set('description_type', $descriptionType, $this->storeId) &&
                $this->fmConfig->set('sku_type_id', $skuTypeId, $this->storeId)
            ) {
                return $this->fmOutput->redirect($this->fmPrestashop->getModuleUrl());
            }
            return $this->fmOutput->showModuleError(FyndiqTranslation::get('Error saving settings'));
        }

        $selectedLanguage = $this->fmConfig->get('language', $this->storeId);
        $pricePercentage = $this->fmConfig->get('price_percentage', $this->storeId);
        $orderImportState = $this->fmConfig->get('import_state', $this->storeId);
        $orderDoneState = $this->fmConfig->get('done_state', $this->storeId);
        $stockMin = $this->fmConfig->get('stock_min', $this->storeId);
        $descriptionType = intval($this->fmConfig->get('description_type', $this->storeId));
        $skuTypeId = intval($this->fmConfig->get('sku_type_id', $this->storeId));

        // if there is a configured language, show it as selected
        $selectedLanguage =  $selectedLanguage ?
            $selectedLanguage :
            $this->fmPrestashop->configurationGet('PS_LANG_DEFAULT');
        $pricePercentage = $pricePercentage ? $pricePercentage : self::DEFAULT_DISCOUNT_PERCENTAGE;
        $orderImportState = $orderImportState ? $orderImportState : self::DEFAULT_ORDER_IMPORT_STATE;
        $orderDoneState = $orderDoneState ? $orderDoneState : self::DEFAULT_ORDER_DONE_STATE;
        $descriptionType = $descriptionType ? $descriptionType : FmUtils::LONG_DESCRIPTION;
        $skuTypeId = $skuTypeId ? $skuTypeId : FmUtils::SKU_DEFAULT;

        $languageId = $this->fmPrestashop->getLanguageId();
        $orderStates = $this->fmPrestashop->orderStateGetOrderStates($languageId);

        $states = array();
        foreach ($orderStates as $orderState) {
            if ($this->fmPrestashop->orderStateInvoiceAvailable($orderState['id_order_state'])) {
                $states[] = $orderState;
            }
        }

        $currency = $this->fmPrestashop->getDefaultCurrency();
        $market =$this->fmPrestashop->getCountryCode();

        $this->data['message'] = array();
        if (!in_array($currency, FyndiqUtils::$allowedCurrencies)) {
            $this->data['message'][] = sprintf(
                FyndiqTranslation::get('Currency `%s` is not supported. Supported currencies are: %s. Please check your settings'),
                $currency,
                implode(', ', FyndiqUtils::$allowedCurrencies)
            );
        }

        if (!in_array($market, FyndiqUtils::$allowedMarkets)) {
            $this->data['message'][] = sprintf(
                FyndiqTranslation::get('Market `%s` is not supported. Supported markets are: %s. Please check your settings'),
                $market,
                implode(', ', FyndiqUtils::$allowedMarkets)
            );
        }

        $this->data['languages'] = $this->fmPrestashop->languageGetLanguages();
        $this->data['price_percentage'] = $pricePercentage;
        $this->data['selected_language'] = $selectedLanguage;
        $this->data['order_states'] = $states;
        $this->data['order_import_state'] = $orderImportState;
        $this->data['order_done_state'] = $orderDoneState;
        $this->data['stock_min'] = $stockMin;
        $this->data['probes'] = $this->getProbes();
        $this->data['description_type_id'] = $descriptionType;
        $this->data['description_types'] = $this->getDescriptonTypes();
        $this->data['sku_type_id'] = $skuTypeId;
        $this->data['sku_types'] = $this->getSKUTypes();
        $this->data['showSKUSelect'] = $showSKUSelect;

        return $this->fmOutput->render('settings', $this->data);
    }

    protected function getDescriptonTypes()
    {
        return array(
            array(
                'id' => FmUtils::LONG_DESCRIPTION,
                'name' => FyndiqTranslation::get('Description'),
            ),
            array(
                'id' => FmUtils::SHORT_DESCRIPTION,
                'name' => FyndiqTranslation::get('Short description'),
            ),
            array(
                'id' => FmUtils::SHORT_AND_LONG_DESCRIPTION,
                'name' => FyndiqTranslation::get('Short and long description'),
            ),
        );
    }

    protected function getSKUTypes()
    {
        return array(
            array(
                'id' => FmUtils::SKU_REFERENCE,
                'name' => FyndiqTranslation::get('Reference code'),
            ),
            array(
                'id' => FmUtils::SKU_EAN,
                'name' => FyndiqTranslation::get('EAN'),
            ),
            array(
                'id' => FmUtils::SKU_ID,
                'name' => FyndiqTranslation::get('Database ID'),
            ),
        );
    }

    protected function getProbes()
    {
        $probes = array(
            array(
                'label' => FyndiqTranslation::get('Checking file permissions'),
                'action' => 'probe_file_permissions',
            ),
            array(
                'label' => FyndiqTranslation::get('Checking database'),
                'action' => 'probe_database',
            ),
            array(
                'label' => FyndiqTranslation::get('Module integrity'),
                'action' => 'probe_module_integrity',
            ),
            array(
                'label' => FyndiqTranslation::get('Connection to Fyndiq'),
                'action' => 'probe_connection',
            ),
        );
        return json_encode($probes);

    }

    private function orders()
    {
        $importDate = $this->fmConfig->get('import_date', $this->storeId);
        $isToday = date('Ymd') === date('Ymd', strtotime($importDate));
        $this->data['import_date'] = $importDate;
        $this->data['isToday'] = $isToday;
        $this->data['import_time'] = date('G:i:s', strtotime($importDate));
        return $this->fmOutput->render('orders', $this->data);
    }

    private function disconnect()
    {
        $updateData = array(
            FyndiqUtils::NAME_PRODUCT_FEED_URL => '',
            FyndiqUtils::NAME_PING_URL => '',
            FyndiqUtils::NAME_NOTIFICATION_URL => '',
        );
        $username = $this->fmConfig->get('username', $this->storeId);
        $apiToken = $this->fmConfig->get('api_token', $this->storeId);
        $this->fmApiModel->callApi('PATCH', 'settings/', $updateData, $username, $apiToken);
        if ($this->fmConfig->delete('username', $this->storeId) &&
            $this->fmConfig->delete('api_token', $this->storeId)) {
            return $this->fmOutput->redirect($this->fmPrestashop->getModuleUrl());
        }
        return $this->fmOutput->showModuleError(FyndiqTranslation::get('Error disconnecting account'));
    }
}
