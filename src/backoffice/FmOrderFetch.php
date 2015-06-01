<?php

class FmOrderFetch extends FyndiqPaginatedFetch
{

    function __construct($fmConfig, $fmOrder, $fmApiModel)
    {
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
        foreach ($data as $order) {
            if (!$this->fmOrder->orderExists($order->id)) {
                $this->fmOrder->create($order);
            }
        }
        return true;
    }

    function getSleepIntervalSeconds()
    {
        return 1 / self::THROTTLE_ORDER_RPS;
    }
}
