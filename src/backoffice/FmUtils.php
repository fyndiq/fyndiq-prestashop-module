<?php

class FyndiqProductSKUNotFound extends Exception
{
}

class FmUtils
{
    const MODULE_NAME = 'fyndiqmerchant';
    const VERSION = '1.0.0';
    const COMMIT = 'XXXXXX';

    public static function getFileWriter($file)
    {
        return new FyndiqCSVFeedWriter($file);
    }
}
