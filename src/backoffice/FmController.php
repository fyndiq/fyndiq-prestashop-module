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

    public function __construct($fmPrestashop, $fmOutput, $fmConfig)
    {
        $this->fmOutput = $fmOutput;
        $this->fmConfig = $fmConfig;
        $this->fmPrestashop = $fmPrestashop;

        $path = $fmPrestashop->getModuleUrl();
        $this->data = array(
            'json_messages' => json_encode(FyndiqTranslation::getAll()),
            'messages' => FyndiqTranslation::getAll(),
            'path' => $path
        );
    }

    // TODO: FIXME
    private function serviceIsOperational($action) {
        return $action;
    }

    public function handleRequest()
    {
        $action = $this->fmPrestashop->toolsGetValue('action');
        $action = $action ? $action : 'main';

        // Force authorize if not authorized
        $action = $this->fmConfig->isAuthorized() ? $action : 'authorize';
        // Force setup if not set up
        $action = $this->fmConfig->isSetUp() ? $action : 'settings';
        $action = $action != 'authorize' ? $this->serviceIsOperational($action) : $action;

        switch($action) {
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
            case 'service':
                return $this->service();
            default:
                return $this->fmOutput->showError(404, 'Not Found', '404 Not Found');
        }
    }

    private function apiUnavailable() {
        return $this->fmOutput->render('api_unavailable', $this->data);
    }

    private function main() {
        $this->data['currency'] = $this->fmPrestashop->getCurrency($this->fmConfig->get('currency'));
        return $this->fmOutput->render('main', $this->data);
    }

    private function authenticate() {
        if ($this->fmPrestashop->toolsIsSubmit('submit_authenticate')) {
            $username = strval($this->fmPrestashop->toolsGetValue('username'));
            $apiToken = strval($this->fmPrestashop->toolsGetValue('api_token'));
            // validate parameters
            if (empty($username) || empty($apiToken)) {
                return $this->fmOutput->displayError(FyndiqTranslation::get('empty-username-token'));
            }
            $this->fmConfig->set('username', $username);
            $this->fmConfig->set('api_token', $apiToken);
            $base = $this->fmPrestashop->getBaseModuleUrl();
            $pingToken = $this->fmPrestashop->toolsEncrypt(time());
            $this->fmConfig->set('ping_token', $pingToken);
            $updateData = array(
                FyndiqUtils::NAME_PRODUCT_FEED_URL =>
                    $base . 'modules/fyndiqmerchant/backoffice/filePage.php',
                FyndiqUtils::NAME_NOTIFICATION_URL =>
                    $base . 'modules/fyndiqmerchant/backoffice/notification_service.php',
                FyndiqUtils::NAME_PING_URL =>
                    $base . 'modules/fyndiqmerchant/backoffice/notification_service.php?event=ping&token=' . $pingToken,
            );
            try {
                $this->updateFeedUrl($updateData);
                sleep(1);
                $this->fmOutput->redirect(FmHelpers::getModuleUrl());
            } catch (Exception $e) {
                $this->fmConfig->delete('username');
                $this->fmConfig->delete('api_token');
                return $this->fmOutput->displayError($e->getMessage());
            }
        }
        return $this->fmOutput->render('authenticate', $this->data);
    }

    private function settings() {
        $selectedLanguage = $this->fmConfig->get('language');
        $pricePercentage = $this->fmConfig->get('price_percentage');
        $orderImportState = $this->fmConfig->get('import_state');
        $orderDoneState = $this->fmConfig->get('done_state');

        $languageId = $this->fmPrestashop->getLanguageId();

        // if there is a configured language, show it as selected
        $selectedLanguage =  $selectedLanguage ?
            $selectedLanguage :
            $this->fmPrestashop->configurationGet('PS_LANG_DEFAULT');
        $pricePercentage = $pricePercentage ? $pricePercentage : self::DEFAULT_DISCOUNT_PERCENTAGE;
        $orderImportState = $orderImportState ? $orderImportState : self::DEFAULT_ORDER_IMPORT_STATE;
        $orderDoneState = $orderDoneState ? $orderDoneState : self::DEFAULT_ORDER_DONE_STATE;

        $orderStates = $this->fmPrestashop->orderStateGetOrderStates($languageId);

        $states = array();
        foreach($orderStates as $orderState) {
            if ($this->fmPrestashop->orderStateInvoiceAvailable($orderState['id_order_state'])){
                $states[] = $orderState;
            }
        }

        $this->data['languages'] = $this->fmPrestashop->languageGetLanguages();
        $this->data['price_percentage'] = $pricePercentage;
        $this->data['selected_language'] = $selectedLanguage;
        $this->data['order_states'] = $states;
        $this->data['order_import_state'] = $orderImportState;
        $this->data['order_done_state'] = $orderDoneState;

        return $this->fmOutput->render('settings', $this->data);
    }

