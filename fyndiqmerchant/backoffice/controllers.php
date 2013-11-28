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
                'exception_type' => get_class($e),
                'error_message' => $e->getMessage()
            ]);
        }
        if ($page == 'settings') {
            $output .= self::show_template($module, 'settings', [
                'languages' => Language::getLanguages(),
                'currencies' => Currency::getCurrencies(),
                'selected_language' => Configuration::get($module->config_name.'_language'),
                'selected_currency' => Configuration::get($module->config_name.'_currency')
            ]);
        }
        if ($page == 'main') {
            $output .= self::show_template($module, 'main', [
                'messages' => FmMessages::get_all(),
                'username' => Configuration::get($module->config_name.'_username'),
                'language' => new Language(Configuration::get($module->config_name.'_language')),
                'currency' => new Currency(Configuration::get($module->config_name.'_currency'))
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
            $authenticated = false;
            try {
                $result = FyndiqAPI::call($module->user_agent, $username, $api_token, 'GET', 'account/', array());
                $authenticated = true;
            } catch (FyndiqAPIConnectionFailed $e) {
                $output .= $module->displayError($module->l(FmMessages::get('api-network-error')));
            } catch (FyndiqAPIDataInvalid $e) {
                $output .= $module->displayError($module->l(FmMessages::get('api-invalid-data').': '.$e->getMessage()));
            } catch (FyndiqAPIAuthorizationFailed $e) {
                $output .= $module->displayError($module->l(FmMessages::get('api-incorrect-credentials')));
            } catch (FyndiqAPITooManyRequests $e) {
                $output .= $module->displayError($module->l(FmMessages::get('api-too-many-requests')));
            }

            # authentication successful
            if ($authenticated) {

                # store values in configuration, to maintain a permanent connection
                Configuration::updateValue($module->config_name.'_username', $username);
                Configuration::updateValue($module->config_name.'_api_token', $api_token);

            # authentication failed
            } else {
                $error = true;

                # delete any stored connection values, which forces the user to authenticate again
                Configuration::deleteByName($module->config_name.'_username');
                Configuration::deleteByName($module->config_name.'_api_token');
            }
        }

        return ['error'=> $error, 'output'=> $output];
    }

    private static function handle_settings($module) {

        $error = false;
        $output = '';

        $language_id = intval(Tools::getValue('language_id'));
        $currency_id = intval(Tools::getValue('currency_id'));

        if (empty($language_id)) {
            $error = true;
            $output .= $module->displayError($module->l(FmMessages::get('settings-empty-language')));
        } else {
            Configuration::updateValue($module->config_name.'_language', $language_id);
        }

        if (empty($currency_id)) {
            $error = true;
            $output .= $module->displayError($module->l(FmMessages::get('settings-empty-currency')));
        } else {
            Configuration::updateValue($module->config_name.'_currency', $currency_id);
        }

        return ['error'=> $error, 'output'=> $output];
    }

    private static function handle_disconnect($module) {

        $error = false;
        $output = '';

        # delete stored connection values
        Configuration::deleteByName($module->config_name.'_username');
        Configuration::deleteByName($module->config_name.'_api_token');

        $output .= $module->displayConfirmation($module->l(FmMessages::get('account-disconnected')));

        return ['error'=> $error, 'output'=> $output];
    }

    private static function show_template($module, $name, $args=[]) {
        global $smarty;

        $output = '';

        $template_args = array_merge($args, [
            'server_path' => dirname(dirname($_SERVER['SCRIPT_FILENAME'])) .'/modules/'.$module->name,
            'module_path' => $module->get('_path'),
        ]);
        $smarty->assign($template_args);
        $output .= $module->display($module->name, 'backoffice/frontend/templates/'.$name.'.tpl');

        return $output;
    }
}
