<?php

class FmPrestashop
{

    // FyndiqMerchant PrestaShop Version 1.4|1.5|1.6
    const FMPSV14 = 'FMPSV14';
    const FMPSV15 = 'FMPSV15';
    const FMPSV16 = 'FMPSV16';

    const DB_INSERT = 1;

    const DEFAULT_LANGUAGE_ID = 1;

    const EXPORT_FILE_NAME_PATTERN = 'feed-%d.csv';

    public $version = '';
    public $moduleName = '';
    private $categoryCache = array();
    private $context;

    public function __construct($moduleName)
    {
        $this->moduleName = $moduleName;
        $version = $this->globalGetVersion();

        if (stripos($version, '1.4.') === 0) {
            $this->version = self::FMPSV14;
        }
        if (stripos($version, '1.5.') === 0) {
            $this->version = self::FMPSV15;
        }
        if (stripos($version, '1.6.') === 0) {
            $this->version = self::FMPSV16;
        }
    }

    // Custom
    public function time()
    {
        return time();
    }

    public function getModuleUrl()
    {
        $url = $this->getBaseModuleUrl();
        $url .= substr(strrchr(_PS_ADMIN_DIR_, '/'), 1);
        if ($this->isPs1516()) {
            $url .= "/index.php?controller=AdminModules&configure=fyndiqmerchant&module_name=fyndiqmerchant";
        } else {
            $url .= "/index.php?tab=AdminModules&configure=fyndiqmerchant&module_name=fyndiqmerchant";
        }
        $url .= '&token=' . Tools::getAdminTokenLite('AdminModules');
        return $url;
    }

    public function getAdminTokenLite()
    {
        return Tools::getAdminTokenLite('AdminOrders');
    }

    /**
     * Returns the export filename path
     *
     * @return string
     */
    public function getExportPath()
    {
        return _PS_CACHE_DIR_ . $this->moduleName . '/';
    }

    /**
     * Get Category Name
     *
     * @param $categoryId
     * @return string
     */
    public function getCategoryName($categoryId)
    {
        if (!isset($this->categoryCache[$categoryId])) {
            $category = new Category($categoryId, $this->getLanguageId());
            $this->categoryCache[$categoryId] = $category->name;
        }
        return $this->categoryCache[$categoryId];
    }

    public function getLanguageId()
    {
        return $this->contextGetContext()->language->id;
    }

    public function getCurrency($currencyId)
    {
        return new Currency($currencyId);
    }

    public function sleep($seconds)
    {
        return sleep($seconds);
    }

    public function dbEscape($value)
    {
        if ($this->version == self::FMPSV14) {
            return pSQL($value);
        }
        return $this->dbGetInstance()->_escape($value);
    }

    public function getShopUrl()
    {
        if ($this->isPs1516() && Shop::getContext() === Shop::CONTEXT_SHOP) {
            $shop = new Shop($this->getCurrentShopId());
            return $shop->getBaseURL();
        }
        // fallback to globals if context is not shop
        return $this->getModuleUrl(false);
    }

    public function getDefaultCurrency()
    {
        return Currency::getDefaultCurrency()->iso_code;
    }

    public function getImageType()
    {
        // get the medium image type
        $imageTypeName = array(
            self::FMPSV16 => 'large_default',
            self::FMPSV15 => 'large_default',
            self::FMPSV14 => 'large'
        );
        $versionName = $imageTypeName[$this->version];
        $imageTypes = ImageType::getImagesTypes();
        foreach ($imageTypes as $type) {
            if ($type['name'] == $versionName) {
                return $type;
            }
        }
        return '';
    }

    public function getImageLink($linkRewrite, $idImage, $imageType)
    {
        if ($this->version == self::FMPSV14) {
            $link = new Link();
            return $link->getImageLink($linkRewrite, $idImage, $imageType);
        }
        if ($this->version == self::FMPSV15 || $this->version == self::FMPSV16) {
            $context = $this->contextGetContext();
            return $context->link->getImageLink($linkRewrite, $idImage, $imageType);
        }
        return '';
    }

    public function getProductAttributes($product, $languageId)
    {
        $getAttrCombinations = array(
            self::FMPSV14 => 'getAttributeCombinaisons',
            self::FMPSV15 => 'getAttributeCombinations',
            self::FMPSV16 => 'getAttributeCombinations'
        );

        # get this products attributes and combination images
        return $product->$getAttrCombinations[$this->version]($languageId);
    }

