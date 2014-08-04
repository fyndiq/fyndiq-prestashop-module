<?php

class FmProductExport {

    /**
     * install table to database
     *
     * @return bool
     */
    public static function install() {
        $module = Module::getInstanceByName('fyndiqmerchant');
        $ret = (bool)Db::getInstance()->Execute(
            'create table if not exists '._DB_PREFIX_.$module->config_name.'_products (
            id int(20) unsigned primary key,
            product_id int(10) unsigned,
            fyndiq_id int(20) unsigned)
        ');
        return $ret;
    }

    /**
     * Create a row in table for mapping the products together.
     *
     * @param $product_id
     * @param $fyndiq_id
     */
    public static function create($product_id, $fyndiq_id) {
        $module = Module::getInstanceByName('fyndiqmerchant');
        $sql = 'insert into '._DB_PREFIX_.$module->config_name.'_products set
            product_id = '.FmHelpers::db_escape($product_id).'
            fyndiq_id = '.FmHelpers::db_escape($fyndiq_id).'
        ';
    }

    /**
     * remove the table from database
     *
     * @return bool
     */
    public static function uninstall() {
        $module = Module::getInstanceByName('fyndiqmerchant');
        $ret = (bool)Db::getInstance()->Execute(
            'drop table ' . _DB_PREFIX_ . $module->config_name . '_products'
        );
        return $ret;
    }
}
