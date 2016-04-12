<?php

class AdminProductsController extends AdminProductsControllerCore
{
    public function __construct()
    {
        parent::__construct();

        $module = $this->getFyndiqModule();
        $this->fmPrestashop = $module->getFmPrestashop();

        // Add Bulk actions
        $this->bulk_actions['export_to_fyndiq'] = array(
            'text' => $module->__('Export to Fyndiq'),
            'icon' => 'icon-plus',
            'confirm' => $module->__('Are you sure you want to export the selected products to Fyndiq?')
        );
        $this->bulk_actions['remove_from_fyndiq'] = array(
            'text' => $module->__('Remove from Fyndiq'),
            'icon' => 'icon-minus',
            'confirm' => $module->__('Are you sure you want to remove the selected products from Fyndiq?')
        );

        $this->_join .= PHP_EOL . ' LEFT JOIN `' . $this->fmPrestashop->getTableName(FmUtils::MODULE_NAME, '_products', true) . '` fyn_p ON fyn_p.product_id = a.id_product';
        $this->_select .= ', IF(fyn_p.id is null, "-", "' . $module->__('Exported') . '") AS fyndiq_exported';

        // Add Table column
        $this->fields_list['fyndiq_exported'] = array(
            'title' => $module->__('Fyndiq'),
        );

        // Add Actions
        $this->actions_available = array_merge($this->actions_available, array('export_to_fyndiq', 'remove_from_fyndiq'));
    }

    protected function getFyndiqModule()
    {
        return Module::getInstanceByName('fyndiqmerchant');
    }

    protected function processBulkExportToFyndiq()
    {
        $module = $this->getFyndiqModule();
        if (is_array($this->boxes) && !empty($this->boxes)) {
            if (Shop::getContext() == Shop::CONTEXT_SHOP) {
                $shopId = (int)$this->context->shop->getContextShopID();
                $fmProductExport = $module->getModel('FmProductExport', $shopId);
                foreach ($this->boxes as $productId) {
                    $fmProductExport->exportProduct($productId, $shopId);
                }
                return true;
            }
            $this->errors[] = $module->__('Please select store context');
            return false;
        }
        $this->errors[] = $module->__('Please select products to be exported to Fyndiq');
        return false;
    }

    protected function processBulkRemoveFromFyndiq()
    {
        if (is_array($this->boxes) && !empty($this->boxes)) {
            if (Shop::getContext() == Shop::CONTEXT_SHOP) {
                $shopId = (int)$this->context->shop->getContextShopID();
                $fmProductExport = $this->getFyndiqModule()->getModel('FmProductExport', $shopId);
                foreach ($this->boxes as $productId) {
                    $fmProductExport->removeProduct($productId, $shopId);
                }
                return true;
            }
            $this->errors[] = $module->__('Please select store context');
            return false;
        }
        $this->errors[] = $module->__('Please select products to be removed from Fyndiq');
        return false;
    }
}
