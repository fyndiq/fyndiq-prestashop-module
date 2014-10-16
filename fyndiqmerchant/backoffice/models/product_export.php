<?php

class FmProductExport {

    /**
     * Create a row in table for mapping the products together.
     *
     * @param $product_id
     * @param $fyndiq_id
     * @return bool
     */
    public static function create($product_id, $fyndiq_id) {
        $module = Module::getInstanceByName('fyndiqmerchant');
        $ret = (bool)Db::getInstance()->Execute('INSERT INTO ' . _DB_PREFIX_ . $module->config_name . '_products (product_id,fyndiq_id) VALUES (' . FmHelpers::db_escape(
            $product_id
        ) . ',' . FmHelpers::db_escape($fyndiq_id) . ')');
        return $ret;
    }
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
            fyndiq_id int(20) unsigned,
            exported_amount int(20) unsigned)
        ');
        return $ret;
    }

    public static function updateStock($product_id, $amount) {
        $module = Module::getInstanceByName('fyndiqmerchant');
        $ret = (bool)Db::getInstance()->Execute('UPDATE ' . _DB_PREFIX_ . $module->config_name . '_products
SET exported_amount=' . FmHelpers::db_escape($amount) . '
WHERE product_id='.FmHelpers::db_escape($product_id).';');
        return $ret;
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
