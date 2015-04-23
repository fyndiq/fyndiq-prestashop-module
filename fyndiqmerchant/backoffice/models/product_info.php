<?php

class FmProductInfo
{

    const SLEEP_INTERVAL_SEC = 1;
    const NS_IN_SEC = 1000000;

    function __construct()
    {
        $module = Module::getInstanceByName('fyndiqmerchant');
        $this->tableName = $module->config_name . '_products';
        $this->dbConn = DB::getInstance();
    }

    public function getAll()
    {
        $nextPagePath = 'product_info/';
        do {
            $data = $this->getPageData($nextPagePath);
            $start = microtime(true);
            $result = false;
            if ($data) {
                $result = $this->updateProducts($data->results);
                $nextPagePath= $this->getPath($data->next);
            }
            // Sleep the remaining microseconds
            usleep($this->getUSleepInterval($start, microtime(true), self::SLEEP_INTERVAL_SEC * self::NS_IN_SEC));
        } while ($result && $nextPagePath);
        return $result;
    }

    public function getPageData($path)
    {
        $ret = FmHelpers::callApi('GET', $path);
        return $ret['data'];
    }

    private function getUSleepInterval($start, $stop, $max)
    {
        return min($max, $max - intval((microtime(true) - $start) * self::NS_IN_SEC));
    }

    private function getPath($url)
    {
        if (empty($url)) {
            return false;
        }
        return implode('/', array_slice(explode('/', $url), 5));
    }

    private function updateProducts($data) {
        $result = true;
        foreach ($data as $statusRow) {
            $result &= FmProduct::updateProductStatus($this->dbConn, $this->tableName,
                $statusRow->product_id, $statusRow->for_sale);
        }
        return $result;
    }
}