    public function getPrice($product, $attributePrice = 0)
    {
        $price = $product->price + $attributePrice;
        // $tax_rules_group = new TaxRulesGroup($product->id_tax_rules_group);
        $currency = new Currency(Configuration::get($this->getModuleName() . '_currency'));
        if ($currency->conversion_rate) {
            $price = $price * $currency->conversion_rate;
        }
        return Tools::ps_round($price, 2);
    }

    public function getModuleName($moduleName = '')
    {
        $module = $this->moduleGetInstanceByName($moduleName);
        return $module->config_name;
    }

    public function getModulePath($moduleName = '')
    {
        $module = $this->moduleGetInstanceByName($moduleName);
        return $module->get('_path');
    }


    public function getTableName($moduleName, $tableSuffix, $prefix = false)
    {
        return ($prefix ? $this->globDbPrefix() : '') . $this->getModuleName() . $tableSuffix;
    }

    public function getCountryCode()
    {
        return $this->contextGetContext()->country->iso_code;
    }

    public function forceCreateDir($path, $rights)
    {
        if (!is_writable($path)) {
            return createDir($path, $rights);
        }
        return true;
    }

    public function markOrderAsDone($orderId, $orderDoneState)
    {
        $objOrder = new Order($orderId);
        $history = new OrderHistory();
        $history->id_order = (int)$objOrder->id;
        return $history->changeIdOrderState($orderDoneState, $objOrder);
    }

    public function getOrderState($state)
    {
        return new OrderState($state);
    }

    public function getOrderStateName($state)
    {
        $currentState = $this->getOrderState($state);
        return $currentState->name[1];
    }

    public function newPrestashopOrder()
    {
        // Create an order
        $prestaOrder = new Order();

        if ($this->version == self::FMPSV15 || $this->version == self::FMPSV16) {
            // create a internal reference for the order.
            $reference = Order::generateReference();
            $prestaOrder->reference = $reference;
        }
        return $prestaOrder;
    }


    public function isPs1516()
    {
        return in_array($this->version, array(self::FMPSV15, self::FMPSV16));
    }

    /**
     * Returns export file name depending on the shop context
     *
     * @return string export file name
     */
    public function getExportFileName()
    {
        if ($this->isPs1516() && Shop::getContext() === Shop::CONTEXT_SHOP) {
            return sprintf(self::EXPORT_FILE_NAME_PATTERN, self::getCurrentShopId());
        }
        // fallback to 0 for non-multistore setups
        return sprintf(self::EXPORT_FILE_NAME_PATTERN, 0);
    }


    /**
     * Returns the current shop id
     *
     * @return int
     */
    public function getCurrentShopId()
    {
        $context = Context::getContext();
        if (Shop::isFeatureActive() && $context->cookie->shopContext) {
            $split = explode('-', $context->cookie->shopContext);
            if (count($split) === 2) {
                return intval($split[1]);
            }
        }
        return intval($context->shop->id);
    }

    public function getTaxAddressType()
    {
        return Configuration::get('PS_TAX_ADDRESS_TYPE');
    }

    public function toolsPsRound($number, $round)
    {
        return (float)Tools::ps_round($number, $round);
    }

    // Global variables
    public function globalPsRootDir()
    {
        return _PS_ROOT_DIR_;
    }

    public function getBaseModuleUrl()
    {
        return _PS_BASE_URL_ . __PS_BASE_URI__;
    }

    public function globalGetVersion()
    {
        return _PS_VERSION_;
    }

    public function globDbPrefix()
    {
        return _DB_PREFIX_;
    }

    // Module
    public function moduleGetInstanceByName($name = '')
    {
        $name = $name ? $name : $this->moduleName;
        return  Module::getInstanceByName($name);
    }

    // Tool
    public function toolsIsSubmit($name)
    {
        return Tools::isSubmit($name);
    }

    public function toolsGetValue($name)
    {
        return Tools::getValue($name);
    }

    public function toolsRedirect($url)
    {
        return Tools::redirect($url, '');
    }

    public function toolsEncrypt($string)
    {
        return Tools::encrypt($string);
    }


    // Configuration
    public function configurationDeleteByName($name)
    {
        return Configuration::deleteByName($name);
    }

    public function configurationGet($name)
    {
        return Configuration::get($name);
    }

    public function configurationUpdateValue($name, $value)
    {
        return Configuration::updateValue($name, $value);
    }

    // Category
    public function categoryGetChildren($categoryId, $languageId)
    {
        return Category::getChildren($categoryId, $languageId);
    }

    // Currency
    public function currencyGetDefaultCurrency()
    {
        return Currency::getDefaultCurrency();
    }

    // OrderState
    public function orderStateGetOrderStates($languageId)
    {
        return OrderState::getOrderStates($languageId);
    }

