<?php

class FmModel
{

    protected $fmPrestashop;
    protected $fmConfig;
    protected $storeId;

    public function __construct($fmPrestashop, $fmConfig, $storeId = -1)
    {
        $this->fmPrestashop = $fmPrestashop;
        $this->fmConfig = $fmConfig;
        $this->storeId = $storeId;
    }

    public function getAllTables()
    {
        $result = array();
        $sql = 'SHOW TABLES';
        $tables = $this->fmPrestashop->dbGetInstance()->ExecuteS($sql);
        foreach ($tables as $table) {
            $result[] = current($table);
        }
        return $result;
    }
}
