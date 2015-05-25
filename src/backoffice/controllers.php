<?php

class FmBackofficeControllers
{

    const DEFAULT_DISCOUNT_PERCENTAGE = 10;
    const DEFAULT_ORDER_IMPORT_STATE = 3;
    const DEFAULT_ORDER_DONE_STATE = 4;

    public static function main($module)
    {
        $output = '';
        $page = '';
        $pageArgs = array();

        if (Tools::isSubmit('submit_authenticate')) {
            $ret = self::handleAuthentication($module);
            return $ret['output'];
        }         # if no api connection exists yet (first time using module, or user pressed Disconnect Account)
        elseif (!FmHelpers::apiConnectionExists($module)) {
            $page = 'authenticate';

        } else {
            # check if api is up
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

            # if api is up
            if ($apiAvailable) {
                # by default, show main page
                $page = 'main';

                # if user pressed Disconnect Account on main pages
                if (Tools::getValue('disconnect')) {
                    self::handleDisconnect($module);
                    Tools::redirect(FmHelpers::getModuleUrl());
                }

                # if user pressed Show Settings button on main page
                if (Tools::getValue('submit_show_settings')) {
                    $page = 'settings';
                }

                # if user pressed Save Settings button on settings page
                if (Tools::isSubmit('submit_save_settings')) {
                    $ret = self::handleSettings($module);
                    $output .= $ret['output'];
                    if ($ret['error']) {
                        $page = 'settings';
                    }
                }

                # if user pressed Save Settings button on settings page
                if (Tools::isSubmit('order')) {
                    $page = 'order';
                }

                # if not all settings exist yet (first time using module)
                if (!FmHelpers::allSettingsExist()) {
                    $page = 'settings';
                }
            }
        }

        #### render decided page

        if ($page == 'authenticate') {
            $output .= self::showTemplate($module, 'authenticate');
        }

        if ($page == 'api_unavailable') {
            $output .= self::showTemplate(
                $module,
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

            # if there is a configured language, show it as selected
            $selectedLanguage =  $selectedLanguage ? $selectedLanguage : Configuration::get('PS_LANG_DEFAULT');
            $pricePercentage = $pricePercentage ? $pricePercentage : self::DEFAULT_DISCOUNT_PERCENTAGE;
            $orderImportState = $orderImportState ? $orderImportState : self::DEFAULT_ORDER_IMPORT_STATE;
            $orderDoneState = $orderDoneState ? $orderDoneState : self::DEFAULT_ORDER_DONE_STATE;


            $orderStates = OrderState::getOrderStates($context->language->id);
            $states = array_filter($orderStates, array('FmBackofficeControllers', 'orderStateCheck'));


            $output .= self::showTemplate(
                $module,
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
                $module,
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
                $module,
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

    private static function orderStateCheck($state)
    {
        return OrderState::invoiceAvailable($state['id_order_state']);
    }

    private static function handleAuthentication($module)
    {
        $error = false;
        $output = '';

        $username = strval(Tools::getValue('username'));
        $apiToken = strval(Tools::getValue('api_token'));

        # validate parameters
        if (empty($username) || empty($apiToken)) {
            $error = true;
            $output .= $module->displayError(FyndiqTranslation::get('empty-username-token'));

            # ready to perform authentication
        } else {
            # authenticate with Fyndiq API
            try {
                # if no exceptions, authentication is successful
                FmConfig::set('username', $username);
                FmConfig::set('api_token', $apiToken);
                $base = FmHelpers::getBaseModuleUrl();
                $pingToken = Tools::encrypt(time());
                FmConfig::set('ping_token', $pingToken);
                $updateData = array(
                    FyndiqUtils::NAME_PRODUCT_FEED_URL => $base . 'modules/fyndiqmerchant/backoffice/filePage.php',
                    FyndiqUtils::NAME_NOTIFICATION_URL => $base . 'modules/fyndiqmerchant/backoffice/notification_service.php',
                    FyndiqUtils::NAME_PING_URL => $base . 'modules/fyndiqmerchant/backoffice/notification_service.php?event=ping&token=' . $pingToken,
                );
                self::updateFeedUrl($updateData);
                sleep(1);
                Tools::redirect(FmHelpers::getModuleUrl());
            } catch (Exception $e) {
                $error = true;
                $output .= $module->displayError($e->getMessage());

                FmConfig::delete('username');
                FmConfig::delete('api_token');
            }
        }

        return array('error' => $error, 'output' => $output);
    }

    private static function handleSettings()
    {
        $languageId = intval(Tools::getValue('language_id'));
        $pricePercentage = intval(Tools::getValue('price_percentage'));
        $orderImportState = intval(Tools::getValue('order_import_state'));
        $orderDoneState = intval(Tools::getValue('order_done_state'));

        if (FmConfig::set('language', $languageId) &&
            FmConfig::set('price_percentage', $pricePercentage) &&
            FmConfig::set('import_state', $orderImportState) &&
            FmConfig::set('done_state', $orderDoneState)
        ) {
            return array('error' => false, 'output' => '');
        }
        return array('error' => true, 'output' => '');
    }

    private static function handleDisconnect($module)
    {
        # delete stored connection values
        if (FmConfig::delete('username') &&
            FmConfig::delete('api_token')) {
            $output = $module->displayConfirmation(FyndiqTranslation::get('account-disconnected'));
            return array('error' => false, 'output' => $output);
        }
        return array('error' => true, 'output' => '');
    }


    private static function updateFeedUrl($data)
    {
        FmHelpers::callApi('PATCH', 'settings/', $data);
    }

    private static function showTemplate($module, $name, $args = array())
    {
        global $smarty;
        $templateArgs = array_merge(
            $args,
            array(
                'server_path' => _PS_ROOT_DIR_ . '/modules/' . $module->name,
                'module_path' => $module->get('_path'),
                'shared_path' => $module->get('_path') . 'backoffice/includes/shared/',
                'service_path' => $module->get('_path') . 'backoffice/service.php',
            )
        );
        $smarty->assign($templateArgs);
        $smarty->registerPlugin('function', 'fi18n', array('FmBackofficeControllers', 'fi18n'));

        return $module->display($module->name, 'backoffice/frontend/templates/' . $name . '.tpl');
    }

    public static function fi18n($params)
    {
        return FyndiqTranslation::get($params['s']);
    }
}
