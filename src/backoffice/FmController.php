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

    private function main() {
        $this->data['currency'] = $this->fmPrestashop->getCurrency($this->fmConfig->get('currency'));
        return $this->fmOutput->render('main', $this->data);
    }

    private function settings() {
        if ($this->fmPrestashop->toolsIsSubmit('submit_save_settings')) {
            $languageId = intval($this->fmPrestashop->toolsGetValue('language_id'));
            $pricePercentage = intval($this->fmPrestashop->toolsGetValue('price_percentage'));
            $orderImportState = intval($this->fmPrestashop->toolsGetValue('order_import_state'));
            $orderDoneState = intval($this->fmPrestashop->toolsGetValue('order_done_state'));

            if ($this->fmConfig->set('language', $languageId) &&
                $this->fmConfig->set('price_percentage', $pricePercentage) &&
                $this->fmConfig->set('import_state', $orderImportState) &&
                $this->fmConfig->set('done_state', $orderDoneState)
            ) {
                return $this->fmOutput->displayError('Error saving settings');
            }
            $this->fmOutput->redirect(FmHelpers::getModuleUrl());
        }

        $selectedLanguage = $this->fmConfig->get('language');
        $pricePercentage = $this->fmConfig->get('price_percentage');
        $orderImportState = $this->fmConfig->get('import_state');
        $orderDoneState = $this->fmConfig->get('done_state');

        // if there is a configured language, show it as selected
        $selectedLanguage =  $selectedLanguage ?
            $selectedLanguage :
            $this->fmPrestashop->configurationGet('PS_LANG_DEFAULT');
        $pricePercentage = $pricePercentage ? $pricePercentage : self::DEFAULT_DISCOUNT_PERCENTAGE;
        $orderImportState = $orderImportState ? $orderImportState : self::DEFAULT_ORDER_IMPORT_STATE;
        $orderDoneState = $orderDoneState ? $orderDoneState : self::DEFAULT_ORDER_DONE_STATE;

        $languageId = $this->fmPrestashop->getLanguageId();
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

    private function orders() {
        $importDate = $this->fmConfig->get('import_date');
        $isToday = date('Ymd') === date('Ymd', strtotime($importDate));
        $this->data['import_date'] = $importDate;
        $this->data['isToday'] = $isToday;
        $this->data['import_time'] = date('G:i:s', strtotime($importDate));
        return $this->fmOutput->render('orders', $this->data);
    }

    private function disconnect() {
        // delete stored connection values
        if ($this->fmConfig->delete('username') &&
            $this->fmConfig->delete('api_token')) {
            return $this->fmOutput->displayError('Error disconnecting account');
        }
        return $this->fmOutput->redirect(FmHelpers::getModuleUrl());
    }

    private function updateFeedUrl($data)
    {
        FmHelpers::callApi('PATCH', 'settings/', $data);
    }
}
