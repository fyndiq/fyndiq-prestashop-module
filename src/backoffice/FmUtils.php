<?php

class FyndiqProductSKUNotFound extends Exception
{
}

class FmUtils
{
    const MODULE_NAME = 'fyndiqmerchant';
    const VERSION = '1.0.0';

    const EXPORT_FILE_NAME_PATTERN = 'feed-%d.csv';

    /**
     * Returns export file name depending on the shop context
     *
     * @return string export file name
     */
    public static function getExportFileName()
    {
        if (Shop::getContext() === Shop::CONTEXT_SHOP) {
            return sprintf(self::EXPORT_FILE_NAME_PATTERN, self::getCurrentShopId());
        }
        // fallback to 0 for non-multistore setups
        return sprintf(self::EXPORT_FILE_NAME_PATTERN, 0);
    }

    public static function getFileWriter($file)
    {
        return new FyndiqCSVFeedWriter($file);
    }
}
