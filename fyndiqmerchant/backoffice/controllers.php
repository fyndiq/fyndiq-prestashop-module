<?php

class FyndiqMerchantBackofficeControllers {
    public static function main($module) {
        $output = null;

        if (!self::api_connection_exists($module)) {
            $output .= self::handle_authentication($module);
        } else {
            $output .= self::handle_disconnect($module);
            $output .= self::handle_products($module);
        }

        if (!self::api_connection_exists($module)) {
            $output .= FyndiqMerchantForms::render('authenticate', $module);
        } else {
            $output .= FyndiqMerchantForms::render('something', $module);
        }

        return $output;
    }

    private static function api_connection_exists($module) {
        return (
            Configuration::hasKey($module->config_name.'_username') &&
            Configuration::hasKey($module->config_name.'_api_token')
        );
    }

    private static function handle_authentication($module) {
        $output = '';

        # handle authenticate form submission
        if (Tools::isSubmit('submit_authenticate')) {
            $username = strval(Tools::getValue('username'));
            $api_token = strval(Tools::getValue('api_token'));

            # validate parameters
            if (empty($username) || empty($api_token)) {
                $output .= $module->displayError(
                    $module->l('Please specify a Username and API token.'));

            # ready to perform authentication
            } else {

                # authenticate with Fyndiq API
                $authenticated = false;
                try {
                    $result = FyndiqAPI::call($module->user_agent, $username, $api_token, array());
                    $authenticated = true;
                } catch (FyndiqAPIConnectionFailed $e) {
                    $output .= $module->displayError(
                        $module->l('Network error, cannot connect to Fyndiq API.'));
                } catch (FyndiqAPIDataInvalid $e) {
                    $output .= $module->displayError(
                        $module->l('Error processing data: '.$e.message));
                } catch (FyndiqAPIAuthorizationFailed $e) {
                    $output .= $module->displayError(
                        $module->l('Incorrect Username or API token. Please double check your provided values.'));
                }

                # authentication successful
                if ($authenticated) {

                    # store values in configuration, to maintain a permanent connection
                    Configuration::updateValue($module->config_name.'_username', $username);
                    Configuration::updateValue($module->config_name.'_api_token', $api_token);

                    # display success message
                    $output .= $module->displayConfirmation(
                        $module->l('You are now connected to your Fyndiq merchant account.'));

                } else {
                    Configuration::deleteByName($module->config_name.'_username');
                    Configuration::deleteByName($module->config_name.'_api_token');
                }
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

            $output .= $module->displayConfirmation(
                $module->l('You have disconnected from your merchant account and Fyndiq API.'));
        }

        return $output;
    }

    private static function handle_products($module) {
        $output = '';

        if (Tools::isSubmit('submit_saveproducts....')) {
            ####
        }

        return $output;
    }
}

?>