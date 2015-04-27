<?php

class FmProductInfo extends FyndiqPaginatedFetch
{

    function __construct()
    {
        $module = Module::getInstanceByName('fyndiqmerchant');
        $this->tableName = $module->config_name . '_products';
        $this->dbConn = DB::getInstance();
    }

    function getInitialPath()
    {
        return 'product_info/';
    }

    function getSleepIntervalSeconds()
    {
        return 1 / self::THROTTLE_PRODUCT_INFO_RPS;
    }

    /**
     * Get product single page products' info
     *
     * @param string $path
     * @return mixed
     */
    public function getPageData($path)
    {
        $ret = FmHelpers::callApi('GET', $path);
        return $ret['data'];
    }

    /**
     * Update product status
     *
     * @param mixed $data
     * @return bool
     */
     public function processData($data) {
        $result = true;
        foreach ($data as $statusRow) {
            $result &= FmProduct::updateProductStatus($this->dbConn, $this->tableName,
                $statusRow->product_id, $statusRow->for_sale);
        }
        return $result;
    }
}
