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
        $page_args = array();

        if (Tools::isSubmit('submit_authenticate')) {
            $ret = self::handle_authentication($module);
            return $ret['output'];
        }

        # if no api connection exists yet (first time using module, or user pressed Disconnect Account)
        elseif (!FmHelpers::api_connection_exists($module)) {
            $page = 'authenticate';

        } else {
            # check if api is up
            $api_available = false;
            try {
                FmHelpers::call_api('GET', 'settings/');
                $api_available = true;
            } catch (Exception $e) {
                if ($e->getMessage() == 'Unauthorized') {
                    $page = 'authenticate';
                } else {
                    $page = 'api_unavailable';
                    $page_args['message'] = $e->getMessage();
                }
            }

            # if api is up
            if ($api_available) {

                # by default, show main page
                $page = 'main';

                # if user pressed Disconnect Account on main pages
                if (Tools::getValue('disconnect')) {
                    self::handle_disconnect($module);
                    Tools::redirect(FmHelpers::get_module_url());
                }

                # if user pressed Show Settings button on main page
                if (Tools::getValue('submit_show_settings')) {
                    $page = 'settings';
                }

                # if user pressed Save Settings button on settings page
                if (Tools::isSubmit('submit_save_settings')) {
                    $ret = self::handle_settings($module);
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
                if (!FmHelpers::all_settings_exist()) {
                    $page = 'settings';
                }
            }
        }

        #### render decided page

        if ($page == 'authenticate') {
            $output .= self::show_template($module, 'authenticate');
        }

        if ($page == 'api_unavailable') {
            $output .= self::show_template(
                $module,
                'api_unavailable',
                $page_args
            );
        }

        if ($page == 'settings') {
            $selectedLanguage = FmConfig::get('language');
            $pricePercentage = FmConfig::get('price_percentage');
            $orderImportState = FmConfig::get('import_state');
            $orderDoneState = FmConfig::get('done_state');


            $path = FmHelpers::get_module_url();
            $context = Context::getContext();

            # if there is a configured language, show it as selected
            $selectedLanguage =  $selectedLanguage ? $selectedLanguage : Configuration::get('PS_LANG_DEFAULT');
            $pricePercentage = $pricePercentage ? $pricePercentage : self::DEFAULT_DISCOUNT_PERCENTAGE;
            $orderImportState = $orderImportState ? $orderImportState : self::DEFAULT_ORDER_IMPORT_STATE;
            $orderDoneState = $orderDoneState ? $orderDoneState : self::DEFAULT_ORDER_DONE_STATE;


            $orderStates = OrderState::getOrderStates($context->language->id);
            $states = array_filter($orderStates, array('FmBackofficeControllers', 'orderStateCheck'));


            $output .= self::show_template(
                $module,
                'settings',
                array(
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
            $path = FmHelpers::get_module_url();
            $output .= self::show_template(
                $module,
                'main',
                array(
                    'messages' => FmMessages::get_all(),
                    'language' => new Language(FmConfig::get('language')),
                    'currency' => new Currency(FmConfig::get('currency')),
                    'username' => FmConfig::get('username'),
                    'path' => $path
                )
            );
        }
        if ($page == 'order') {
            $path = FmHelpers::get_module_url();
            $import_date = FmConfig::get('import_date');
            $isToday = date('Ymd') === date('Ymd', strtotime($import_date));
            $output .= self::show_template(
                $module,
                'order',
                array(
                    'import_date' => $import_date,
                    'isToday' => $isToday,
                    'import_time' => date('G:i:s', strtotime($import_date)),
                    'messages' => FmMessages::get_all(),
                    'path' => $path
                )
            );
        }

        return $output;
    }

    private static function orderStateCheck($state) {
        return !OrderState::invoiceAvailable($state['id_order_state']);
    }

    private static function handle_authentication($module)
    {

        $error = false;
        $output = '';

        $username = strval(Tools::getValue('username'));
        $api_token = strval(Tools::getValue('api_token'));

        # validate parameters
        if (empty($username) || empty($api_token)) {
            $error = true;
            $output .= $module->displayError($module->l(FmMessages::get('empty-username-token')));

            # ready to perform authentication
        } else {

            # authenticate with Fyndiq API
            try {
                # if no exceptions, authentication is successful
                FmConfig::set('username', $username);
                FmConfig::set('api_token', $api_token);
                self::_updateFeedurl(FmHelpers::get_module_url(false) . 'modules/fyndiqmerchant/backoffice/filePage.php');
                sleep(1);
                Tools::redirect(FmHelpers::get_module_url());
            } catch (Exception $e) {
                $error = true;
                $output .= $module->displayError($module->l($e->getMessage()));

                FmConfig::delete('username');
                FmConfig::delete('api_token');
            }
        }

        return array('error' => $error, 'output' => $output);
    }

    private static function handle_settings($module)
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

    private static function handle_disconnect($module)
    {
        # delete stored connection values
        if (FmConfig::delete('username') &&
            FmConfig::delete('api_token')) {
            $output = $module->displayConfirmation($module->l(FmMessages::get('account-disconnected')));
            return array('error' => false, 'output' => $output);
        }
        return array('error' => true, 'output' => '');
    }


    private static function _updateFeedurl($path)
    {
        $object = new stdClass();
        $object->product_feed_url = $path;
        FmHelpers::call_api('PATCH', 'settings/', $object);
    }

    private static function show_template($module, $name, $args = array())
    {
        global $smarty;

        $template_args = array_merge(
            $args,
            array(
                'server_path' => dirname(dirname($_SERVER['SCRIPT_FILENAME'])) . '/modules/' . $module->name,
                'module_path' => $module->get('_path'),
                'shared_path' => $module->get('_path') . 'backoffice/includes/shared/',
                'service_path' => $module->get('_path') . 'backoffice/service.php',
            )
        );
        $smarty->assign($template_args);

        return $module->display($module->name, 'backoffice/frontend/templates/' . $name . '.tpl');
    }
}
