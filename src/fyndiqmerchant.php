<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once('backoffice/FmUtils.php');
require_once('backoffice/FmConfig.php');
require_once('backoffice/FmOutput.php');
require_once('backoffice/includes/fyndiqAPI/fyndiqAPI.php');
require_once 'backoffice/includes/shared/src/init.php';
require_once('backoffice/FmPrestashop.php');
require_once('backoffice/FmController.php');
require_once('backoffice/models/FmModel.php');
require_once('backoffice/models/FmProductExport.php');
require_once('backoffice/models/FmApiModel.php');
require_once('backoffice/models/FmOrder.php');

class FyndiqMerchant extends Module
{

    public function __construct()
    {
        $this->config_name = 'FYNDIQMERCHANT';
        $this->name = FmUtils::MODULE_NAME;
        $this->tab = 'market_place';
        $this->version = FmUtils::VERSION;
        $this->author = 'Fyndiq AB';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.5.0', 'max' => '1.6');

        parent::__construct();
        //Init translations
        FyndiqTranslation::init(Language::getIsoById($this->context->language->id));
        $this->displayName = 'Fyndiq';
        $this->description = FyndiqTranslation::get('module-description');
        $this->confirmUninstall = FyndiqTranslation::get('uninstall-confirm');

        // custom properties specific to this module
        // determines which PrestaShop language should be used when getting from database
        $this->language_id = 1;
        // used as user agent string when calling the API
        $this->user_agent = $this->name . '-' . $this->version;
    }

    public function install()
    {
        $ret = true;

        $ret &= (bool)parent::install();

        // Create tab
        $ret &= $this->installTab();

        // create product mapping database
        $fmPrestashop = new FmPrestashop(FmUtils::MODULE_NAME);
        $fmProductExport = new FmProductExport($fmPrestashop, nil);
        $ret &= $fmProductExport->install();

        // create order mapping database
        $ret &= FmOrder::install();

        return (bool)$ret;
    }

    public function uninstall()
    {
        $ret = true;

        $ret &= (bool)parent::uninstall();

        // Delete configuration
        $ret &= (bool)FmConfig::delete('username');
        $ret &= (bool)FmConfig::delete('api_token');
        $ret &= (bool)FmConfig::delete('language');
        $ret &= (bool)FmConfig::delete('price_percentage');
        $ret &= (bool)FmConfig::delete('import_state');
        $ret &= (bool)FmConfig::delete('done_state');

        // Drop product table
        $fmPrestashop = new FmPrestashop(FmUtils::MODULE_NAME);
        $fmProductExport = new FmProductExport($fmPrestashop, nil);
        $ret &= $fmProductExport->uninstall();

        // Remove the menu tab
        $ret &= $this->uninstallTab();

        // drop order table
        $ret &= FmOrder::uninstall();

        return (bool)$ret;
    }

    /**
     * Install tab to the menu
     *
     * @return mixed
     */
    private function installTab()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminPage';
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Fyndiq';
        }
        $tab->id_parent = (int)Tab::getIdFromClassName('AdminParentModules');
        $tab->module = $this->name;
        return $tab->add();
    }

    /**
     * Remove tab from menu
     *
     * @return mixed
     */
    private function uninstallTab()
    {
        $idTab = (int)Tab::getIdFromClassName('AdminPage');
        if ($idTab) {
            $tab = new Tab($idTab);
            return $tab->delete();
        }
        return false;
    }

    public function getContent()
    {
        $fmPrestashop = new FmPrestashop(FmUtils::MODULE_NAME);
        $fmOutput = new FmOutput($fmPrestashop, $this, $this->context->smarty);
        $fmConfig = new FmConfig($fmPrestashop);
        $fmApiModel = new FmApiModel($fmConfig->get('username'), $fmConfig->get('api_token'));
        $controller = new FmController($fmPrestashop, $fmOutput, $fmConfig, $fmApiModel);
        return $controller->handleRequest();
    }

    public function get($name)
    {
        return $this->$name;
    }
}
