<?php

class FmOrderFetch extends FyndiqPaginatedFetch
{

    function __construct($fmPrestashop, $fmConfig, $fmOrder, $fmApiModel)
    {
        $this->fmPrestashop = $fmPrestashop;
        $this->fmConfig = $fmConfig;
        $this->fmOrder = $fmOrder;
        $this->fmApiModel = $fmApiModel;
    }

    function getInitialPath()
    {
        $url = 'orders/';
        $date = $this->fmConfig->get('import_date');
        if (!empty($date)) {
            $url .= '?min_date=' . urlencode($date);
        }
        return $url;
    }

    function getPageData($path)
    {
        $ret = $this->fmApiModel->callApi('GET', $path);
        return $ret['data'];
    }

    function processData($data)
    {
        $idOrderState = $this->fmConfig->get('import_state');
        $taxAddressType = $this->fmPrestashop->getTaxAddressType();
        foreach ($data as $order) {
            if (!$this->fmOrder->orderExists($order->id)) {
                $this->fmOrder->create($order, $idOrderState, $taxAddressType);
            }
        }
        return true;
    }

    function getSleepIntervalSeconds()
    {
        return 1 / self::THROTTLE_ORDER_RPS;
    }
}
