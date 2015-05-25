<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once('backoffice/models/config.php');
require_once('backoffice/includes/fyndiqAPI/fyndiqAPI.php');
require_once 'backoffice/includes/shared/src/init.php';
require_once('backoffice/helpers.php');
require_once('backoffice/controllers.php');
require_once('backoffice/models/product_export.php');
require_once('backoffice/models/order.php');

class FyndiqMerchant extends Module
{

    public function __construct()
    {
        $this->config_name = 'FYNDIQMERCHANT';
        $this->name = 'fyndiqmerchant';
        $this->tab = 'market_place';
        $this->version = '1.0.0';
        $this->author = 'Fyndiq AB';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.5.0', 'max' => '1.6');

        parent::__construct();
        //Init translations
        FyndiqTranslation::init(Language::getIsoById($this->context->language->id));
        $this->displayName = 'Fyndiq';
        $this->description = FyndiqTranslation::get('module-description');
        $this->confirmUninstall = FyndiqTranslation::get('uninstall-confirm');

        if (FmHelpers::apiConnectionExists($this)) {
            $this->warning = $this->l(FyndiqTranslation::get('not-authenticated-warning'));
        }

        // custom properties specific to this module
        // determines which prestashop language should be used when getting from database
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
        $ret &= FmProductExport::install();

        // create order mapping database
        $ret &= FmOrder::install();

        return (bool)$ret;
    }

    public function uninstall()
    {
        $ret = true;

        $ret &= (bool)parent::uninstall();

        // delete configuration
        $ret &= (bool)FmConfig::delete('username');
        $ret &= (bool)FmConfig::delete('api_token');
        $ret &= (bool)FmConfig::delete('language');
        $ret &= (bool)FmConfig::delete('price_percentage');
        // NOTE: Don't delete the import date to prevent duplicated orders on reinstall
        //$ret &= (bool)FmConfig::delete('import_date');
        $ret &= (bool)FmConfig::delete('import_state');
        $ret &= (bool)FmConfig::delete('done_state');

        // drop product table
        $ret &= FmProductExport::uninstall();

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
        return FmBackofficeControllers::main($this);
    }

    public function get($name)
    {
        return $this->$name;
    }
}
