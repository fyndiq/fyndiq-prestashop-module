<?php

class FmController
{
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
    }

    public function handleRequest()
    {
        return $this->settings();
    }

    private function settings()
    {
        $module = $this->fmPrestashop->moduleGetInstanceByName();
        if ($this->fmPrestashop->toolsIsSubmit('submit'.$module->name)) {
            $this->_postValidation();
            if (!count($this->_postErrors)) {
                $this->_postProcess();
            } else {
                foreach ($this->_postErrors as $err) {
                    $this->_html .= $this->fmOutput->showModuleError($err);
                }
            }
        } else {
            $this->_html .= '<br />';
        }

        $this->_html .= $this->displayForm($module);
        return $this->_html;
    }

    protected function _postValidation()
    {
        if (!$this->fmPrestashop->toolsGetValue('username')) {
            $this->_postErrors[] = FyndiqTranslation::get('Username is required');
        }

        if (!$this->fmPrestashop->toolsGetValue('api_token')) {
            $this->_postErrors[] = FyndiqTranslation::get('API Token is required');
        }

        if (empty($this->fmPrestashop->toolsGetValue('price_percentage'))) {
            $this->_postErrors[] = FyndiqTranslation::get('Price Percentage is required');
        } elseif (!is_numeric($this->fmPrestashop->toolsGetValue('price_percentage'))) {
            $this->_postErrors[] = FyndiqTranslation::get('Price Percentage should be numeric');
        } elseif (intval($this->fmPrestashop->toolsGetValue('price_percentage')) < 1 || intval($this->fmPrestashop->toolsGetValue('price_percentage')) > 100) {
            $this->_postErrors[] = FyndiqTranslation::get('Price Percentage should be a number between 1 and 100');
        }

        if (empty($this->fmPrestashop->toolsGetValue('stock_min'))) {
            $this->_postErrors[] = FyndiqTranslation::get('Lowest quantity is required');
        } elseif (!is_numeric($this->fmPrestashop->toolsGetValue('stock_min'))) {
            $this->_postErrors[] = FyndiqTranslation::get('Lowest quantity should be numeric');
        } elseif (intval($this->fmPrestashop->toolsGetValue('stock_min')) < 1) {
            $this->_postErrors[] = FyndiqTranslation::get('Lowest quantity should be more than 1');
        }
    }

    protected function _postProcess()
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

        $res = $this->sendSettings($username, $api_token, $pingToken, $disable_orders);

        if ($res !== 'success') {
            return $this->_html .= $this->fmOutput->showModuleError(FyndiqTranslation::get($res));
        }

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
        ) {
            return $this->_html .= $this->fmOutput->showModuleSuccess(FyndiqTranslation::get('Settings updated'));
        }
        return $this->_html .= $this->fmOutput->showModuleError(FyndiqTranslation::get('Error saving settings'));
    }

    protected function sendSettings($username, $apiToken, $pingToken, $ordersEnable)
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

    public function displayForm($module)
    {
        $fields_form = $this->getSettingsForm();
        $helper = new HelperForm();

        // Modul and token
        $helper->module = $module;
        $helper->name_controller = $module->name;
        $helper->token = $this->fmPrestashop->getAdminTokenLite('AdminModules');

        // Language
        $default_lang = (int)$this->fmPrestashop->configurationGet('PS_LANG_DEFAULT');
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        // Title and toolbar
        $helper->title = $module->displayName;
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

    public function getSettingsForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title'=> FyndiqTranslation::get('Settings'),
                    'icon' => 'icon-cogs'
                ),
                'description' => FyndiqTranslation::get('In order to use this module, you have to select which language you will be using.').
                        FyndiqTranslation::get('The language, you select, will be used when exporting products to Fyndiq').
                        FyndiqTranslation::get('Make sure you select a language that contains Swedish product info!'),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label'=> FyndiqTranslation::get('Username'),
                        'name' => 'username',
                        'desc' => FyndiqTranslation::get('Enter here your fyndiq usernamey'),
                    ),
                    array(
                        'type' => 'text',
                        'label'=> FyndiqTranslation::get('API Token'),
                        'name' => 'api_token',
                        'desc' => FyndiqTranslation::get('Enter here your fyndiq API Token.'),
                    ),
                    array(
                        'type' => 'switch',
                        'label'=> FyndiqTranslation::get('Import Order'),
                        'name' => 'disable_orders',
                        'is_bool'=> true,
                        'desc' => FyndiqTranslation::get('Enable order import from Fyndiq'),
                        'values'=> array(
                                array(
                                    'id' => 'active_on',
                                    'value' => 1,
                                    'label' => FyndiqTranslation::get('Enabled')
                                ),
                                array(
                                    'id' => 'active_off',
                                    'value' => 0,
                                    'label' => FyndiqTranslation::get('Disabled')
                                )
                            ),
                    ),
                    array(
                        'type' => 'select',
                        'label' => FyndiqTranslation::get('Language'),
                        'name' => 'language',
                        'desc' => FyndiqTranslation::get('In order to use this module, you have to select which language you will be using.
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
                        'label' => FyndiqTranslation::get('Percentage in numbers only'),
                        'name' => 'price_percentage',
                        'class' => 'fixed-width-xs',
                        'suffix' => '%',
                        'desc' => FyndiqTranslation::get('This percentage is the percentage of the price that will be cut off your price, if 10% percentage it will be 27 SEK of 30 SEK (10% of 30 SEK is 3 SEK).')
                    ),
                    array(
                        'type' => 'text',
                        'label' => FyndiqTranslation::get('Lowest quantity to send to Fyndiq'),
                        'name' => 'stock_min',
                        'class' => 'fixed-width-xs'
                    ),
                    array(
                        'type' => 'select',
                        'label' => FyndiqTranslation::get('Description to use'),
                        'name' => 'description_type',
                        'options' => array(
                            'query' => $this->getDescriptonTypes(),
                            'id' => 'id',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'select',
                        'label' => FyndiqTranslation::get('Import State'),
                        'name' => 'import_state',
                        'options' => array(
                            'query' => $this->getOrderStates(),
                            'id' => 'id_order_state',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'select',
                        'label' => FyndiqTranslation::get('Done State'),
                        'name' => 'done_state',
                        'options' => array(
                            'query' => $this->getOrderStates(),
                            'id' => 'id_order_state',
                            'name' => 'name'
                        )
                    ),
                ),
                'submit' => array(
                    'title' => FyndiqTranslation::get('Save')
                )
            ),
        );
    }
}
