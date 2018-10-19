<?php

class FmModel
{

    protected $fmPrestashop;
    protected $fmConfig;

    public function __construct($fmPrestashop, $fmConfig)
    {
        $this->fmPrestashop = $fmPrestashop;
        $this->fmConfig = $fmConfig;
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
