<?php

class AdminProductsController extends AdminProductsControllerCore
{

    protected function getFyndiqModule()
    {
        return Module::getInstanceByName('fyndiqmerchant');
    }

    public function __construct()
    {
        parent::__construct();

        // Add Bulk actions
        $this->bulk_actions['export_to_fyndiq'] = array(
            'text' => $this->l('Export to Fyndiq'),
            'icon' => 'icon-plus',
            'confirm' => $this->l('Export to Fyndiq?')
        );
        $this->bulk_actions['remove_from_fyndiq'] = array(
            'text' => $this->l('Remove from Fyndiq'),
            'icon' => 'icon-minus',
            'confirm' => $this->l('Remove from Fyndiq?')
        );

        $this->_join .= PHP_EOL . ' LEFT JOIN `' . _DB_PREFIX_ . 'FYNDIQMERCHANT_products` fyn_p ON fyn_p.product_id = a.id_product';
        $this->_select .= ', IF(fyn_p.id is null, "-", "Exported") AS fyndiq_exported';

        // Add Table column
        $this->fields_list['fyndiq_exported'] = array(
            'title' => $this->l('Fyndiq'),
        );

        // Add Actions
        $this->actions_available = array_merge($this->actions_available, array('export_to_fyndiq', 'remove_from_fyndiq'));
    }

    protected function processBulkExportToFyndiq()
    {
        if (is_array($this->boxes) && !empty($this->boxes)) {
            if (Shop::getContext() == Shop::CONTEXT_SHOP) {
                $shopId = (int)$this->context->shop->getContextShopID();
                error_log('EXPORT: ' . json_encode($this->boxes));
                $fmProductExport = $this->getFyndiqModule()->getModel('FmProductExport');
                foreach ($this->boxes as $productId) {
                    $fmProductExport->exportProduct($productId, $shopId);
                }
                return true;
            }
            // ERROR: not store context
            return false;
        }
        // ERROR: nothing selected
        return false;
    }

    protected function processBulkRemoveFromFyndiq()
    {
        if (is_array($this->boxes) && !empty($this->boxes)) {
            if (Shop::getContext() == Shop::CONTEXT_SHOP) {
                $shopId = (int)$this->context->shop->getContextShopID();
                $fmProductExport = $this->getFyndiqModule()->getModel('FmProductExport');
                foreach ($this->boxes as $productId) {
                    $fmProductExport->removeProduct($productId, $shopId);
                }
                return true;
            }
            // ERROR: not store context
            return false;
        }
        // ERROR: nothing selected
        return false;
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['updateFyndiqStatus'] = array(
                    'href' => self::$currentIndex.'&updateFyndiqStatus&token='.$this->token,
                    'desc' => $this->l('Update Fyndiq status', null, null, false),
                    'icon' => 'process-icon-refresh'
                );
        }
        return parent::initPageHeaderToolbar();
    }

    public function initProcess()
    {
        if (Tools::isSubmit('updateFyndiqStatus')) {
            error_log('updateFyndiqStatus');
        }
        parent::initProcess();
    }
}
