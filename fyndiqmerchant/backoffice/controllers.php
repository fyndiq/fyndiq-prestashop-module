<?php

class FmBackofficeControllers {
    public static function main($module) {

        $output = null;

        #### first handle submits and showing of error messages
        $output .= self::handle_authentication($module);
        $output .= self::handle_settings($module);
        $output .= self::handle_disconnect($module);

        #### then display the proper body content
        # if no api connection exists, display authentication form
        if (!self::api_connection_exists($module)) {
            $output .= self::show_template($module, 'authenticate');

        } else {

            # check if api is up
            $api_available = false;
            try {
                FmHelpers::call_api('GET', 'account/');
                $api_available = true;
            } catch (Exception $e) {
                $output .= self::show_template($module, 'api_unavailable', [
                    'exception_type' => get_class($e),
                    'error_message' => $e->getMessage()
                ]);
            }

            # if api is up
            if ($api_available) {

                # show settings if:
                # a) no settings have been saved yet (first time using module), or
                # b) user presses Change Settings button on main template
                $show_settings = false;
                $show_settings = $show_settings || !self::all_settings_exist($module);
                $show_settings = $show_settings || Tools::isSubmit('submit_show_settings');

                if ($show_settings) {
                    $output .= self::show_template($module, 'settings', [
                        'languages' => Language::getLanguages(),
                        'currencies' => Currency::getCurrencies(),
                        'selected_language' => Configuration::get($module->config_name.'_language'),
                        'selected_currency' => Configuration::get($module->config_name.'_currency')
                    ]);

                # else display main template
                } else {
                    $output .= self::show_template($module, 'main', [
                        'messages' => FmMessages::get_all(),
                        'username' => Configuration::get($module->config_name.'_username'),
                        'language' => new Language(Configuration::get($module->config_name.'_language')),
                        'currency' => new Currency(Configuration::get($module->config_name.'_currency'))
                    ]);
                }
            }
        }

        return $output;
    }

    private static function api_connection_exists($module) {
        $ret = true;
        $ret = $ret && Configuration::get($module->config_name.'_username') !== false;
        $ret = $ret && Configuration::get($module->config_name.'_api_token') !== false;
        return $ret;
    }

    private static function all_settings_exist($module) {
        $ret = true;
        $ret = $ret && Configuration::get($module->config_name.'_language') !== false;
        return $ret;
    }

    private static function handle_authentication($module) {

        $output = '';

        # handle authenticate form submission
        if (Tools::isSubmit('submit_authenticate')) {
            $username = strval(Tools::getValue('username'));
            $api_token = strval(Tools::getValue('api_token'));

            # validate parameters
            if (empty($username) || empty($api_token)) {
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

                    # delete any stored connection values, which forces the user to authenticate again
                    Configuration::deleteByName($module->config_name.'_username');
                    Configuration::deleteByName($module->config_name.'_api_token');
                }
            }
        }

        return $output;
    }

    private static function handle_settings($module) {
        $output = '';

        if (Tools::isSubmit('submit_save_settings')) {
            $language_id = intval(Tools::getValue('language_id'));
            $currency_id = intval(Tools::getValue('currency_id'));

            if (empty($language_id)) {
                $output .= $module->displayError($module->l(FmMessages::get('settings-empty-language')));
            } else {
                Configuration::updateValue($module->config_name.'_language', $language_id);
            }

            if (empty($currency_id)) {
                $output .= $module->displayError($module->l(FmMessages::get('settings-empty-currency')));
            } else {
                Configuration::updateValue($module->config_name.'_currency', $currency_id);
            }
        }

        return $output;
    }

    private static function handle_disconnect($module) {

        $output = '';

        if (Tools::isSubmit('submit_disconnect')) {

            # delete stored connection values
            Configuration::deleteByName($module->config_name.'_username');
            Configuration::deleteByName($module->config_name.'_api_token');

            $output .= $module->displayConfirmation($module->l(FmMessages::get('account-disconnected')));
        }

        return $output;
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
