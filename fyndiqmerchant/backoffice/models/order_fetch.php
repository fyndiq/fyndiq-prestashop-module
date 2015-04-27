<?php

class FmOrderFetch extends FyndiqPaginatedFetch
{

    function getInitialPath()
    {
        $url = 'orders/';
        $date = FmConfig::get('import_date');
        if (!empty($date)) {
            $url .= '?min_date=' . urlencode($date);
        }
        return $url;
    }

    function getPageData($path)
    {
        $ret = FmHelpers::callApi('GET', $path);
        return $ret['data'];
    }

    function processData($data)
    {
        foreach ($data as $order) {
            if (!FmOrder::orderExists($order->id)) {
                FmOrder::create($order);
            }
        }
        return true;
    }

    function getSleepIntervalSeconds()
    {
        return 1 / self::THROTTLE_ORDER_RPS;
    }
}
