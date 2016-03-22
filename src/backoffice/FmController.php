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
        $storeId = intval($this->fmPrestashop->getStoreId());
        $languageId = intval($this->fmPrestashop->getLanguageId());
        $output = '';
        if ($this->fmPrestashop->toolsIsSubmit('submit' . $this->module->name)) {
            $postErrors = $this->postValidation();
            foreach ($postErrors as $err) {
                $output .= $this->fmOutput->showModuleError($err);
            }
            if (count($postErrors) === 0) {
                $output .= $this->postProcess($storeId);
            }
        }
        $output .= $this->renderForm($storeId, $languageId);
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
        $postArr['currency'] = intval($this->fmPrestashop->toolsGetValue('currency'));
        $postArr['price_percentage'] = intval($this->fmPrestashop->toolsGetValue('price_percentage'));
        $postArr['import_state'] = intval($this->fmPrestashop->toolsGetValue('import_state'));
        $postArr['done_state'] = intval($this->fmPrestashop->toolsGetValue('done_state'));
        $postArr['stock_min'] = intval($this->fmPrestashop->toolsGetValue('stock_min'));
        $postArr['stock_min'] = $postArr['stock_min'] < 0 ? 0 : $postArr['stock_min'];
        $postArr['customerGroup_id'] = intval($this->fmPrestashop->toolsGetValue('customerGroup_id'));
        $postArr['description_type'] = $this->fmPrestashop->toolsGetValue('description_type');
        $postArr['ean_type'] = $this->fmPrestashop->toolsGetValue('ean_type');
        $postArr['isbn_type'] = $this->fmPrestashop->toolsGetValue('isbn_type');
        $postArr['mpn_type'] = $this->fmPrestashop->toolsGetValue('mpn_type');
        $postArr['brand_type'] = $this->fmPrestashop->toolsGetValue('brand_type');
        $postArr['ping_token'] = $this->fmPrestashop->toolsEncrypt(time());
        $postArr['is_active_cron_task'] = $this->fmPrestashop->toolsGetValue('set_cronjob') ?
            intval($this->fmPrestashop->toolsGetValue('is_active_cron_task')) :
            $this->fmConfig->get('is_active_cron_task', $storeId);
        $postArr['fm_interval'] = $this->fmPrestashop->toolsGetValue('set_cronjob') ?
            intval($this->fmPrestashop->toolsGetValue('fm_interval')) :
            $this->fmConfig->get('fm_interval', $storeId);

        $base = $this->fmPrestashop->getBaseModuleUrl();
        $updateData = array(
                FyndiqUtils::NAME_PRODUCT_FEED_URL =>
                    $base . 'modules/fyndiqmerchant/backoffice/filePage.php?store_id=' .
                    $storeId . '&token=' . $postArr['ping_token'],
                FyndiqUtils::NAME_PING_URL =>
                    $base . 'modules/fyndiqmerchant/backoffice/notification_service.php?event=ping&token=' .
                    $postArr['ping_token'] . '&store_id=' . $storeId,
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

    /**
     * renderForm renders the module settings form
     * @param  int $storeId StoreId
     * @param  int $languageId LanguageId
     * @return string
     */
    public function renderForm($storeId, $languageId)
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

        foreach ($this->fmPrestashop->languageGetLanguages() as $lang) {
            $helper->languages[] = array(
                'id_lang' => $lang['id_lang'],
                'iso_code' => $lang['iso_code'],
                'name' => $lang['name'],
                'is_default' => ($defaultLang == $lang['id_lang'] ? 1 : 0),
            );
        }
        $helper->fields_value = $this->getConfigFieldsValues($storeId);
        $fieldForms = array(
            $this->getGeneralSettingsForm($languageId),
            $this->getFieldsMappingsForm($languageId),
        );

        /** add hidden feature for the Cron task. To see this feature add extra param &set_conjobs=1*/
        if ($this->fmPrestashop->toolsGetValue('set_cronjob')) {
            $fieldForms[] = $this->getCronJobSettingsForm($storeId);
        }
        return $helper->generateForm($fieldForms);
    }

    public function getConfigFieldsValues($storeId)
    {
        $result = array();
        foreach (FmUtils::getConfigKeys() as $key => $value) {
            $configVal = $this->fmConfig->get($key, $storeId);
            $result[$key] = $this->fmPrestashop->toolsGetValue($key, $configVal !==false ? $configVal : $value);
        }
        return $result;
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

    /**
     * getSKUTypes returns the SKU types available
     * @return array
     */
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

    /**
     * getInterval returns the cron running intervals array
     * @return array
     */
    protected function getInterval()
    {
        return array(
            array(
                'id' => FmUtils::CRON_INTERVAL_10,
                'name' => sprintf($this->module->__('%d Minutes'), FmUtils::CRON_INTERVAL_10),
            ),
            array(
                'id' => FmUtils::CRON_INTERVAL_30,
                'name' => sprintf($this->module->__('%d Minutes'), FmUtils::CRON_INTERVAL_30),
            ),
            array(
                'id' => FmUtils::CRON_INTERVAL_60,
                'name' => sprintf($this->module->__('%d Minutes'), FmUtils::CRON_INTERVAL_60),
            ),
        );
    }

    /**
     * getAllProductAndCombinationsFields return all fields defined for products and combinations
     * @return array
     */
    private function getAllProductAndCombinationsFields()
    {
        $allFieldsIds = array_unique(
            array_merge(
                array_keys(
                    $this->fmPrestashop->productGetFields()
                ),
                array_keys(
                    $this->fmPrestashop->combinationGetFields(),
                )
            )
        );
        $fieldsIdsAndNames = array();
        foreach ($allFieldsIds as $fieldId) {
            $fieldsIdsAndNames[] = array(
                'id' => FmFormSetting::serializeMappingValue(FmFormSetting::MAPPING_TYPE_PRODUCT_FIELD, $fieldId),
                'name' => $fieldId,
            );
        }
        return $fieldsIdsAndNames;
    }

    /**
     * getAllProductFeatures returns array containing all product features
     * @param  int $languageId Language id
     * @return array
     */
    private function getAllProductFeatures($languageId)
    {
        $query = 'SELECT id_feature, name
                FROM ' . _DB_PREFIX_ . 'feature_lang
                WHERE id_lang=' . $languageId;
        $queryResults = $this->fmPrestashop->dbGetInstance()->executeS($query);
        $productFeatures = array();
        foreach ($queryResults as $queryResult) {
            $productFeatures[] = array(
                'id' => FmFormSetting::serializeMappingValue(
                    FmFormSetting::MAPPING_TYPE_PRODUCT_FEATURE,
                    $queryResult['id_feature']
                ),
                'name' => $queryResult['name'],
            );
        }
        return $productFeatures;
    }

    /**
     * getAllMappingOptions returns all fields and features
     * @param  int $languageId Language id
     * @return array
     */
    private function getAllMappingOptions($languageId)
    {
        return array_merge(
            $this->getAllProductFeatures($languageId),
            $this->getAllProductAndCombinationsFields()
        );
    }

    /**
     * getGeneralSettingsForm returns the general settings form
     * @param  int $languageId LanguageId
     * @return FmFormSetting
     */
    private function getGeneralSettingsForm($languageId)
    {
        $orderStates = $this->getOrderStates($languageId);
        $customerGroups = $this->fmPrestashop->groupGetGroups($languageId);
        $languages = $this->fmPrestashop->languageGetLanguages();
        $currencies = $this->fmPrestashop->getCurrencies();

        $formSettings = new FmFormSetting();
        return $formSettings
            ->setLegend($this->module->__('Settings'), 'icon-cogs')
            ->setTextField($this->module->__('Username'), 'username', $this->module->__('Enter here your fyndiq username'), '')
            ->setTextField($this->module->__('API Token'), 'api_token', $this->module->__('Enter here your fyndiq API Token.'), '')
            ->setSwitch($this->module->__('Disable Order'), 'disable_orders', $this->module->__('Enable/Disable order import from Fyndiq'))
            ->setSelect($this->module->__('Language'), 'language', $this->module->__('In order to use this module, you have to select which language you will be using.
                                    The language, you select, will be used when exporting products to Fyndiq.
                                    Make sure you select a language that contains Swedish product info!'), $languages, 'id_lang', 'name')
            ->setSelect($this->module->__('Currency'), 'currency', '', $currencies, 'id_currency', 'name')
            ->setTextField($this->module->__('Percentage in numbers only'), 'price_percentage', $this->module->__('This percentage is the percentage of the price that will be cut off your price,
                                             if 10% percentage it will be 27 SEK of 30 SEK (10% of 30 SEK is 3 SEK).'), 'fixed-width-xs')
            ->setTextField($this->module->__('Lowest quantity to send to Fyndiq'), 'stock_min', '', 'fixed-width-xs')
            ->setSelect($this->module->__('Customer Group'), 'customerGroup_id', $this->module->__('Select Customer group to send to fyndiq'), $customerGroups, 'id_group', 'name')
            ->setSelect($this->module->__('Import State'), 'import_state', '', $orderStates, 'id_order_state', 'name')
            ->setSelect($this->module->__('Done State'), 'done_state', '', $orderStates, 'id_order_state', 'name')
            ->setSubmit($this->module->__('Save'))
            ->getFormElementsSettings();
    }

    /**
     * getDescriptionTypes return the description type options
     * @param  array $mappingOptions available mapping options
     * @return array
     */
    protected function getDescriptionTypes($mappingOptions)
    {
        function filterOutDescriptions($var)
        {
            return !in_array($var['id'], array(
                FmFormSetting::serializeMappingValue(
                    FmFormSetting::MAPPING_TYPE_PRODUCT_FIELD,
                    'description_short'
                ),
                FmFormSetting::serializeMappingValue(
                    FmFormSetting::MAPPING_TYPE_PRODUCT_FIELD,
                    'description'
                )
            ));
        }
        $descriptionTypes = array_filter($mappingOptions, 'filterOutDescriptions');

        $extraDescriptionTypes = array(
            array(
                'id' => FmFormSetting::serializeMappingValue(
                    FmFormSetting::MAPPING_TYPE_PRODUCT_FIELD,
                    'description'
                ),
                'name' => $this->module->__('Description')
            ),
            array(
                'id' => FmFormSetting::serializeMappingValue(
                    FmFormSetting::MAPPING_TYPE_PRODUCT_FIELD,
                    'description_short'
                ),
                'name' => $this->module->__('Short description')
            ),
            array(
                'id' => FmFormSetting::serializeMappingValue(
                    FmFormSetting::MAPPING_TYPE_SHORT_AND_LONG_DESCRIPTION,
                    ''
                ),
                'name' => $this->module->__('Short and long description')
            )
        );
        return array_merge($extraDescriptionTypes, $descriptionTypes);
    }

    /**
     * getEANTypes returns the EAN type options
     * @param  array $mappingOptions available mapping options
     * @return array
     */
    private function getEANTypes($mappingOptions)
    {
        function filterOutEAN($var)
        {
            return $var['id'] !== FmFormSetting::serializeMappingValue(
                FmFormSetting::MAPPING_TYPE_PRODUCT_FIELD,
                'ean13'
            );
        }
        $eanTypes = array_filter($mappingOptions, 'filterOutEAN');
        $extraEanTypes = array(
            array(
                'id' => FmFormSetting::serializeMappingValue(
                    FmFormSetting::MAPPING_TYPE_NO_MAPPING,
                    ''
                ),
                'name' => ''
            ),
            array(
                'id' => FmFormSetting::serializeMappingValue(
                    FmFormSetting::MAPPING_TYPE_PRODUCT_FIELD,
                    'ean13'
                ),
                'name' => $this->module->__('EAN')
            ),
        );
        return array_merge($extraEanTypes, $eanTypes);
    }

    /**
     * getISBNTypes returns the ISBN type options
     * @param  array $mappingOptions available mapping options
     * @return array
     */
    private function getISBNTypes($mappingOptions)
    {
        $blankMappingOption = array(
            'id' => FmFormSetting::serializeMappingValue(FmFormSetting::MAPPING_TYPE_NO_MAPPING),
            'name' => ''
        );
        return array_merge(array($blankMappingOption), $allMappingOptions);
    }

    /**
     * getMPNTypes returns the MPN type options
     * @param  array $mappingOptions available mapping options
     * @return array
     */
    private function getMPNTypes($mappingOptions)
    {
        $blankMappingOption = array(
            'id' => FmFormSetting::serializeMappingValue(FmFormSetting::MAPPING_TYPE_NO_MAPPING),
            'name' => ''
        );
        return array_merge(array($blankMappingOption), $allMappingOptions);
    }

    /**
     * getBrandTypes returns the Brand type options
     * @param  array $mappingOptions available mapping options
     * @return array
     */
    private function getBrandTypes($mappingOptions)
    {
        $extraBrandOptions = array(
            array(
                'id' => FmFormSetting::serializeMappingValue(
                    FmFormSetting::MAPPING_TYPE_NO_MAPPING,
                    ''
                ),
                'name' => ''
            ),
            array(
                'id' => FmFormSetting::serializeMappingValue(
                    FmFormSetting::MAPPING_TYPE_MANUFACTURER_NAME,
                    ''
                ),
                'name' => 'Manufacturer name'
            ),
        );
        return array_merge($extraBrandOptions, $allMappingOptions);
    }

    /**
     * getFieldsMappingsForm generates the field mapping form
     * @param  int $languageId LanguageId
     * @return FmFormSetting
     */
    private function getFieldsMappingsForm($languageId)
    {
        $allPossibleMappings = $this->getAllMappingOptions($languageId);
        $formFieldsMappings = new FmFormSetting();
        return $formFieldsMappings
            ->setLegend($this->module->__('Fields mappings'), 'icon-cogs')
            ->setSelect($this->module->__('Description to use'), 'description_type', '', $this->getDescriptionTypes($allPossibleMappings), 'id', 'name')
            ->setSelect($this->module->__('EAN to use'), 'ean_type', '', $this->getEANTypes($allPossibleMappings), 'id', 'name')
            ->setSelect($this->module->__('ISBN to use'), 'isbn_type', '', $this->getISBNTypes($allPossibleMappings), 'id', 'name')
            ->setSelect($this->module->__('MPN to use'), 'mpn_type', '', $this->getMPNTypes($allPossibleMappings), 'id', 'name')
            ->setSelect($this->module->__('Brand to use'), 'brand_type', '', $this->getBrandTypes($allPossibleMappings), 'id', 'name')
            ->setSubmit($this->module->__('Save'))
            ->getFormElementsSettings();
    }

    /**
     * getGeneralSettingsForm generate form for CronJob options settings
     * @return array return form elements
     */
    private function getCronJobSettingsForm($storeId)
    {
        if (!$this->fmPrestashop->configurationGetGlobal('cronjobs_execution_token')) {
            $token = $this->fmPrestashop->toolsEncrypt($this->fmPrestashop->toolsShopDomainSsl().time());
            $this->fmPrestashop->configurationUpdateGlobalValue('cronjobs_execution_token', $token);
        } else {
            $token = $this->fmPrestashop->configurationGetGlobal('cronjobs_execution_token');
        }
        $cronUrl = $this->fmPrestashop->getBaseModuleUrl().'modules/fyndiqmerchant/backoffice/notification_service.php?event=cron_execute'.'&token='.$token;
        $interval = $this->getInterval();
        $isIntervalOptionDisable = true;
        $helpText = $this->module->__('To enable this feature, first of all select Yes and then set Interval. Make sure the curl library is installed on your server. To execute your cron tasks, please insert the following line in your cron tasks manager:');
        $helpText .= '</br></br><ul class="list-unstyled">
                        <li><code>*/10 * * * * curl "'.$cronUrl.'"</code></li>
                    </ul>';

        if ($this->fmConfig->get('is_active_cron_task', $storeId)) {
            $isIntervalOptionDisable = false;
        }
        $formSettings = new FmFormSetting();
        return $formSettings
            ->setLegend($this->module->__('Feed Generator'), 'icon-cogs')
            ->setDescriptions($helpText)
            ->setSwitch($this->module->__('Active Cron Task'), 'is_active_cron_task', $this->module->__('Active/Deactive cron task for this module'))
            ->setSelect($this->module->__('Interval'), 'fm_interval', '', $interval, 'id', 'name', $isIntervalOptionDisable)
            ->setSubmit($this->module->__('Save'))
            ->getFormElementsSettings();
    }
}
