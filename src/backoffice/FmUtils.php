<?php

class FyndiqProductSKUNotFound extends Exception
{
}

class FmUtils
{
    const MODULE_NAME = 'fyndiqmerchant';
    const VERSION = '1.0.0';
    const COMMIT = 'XXXXXX';
    const REPOSITORY_PATH = 'fyndiq/fyndiq-prestashop-module/';
    const DISABLE_UPDATE_CHECK = 0;

    const LONG_DESCRIPTION = 1;
    const SHORT_DESCRIPTION = 2;
    const SHORT_AND_LONG_DESCRIPTION = 3;

    public static function getFileWriter($file)
    {
        return new FyndiqCSVFeedWriter($file);
    }
}
