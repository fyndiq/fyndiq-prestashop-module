<?php

class FyndiqProductSKUNotFound extends Exception
{
}

class FmUtils
{
    const MODULE_NAME = 'fyndiqmerchant';
    const VERSION = '2.0.0';
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

    const SKU_SEPARATOR = '-';

    const DEFAULT_DISCOUNT_PERCENTAGE = 10;
    const DEFAULT_ORDER_IMPORT_STATE = 3;
    const DEFAULT_ORDER_DONE_STATE = 4;
    const DEFAULT_CUSTOMER_GROUP_ID = 1;


    public static function getFileWriter($file)
    {
        return new FyndiqCSVFeedWriter($file);
    }

    public static function jsonEncode($data)
    {
        if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
            return json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        return json_encode($data);
    }

    public static function getConfigKeys($languageId = '')
    {
        return array(
                'username' => '',
                'api_token' => '',
                'disable_orders' => self::ORDERS_ENABLED,
                'language' => $languageId,
                'price_percentage' => self::DEFAULT_DISCOUNT_PERCENTAGE,
                'stock_min' => 0,
                'customerGroup_id' => self::DEFAULT_CUSTOMER_GROUP_ID,
                'description_type' => self::LONG_DESCRIPTION,
                'import_state' =>self::DEFAULT_ORDER_IMPORT_STATE,
                'done_state' =>self::DEFAULT_ORDER_DONE_STATE,
                'ping_token' => ''
        );
    }
}
