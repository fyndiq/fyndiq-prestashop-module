<?php

class AdminProductsController extends AdminProductsControllerCore
{

    public function __construct()
    {
        parent::__construct();
        error_log('AdminProductsController' . time());
        $this->bulk_actions['export_to_fyndiq'] = array(
            'text' => $this->l('Export to Fyndiq'),
            'icon' => 'icon-plus',
            'confirm' => $this->l('Export to Fyndiq?')
        );
        $this->bulk_actions['remove_from_fyndiq'] = array(
            'text' => $this->l('Remove to Fyndiq'),
            'icon' => 'icon-minus',
            'confirm' => $this->l('Remove from Fyndiq?')
        );
    }
}
