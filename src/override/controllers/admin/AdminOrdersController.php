<?php

class AdminOrdersController extends AdminOrdersControllerCore
{

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

    public function initProcess() {
        if (Tools::isSubmit('importFyndiqOrders')) {
            error_log('importFyndiqOrders');
        }
        parent::initProcess();
    }

}
