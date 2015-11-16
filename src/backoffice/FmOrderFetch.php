<?php

class FmOrderFetch extends FyndiqPaginatedFetch
{

    function __construct($fmPrestashop, $fmConfig, $fmOrder, $fmApiModel)
    {
        $this->fmPrestashop = $fmPrestashop;
        $this->fmConfig = $fmConfig;
        $this->fmOrder = $fmOrder;
        $this->fmApiModel = $fmApiModel;
        $this->storeId = $this->fmPrestashop->getStoreId();
    }

    function getInitialPath()
    {
        $url = 'orders/';
        $date = $this->fmConfig->get('import_date', $this->storeId);
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
        $idOrderState = $this->fmConfig->get('import_state', $this->storeId);
        $taxAddressType = $this->fmPrestashop->getTaxAddressType();
        $skuTypeId = intval($this->fmConfig->get('sku_type_id', $this->storeId));
        foreach ($data as $order) {
            if (!$this->fmOrder->orderExists($order->id)) {
                $this->fmOrder->create($order, $idOrderState, $taxAddressType, $skuTypeId);
            }
        }
        return true;
    }

    function getSleepIntervalSeconds()
    {
        return 1 / self::THROTTLE_ORDER_RPS;
    }
}
