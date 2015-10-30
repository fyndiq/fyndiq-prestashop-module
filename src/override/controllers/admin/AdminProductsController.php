<?php

class AdminProductsController extends AdminProductsControllerCore
{

    public function __construct()
    {
        parent::__construct();
        error_log('AdminProductsController');

        // Add Bulk actions
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

        $this->_join .= PHP_EOL . ' LEFT JOIN `' . _DB_PREFIX_ . 'FYNDIQMERCHANT_products` fyn_p ON fyn_p.product_id = a.id_product';

        // Add Table column
        $this->fields_list['state'] = array(
            'title' => $this->l('Exported to Fyndiq'),
        );

        // Add Actions
        $this->actions_available = array_merge($this->actions_available, array('export_to_fyndiq', 'remove_from_fyndiq'));
    }

    protected function processBulkExportToFyndiq(){
        if (is_array($this->boxes) && !empty($this->boxes)){
            error_log('EXPORT: ' . json_encode($this->boxes));
            $defaultDiscount = 0; // getDefaultDiscount();
            foreach ($this->boxes as $product_id){
                //INSERT OR UPDATE ($product_id, $defaultDiscount)
            }
        }
    }

    protected function processRemoveFromFyndiq(){
        if (is_array($this->boxes) && !empty($this->boxes)){
            error_log('REMOVE: ' . json_encode($this->boxes));
            $ids = array();
            foreach ($this->boxes as $product_id){
                $ids[] = intval($product_id);
            }
            // DELETE where product_id in ($ids)
        }
    }

    public function renderList()
    {
        $this->addRowAction('export_to_fyndiq');
        $this->addRowAction('remove_from_fyndiq');
        return parent::renderList();
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['update_fyndiq_status'] = array(
                    'href' => self::$currentIndex.'&update_fyndiq_status&token='.$this->token,
                    'desc' => $this->l('Update Fyndiq status', null, null, false),
                    'icon' => 'process-icon-refresh'
                );
        }
        return parent::initPageHeaderToolbar();
    }

}