    public function orderStateInvoiceAvailable($orderStateId)
    {
        return OrderState::invoiceAvailable($orderStateId);
    }

    // Language
    public function languageGetLanguages()
    {
        return Language::getLanguages();
    }

    // Context
    public function contextGetContext()
    {
        if ($this->context) {
            return $this->context;
        }

        if ($this->isPs1516()) {
            $this->context = Context::getContext();
            $this->context->id_lang = self::DEFAULT_LANGUAGE_ID;
            $this->context->currency = Currency::getDefaultCurrency();
            return $this->context;
        }
        global $cookie, $smarty;

        // mock the context for PS 1.4
        $this->context = new stdClass();
        $this->context->shop = new stdClass();
        $this->context->shop->id = Shop::getCurrentShop();
        $this->context->shop->id_shop_group = 1;

        $this->context->id_lang = self::DEFAULT_LANGUAGE_ID;
        $this->context->smarty = $smarty;

        if (!empty($cookie->id_currency)) {
            $this->context->currency = new Currency((int)$cookie->id_currency);
            $this->context->language = new Language((int)$cookie->id_lang);
            $this->context->country = new Country((int)$cookie->id_country);
            return $this->context;
        }
        $this->context->currency = Currency::getDefaultCurrency();
        $this->context->country = new Country((int)Country::getDefaultCountryId());
        $this->context->language = new Language((int)$this->context->id_lang);
        return $this->context;
    }

    // DB
    public function dbGetInstance()
    {
        return Db::getInstance();
    }

    public function dbUpdate($table, $data, $where = '', $limit = 0, $nullValues = false, $useCache = true, $addPrefix = true)
    {
        if ($this->isPs1516()) {
            return $this->dbGetInstance()->update(
                $table,
                $data,
                $where,
                $limit,
                $nullValues,
                $useCache,
                $addPrefix
            );
        }
        return $this->dbGetInstance()->autoExecute(
            $this->globDbPrefix() . $table,
            $data,
            'UPDATE',
            $where,
            $limit,
            $useCache
        );
    }

    public function dbInsert($table, $data, $nullValues = false, $useCache = true, $type = self::DB_INSERT, $addPrefix = true)
    {
        if ($this->isPs1516()) {
            return $this->dbGetInstance()->insert(
                $table,
                $data,
                $nullValues,
                $useCache,
                $type,
                $addPrefix
            );
        }
        return $this->dbGetInstance()->autoExecute(
            $this->globDbPrefix() . $table,
            $data,
            'INSERT',
            false,
            false,
            $useCache
        );
    }

    public function dbDelete($table, $where = '', $limit = 0, $useCache = true, $addPrefix = true)
    {
        if ($this->isPs1516()) {
            return $this->dbGetInstance()->delete(
                $table,
                $where,
                $limit,
                $useCache,
                $addPrefix
            );
        }
        return $this->dbGetInstance()->delete(
            $this->globDbPrefix() . $table,
            $where,
            $limit,
            $useCache
        );

    }

    // Manufacturer
    public function manufacturerGetNameById($manufacturerId)
    {
        return Manufacturer::getNameById($manufacturerId);
    }

    // Product

    public function productNew($idProduct = null, $full = false, $idLang = null, $idShop = null, $context = null)
    {
        return new Product($idProduct, $full, $idLang, $idShop, $context);
    }

    public function productGetQuantity($productId)
    {
        return Product::getQuantity($productId);
    }

    public function productGetTaxRate($product)
    {
        if ($this->isPs1516()) {
            return $product->getTaxesRate();
        }
        return Tax::getProductTaxRate($product->id, null);
    }

    // Address
    public function newAddress($idAddress = null, $idLang = null)
    {
        return new Address($idAddress, $idLang);
    }

    // Cart
    public function newCart()
    {
        return new Cart();
    }

    public function cartOnlyProducts()
    {
        return Cart::ONLY_PRODUCTS;
    }

    public function cartBoth()
    {
        return Cart::BOTH;
    }

    // Customer
    public function newCustomer()
    {
        return new Customer();
    }

    // DbQuery
    public function newDbQuery()
    {
        return new DbQuery();
    }

    // OrderHistory
    public function newOrderHistory()
    {
        return new OrderHistory();
    }

    // Message
    public function newMessage()
    {
        return new Message();
    }

    // OrderDetail
    public function newOrderDetail()
    {
        return new OrderDetail();
    }

    // Country
    public function newCountry($countryId, $languageId)
    {
        return new Country($countryId, $languageId);
    }

    public function newOrder($id = null, $idLang = null)
    {
        return new Order($id, $idLang);
    }
}
