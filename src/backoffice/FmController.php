<?php

class FmController
{
    private $fmOutput;
    private $fmConfig;
    private $fmPrestashop;
    protected $_module;

    public function __construct($fmPrestashop, $fmOutput, $fmConfig, $fmApiModel)
    {
        $this->fmOutput = $fmOutput;
        $this->fmConfig = $fmConfig;
        $this->fmPrestashop = $fmPrestashop;
        $this->fmApiModel = $fmApiModel;
        $this->_module = $this->fmPrestashop->moduleGetInstanceByName();
    }

    public function handleRequest()
    {
        $output = '';
        $patchVersion = $this->fmConfig->get('patch_version', 0);
        $this->patchTables($patchVersion, 0);
        $storeId = $this->fmPrestashop->getStoreId();
        if ($this->fmPrestashop->toolsIsSubmit('submit'.$this->_module->name)) {
            $postErrors = $this->postValidation();
            if (!count($postErrors)) {
                $output .= $this->postProcess($storeId);
            }
            foreach ($postErrors as $err) {
                $output .= $this->fmOutput->showModuleError($err);
            }
        }
        $output .= $this->displayForm($storeId);
        return $output;
    }

    protected function postValidation()
    {
        $errors = array();
        $percentage = $this->fmPrestashop->toolsGetValue('price_percentage');
        $stockMin = $this->fmPrestashop->toolsGetValue('stock_min');

        if (!$this->fmPrestashop->toolsGetValue('username')) {
            $errors[] = $this->_module->__('Username is required');
        }

        if (!$this->fmPrestashop->toolsGetValue('api_token')) {
            $errors[] = $this->_module->__('API Token is required');
        }

        if (!empty($percentage) && !is_numeric($percentage)) {
            $errors[] = $this->_module->__('Price Percentage should be numeric');
        }

        if (!empty($stockMin) && !is_numeric($stockMin)) {
            $errors[] = $this->_module->__('Lowest quantity should be numeric');
        }
        return $errors;
    }

    protected function postProcess($storeId)
    {
        $username = $this->fmPrestashop->toolsGetValue('username');
        $api_token = $this->fmPrestashop->toolsGetValue('api_token');
        $disable_orders = intval($this->fmPrestashop->toolsGetValue('disable_orders'));
        $languageId = intval($this->fmPrestashop->toolsGetValue('language'));
        $pricePercentage = intval($this->fmPrestashop->toolsGetValue('price_percentage'));
        $orderImportState = intval($this->fmPrestashop->toolsGetValue('import_state'));
        $orderDoneState = intval($this->fmPrestashop->toolsGetValue('done_state'));
        $stockMin = intval($this->fmPrestashop->toolsGetValue('stock_min'));
        $stockMin = $stockMin < 0 ? 0 : $stockMin;
        $descriptionType = intval($this->fmPrestashop->toolsGetValue('description_type'));
        $pingToken = $this->fmPrestashop->toolsEncrypt(time());

        $base = $this->fmPrestashop->getBaseModuleUrl();
        $updateData = array(
                FyndiqUtils::NAME_PRODUCT_FEED_URL =>
                    $base . 'modules/fyndiqmerchant/backoffice/filePage.php?store_id=' . $storeId . '&token=' . $pingToken,
                FyndiqUtils::NAME_PING_URL =>
                    $base . 'modules/fyndiqmerchant/backoffice/notification_service.php?event=ping&token=' . $pingToken . '&store_id=' . $storeId,
        );
        if (!$disable_orders) {
            $updateData[FyndiqUtils::NAME_NOTIFICATION_URL] =
                $base . 'modules/fyndiqmerchant/backoffice/notification_service.php?event=order_created&store_id=' . $storeId;
        }
        try {
            $this->fmApiModel->callApi('PATCH', 'settings/', $updateData, $username, $api_token);
        } catch (Exception $e) {
            if ($e instanceof FyndiqAPIUnsupportedStatus) {
                return $this->fmOutput->showModuleError($this->_module->__('Currently API is Unavailable'));
            }
            if ($e instanceof FyndiqAPIAuthorizationFailed) {
                return $this->fmOutput->showModuleError($this->_module->__('Invalid username or API token'));
            }
            return $this->fmOutput->showModuleError($this->_module->__($e->getMessage()));
        }
        if ($this->fmConfig->set('username', $username, $storeId) &&
            $this->fmConfig->set('api_token', $api_token, $storeId) &&
            $this->fmConfig->set('disable_orders', $disable_orders, $storeId) &&
            $this->fmConfig->set('language', $languageId, $storeId) &&
            $this->fmConfig->set('price_percentage', $pricePercentage, $storeId) &&
            $this->fmConfig->set('import_state', $orderImportState, $storeId) &&
            $this->fmConfig->set('done_state', $orderDoneState, $storeId) &&
            $this->fmConfig->set('stock_min', $stockMin, $storeId) &&
            $this->fmConfig->set('description_type', $descriptionType, $storeId) &&
            $this->fmConfig->set('ping_token', $pingToken, $storeId)
        ) {
            return $this->fmOutput->showModuleSuccess($this->_module->__('Settings updated'));
        }
        return $this->fmOutput->showModuleError($this->_module->__('Error saving settings'));
    }

