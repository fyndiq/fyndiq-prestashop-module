<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once('backoffice/includes/fyndiqAPI/fyndiqAPI.php');
require_once('backoffice/includes/shared/src/init.php');
require_once('backoffice/FmUtils.php');
require_once('backoffice/FmCart.php');
require_once('backoffice/FmConfig.php');
require_once('backoffice/FmOutput.php');
require_once('backoffice/FmPrestashop.php');
require_once('backoffice/FmController.php');
require_once('backoffice/models/FmModel.php');
require_once('backoffice/models/FmProductExport.php');
require_once('backoffice/models/FmApiModel.php');
require_once('backoffice/models/FmOrder.php');
require_once('backoffice/FmOrderFetch.php');

class FyndiqMerchant extends Module
{

    private $fmPrestashop = null;
    private $fmConfig = null;
    private $modules = array();

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

        // Initialize translations
        $this->fmPrestashop = new FmPrestashop(FmUtils::MODULE_NAME);
        $this->fmConfig = new FmConfig($this->fmPrestashop);
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

        $fmProductExport = new FmProductExport($this->fmPrestashop, $this->fmConfig);
        $fmOrder = new FmOrder($this->fmPrestashop, $this->fmConfig);
        $this->fmConfig->set('patch_version', 3, 0);

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

        $fmProductExport = new FmProductExport($this->fmPrestashop, $this->fmConfig);
        $fmOrder = new FmOrder($this->fmPrestashop, $this->fmConfig);
        $storeId = $this->fmPrestashop->getStoreId();

        // Delete configuration
        $ret &= (bool)$this->fmConfig->delete('username', $storeId);
        $ret &= (bool)$this->fmConfig->delete('api_token', $storeId);
        $ret &= (bool)$this->fmConfig->delete('language', $storeId);
        $ret &= (bool)$this->fmConfig->delete('price_percentage', $storeId);
        $ret &= (bool)$this->fmConfig->delete('import_state', $storeId);
        $ret &= (bool)$this->fmConfig->delete('done_state', $storeId);

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
        $storeId = $this->fmPrestashop->getStoreId();
        $fmOutput = new FmOutput($this->fmPrestashop, $this, $this->fmPrestashop->contextGetContext()->smarty);
        $this->fmConfig = new FmConfig($this->fmPrestashop);
        $fmApiModel = new FmApiModel($this->fmPrestashop, $this->fmConfig, $storeId);
        $controller = new FmController($this->fmPrestashop, $fmOutput, $this->fmConfig, $fmApiModel);
        return $controller->handleRequest();
    }

    public function get($name)
    {
        return $this->$name;
    }

    /**
     * get API Object
     *
     * @return object
     */
    public function getApiModel()
    {
         $storeId = $this->fmPrestashop->getStoreId();
            $fmApiModel = new FmApiModel(
                $this->fmConfig->get('username', $storeId),
                $this->fmConfig->get('api_token', $storeId),
                $this->fmPrestashop->globalGetVersion()
            );
        return $fmApiModel;
    }

    /**
     * get prestashop Object
     *
     * @return object
     */
    public function getFmPrestashop()
    {
        return $this->fmPrestashop;

    }


    public function hookDisplayAdminProductsExtra($params)
    {
        $this->smarty->assign(
            array(
                'fyndiq_price' => 666,
                'fyndiq_exported' => true,
            )
        );
        return $this->display(__FILE__, 'backoffice/frontend/templates/tab-fyndiq.tpl');
    }


    public function getModel($modelName, $storeId = -1)
    {
        if (!isset($this->modules[$modelName])) {
            $this->modules[$modelName] = new $modelName($this->fmPrestashop, $this->fmConfig, $storeId);
        }
        return $this->modules[$modelName];
    }

    public function __($text)
    {
        return FyndiqTranslation::get($text);
    }
}
