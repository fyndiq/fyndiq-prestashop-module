<?php


class FmPrestashop {

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

    public function getCurrency($currencyId){
        return new Currency($currencyId);
    }

    // Global variables
    public function globalPsRootDir() {
        return _PS_ROOT_DIR_;
    }

    public function getBaseModuleUrl()
    {
        return _PS_BASE_URL_ . __PS_BASE_URI__;
    }

    // Tool
    public function toolsIsSubmit($name) {
        return Tools::isSubmit($name);
    }

    public function toolsGetValue($name) {
        return Tools::getValue($name);
    }

    public function toolsRedirect($url) {
        return Tools::redirect($url);
    }

    public function toolsEncrypt($string) {
        return Tools::encrypt($string);
    }


    // Configuration
    public function configurationDeleteByName($name) {
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
    public function categoryGetChildren($categoryId, $languageId) {
        return Category::getChildren($categoryId, $languageId);
    }

    // Currency
    public function currencyGetDefaultCurrency() {
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
    public function languageGetLanguages(){
        return Language::getLanguages();
    }

    // Context
    public function contextGetContext(){
        return Context::getContext();
    }
}
