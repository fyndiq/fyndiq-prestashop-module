<?php

class FmConfig
{

    const CONFIG_NAME = 'FYNDIQMERCHANT';

    protected $fmPrestashop;

    public function __construct($fmPrestashop)
    {
        $this->fmPrestashop = $fmPrestashop;
    }

    private function key($name)
    {
        return self::CONFIG_NAME . '_' . $name;
    }

    public function delete($name)
    {
        return $this->fmPrestashop->configurationDeleteByName($this->key($name));
    }

    public function get($name)
    {
        return $this->fmPrestashop->configurationGet($this->key($name));
    }

    public function set($name, $value)
    {
        return $this->fmPrestashop->configurationUpdateValue($this->key($name), $value);
    }

    public function isAuthorized()
    {
        $ret = true;
        $ret &= $this->get('username') !== false;
        $ret &= $this->get('api_token') !== false;

        return (bool)$ret;
    }

    public function isSetUp()
    {
        $ret = true;
        $ret &= $this->get('language') !== false;
        $ret &= $this->get('import_state') !== false;
        $ret &= $this->get('done_state') !== false;

        return (bool)$ret;
    }
}
