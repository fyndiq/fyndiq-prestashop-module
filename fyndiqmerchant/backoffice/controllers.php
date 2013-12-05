<?php

class FmBackofficeControllers {
    public static function main($module) {

        $output = '';

        if (Tools::isSubmit('submit_authenticate')) {
            $ret = self::handle_authentication($module);
            $output .= $ret['output'];
        }

        # if no api connection exists yet (first time using module, or user pressed Disconnect Account)
        if (!FmHelpers::api_connection_exists($module)) {
            $page = 'authenticate';

        } else {

            # check if api is up
            $api_available = false;
            try {
                FmHelpers::call_api('GET', 'account/');
                $api_available = true;
            } catch (Exception $e) {
                $page = 'api_unavailable';
            }

            # if api is up
            if ($api_available) {

                # by default, show main page
                $page = 'main';

                # if user pressed Disconnect Account on main pages
                if (Tools::isSubmit('submit_disconnect')) {
                    $ret = self::handle_disconnect($module);
                    $output .= $ret['output'];
                    $page = 'authenticate';
                }

                # if user pressed Show Settings button on main page
                if (Tools::isSubmit('submit_show_settings')) {
                    $page = 'settings';
                }

                # if user pressed Save Settings button on settings page
                if (Tools::isSubmit('submit_save_settings') ) {
                    $ret = self::handle_settings($module);
                    $output .= $ret['output'];
                    if ($ret['error']) {
                        $page = 'settings';
                    }
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
            $output .= self::show_template($module, 'api_unavailable', [
                'exception_type'=> get_class($e),
                'error_message'=> $e->getMessage()
            ]);
        }

        if ($page == 'settings') {
            $configured_language = FmConfig::get('language');
            $configured_currency = FmConfig::get('currency');

            # if there is a configured language, show it as selected
            if ($configured_language) {
                $selected_language = $configured_language;
            } else {
                # else show the default language as selected
                $selected_language = Configuration::get('PS_LANG_DEFAULT');
            }
            # if there is a configured currency, show it as selected
            if ($configured_currency) {
                $selected_currency = $configured_currency;
            } else {
                # else show the default currency as selected
                $selected_currency = Currency::getDefaultCurrency()->id;
            }

            $output .= self::show_template($module, 'settings', [
                'auto_import'=> FmConfig::get('auto_import'),
                'auto_export'=> FmConfig::get('auto_export'),
                'languages'=> Language::getLanguages(),
                'currencies'=> Currency::getCurrencies(),
                'selected_language'=> $selected_language,
                'selected_currency'=> $selected_currency
            ]);
        }

        if ($page == 'main') {
            $output .= self::show_template($module, 'main', [
                'messages'=> FmMessages::get_all(),
                'auto_import'=> FmConfig::get('auto_import'),
                'auto_export'=> FmConfig::get('auto_export'),
                'language'=> new Language(FmConfig::get('language')),
                'currency'=> new Currency(FmConfig::get('currency')),
                'username'=> FmConfig::get('username')
            ]);
        }

        return $output;
    }

    private static function handle_authentication($module) {

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
                FmHelpers::call_api_raw($username, $api_token, 'GET', 'account/', array());

                # if no exceptions, authentication is successful
                FmConfig::set('username', $username);
                FmConfig::set('api_token', $api_token);

            } catch (Exception $e) {
                $error = true;
                $output .= $module->displayError($module->l($e->getMessage()));

                FmConfig::delete('username');
                FmConfig::delete('api_token');
            }
        }

        return ['error'=> $error, 'output'=> $output];
    }

    private static function handle_settings($module) {

        $error = false;
        $output = '';

        $language_id = intval(Tools::getValue('language_id'));
        $currency_id = intval(Tools::getValue('currency_id'));
        $auto_import = boolval(Tools::getValue('auto_import'));
        $auto_export = boolval(Tools::getValue('auto_export'));

        if ($auto_import) {

            # get protocol and domain for shop (if multishop is enabled, it uses the main shop)
            $notification_url = Tools::getShopDomainSsl(true, false);
            # get full path of the module (based on __PS_BASE_URI__ in settings)
            $notification_url .= $module->get('_path');
            # path to the actual file
            $notification_url .= 'backoffice/notification_service.php';

            try {
                // FmHelpers::call_api('PATCH', 'account/', [
                //     'notify_url'=> $notification_url,
                //     'notify_answer'=> _COOKIE_KEY_
                // ]);
            } catch (Exception $e) {
                $error = true;
                $output .= $module->displayError($module->l($e->getMessage()));
            }
        }

        if (!$error) {
            FmConfig::set('language', $language_id);
            FmConfig::set('currency', $currency_id);
            FmConfig::set('auto_import', $auto_import);
            FmConfig::set('auto_export', $auto_export);
        }

        return ['error'=> $error, 'output'=> $output];
    }

    private static function handle_disconnect($module) {

        $error = false;
        $output = '';

        # delete stored connection values
        FmConfig::delete('username');
        FmConfig::delete('api_token');

        $output .= $module->displayConfirmation($module->l(FmMessages::get('account-disconnected')));

        return ['error'=> $error, 'output'=> $output];
    }

    private static function show_template($module, $name, $args=[]) {
        global $smarty;

        $template_args = array_merge($args, [
            'server_path'=> dirname(dirname($_SERVER['SCRIPT_FILENAME'])).'/modules/'.$module->name,
            'module_path'=> $module->get('_path'),
        ]);
        $smarty->assign($template_args);
        return $module->display($module->name, 'backoffice/frontend/templates/'.$name.'.tpl');
    }
}
