<?php

class FmController
{
    private $fmOutput;
    private $fmConfig;
    private $fmPrestashop;
    protected $module;

    public function __construct($fmPrestashop, $fmOutput, $fmConfig, $fmApiModel)
    {
        $this->fmOutput = $fmOutput;
        $this->fmConfig = $fmConfig;
        $this->fmPrestashop = $fmPrestashop;
        $this->fmApiModel = $fmApiModel;
        $this->module = $this->fmPrestashop->moduleGetInstanceByName();
    }

    public function handleRequest()
    {
        $output = '';
        $storeId = $this->fmPrestashop->getStoreId();
        if ($this->fmPrestashop->toolsIsSubmit('submit' . $this->module->name)) {
            $postErrors = $this->postValidation();
            foreach ($postErrors as $err) {
                $output .= $this->fmOutput->showModuleError($err);
            }
            if (count($postErrors) === 0) {
                $output .= $this->postProcess($storeId);
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
            $errors[] = $this->module->__('Username is required');
        }

        if (!$this->fmPrestashop->toolsGetValue('api_token')) {
            $errors[] = $this->module->__('API Token is required');
        }

        if (!empty($percentage) && !is_numeric($percentage)) {
            $errors[] = $this->module->__('Price Percentage must be a number');
        }

        if (!empty($stockMin) && !is_numeric($stockMin)) {
            $errors[] = $this->module->__('Lowest quantity must be a numeber');
        }
        return $errors;
    }

    protected function postProcess($storeId)
    {
        /** Array index name must be same as param's name */
        $postArr = array();
        $postArr['username'] = $this->fmPrestashop->toolsGetValue('username');
        $postArr['api_token'] = $this->fmPrestashop->toolsGetValue('api_token');
        $postArr['disable_orders'] = intval($this->fmPrestashop->toolsGetValue('disable_orders'));
        $postArr['language'] = intval($this->fmPrestashop->toolsGetValue('language'));
        $postArr['price_percentage'] = intval($this->fmPrestashop->toolsGetValue('price_percentage'));
        $postArr['import_state'] = intval($this->fmPrestashop->toolsGetValue('import_state'));
        $postArr['done_state'] = intval($this->fmPrestashop->toolsGetValue('done_state'));
        $postArr['stock_min'] = intval($this->fmPrestashop->toolsGetValue('stock_min'));
        $postArr['stock_min'] = $postArr['stock_min'] < 0 ? 0 : $postArr['stock_min'];
        $postArr['customerGroup_id'] = intval($this->fmPrestashop->toolsGetValue('customerGroup_id'));
        $postArr['description_type'] = intval($this->fmPrestashop->toolsGetValue('description_type'));
        $postArr['ping_token'] = $this->fmPrestashop->toolsEncrypt(time());

        $base = $this->fmPrestashop->getBaseModuleUrl();
        $updateData = array(
                FyndiqUtils::NAME_PRODUCT_FEED_URL =>
                    $base . 'modules/fyndiqmerchant/backoffice/filePage.php?store_id=' . $storeId . '&token=' . $postArr['ping_token'],
                FyndiqUtils::NAME_PING_URL =>
                    $base . 'modules/fyndiqmerchant/backoffice/notification_service.php?event=ping&token=' . $postArr['ping_token'] . '&store_id=' . $storeId,
        );
        if (!$postArr['disable_orders']) {
            $updateData[FyndiqUtils::NAME_NOTIFICATION_URL] =
                $base . 'modules/fyndiqmerchant/backoffice/notification_service.php?event=order_created&store_id=' . $storeId;
        }
        try {
            $this->fmApiModel->callApi('PATCH', 'settings/', $updateData, $postArr['username'], $postArr['api_token']);
        } catch (Exception $e) {
            if ($e instanceof FyndiqAPIUnsupportedStatus) {
                return $this->fmOutput->showModuleError($this->module->__('Currently API is Unavailable'));
            }
            if ($e instanceof FyndiqAPIAuthorizationFailed) {
                return $this->fmOutput->showModuleError($this->module->__('Invalid username or API token'));
            }
            return $this->fmOutput->showModuleError($e->getMessage());
        }

        foreach (FmUtils::getConfigKeys() as $key => $value) {
            if (!$this->fmConfig->set($key, $postArr[$key], $storeId)) {
                return $this->fmOutput->showModuleError($this->module->__('Error saving settings'));
            }
        }
        return $this->fmOutput->showModuleSuccess($this->module->__('Settings updated'));
    }

    public function displayForm($storeId)
    {
        $helper = new HelperForm();

        // Module and token
        $helper->module = $this->module;
        $helper->name_controller = $this->module->name;
        $helper->token = $this->fmPrestashop->getAdminTokenLite('AdminModules');

        // Language
        $defaultLang = intval($this->fmPrestashop->configurationGet('PS_LANG_DEFAULT'));
        $helper->default_form_language = $defaultLang;
        $helper->allow_employee_form_lang = $defaultLang;

        // Title and toolbar
        $helper->title = $this->module->displayName;
        $helper->submit_action = 'submit' . $this->module->name;

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues($storeId),
            'languages' => $this->fmPrestashop->languageGetLanguages(),
            'id_language' => $this->fmPrestashop->getLanguageId(),
        );
        $fieldsForm = $this->getSettingsForm();
        return $helper->generateForm(array($fieldsForm));
    }

