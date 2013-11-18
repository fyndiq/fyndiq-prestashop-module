<?php

class FmBackofficeControllers {
    public static function main($module) {
        global $smarty;

        $output = null;

        ## first handle submits and showing of error messages
        # if no api connection, only process authenticate form
        if (!self::api_connection_exists($module)) {
            $output .= self::handle_authentication($module);

        # if no language choice exists, only process choose language form
        } else if (!self::language_choice_exists($module)) {
            $output .= self::handle_language_choice($module);

        # else handle any forms used in main template
        } else {
            $output .= self::handle_switch_language($module);
            $output .= self::handle_disconnect($module);
        }

        ## then display the proper body content
        # if no api connection exists, display authentication form
        if (!self::api_connection_exists($module)) {
            $smarty->assign(array(
                'server_path' => dirname(dirname($_SERVER['SCRIPT_FILENAME'])) .'/modules/'.$module->name,
                'module_path' => $module->get('_path')
            ));
            $output .= $module->display($module->name, 'backoffice/frontend/templates/authenticate.tpl');

        } else {

            # check if api is up
            $api_available = false;
            try {
                FmHelpers::call_api('account/');
                $api_available = true;
            } catch (Exception $e) {
                $smarty->assign(array(
                    'server_path' => dirname(dirname($_SERVER['SCRIPT_FILENAME'])) .'/modules/'.$module->name,
                    'module_path' => $module->get('_path'),
                    'exception_type' => get_class($e),
                    'error_message' => $e->getMessage()
                ));
                $output .= $module->display($module->name, 'backoffice/frontend/templates/api_unavailable.tpl');
            }

            # if api is up
            if ($api_available) {

                # if no language choice exists, display choose language form
                if (!self::language_choice_exists($module)) {
                    $smarty->assign(array(
                        'server_path' => dirname(dirname($_SERVER['SCRIPT_FILENAME'])) .'/modules/'.$module->name,
                        'module_path' => $module->get('_path'),
                        'languages' => Language::getLanguages(),
                        'selected_language' => Configuration::get($module->config_name.'_language')
                    ));
                    $output .= $module->display($module->name, 'backoffice/frontend/templates/language.tpl');

                # else display main template
                } else {
                    $smarty->assign(array(
                        'server_path' => dirname(dirname($_SERVER['SCRIPT_FILENAME'])) .'/modules/'.$module->name,
                        'module_path' => $module->get('_path'),
                        'username' => Configuration::get($module->config_name.'_username'),
                        'language' => new Language(Configuration::get($module->config_name.'_language'))
                    ));
                    $output .= $module->display($module->name, 'backoffice/frontend/templates/main.tpl');
                }
            }
        }

        return $output;
    }

    private static function api_connection_exists($module) {
        $username = Configuration::get($module->config_name.'_username');
        $api_token = Configuration::get($module->config_name.'_api_token');
        return ($username !== false && $api_token !== false);
    }

    private static function language_choice_exists($module) {
        $language_id = Configuration::get($module->config_name.'_language');
        return $language_id !== false;
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
                    $result = FyndiqAPI::call($module->user_agent, $username, $api_token, 'account/', array());
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

    private static function handle_language_choice($module) {
        $output = '';

        if (Tools::isSubmit('submit_language')) {
            $language_id = strval(Tools::getValue('language_id'));

            # validate that a choice has been made
            if (empty($language_id)) {
                $output .= $module->displayError($module->l(FmMessages::get('empty-language-choice')));
            } else {

                # save language choice
                Configuration::updateValue($module->config_name.'_language', $language_id);
            }
        }

        return $output;
    }

    private static function handle_switch_language($module) {
        $output = '';

        if (Tools::isSubmit('submit_switch_language')) {
            Configuration::deleteByName($module->config_name.'_language');
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
}
