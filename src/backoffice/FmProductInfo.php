<?php

class FmProductInfo extends FyndiqPaginatedFetch
{

    function __construct($fmProduct, $fmApiModel, $tableName)
    {
        $this->fmProduct = $fmProduct;
        $this->fmApiModel = $fmApiModel;
        $this->tableName = $tableName;
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
        $ret = $this->fmApiModel->callApi('GET', $path);
        return $ret['data'];
    }

    /**
     * Update product status
     *
     * @param mixed $data
     * @return bool
     */
    public function processData($data)
    {
        $result = true;
        foreach ($data as $statusRow) {
            $result &= $this->fmProduct->updateProductStatus(
                $this->tableName,
                $statusRow->product_id,
                $statusRow->for_sale
            );
        }
        return $result;
    }

    public function getAll()
    {
        $this->fmProduct->updateAllProductStatus($this->tableName, FmProductExport::PENDING);
        return parent::getAll();
    }
}
