<?php

class FmBackofficeControllers {
    public static function main($module) {

        $output = '';
        $page = '';
        $page_args = array();

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
                $page_args['message'] = $e->getMessage();
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
                if (Tools::getValue('submit_show_settings')) {
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

        // Configuration::get('PS_SHOP_DEFAULT')
        // Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE');
        // new ShopGroup((int)Tools::getValue('id_shop_group'))
        // foreach (ShopGroup::getShopGroups() as $group)
        // new Shop((int)Tools::getValue('id_shop'))
        // Shop::getTotalShops()
        // $shops = Shop::getShops(true);
        // Shop::getCategories($id_shop);
        // Category::getRootCategories();
        // if (Shop::getContext() == Shop::CONTEXT_SHOP

        #### render decided page

        if ($page == 'authenticate') {
            $output .= self::show_template($module, 'authenticate');
        }

        if ($page == 'api_unavailable') {
            $output .= self::show_template($module, 'api_unavailable', array(
                'message'=> $page_args['message']
            ));
        }

        if ($page == 'settings') {
            $configured_language = FmConfig::get('language');
            $configured_currency = FmConfig::get('currency');
            $configured_price_percentage = FmConfig::get('price_percentage');
            $configured_quantity_percentage = FmConfig::get('quantity_percentage');

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

            # if there is a configured percentage, set that value
            if ($configured_price_percentage) {
                $typed_price_percentage = $configured_price_percentage;
            } else {
                # else set the default value of 10%.
                $typed_price_percentage = 10;
            }

            # if there is a configured percentage, set that value
            if ($configured_quantity_percentage) {
                $typed_quantity_percentage = $configured_quantity_percentage;
            } else {
                # else set the default value of 10%.
                $typed_quantity_percentage = 20;
            }

            $path = FmHelpers::get_module_url();

            $output .= self::show_template($module, 'settings', array(
                'auto_import'=> FmConfig::get('auto_import'),
                'auto_export'=> FmConfig::get('auto_export'),
                'price_percentage' => $typed_price_percentage,
                'quantity_percentage' => $typed_quantity_percentage,
                'languages'=> Language::getLanguages(),
                'currencies'=> Currency::getCurrencies(),
                'selected_language'=> $selected_language,
                'selected_currency'=> $selected_currency,
                'path' => $path
            ));
        }
        if ($page == 'main') {
            $path = FmHelpers::get_module_url();
            $output .= self::show_template($module, 'main', array(
                'messages'=> FmMessages::get_all(),
                'auto_import'=> FmConfig::get('auto_import'),
                'auto_export'=> FmConfig::get('auto_export'),
                'language'=> new Language(FmConfig::get('language')),
                'currency'=> new Currency(FmConfig::get('currency')),
                'username'=> FmConfig::get('username'),
                'path' => $path
            ));
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

        return array('error'=> $error, 'output'=> $output);
    }

    private static function handle_settings($module) {

        $error = false;
        $output = '';

        $language_id = intval(Tools::getValue('language_id'));
        $currency_id = intval(Tools::getValue('currency_id'));
        $auto_import = boolval(Tools::getValue('auto_import'));
        $auto_export = boolval(Tools::getValue('auto_export'));
        $price_percentage = intval(Tools::getValue('price_percentage'));
        $quantity_percentage = intval(Tools::getValue('quantity_percentage'));

        if ($auto_import) {

            # get protocol and domain for shop (if multishop is enabled, it uses the main shop)
            $notification_url = Tools::getShopDomainSsl(true, false);
            # get full path of the module (based on __PS_BASE_URI__ in settings)
            $notification_url .= $module->get('_path');
            # path to the actual file
            $notification_url .= 'backoffice/notification_service.php';

            try {
                // FmHelpers::call_api('PATCH', 'account/', array(
                //     'notify_url'=> $notification_url,
                //     'notify_answer'=> _COOKIE_KEY_
                // ));
            } catch (Exception $e) {
                $error = true;
                $output .= $module->displayError($module->l($e->getMessage()));
            }
        }

        if (!$error) {
            FmConfig::set('price_percentage', $price_percentage);
            FmConfig::set('quantity_percentage', $quantity_percentage);
            FmConfig::set('language', $language_id);
            FmConfig::set('currency', $currency_id);
            FmConfig::set('auto_import', $auto_import);
            FmConfig::set('auto_export', $auto_export);
        }

        return array('error'=> $error, 'output'=> $output);
    }

    private static function handle_disconnect($module) {

        $error = false;
        $output = '';

        # delete stored connection values
        FmConfig::delete('username');
        FmConfig::delete('api_token');

        $output .= $module->displayConfirmation($module->l(FmMessages::get('account-disconnected')));

        return array('error'=> $error, 'output'=> $output);
    }

    private static function show_template($module, $name, $args=array()) {
        global $smarty;

        $template_args = array_merge($args, array(
            'server_path'=> dirname(dirname($_SERVER['SCRIPT_FILENAME'])).'/modules/'.$module->name,
            'module_path'=> $module->get('_path'),
        ));
        $smarty->assign($template_args);
        return $module->display($module->name, 'backoffice/frontend/templates/'.$name.'.tpl');
    }
}
