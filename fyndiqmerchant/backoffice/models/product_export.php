<?php

class FmProductExport {
    public static function create($product_id, $fyndiq_id) {
        $module = Module::getInstanceByName('fyndiqmerchant');
        $sql = 'insert into '._DB_PREFIX_.$module->config_name.'_products set
            product_id = '.FmHelpers::db_escape($product_id).'
            fyndiq_id = '.FmHelpers::db_escape($fyndiq_id).'
        ';
    }
}
