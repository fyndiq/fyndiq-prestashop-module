<?php

class FmOrderFetch extends FyndiqPaginatedFetch
{

    protected $lastTimestamp = 0;

    public function __construct($fmOrder, $fmApiModel, $importDate)
    {
        $this->fmOrder = $fmOrder;
        $this->fmApiModel = $fmApiModel;
        $this->importDate = $importDate;
    }

    public function getInitialPath()
    {
        $url = 'orders/';
        if (!empty($this->importDate)) {
            $url .= '?min_date=' . urlencode($this->importDate);
        }
        return $url;
    }

    public function getPageData($path)
    {
        $ret = $this->fmApiModel->callApi('GET', $path);
        return $ret['data'];
    }

    public function processData($data)
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

    public function getSleepIntervalSeconds()
    {
        return 1 / self::THROTTLE_ORDER_RPS;
    }

    public function getLastTimestamp()
    {
        return $this->lastTimestamp;
    }
}
