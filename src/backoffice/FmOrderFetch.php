<?php

class FmOrderFetch extends FyndiqPaginatedFetch
{

    protected $lastTimestamp = 0;

    function __construct($fmOrder, $fmApiModel, $importDate)
    {
        $this->fmOrder = $fmOrder;
        $this->fmApiModel = $fmApiModel;
        $this->importDate = $importDate;
    }

    function getInitialPath()
    {
        $url = 'orders/';
        if (!empty($this->importDate)) {
            $url .= '?min_date=' . urlencode($this->importDate);
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
            $timestamp = strtotime($order->created);
            if ($timestamp > $this->lastTimestamp) {
                $this->lastTimestamp = $timestamp;
            }
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

    function getLastTimestamp()
    {
        return $this->lastTimestamp;
    }
}