    public function getConfigFieldsValues($storeId)
    {
        $result = array();
        foreach (FmUtils::getConfigKeys() as $key => $value) {
            $configVal = $this->fmConfig->get($key, $storeId);
            $result[$key] = $this->fmPrestashop->toolsGetValue($key, $configVal ? $configVal : $value);
        }
        return $result;
    }

    protected function getDescriptonTypes()
    {
        return array(
            array(
                'id' => FmUtils::LONG_DESCRIPTION,
                'name' => $this->module->__('Description'),
            ),
            array(
                'id' => FmUtils::SHORT_DESCRIPTION,
                'name' => $this->module->__('Short description'),
            ),
            array(
                'id' => FmUtils::SHORT_AND_LONG_DESCRIPTION,
                'name' => $this->module->__('Short and long description'),
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
                'name' => $this->module->__('Reference code'),
            ),
            array(
                'id' => FmUtils::SKU_EAN,
                'name' => $this->module->__('EAN'),
            ),
            array(
                'id' => FmUtils::SKU_ID,
                'name' => $this->module->__('Database ID'),
            ),
        );
    }

    protected function getProbes()
    {
        $probes = array(
            array(
                'label' => $this->module->__('Checking for duplicate SKU-s'),
                'action' => 'probe_products',
            ),
            array(
                'label' => $this->module->__('Checking file permissions'),
                'action' => 'probe_file_permissions',
            ),
            array(
                'label' => $this->module->__('Checking database'),
                'action' => 'probe_database',
            ),
            array(
                'label' => $this->module->__('Module integrity'),
                'action' => 'probe_module_integrity',
            ),
            array(
                'label' => $this->module->__('Connection to Fyndiq'),
                'action' => 'probe_connection',
            ),
            array(
                'label' => $this->module->__('Installed modules'),
                'action' => 'probe_modules',
            ),
        );
        return json_encode($probes);

    }

    private function getSettingsForm()
    {
        $languageId = $this->fmPrestashop->getLanguageId();
        $orderStates = $this->getOrderStates($languageId);
        $customerGroups = $this->fmPrestashop->groupGetGroups($languageId);
        $languages = $this->fmPrestashop->languageGetLanguages();
        $desciotionsType = $this->getDescriptonTypes();

        $formSettings = new FmFormSetting();
        return $formSettings
            ->setLegend($this->module->__('Settings'), 'icon-cogs')
            ->setTextField($this->module->__('Username'), 'username', $this->module->__('Enter here your fyndiq username'), '')
            ->setTextField($this->module->__('API Token'), 'api_token', $this->module->__('Enter here your fyndiq API Token.'), '')
            ->setSwitch($this->module->__('Disable Order'), 'disable_orders', $this->module->__('Enable/Disable order import from Fyndiq'))
            ->setSelect($this->module->__('Language'), 'language', $this->module->__('In order to use this module, you have to select which language you will be using.
                                    The language, you select, will be used when exporting products to Fyndiq.
                                    Make sure you select a language that contains Swedish product info!'), $languages, 'id_lang', 'name')
            ->setTextField($this->module->__('Percentage in numbers only'), 'price_percentage', $this->module->__('This percentage is the percentage of the price that will be cut off your price,
                                             if 10% percentage it will be 27 SEK of 30 SEK (10% of 30 SEK is 3 SEK).'), 'fixed-width-xs')
            ->setTextField($this->module->__('Lowest quantity to send to Fyndiq'), 'stock_min', '', 'fixed-width-xs')
            ->setSelect($this->module->__('Customer Group'), 'customerGroup_id', $this->module->__('Select Customer group to send to fyndiq'), $customerGroups, 'id_group', 'name')
            ->setSelect($this->module->__('Description to use'), 'description_type', '', $desciotionsType, 'id', 'name')
            ->setSelect($this->module->__('Import State'), 'import_state', '', $orderStates, 'id_order_state', 'name')
            ->setSelect($this->module->__('Done State'), 'done_state', '', $orderStates, 'id_order_state', 'name')
            ->setSubmit($this->module->__('Save'))
            ->getFormElementsSettings();
    }
}
