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

    public static function getFileWriter($file)
    {
        return new FyndiqCSVFeedWriter($file);
    }
}
