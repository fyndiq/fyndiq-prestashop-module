<?php
class AdminOrdersController extends AdminOrdersControllerCore
{

    public function __construct()
    {
        parent::__construct();

        $module = $this->getFyndiqModule();

          // Add Bulk actions
        $this->bulk_actions['download_delivery_notes'] = array(
            'text' => $module->__('Download Delivery Notes')
        );

        $this->_join .= PHP_EOL . ' LEFT JOIN `' . _DB_PREFIX_ . 'FYNDIQMERCHANT_orders` fyn_o ON fyn_o.order_id = a.id_order';
        $this->_select .= ', IF(fyn_o.id is null, "-", fyn_o.fyndiq_orderid) AS fyndiq_order';

        // Add Table column
        $this->fields_list['fyndiq_order'] = array(
            'title' => $module->__('Fyndiq Order'),
        );

            // Add Actions
        $this->actions_available = array_merge($this->actions_available, array('download_delivery_notes'));
    }

    protected function processBulkDownloadDeliveryNotes()
    {
        $module = $this->getFyndiqModule();
        $fynOrdersId = array();
        $invalidOrdId = array();
        $filterFynOrd = array(
            'orders' => array()
        );

        if (is_array($this->boxes) && !empty($this->boxes)) {
            if (Shop::getContext() == Shop::CONTEXT_SHOP) {
                $fmOrder = $module->getModel('FmOrder');
                // filter the fyndiq order ID
                foreach ($this->boxes as $orderId) {
                    if ($fynOrderId = $fmOrder->FynOrderExists($orderId)) {
                        $filterFynOrd['orders'][] = array('order' => intval($fynOrderId));
                        array_push($fynOrdersId, intval($fynOrderId));
                    } else {
                        array_push($invalidOrdId, $orderId);
                    }
                }
                // generating PDF file
                if (!count($invalidOrdId)) {
                    try {
                           $ret = $module->getApiModel()->callApi('POST', 'delivery_notes/', $filterFynOrd);
                           $fileName = 'delivery_notes-' . implode('_', $fynOrdersId) .'.pdf';
                        if ($ret['status'] == 200) {
                            $file = fopen('php://temp', 'wb+');
                            // Saving data to file
                            fputs($file, $ret['data']);
                            $module->getFmOutput()->streamFile($file, $fileName, 'application/pdf', strlen($ret['data']));
                            fclose($file);
                            return null;
                        }
                            return FyndiqTranslation::get('unhandled-error-message');
                    } catch (Exception $e) {
                        $module->getFmOutput->output($e->getMessage());
                        return null;
                    }

                    return true;
                } else {

                    $this->errors[] = $module->__(implode(',', $invalidOrdId) . ' these are not Fyndiq Order. Please select only Fyndiq order');
                    return false;
                }

            }

        }
        $this->errors[] = $module->__('Please, pick at least one order');
        return false;
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