    public function displayForm($storeId)
    {
        $fields_form = $this->getSettingsForm();
        $helper = new HelperForm();

        // Modul and token
        $helper->module = $this->_module;
        $helper->name_controller = $this->_module->name;
        $helper->token = $this->fmPrestashop->getAdminTokenLite('AdminModules');

        // Language
        $default_lang = (int)$this->fmPrestashop->configurationGet('PS_LANG_DEFAULT');
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        // Title and toolbar
        $helper->title = $this->_module->displayName;
        $helper->submit_action = 'submit'.$this->_module->name;

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues($storeId),
            'languages' => $this->fmPrestashop->configurationGet('PS_LANG_DEFAULT'),
            'id_language' => $this->fmPrestashop->getLanguageId()
        );
        return $helper->generateForm(array($fields_form));
    }

    public function getConfigFieldsValues($storeId)
    {
        return array(
            'username' => $this->fmPrestashop->toolsGetValue('username', $this->fmConfig->get('username', $storeId)),
            'api_token' => $this->fmPrestashop->toolsGetValue('api_token', $this->fmConfig->get('api_token', $storeId)),
            'disable_orders' => $this->fmPrestashop->toolsGetValue('disable_orders', $this->fmConfig->get('disable_orders', $storeId)),
            'language' => $this->fmPrestashop->toolsGetValue('language', $this->fmConfig->get('language', $storeId)),
            'price_percentage' => $this->fmPrestashop->toolsGetValue('price_percentage', $this->fmConfig->get('price_percentage', $storeId)),
            'stock_min' => $this->fmPrestashop->toolsGetValue('stock_min', $this->fmConfig->get('stock_min', $storeId)),
            'description_type' => $this->fmPrestashop->toolsGetValue('description_type', $this->fmConfig->get('description_type', $storeId)),
            'import_state' => $this->fmPrestashop->toolsGetValue('import_state', $this->fmConfig->get('import_state', $storeId)),
            'done_state' => $this->fmPrestashop->toolsGetValue('done_state', $this->fmConfig->get('done_state', $storeId))
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
                'name' => $this->_module->__('Description'),
            ),
            array(
                'id' => FmUtils::SHORT_DESCRIPTION,
                'name' => $this->_module->__('Short description'),
            ),
            array(
                'id' => FmUtils::SHORT_AND_LONG_DESCRIPTION,
                'name' => $this->_module->__('Short and long description'),
            ),
        );
    }

    protected function getOrderStates($languageId)
    {
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
                'name' => $this->_module->__('Reference code'),
            ),
            array(
                'id' => FmUtils::SKU_EAN,
                'name' => $this->_module->__('EAN'),
            ),
            array(
                'id' => FmUtils::SKU_ID,
                'name' => $this->_module->__('Database ID'),
            ),
        );
    }

    protected function getProbes()
    {
        $probes = array(
            array(
                'label' => $this->_module->__('Checking for duplicate SKU-s'),
                'action' => 'probe_products',
            ),
            array(
                'label' => $this->_module->__('Checking file permissions'),
                'action' => 'probe_file_permissions',
            ),
            array(
                'label' => $this->_module->__('Checking database'),
                'action' => 'probe_database',
            ),
            array(
                'label' => $this->_module->__('Module integrity'),
                'action' => 'probe_module_integrity',
            ),
            array(
                'label' => $this->_module->__('Connection to Fyndiq'),
                'action' => 'probe_connection',
            ),
            array(
                'label' => $this->_module->__('Installed modules'),
                'action' => 'probe_modules',
            ),
        );
        return json_encode($probes);

    }

    private function getSettingsForm()
    {
        $languageId = $this->fmPrestashop->getLanguageId();
        $orderStates = $this->getOrderStates($languageId);
        $languages = $this->fmPrestashop->languageGetLanguages();
        $desciotionsType = $this->getDescriptonTypes();

        $formSettings = new FmFormSetting($this->_module);
        $formSettings->setLegend('Settings', 'icon-cogs');
        $formSettings->setDescriptions('In order to use this module, you have to select which language you will be using.
                                        The language, you select, will be used when exporting products to Fyndiq Make sure
                                        you select a language that contains Swedish product info!');
        $formSettings->setTextField('Username', 'username', 'Enter here your fyndiq username', '');
        $formSettings->setTextField('API Token', 'api_token', 'Enter here your fyndiq API Token.', '');
        $formSettings->setSwitch('Disable Order', 'disable_orders', 'Enable/Disable order import from Fyndiq');
        $formSettings->setSelect('Language', 'language', 'In order to use this module, you have to select which language you will be using.
                                The language, you select, will be used when exporting products to Fyndiq.
                                Make sure you select a language that contains Swedish product info!', $languages, 'id_lang', 'name');
        $formSettings->setTextField('Percentage in numbers only', 'price_percentage', 'This percentage is the percentage of the price that will be cut off your price,
                                         if 10% percentage it will be 27 SEK of 30 SEK (10% of 30 SEK is 3 SEK).', 'fixed-width-xs');
        $formSettings->setTextField('Lowest quantity to send to Fyndiq', 'stock_min', '', 'fixed-width-xs');
        $formSettings->setSelect('Description to use', 'description_type', '', $desciotionsType, 'id', 'name');
        $formSettings->setSelect('Import State', 'import_state', '', $orderStates, 'id_order_state', 'name');
        $formSettings->setSelect('Done State', 'done_state', '', $orderStates, 'id_order_state', 'name');
        $formSettings->setSubmit('Save');

        return $formSettings->getFormElementsSettings();
    }
}
