<?php

class FmPrestashop
{

    // FyndiqMerchant PrestaShop Version 1.4|1.5|1.6
    const FMPSV14 = 'FMPSV14';
    const FMPSV15 = 'FMPSV15';
    const FMPSV16 = 'FMPSV16';

    public $fmPsv = '';

    public function __construct()
    {
        $version = $this->globalGetVersion();

        if (stripos($version, '1.4.') === 0) {
            $this->fmPsv = self::FMPSV14;
        }
        if (stripos($version, '1.5.') === 0) {
            $this->fmPsv = self::FMPSV15;
        }
        if (stripos($version, '1.6.') === 0) {
            $this->fmPsv = self::FMPSV16;
        }
    }

    // Custom
    public function getModuleUrl()
    {
        $url = $this->getBaseModuleUrl();
        $url .= substr(strrchr(_PS_ADMIN_DIR_, '/'), 1);
        $url .= "/index.php?controller=AdminModules&configure=fyndiqmerchant&module_name=fyndiqmerchant";
        $url .= '&token=' . Tools::getAdminTokenLite('AdminModules');
        return $url;
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
        if ($this->fmPsv == self::FMPSV14) {
            return pSQL($value);
        }
        return Db::getInstance()->_escape($value);
    }

    public function getShopUrl()
    {
        if (Shop::getContext() === Shop::CONTEXT_SHOP) {
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

    // Global variables
    public function globalPsRootDir()
    {
        return _PS_ROOT_DIR_;
    }

    public function getBaseModuleUrl()
    {
        return _PS_BASE_URL_ . __PS_BASE_URI__;
    }

    private function globalGetVersion()
    {
        return _PS_VERSION_;
    }

    public function globDbPrefix()
    {
        return _DB_PREFIX_;
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
        return Tools::redirect($url);
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
        return $this->fmPrestashop->configurationUpdateValue($this->key($name), $value);
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
        return Context::getContext();
    }

    // DB
    public function dbGetInstance()
    {
        return Db::getInstance();
    }

    // Manufacturer
    public function manufacturerGetNameById($manufacturerId)
    {
        return Manufacturer::getNameById($manufacturerId);
    }

    // Product
    public function productGetQuantity($productId)
    {
        return Product::getQuantity($productId);
    }

}
