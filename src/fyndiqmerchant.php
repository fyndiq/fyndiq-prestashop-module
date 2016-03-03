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
require_once('backoffice/FmFormSetting.php');

class FyndiqMerchant extends Module
{

    private $fmPrestashop = null;
    private $fmConfig = null;
    private $modules = array();
    private $storeId = null;

    public function __construct()
    {
        $this->config_name = 'FYNDIQMERCHANT';
        $this->name = FmUtils::MODULE_NAME;
        $this->tab = 'market_place';
        $this->version = FmUtils::VERSION;
        $this->author = 'Fyndiq AB';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.5.0', 'max' => '1.6');
        $this->bootstrap = true;

        parent::__construct();

        // Initialize translations
        $this->fmPrestashop = new FmPrestashop(FmUtils::MODULE_NAME);
        $this->fmConfig = new FmConfig($this->fmPrestashop);
        $languageId = $this->fmPrestashop->getLanguageId();
        FyndiqTranslation::init($this->fmPrestashop->languageGetIsoById($languageId));
        $this->storeId = $this->fmPrestashop->getStoreId();

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
        if (!parent::install() || !$this->registerHook('displayAdminProductsExtra')) {
            return false;
        }

        $fmProductExport = new FmProductExport($this->fmPrestashop, $this->fmConfig);
        $fmOrder = new FmOrder($this->fmPrestashop, $this->fmConfig);

        $this->registerHook('displayAdminProductsExtra');
        $this->registerHook('displayBackOfficeHeader');
        $this->registerHook('actionProductUpdate');

        return $fmProductExport->install() && $fmOrder->install();
    }

    public function uninstall()
    {
        if (!parent::uninstall() || !$this->deleteConfig()) {
            return false;
        }

        $fmProductExport = new FmProductExport($this->fmPrestashop, $this->fmConfig);
        $fmOrder = new FmOrder($this->fmPrestashop, $this->fmConfig);

        return $fmProductExport->uninstall() && $fmOrder->uninstall();
    }

    private function deleteConfig()
    {
        foreach (FmUtils::getConfigKeys() as $key => $value) {
            if (!(bool)$this->fmConfig->delete($key, $this->storeId)) {
                return false;
            }
        }
        return true;
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
        $this->fmConfig = new FmConfig($this->fmPrestashop);
        $fmApiModel = new FmApiModel($this->fmPrestashop, $this->fmConfig, $this->storeId);
        $controller = new FmController($this->fmPrestashop, $fmOutput, $this->fmConfig, $fmApiModel);
        return $controller->handleRequest();
    }

    public function get($name)
    {
        return $this->$name;
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

    public function hookDisplayBackOfficeHeader($params)
    {
        if(Tools::getValue('controller') === 'AdminProducts')
        {
            $this->fmPrestashop->contextGetContext()->controller->addJS(($this->_path) . 'backoffice/frontend/templates/tab-fyndiq.js' );
        }
    }

    public function hookDisplayAdminProductsExtra($params)
    {
        $productId = (int)Tools::getValue('id_product');
        $productModel = new FmProductExport($this->fmPrestashop, $this->fmConfig);
        $storeId = $this->fmPrestashop->getStoreId();
        $fynProduct = $productModel->getProduct($productId, $storeId);
        $this->smarty->assign(
            array(
                'fyndiq_exported' => !empty($fynProduct),
                'fyndiq_title' => $fynProduct['name'],
                'fyndiq_title_label' => $this->__("Name"),
                'fyndiq_title_tooltip' => $this->__("Title of the product as it will appear on Fyndiq"),
                'fyndiq_title_minlength' => FyndiqFeedWriter::$minLength[FyndiqFeedWriter::PRODUCT_TITLE],
                'fyndiq_title_maxlength' => FyndiqFeedWriter::$lengthLimitedColumns[FyndiqFeedWriter::PRODUCT_TITLE],
                'fyndiq_description' => $fynProduct['description'],
                'fyndiq_description_label' => $this->__("Description"),
                'fyndiq_description_tooltip' => $this->__("Description of the product as it will appear on Fyndiq"),
                'fyndiq_description_minlength' => FyndiqFeedWriter::$minLength[FyndiqFeedWriter::PRODUCT_DESCRIPTION],
                'fyndiq_description_maxlength' => FyndiqFeedWriter::$lengthLimitedColumns[FyndiqFeedWriter::PRODUCT_DESCRIPTION],
            )
        );
        return $this->display(__FILE__, 'backoffice/frontend/templates/tab-fyndiq.tpl');
    }

    public function hookActionProductUpdate($params)
    {
        $productId = (int)$this->fmPrestashop->toolsGetValue('id_product');
        $productModel = new FmProductExport($this->fmPrestashop, $this->fmConfig);
        $storeId = $this->fmPrestashop->getStoreId();
        $exported = $this->fmPrestashop->toolsGetValue('fyndiq_exported');
        $title = $this->fmPrestashop->toolsGetValue('fyndiq_title');
        $description = $this->fmPrestashop->toolsGetValue('fyndiq_description');

        if($exported && !$productModel->productExists($productId, $storeId)) {
            $productModel->addProduct($productId, $storeId, $title, $description);
            return;
        }
        if($exported && $productModel->productExists($productId, $storeId)) {
            $productModel->updateProduct($productId, $storeId,  $title, $description);
        }
        if(!$exported && $productModel->productExists($productId, $storeId)) {
            $productModel->removeProduct($productId, $storeId);
        }
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
