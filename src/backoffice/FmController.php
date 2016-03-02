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
        $postArr['is_active_cron_task'] = $this->fmPrestashop->toolsGetValue('set_cronjobs') ?
                                            intval($this->fmPrestashop->toolsGetValue('is_active_cron_task'))
                                            : $this->fmConfig->get('is_active_cron_task', $storeId);

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
        if (!$this->setCronJobs($updateData[FyndiqUtils::NAME_PRODUCT_FEED_URL], $postArr['is_active_cron_task'])) {
            return $this->fmOutput->showModuleError($this->module->__('Error adding cron task to the prestashop webservice'));
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

        foreach ($this->fmPrestashop->languageGetLanguages() as $lang) {
            $helper->languages[] = array(
                'id_lang' => $lang['id_lang'],
                'iso_code' => $lang['iso_code'],
                'name' => $lang['name'],
                'is_default' => ($defaultLang == $lang['id_lang'] ? 1 : 0),
            );
        }
        $helper->fields_value = $this->getConfigFieldsValues($storeId);
        $fieldsForms[] =  $this->getGeneralSettingsForm();

        /** add hidden feature for the Cron task. To see this feature add extra param &set_conjobs=1*/
        if ($this->fmPrestashop->toolsGetValue('set_cronjobs')) {
            $fieldsForms[] = $this->getCronJobSettingsForm();
        }
        return $helper->generateForm($fieldsForms);
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

    private function getGeneralSettingsForm()
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

    /**
     * getGeneralSettingsForm generate form for CronJob options settings
     * @return array return form elements
     */
    private function getCronJobSettingsForm()
    {
        $isDisabled= false;
        $alertMsg= $this->checkCronjobsAvailableToUse();
        if ($alertMsg) {
            $isDisabled= true;
        }
        $formSettings = new FmFormSetting();
        return $formSettings
            ->setLegend($this->module->__('Feed Generator'), 'icon-cogs')
            ->setDescriptions($this->module->__($alertMsg))
            ->setSwitch($this->module->__('Active Cron Task'), 'is_active_cron_task', $this->module->__('Active/Deactive cron task for this module'), $isDisabled)
            ->setSubmit($this->module->__('Save'))
            ->getFormElementsSettings();
    }

    /**
     * checkCronjobsAvailableToUse. check whether Prestashop Cron jobs module is available or not to add cron task using fyndiq module
     * @return string return empty string if available
     */
    private function checkCronjobsAvailableToUse()
    {
        if (!$this->fmPrestashop->isModuleInstalled('cronjobs')) {
            return 'Prestashop Cron task manager module is not installed!! In order to use this option you have to install Cron task manager module from Modules and Services.';
        }
        if (!$this->fmPrestashop->isModuleEnabled('cronjobs')) {
            return 'Prestashop Cron task manager module is not Enabled!! In order to use this option you have to enable Cron task manager module from Modules and Services.';
        }
        if ($this->fmPrestashop->configurationGet('CRONJOBS_MODE') !== 'webservice') {
            return 'Use the PrestaShop cron tasks webservice to enable this option. Go to Cron task Manager from modules and services then change the Cron mode to Basic';
        }
        return '';
    }

    /**
     * setCronJobs, add/update a Cron task to the prestashop cron task manager.
     * @param string  $url    cron task URL
     * @param boolean $active
     * @return boolean
     */
    private function setCronJobs($url, $active = false)
    {
        $description = "Fyndiq Product feed";
        $task = urlencode($url);
        $hour = -1;
        $day = -1;
        $month = -1;
        $day_of_week = -1;
        $tableName = 'cronjobs';
        $result = $this->fmPrestashop->dbGetInstance()->getRow('SELECT id_cronjob FROM '._DB_PREFIX_.$tableName.'
            WHERE `description` = \''.$description.'\' AND `hour` = \''.$hour.'\' AND `day` = \''.$day.'\'
            AND `month` = \''.$month.'\' AND `day_of_week` = \''.$day_of_week.'\'');
        if ($result == false) {
            $context = $this->fmPrestashop->contextGetContext();
            $id_shop = (int)$context->shop->id;
            $id_shop_group = (int)$context->shop->id_shop_group;
            $query = 'INSERT INTO '._DB_PREFIX_.$tableName.'
                (`description`, `task`, `hour`, `day`, `month`, `day_of_week`, `updated_at`, `active`, `id_shop`, `id_shop_group`)
                VALUES (\''.$description.'\', \''.$task.'\', \''.$hour.'\', \''.$day.'\', \''.$month.'\', \''.$day_of_week.'\', NULL,'.$active.', '.$id_shop.', '.$id_shop_group.')';
            if (($result = $this->fmPrestashop->dbGetInstance()->execute($query)) != false) {
                return true;
            }
            return false;
        }
        return $this->fmPrestashop->dbGetInstance()->execute('UPDATE '._DB_PREFIX_.$tableName.'
                    SET `active` = '. $active.' WHERE `id_cronjob` = \''.(int)$result['id_cronjob'].'\'');
    }
}