/*


        $output = '';
        $page = '';
        $pageArgs = array();

        if ($this->fmPrestashop->toolsIsSubmit('submit_authenticate')) {
            $ret = $this->handleAuthentication();
            return $ret['output'];
        } elseif (!$this->fmConfig->apiConnectionExists($this->module)) {
            // if no api connection exists yet (first time using module, or user pressed Disconnect Account)
            $page = 'authenticate';
        } else {
            // check if api is up
            $apiAvailable = false;
            try {
                FmHelpers::callApi('GET', 'settings/');
                $apiAvailable = true;
            } catch (Exception $e) {
                if ($e->getMessage() == 'Unauthorized') {
                    $page = 'authenticate';
                } else {
                    $page = 'api_unavailable';
                    $pageArgs['message'] = $e->getMessage();
                }
            }

            // if api is up
            if ($apiAvailable) {
                // by default, show main page
                $page = 'main';

                // if user pressed Disconnect Account on main pages
                if ($this->fmPrestashop->toolsGetValue('disconnect')) {
                    $this->handleDisconnect();
                    return $this->fmPrestashop->redirect(FmHelpers::getModuleUrl());
                }

                // if user pressed Show Settings button on main page
                if ($this->fmPrestashop->toolsGetValue('submit_show_settings')) {
                    $page = 'settings';
                }

                // if user pressed Save Settings button on settings page
                if ($this->fmPrestashop->toolsIsSubmit('submit_save_settings')) {
                    $ret = $this->handleSettings();
                    $output .= $ret['output'];
                    if ($ret['error']) {
                        $page = 'settings';
                    }
                }

                // if user pressed Save Settings button on settings page
                if ($this->fmPrestashop->toolsIsSubmit('order')) {
                    $page = 'order';
                }

                // if not all settings exist yet (first time using module)
                if (!FmHelpers::allSettingsExist()) {
                    $page = 'settings';
                }
            }
        }

        // render decided page

        if ($page == 'authenticate') {
            return $this->showTemplate('authenticate');
        }

        if ($page == 'api_unavailable') {
            $output .= $this->showTemplate(
                'api_unavailable',
                $pageArgs
            );
        }

        if ($page == 'settings') {
            $selectedLanguage = FmConfig::get('language');
            $pricePercentage = FmConfig::get('price_percentage');
            $orderImportState = FmConfig::get('import_state');
            $orderDoneState = FmConfig::get('done_state');


            $path = FmHelpers::getModuleUrl();
            $context = Context::getContext();

            // if there is a configured language, show it as selected
            $selectedLanguage =  $selectedLanguage ? $selectedLanguage : Configuration::get('PS_LANG_DEFAULT');
            $pricePercentage = $pricePercentage ? $pricePercentage : self::DEFAULT_DISCOUNT_PERCENTAGE;
            $orderImportState = $orderImportState ? $orderImportState : self::DEFAULT_ORDER_IMPORT_STATE;
            $orderDoneState = $orderDoneState ? $orderDoneState : self::DEFAULT_ORDER_DONE_STATE;


            $orderStates = OrderState::getOrderStates($context->language->id);
            $states = array_filter($orderStates, array('FmController', 'orderStateCheck'));

            $output .= self::showTemplate(
                'settings',
                array(
                    'json_messages' => json_encode(FyndiqTranslation::getAll()),
                    'messages' => FyndiqTranslation::getAll(),
                    'price_percentage' => $pricePercentage,
                    'languages' => Language::getLanguages(),
                    'selected_language' => $selectedLanguage,
                    'order_states' => $states,
                    'order_import_state' => $orderImportState,
                    'order_done_state' => $orderDoneState,
                    'path' => $path
                )
            );
        }
        if ($page == 'main') {
            $path = FmHelpers::getModuleUrl();
            $output .= self::showTemplate(
                'main',
                array(
                    'json_messages' => json_encode(FyndiqTranslation::getAll()),
                    'messages' => FyndiqTranslation::getAll(),
                    'language' => new Language(FmConfig::get('language')),
                    'currency' => new Currency(FmConfig::get('currency')),
                    'username' => FmConfig::get('username'),
                    'path' => $path
                )
            );
        }
        if ($page == 'order') {
            $path = FmHelpers::getModuleUrl();
            $importDate = FmConfig::get('import_date');
            $isToday = date('Ymd') === date('Ymd', strtotime($importDate));
            $output .= self::showTemplate(
                'order',
                array(
                    'import_date' => $importDate,
                    'isToday' => $isToday,
                    'import_time' => date('G:i:s', strtotime($importDate)),
                    'json_messages' => json_encode(FyndiqTranslation::getAll()),
                    'messages' => FyndiqTranslation::getAll(),
                    'path' => $path
                )
            );
        }

        return $output;
    }
*/


    private static function handleSettings()
    {
        $languageId = intval($this->fmPrestashop->toolsGetValue('language_id'));
        $pricePercentage = intval($this->fmPrestashop->toolsGetValue('price_percentage'));
        $orderImportState = intval($this->fmPrestashop->toolsGetValue('order_import_state'));
        $orderDoneState = intval($this->fmPrestashop->toolsGetValue('order_done_state'));

        if (FmConfig::set('language', $languageId) &&
            FmConfig::set('price_percentage', $pricePercentage) &&
            FmConfig::set('import_state', $orderImportState) &&
            FmConfig::set('done_state', $orderDoneState)
        ) {
            return array('error' => false, 'output' => '');
        }
        return array('error' => true, 'output' => '');
    }

    private function handleDisconnect()
    {
        // delete stored connection values
        if (FmConfig::delete('username') &&
            FmConfig::delete('api_token')) {
            $output = $this->module->displayConfirmation(FyndiqTranslation::get('account-disconnected'));
            return array('error' => false, 'output' => $output);
        }
        return array('error' => true, 'output' => '');
    }


    private function updateFeedUrl($data)
    {
        FmHelpers::callApi('PATCH', 'settings/', $data);
    }
}
