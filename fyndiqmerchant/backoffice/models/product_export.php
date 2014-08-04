<?php

class FmProductExport {
    public static function create($product_id, $fyndiq_id) {
        $module = Module::getInstanceByName('fyndiqmerchant');
        $ret = (bool)Db::getInstance()->Execute('INSERT INTO ' . _DB_PREFIX_ . $module->config_name . '_products (product_id,fyndiq_id) VALUES (' . FmHelpers::db_escape(
            $product_id
        ) . ',' . FmHelpers::db_escape($fyndiq_id) . ')');
        return $ret;
    }
}
