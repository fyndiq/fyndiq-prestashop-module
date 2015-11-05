<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once('backoffice/includes/fyndiqAPI/fyndiqAPI.php');
require_once('backoffice/includes/shared/src/init.php');
require_once('backoffice/FmUtils.php');
require_once('backoffice/FmConfig.php');
require_once('backoffice/FmOutput.php');
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
        $this->ps_versions_compliancy = array('min' => '1.4.0', 'max' => '1.6');

        parent::__construct();

        // Initialize translations
        $this->fmPrestashop = new FmPrestashop(FmUtils::MODULE_NAME);
        $languageId = $this->fmPrestashop->getLanguageId();
        FyndiqTranslation::init($this->fmPrestashop->languageGetIsoById($languageId));

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

        $fmConfig = new FmConfig($this->fmPrestashop);
        $fmProductExport = new FmProductExport($this->fmPrestashop, $fmConfig);
        $fmOrder = new FmOrder($this->fmPrestashop, $fmConfig);

        // create product mapping database
        $ret &= $fmProductExport->install();

        // create order mapping database
        $ret &= $fmOrder->install();

        $this->registerHook('displayAdminProductsExtra');

        return (bool)$ret;
    }

    public function uninstall()
    {
        $ret = true;

        $ret &= (bool)parent::uninstall();

        $fmConfig = new FmConfig($this->fmPrestashop);
        $fmProductExport = new FmProductExport($this->fmPrestashop, $fmConfig);
        $fmOrder = new FmOrder($this->fmPrestashop, $fmConfig);

        // Delete configuration
        $ret &= (bool)$fmConfig->delete('username');
        $ret &= (bool)$fmConfig->delete('api_token');
        $ret &= (bool)$fmConfig->delete('language');
        $ret &= (bool)$fmConfig->delete('price_percentage');
        $ret &= (bool)$fmConfig->delete('import_state');
        $ret &= (bool)$fmConfig->delete('done_state');

        // Drop product table
        $ret &= $fmProductExport->uninstall();

        // Remove the menu tab
        $ret &= $this->uninstallTab();

        // drop order table
        $ret &= $fmOrder->uninstall();

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
        $tab->class_name = 'FyndiqPage';
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
        $idTab = (int)Tab::getIdFromClassName('FyndiqPage');
        if ($idTab) {
            $tab = new Tab($idTab);
            return $tab->delete();
        }
        return false;
    }

    private function setAdminPathCookie()
    {
        global $cookie;
        $fyCookie = new Cookie(FmUtils::MODULE_NAME);
        $fyCookie->adminPath = $cookie->_path;
        $fyCookie->id_currency = $cookie->id_currency;
        $fyCookie->id_lang = $cookie->id_lang;
        $fyCookie->id_country = $cookie->id_country;
        $fyCookie->id_employee = $cookie->id_employee;
        $fyCookie->write();
    }

    public function getContent()
    {
        if (!$this->fmPrestashop->isPs1516()) {
            $this->setAdminPathCookie();
        }
        $fmOutput = new FmOutput($this->fmPrestashop, $this, $this->fmPrestashop->contextGetContext()->smarty);
        $fmConfig = new FmConfig($this->fmPrestashop);
        $fmApiModel = new FmApiModel($fmConfig->get('username'), $fmConfig->get('api_token'), $this->fmPrestashop->globalGetVersion());
        $controller = new FmController($this->fmPrestashop, $fmOutput, $fmConfig, $fmApiModel);
        return $controller->handleRequest();
    }

    public function get($name)
    {
        return $this->$name;
    }


    public function hookDisplayAdminProductsExtra($params)
    {
         return $this->display(__FILE__, 'backoffice/frontend/templates/tab-fyndiq.tpl');
    }

}
