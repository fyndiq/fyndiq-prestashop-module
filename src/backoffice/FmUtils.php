<?php

class FyndiqProductSKUNotFound extends Exception
{
}

class FmUtils
{
    const MODULE_NAME = 'fyndiqmerchant';
    const VERSION = '1.0.4';
    const COMMIT = 'XXXXXX';
    const REPOSITORY_PATH = 'fyndiq/fyndiq-prestashop-module/';
    const DISABLE_UPDATE_CHECK = 0;

    const LONG_DESCRIPTION = 1;
    const SHORT_DESCRIPTION = 2;
    const SHORT_AND_LONG_DESCRIPTION = 3;

    const ORDERS_ENABLED = 0;
    const ORDERS_DISABLED = 1;

    const SKU_DEFAULT = 0;

    const SKU_REFERENCE = 0;
    const SKU_EAN = 1;
    const SKU_ID = 2;
    const FLAG_CANCEL = 6;

    const SKU_SEPARATOR = '-';


    public static function getFileWriter($file)
    {
        return new FyndiqCSVFeedWriter($file);
    }
}
