<?php

class AdminOrdersController extends AdminOrdersControllerCore
{

    public function __construct()
    {
        parent::__construct();

        $module = $this->getFyndiqModule();

        $this->_join .= PHP_EOL . ' LEFT JOIN `' . _DB_PREFIX_ . 'FYNDIQMERCHANT_orders` fyn_o ON fyn_o.order_id = a.id_order';
        $this->_select .= ', IF(fyn_o.id is null, "-", fyn_o.fyndiq_orderid) AS fyndiq_order';

        // Add Table column
        $this->fields_list['fyndiq_order'] = array(
            'title' => $module->__('Fyndiq Order'),
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
            error_log('importFyndiqOrders');
        }
        parent::initProcess();
    }

    protected function getFyndiqModule()
    {
        return Module::getInstanceByName('fyndiqmerchant');
    }
}
