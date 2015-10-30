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
        $this->actions_available = array_merge($this->actions_available, array('export_to_fyndiq', 'remove_from_fyndiq'));
    }

    protected function processBulkExportToFyndiq(){
        if (is_array($this->boxes) && !empty($this->boxes)){
            error_log('EXPORT: ' . json_encode($this->boxes));
            foreach ($this->boxes as $product_id){

            }
        }
    }

    protected function processRemoveFromFyndiq(){
        if (is_array($this->boxes) && !empty($this->boxes)){
            error_log('REMOVE: ' . json_encode($this->boxes));
            foreach ($this->boxes as $product_id){

            }
        }
    }

}
