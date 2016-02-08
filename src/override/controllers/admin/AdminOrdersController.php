<?php

class AdminOrdersController extends AdminOrdersControllerCore
{

    public function __construct()
    {
        parent::__construct();

        $this->module = $this->getFyndiqModule();
        $this->fmPrestashop = new FmPrestashop('fyndiqmerchant');
        $this->fmConfig = new fmConfig($this->fmPrestashop);

        $this->_join .= PHP_EOL . ' LEFT JOIN `' . _DB_PREFIX_ . 'FYNDIQMERCHANT_orders` fyn_o ON fyn_o.order_id = a.id_order';
        $this->_select .= ', IF(fyn_o.id is null, "-", fyn_o.fyndiq_orderid) AS fyndiq_order';

        // Add Table column
        $this->fields_list['fyndiq_order'] = array(
            'title' => $this->module->__('Fyndiq Order'),
        );
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['importFyndiqOrders'] = array(
                    'href' => self::$currentIndex .'&importFyndiqOrders&token=' . $this->token,
                    'desc' => $this->l('Import Fyndiq orders', null, null, false),
                    'icon' => 'process-icon-refresh'
                );
        }
        return parent::initPageHeaderToolbar();
    }

    public function initProcess()
    {
        if (Tools::isSubmit('importFyndiqOrders')) {
            $this->importOrdersToFyndiq();
        }
        parent::initProcess();
    }

    protected function importOrdersToFyndiq()
    {
        $storeId = $this->fmPrestashop->getStoreId();
        $importOrdersStatus = $this->fmConfig->get('disable_orders', $storeId);
        if ($importOrdersStatus === FmUtils::ORDERS_DISABLED) {
            $this->errors[] = $this->module->__('Orders are disabled.');
            return false;
        }
        $importDate = $this->fmConfig->get('import_date', $storeId);
        $fmOrder = $this->module->getModel('FmOrder', $storeId);
        $fmApiModel = $this->module->getModel('FmApiModel', $storeId);

        try {
            $fmOrderFetch = new FmOrderFetch($fmOrder, $fmApiModel, $importDate);
            $fmOrderFetch->getAll();
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }

        $idOrderState = $this->fmConfig->get('import_state', $storeId);
        $taxAddressType = $this->fmPrestashop->getTaxAddressType();
        $skuTypeId = intval($this->fmConfig->get('sku_type_id', $storeId));

        try {
            $fmOrder->processFullOrderQueue($idOrderState, $taxAddressType, $skuTypeId);
        } catch (Exception $e) {
            $this->errors = array_merge($this->errors, explode("\n", $e->getMessage()));
        }
        $time = $fmOrderFetch->getLastTimestamp();
        if ($time) {
            $newDate = date('Y-m-d H:i:s', $time);
            $this->fmConfig->set('import_date', $newDate, $storeId);
        }
    }

    protected function getFyndiqModule()
    {
        return Module::getInstanceByName('fyndiqmerchant');
    }
}
