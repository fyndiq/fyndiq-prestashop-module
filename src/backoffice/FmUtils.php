<?php

class FyndiqProductSKUNotFound extends Exception
{
}

class FmUtils
{
    const MODULE_NAME = 'fyndiqmerchant';
    const VERSION = '1.0.0';

    public static function getFileWriter($file)
    {
        return new FyndiqCSVFeedWriter($file);
    }
}
