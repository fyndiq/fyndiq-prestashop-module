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
}
