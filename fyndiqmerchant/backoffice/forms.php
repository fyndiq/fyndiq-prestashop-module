<?php

class FyndiqMerchantForms {
    public static function render($form_name, $module) {
        return FyndiqMerchantForms::$form_name($module);
    }

    public static function authenticate($module) {
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $module->l('Authentication'),
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $module->l('Username'),
                    'name' => 'username',
                    'size' => 27,
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'label' => $module->l('API Token'),
                    'name' => 'api_token',
                    'size' => 42,
                    'required' => true
                )
            ),
            'submit' => array(
                'title' => $module->l('Connect merchant account'),
            )
        );

        $helper = new HelperForm();

        $helper->module = $module;
        $helper->name_controller = $module->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$module->name;

        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        $helper->title = $module->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submit_authenticate';
        $helper->toolbar_btn = array(
            'save' => array(
                'desc' => $module->l('asdf'),
                'href' => AdminController::$currentIndex.'&configure='.$module->name.'&save'.$module->name.
                          '&token='.Tools::getAdminTokenLite('AdminModules'),
            ),
            'back' => array(
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $module->l('Back to list')
            )
        );

        $helper->fields_value['username'] = Configuration::get('username');
        $helper->fields_value['api_token'] = Configuration::get('api_token');

        return $helper->generateForm($fields_form);
    }

    public static function something($module) {
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $module->l('Products'),
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $module->l('Dummy placeholder field'),
                    'name' => 'username',
                    'size' => 20,
                    'required' => true
                )
            ),
            'submit' => array(
                'title' => $module->l('Save'),
            ),
        );

        $helper = new HelperForm();

        $helper->module = $module;
        $helper->name_controller = $module->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$module->name;

        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        $helper->title = $module->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submit_saveproductssss';
        $helper->toolbar_btn = array(
            'disconnect' => array(
                'desc' => $module->l('Disconnect account'),
                'imgclass' => 'cancel',
                'js' => 'return confirm(\'Are you sure you want to disconnect from your Fyndiq merchant account?\');',
                'href' => AdminController::$currentIndex.'&configure='.$module->name.'&submit_disconnect=true'.
                          '&token='.Tools::getAdminTokenLite('AdminModules')
            ),

            'back' => array(
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $module->l('Back to list')
            )
        );

        $helper->fields_value['username'] = Configuration::get('username');
        $helper->fields_value['api_token'] = Configuration::get('api_token');

        return $helper->generateForm($fields_form);
    }
}

?>