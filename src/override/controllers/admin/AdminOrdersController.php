<?php

class AdminOrdersController extends AdminOrdersControllerCore
{

    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['update_fyndiq_status'] = array(
                    'href' => self::$currentIndex .'&import_fyndiq_orders&token=' . $this->token,
                    'desc' => $this->l('Import Fyndiq orders', null, null, false),
                    'icon' => 'process-icon-refresh'
                );
        }
        return parent::initPageHeaderToolbar();
    }


}
