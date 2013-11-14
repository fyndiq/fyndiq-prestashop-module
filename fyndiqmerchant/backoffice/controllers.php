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
            $output .= self::handle_disconnect($module);
        }

        ## then display the proper body content
        # if no api connection exists, display authentication form
        if (!self::api_connection_exists($module)) {
            $smarty->assign(array(
                'module_path' => $module->get('_path')
            ));
            $output .= $module->display($module->name, 'backoffice/templates/authenticate.tpl');

        } else {

            # check if api is up
            $api_up = false;
            try {
                FmHelpers::call_api('account/');
                $api_up = true;
            } catch (Exception $e) {}

            # if api is not available, show api down template
            if (!$api_up) {
                $smarty->assign(array(
                    'module_path' => $module->get('_path'),
                    'message' => $e->getMessage()
                ));
                $output .= $module->display($module->name, 'backoffice/templates/api_down.tpl');

            # if api is available
            } else {

                # if no language choice exists, display choose language form
                if (self::language_choice_exists($module)) {
                    $smarty->assign(array(
                        'module_path' => $module->get('_path'),
                    ));
                    $output .= $module->display($module->name, 'backoffice/templates/language.tpl');

                # else display main template
                } else {
                    $smarty->assign(array(
                        'module_path' => $module->get('_path'),
                        'username' => Configuration::get($module->config_name.'_username')
                    ));
                    $output .= $module->display($module->name, 'backoffice/templates/main.tpl');
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
        $language = Configuration::get($module->config_name.'_language');
        return $language !== false;
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
                    $output .= $module->displayError($module->l(FmMessages::get('data-processing-error').': '.$e->getMessage()));
                } catch (FyndiqAPIAuthorizationFailed $e) {
                    $output .= $module->displayError($module->l(FmMessages::get('authorization-fail')));
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

        if (Tools::isSubmit('submit_choose_language')) {
            $language = strval(Tools::getValue('language'));

            # validate that a choice has been made
            if (empty($language)) {
                $output .= $module->displayError($module->l(FmMessages::get('empty-language-choice')));
            } else {

                # save language choice
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
}
