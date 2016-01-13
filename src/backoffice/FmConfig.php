<?php

class FmConfig
{

    const CONFIG_NAME = 'FYNDIQMERCHANT';
    const DEFAULT_STORE_ID = 1;

    protected $fmPrestashop;

    public function __construct($fmPrestashop)
    {
        $this->fmPrestashop = $fmPrestashop;
    }

    private function key($name, $storeId)
    {
        if ($storeId === false) {
            $storeId = $this->fmPrestashop->getStoreId();
        }
        if ($storeId == self::DEFAULT_STORE_ID) {
            return self::CONFIG_NAME . '_' . $name;
        }
        return self::CONFIG_NAME . '_' .$storeId . '_' . $name;
    }

    public function delete($name, $storeId)
    {
        return $this->fmPrestashop->configurationDeleteByName($this->key($name, $storeId));
    }

    public function get($name, $storeId)
    {
        return $this->fmPrestashop->configurationGet($this->key($name, $storeId));
    }

    public function set($name, $value, $storeId)
    {
        return $this->fmPrestashop->configurationUpdateValue($this->key($name, $storeId), $value);
    }

    public function isAuthorized($storeId)
    {
        $ret = true;
        $ret &= $this->get('username', $storeId) !== false;
        $ret &= $this->get('api_token', $storeId) !== false;

        return (bool)$ret;
    }

    public function isSetUp($storeId)
    {
        $ret = true;
        $ret &= $this->get('language', $storeId) !== false;
        $ret &= $this->get('import_state', $storeId) !== false;
        $ret &= $this->get('done_state', $storeId) !== false;
        return (bool)$ret;
    }
}
