<?php


class FmPrestashop {

    /*
     *  Global variables
     */
    public function globalPsRootDir() {
        return _PS_ROOT_DIR_;
    }

    /*
     *  Tool
     */
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


    /*
     *  Configuration
     */

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

    /*
     * Category
     */
    public function categoryGetChildren($categoryId, $languageId) {
        return Category::getChildren($categoryId, $languageId);
    }

    /*
     * Currency
     */
    public function currencyGetDefaultCurrency() {
        return Currency::getDefaultCurrency();
    }

    /*
     * OrderState
     */
    public function orderStateGetOrderStates($languageId)
    {
        return OrderState::getOrderStates($languageId);
    }

    public function orderStateInvoiceAvailable($orderStateId)
    {
        return OrderState::invoiceAvailable($orderStateId);
    }

    /*
     * Language
     */
    public function languageGetLanguages(){
        return Language::getLanguages();
    }

    /*
     * Context
     */
    public function contextGetContext(){
        return Context::getContext();
    }


}
