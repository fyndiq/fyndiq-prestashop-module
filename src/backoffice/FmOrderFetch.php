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
        foreach ($data as $order) {
            if (!$this->fmOrder->orderQueued(intval($order->id))) {
                $this->fmOrder->addToQueue($order);
            }
        }
        return true;
    }

    function getSleepIntervalSeconds()
    {
        return 1 / self::THROTTLE_ORDER_RPS;
    }
}
