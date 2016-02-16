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

    protected $_html = '';
    protected $_postErrors = array();

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
        $action = $action ? $action : 'generic';

        // Force authorize if not authorized
        //$action = $this->fmConfig->isAuthorized($this->storeId) ? $action : 'authenticate';
        // Force setup if not set up
        //$action = $this->fmConfig->isSetUp($this->storeId) ? $action : 'settings';
        //$action = $action != 'authenticate' ? $this->serviceIsOperational($action) : $action;
        $patchVersion = $this->fmConfig->get('patch_version', 0);
        $this->patchTables($patchVersion, 0);

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
            case 'generic':
                return $this->generic();
            default:
                return $this->fmOutput->showModuleError(FyndiqTranslation::get('Page not found'));
        }
    }



    private function apiUnavailable()
    {
        return $this->fmOutput->render('api_unavailable', $this->data);
    }

    private function generic()
    {
        $module = $this->fmPrestashop->moduleGetInstanceByName();
        if ($this->fmPrestashop->toolsIsSubmit('submit'.$module->name))
        {
            $this->_postValidation($module);
            if (!count($this->_postErrors))
                $this->_postProcess($module);
            else
                foreach ($this->_postErrors as $err)
                    $this->_html .= $module->displayError($err);
        }
        else
            $this->_html .= '<br />';

        $this->_html .= $this->displayForm();
        return $this->_html;
    }

    protected function _postValidation($module)
    {
        if (!$this->fmPrestashop->toolsGetValue('username'))
            $this->_postErrors[] = $module->l('Username is required');
        if (!$this->fmPrestashop->toolsGetValue('api_token'))
            $this->_postErrors[] = $module->l('API Token is required');
        if (empty($this->fmPrestashop->toolsGetValue('price_percentage')))
            $this->_postErrors[] = $module->l('Price Percentage is required');
        elseif (!is_numeric($this->fmPrestashop->toolsGetValue('price_percentage')))
            $this->_postErrors[] = $module->l('Price Percentage should be numeric');
        elseif (intval($this->fmPrestashop->toolsGetValue('price_percentage')) < 1 || intval($this->fmPrestashop->toolsGetValue('price_percentage')) > 100)
            $this->_postErrors[] = $module->l('Price Percentage should be a number between 1 and 100');
        if (empty($this->fmPrestashop->toolsGetValue('stock_min')))
            $this->_postErrors[] = $module->l('Lowest quantity is required');
        elseif (!is_numeric($this->fmPrestashop->toolsGetValue('stock_min')))
            $this->_postErrors[] = $module->l('Lowest quantity should be numeric');
        elseif (intval($this->fmPrestashop->toolsGetValue('stock_min')) < 1)
            $this->_postErrors[] = $module->l('Lowest quantity should be more than 1');
    }

    protected function _postProcess($module)
    {
        $username = $this->fmPrestashop->toolsGetValue('username');
        $api_token = $this->fmPrestashop->toolsGetValue('api_token');
        $disable_orders = intval($this->fmPrestashop->toolsGetValue('disable_orders'));
        $languageId = intval($this->fmPrestashop->toolsGetValue('language_id'));
        $pricePercentage = intval($this->fmPrestashop->toolsGetValue('price_percentage'));
        $orderImportState = intval($this->fmPrestashop->toolsGetValue('import_state'));
        $orderDoneState = intval($this->fmPrestashop->toolsGetValue('done_state'));
        $stockMin = intval($this->fmPrestashop->toolsGetValue('stock_min'));
        $stockMin = $stockMin < 0 ? 0 : $stockMin;
        $descriptionType = intval($this->fmPrestashop->toolsGetValue('description_type'));
        $pingToken = $this->fmPrestashop->toolsEncrypt(time());

        $res = $this->sendSettings($username,$api_token,$pingToken,$disable_orders);

        if ($res !== 'success')
            return $this->_html .= $this->fmOutput->showModuleError(FyndiqTranslation::get($res));

        if ($this->fmConfig->set('username', $username, $this->storeId) &&
            $this->fmConfig->set('api_token', $api_token, $this->storeId) &&
            $this->fmConfig->set('disable_orders', $disable_orders, $this->storeId) &&
            $this->fmConfig->set('language', $languageId, $this->storeId) &&
            $this->fmConfig->set('price_percentage', $pricePercentage, $this->storeId) &&
            $this->fmConfig->set('import_state', $orderImportState, $this->storeId) &&
            $this->fmConfig->set('done_state', $orderDoneState, $this->storeId) &&
            $this->fmConfig->set('stock_min', $stockMin, $this->storeId) &&
            $this->fmConfig->set('description_type', $descriptionType, $this->storeId) &&
            $this->fmConfig->set('ping_token', $pingToken, $this->storeId)
        )
            return $this->_html .= $module->displayConfirmation($module->l('Settings updated'));
       return $this->_html .= $this->fmOutput->showModuleError(FyndiqTranslation::get('Error saving settings'));
    }

    protected function sendSettings ($username,$apiToken,$pingToken,$ordersEnable)
    {
        $base = $this->fmPrestashop->getBaseModuleUrl();
        $updateData = array(
                FyndiqUtils::NAME_PRODUCT_FEED_URL =>
                    $base . 'modules/fyndiqmerchant/backoffice/filePage.php?store_id=' . $this->storeId . '&token=' . $pingToken,
                FyndiqUtils::NAME_PING_URL =>
                    $base . 'modules/fyndiqmerchant/backoffice/notification_service.php?event=ping&token=' . $pingToken . '&store_id=' . $this->storeId,
        );
        if ($ordersEnable) {
            $updateData[FyndiqUtils::NAME_NOTIFICATION_URL] =
                $base . 'modules/fyndiqmerchant/backoffice/notification_service.php?event=order_created&store_id=' . $this->storeId;
        }
        try {
            $res = $this->fmApiModel->callApi('PATCH', 'settings/', $updateData, $username, $apiToken);
            return 'success';
        } catch (Exception $e) {
                if ($e->getMessage() == 'Unauthorized') {
                    return 'Invalid Username or API token';
                }
            return !$res ? 'Currently API is Unavailable': $e->getMessage();
        }
    }

    public function displayForm()
    {
        $module = $this->fmPrestashop->moduleGetInstanceByName();
        $this->fmConfig->set('price_percentage', 10, $this->storeId);
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $module->l('Settings'),
                    'icon' => 'icon-cogs'
                ),
                'description' => $module->l('In order to use this module, you have to select which language you will be using.').
                        $module->l('The language, you select, will be used when exporting products to Fyndiq').
                        $module->l('Make sure you select a language that contains Swedish product info!'),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $module->l('Username'),
                        'name' => 'username',
                        'desc' => $module->l('Enter here your fyndiq username.'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $module->l('API Token'),
                        'name' => 'api_token',
                        'desc' => $module->l('Enter here your fyndiq API Token.'),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $module->l('Import Order'),
                        'name' => 'disable_orders',
                        'is_bool' => true,
                        'desc' => $module->l('Enable order import from Fyndiq'),
                        'values' => array(
                                array(
                                    'id' => 'active_on',
                                    'value' => 1,
                                    'label' => $module->l('Enabled')
                                ),
                                array(
                                    'id' => 'active_off',
                                    'value' => 0,
                                    'label' => $module->l('Disabled')
                                )
                            ),
                    ),
                    array(
                        'type' => 'select',
                        'label' => $module->l('Language'),
                        'name' => 'language',
                        'desc' => $module->l('In order to use this module, you have to select which language you will be using.
                                The language, you select, will be used when exporting products to Fyndiq.
                                Make sure you select a language that contains Swedish product info!'),
                        'options' => array(
                            'query' => $this->fmPrestashop->languageGetLanguages(),
                            'id' => 'id_lang',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'text',
                        'label' => $module->l('Percentage in numbers only'),
                        'name' => 'price_percentage',
                        'class' => 'fixed-width-xs',
                        'suffix' => '%',
                        'desc' => $module->l('This percentage is the percentage of the price that will be cut off your price, if 10% percentage it will be 27 SEK of 30 SEK (10% of 30 SEK is 3 SEK).')
                    ),
                    array(
                        'type' => 'text',
                        'label' => $module->l('Lowest quantity to send to Fyndiq'),
                        'name' => 'stock_min',
                        'class' => 'fixed-width-xs'
                    ),
                    array(
                        'type' => 'select',
                        'label' => $module->l('Description to use'),
                        'name' => 'description_type',
                        'options' => array(
                            'query' => $this->getDescriptonTypes(),
                            'id' => 'id',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'select',
                        'label' => $module->l('Import State'),
                        'name' => 'import_state',
                        'options' => array(
                            'query' => $this->getOrderStates(),
                            'id' => 'id_order_state',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'select',
                        'label' => $module->l('Done State'),
                        'name' => 'done_state',
                        'options' => array(
                            'query' => $this->getOrderStates(),
                            'id' => 'id_order_state',
                            'name' => 'name'
                        )
                    ),
                ),
                'submit' => array(
                    'title' => $module->l('Save')
                )
            ),
        );


        $helper = new HelperForm();
        // Module, token and currentIndex
        $helper->module = $module;
        $helper->name_controller = $module->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        // Language
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        // Title and toolbar
        $helper->title = $module->displayName;
        $helper->show_toolbar = false;        // false -> remove toolbar
        $helper->toolbar_scroll = false;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit'.$module->name;

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->fmPrestashop->configurationGet('PS_LANG_DEFAULT'),
            'id_language' => $this->fmPrestashop->getLanguageId()
        );
        return $helper->generateForm(array($fields_form));
    }

    public function getConfigFieldsValues()
    {
        return array(
            'username' => $this->fmPrestashop->toolsGetValue('username', $this->fmConfig->get('username', $this->storeId)),
            'api_token' => $this->fmPrestashop->toolsGetValue('api_token', $this->fmConfig->get('api_token', $this->storeId)),
            'disable_orders' => $this->fmPrestashop->toolsGetValue('disable_orders', $this->fmConfig->get('disable_orders', $this->storeId)),
            'language' => $this->fmPrestashop->toolsGetValue('language', $this->fmConfig->get('language', $this->storeId)),
            'price_percentage' => $this->fmPrestashop->toolsGetValue('price_percentage', $this->fmConfig->get('price_percentage', $this->storeId)),
            'stock_min' => $this->fmPrestashop->toolsGetValue('stock_min', $this->fmConfig->get('stock_min', $this->storeId)),
            'description_type' => $this->fmPrestashop->toolsGetValue('description_type', $this->fmConfig->get('description_type', $this->storeId)),
            'import_state' => $this->fmPrestashop->toolsGetValue('import_state', $this->fmConfig->get('import_state', $this->storeId)),
            'done_state' => $this->fmPrestashop->toolsGetValue('done_state', $this->fmConfig->get('done_state', $this->storeId))
        );
    }

    // TODO: Remove me once beta merchants are patched
    private function patchTables($patchVersion, $storeId)
    {
        $newVersion = $patchVersion;
        try {
            $version = 1;
            if ($patchVersion < $version) {
                $tableName = $this->fmPrestashop->getTableName(FmUtils::MODULE_NAME, '_products', true);
                $sql = 'ALTER TABLE ' . $tableName . ' ADD COLUMN store_id int(10) unsigned DEFAULT 1 AFTER id';
                $this->fmPrestashop->dbGetInstance()->ExecuteS($sql);
            }
            $newVersion = $version;
        } catch (Exception $e) {
            // be discrete
        }
        try {
            $version = 2;
            if ($patchVersion < $version) {
                $tableName = $this->fmPrestashop->getTableName(FmUtils::MODULE_NAME, '_orders', true);
                $sql = 'DROP INDEX orderIndex ON ' . $tableName . ';';
                $this->fmPrestashop->dbGetInstance()->ExecuteS($sql);
                $sql = 'CREATE INDEX orderIndexNew ON ' . $tableName . ' (fyndiq_orderid);';
                $this->fmPrestashop->dbGetInstance()->ExecuteS($sql);
            }
            $newVersion = $version;
        } catch (Exception $e) {
            // be discrete
        }

        try {
            $version = 3;
            if ($patchVersion < $version) {
                $tableName = $this->fmPrestashop->getTableName(FmUtils::MODULE_NAME, '_orders', true);

                $sql = 'ALTER TABLE ' . $tableName . '
                        ADD COLUMN status INT(10) DEFAULT 1,
                        ADD COLUMN body TEXT DEFAULT null,
                        ADD COLUMN created timestamp DEFAULT CURRENT_TIMESTAMP';
                $this->fmPrestashop->dbGetInstance()->Execute($sql, false);
            }
            $newVersion = $version;
        } catch (Exception $e) {
            // be discrete
        }
        if ($newVersion != $patchVersion) {
            $this->fmConfig->set('patch_version', $newVersion, $storeId);
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
                    $base . 'modules/fyndiqmerchant/backoffice/filePage.php?store_id=' . $this->storeId . '&token=' . $pingToken,
                FyndiqUtils::NAME_PING_URL =>
                    $base . 'modules/fyndiqmerchant/backoffice/notification_service.php?event=ping&token=' . $pingToken . '&store_id=' . $this->storeId,
            );
            if ($importOrdersStatus == FmUtils::ORDERS_ENABLED) {
                $updateData[FyndiqUtils::NAME_NOTIFICATION_URL] =
                    $base . 'modules/fyndiqmerchant/backoffice/notification_service.php?event=order_created&store_id=' . $this->storeId;
            }
            try {
                $this->fmApiModel->callApi('PATCH', 'settings/', $updateData, $username, $apiToken);

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
        // TODO: REMOVE ME
        $module = $this->fmPrestashop->moduleGetInstanceByName();
        $module->uninstallOverrides();
        $module->installOverrides();

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

    protected function getOrderStates()
    {
        $languageId = $this->fmPrestashop->getLanguageId();
        $orderStates = $this->fmPrestashop->orderStateGetOrderStates($languageId);
        $states = array();
        foreach ($orderStates as $orderState) {
            if ($this->fmPrestashop->orderStateInvoiceAvailable($orderState['id_order_state'])) {
                $states[] = $orderState;
            }
        }
        return $states;
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
                'label' => FyndiqTranslation::get('Checking for duplicate SKU-s'),
                'action' => 'probe_products',
            ),
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
            array(
                'label' => FyndiqTranslation::get('Installed modules'),
                'action' => 'probe_modules',
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
