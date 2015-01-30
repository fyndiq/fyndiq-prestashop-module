<?php

class FmProductExport
{

    static function productExist($product_id)
    {
        $module = Module::getInstanceByName('fyndiqmerchant');
        $sql = "SELECT * FROM " . _DB_PREFIX_ . $module->config_name . "_products WHERE product_id='" . $product_id . "' LIMIT 1";
        $data = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS($sql);

        return count($data) > 0;
    }

    static function addProduct($product_id, $export_qty, $exported_price_percentage)
    {
        $module = Module::getInstanceByName('fyndiqmerchant');
        $data = array(
            'product_id' => (int)$product_id,
            'exported_qty' => (int)$export_qty,
            'exported_price_percentage' => (int)$exported_price_percentage
        );
        $return = Db::getInstance()->insert($module->config_name . "_products", $data);

        return $return;
    }

    public static function updateProduct($product_id, $export_qty, $exported_price_percentage)
    {
        $module = Module::getInstanceByName('fyndiqmerchant');
        $data = array('exported_qty' => $export_qty, 'exported_price_percentage' => $exported_price_percentage);

        return (bool)Db::getInstance()->update(
            $module->config_name . "_products",
            $data,
            "product_id == '{$product_id}'",
            1
        );
    }

    public static function deleteProduct($product_id)
    {
        $module = Module::getInstanceByName('fyndiqmerchant');

        Db::getInstance()->delete($module->config_name . "_products_combos", "product_id == '{$product_id}'");

        return (bool)Db::getInstance()->delete($module->config_name . "_products", "product_id == '{$product_id}'", 1);
    }


    /**
     * Check if combonation exists
     *
     * @param $product_id
     * @param $combo_id
     * @return bool
     */
    static function comboExist($product_id, $combo_id)
    {
        $module = Module::getInstanceByName('fyndiqmerchant');
        $sql = "SELECT * FROM " . _DB_PREFIX_ . $module->config_name . "_products_combos WHERE product_id='" . $product_id . "' AND combo_id='" . $combo_id . "' LIMIT 1";
        $data = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS($sql);

        return count($data) > 0;
    }

    /**
     * add a combo for product
     *
     * @param integer $product_id
     * @param integer $combo_id
     * @param integer $export_qty
     * @param integer $exported_price_percentage
     * @return mixed
     */
    static function addCombo($product_id, $combo_id, $export_qty, $exported_price_percentage)
    {
        $module = Module::getInstanceByName('fyndiqmerchant');
        $data = array(
            'product_id' => (int)$product_id,
            'combo_id' => (int)$combo_id,
            'exported_qty' => (int)$export_qty,
            'exported_price_percentage' => (int)$exported_price_percentage
        );
        $return = Db::getInstance()->insert($module->config_name . "_products_combos", $data);

        return $return;
    }

    /**
     * update combo for product
     *
     * @param integer $product_id
     * @param integer $combo_id
     * @param integer $export_qty
     * @param integer $exported_price_percentage
     * @return bool
     */
    public static function updateCombo($product_id, $combo_id, $export_qty, $exported_price_percentage)
    {
        $module = Module::getInstanceByName('fyndiqmerchant');
        $data = array('exported_qty' => $export_qty, 'exported_price_percentage' => $exported_price_percentage);

        return (bool)Db::getInstance()->update(
            $module->config_name . "_products_combos",
            $data,
            "product_id == '{$product_id}' AND combo_id == '{$combo_id}'",
            1
        );
    }

    /**
     * delete a combo for product
     *
     * @param integer $product_id
     * @param integer $combo_id
     * @return bool
     */
    public static function deleteCombo($product_id, $combo_id)
    {
        $module = Module::getInstanceByName('fyndiqmerchant');

        return (bool)Db::getInstance()->delete($module->config_name . "_products_combos", "product_id == '{$product_id}' AND combo_id == '{$combo_id}'", 1);
    }

    /**
     * install table to database
     *
     * @return bool
     */
    public static function install()
    {
        $module = Module::getInstanceByName('fyndiqmerchant');
        $ret = (bool)Db::getInstance()->Execute(
            'create table if not exists ' . _DB_PREFIX_ . $module->config_name . '_products (
            id int(20) unsigned primary key AUTO_INCREMENT,
            product_id int(10) unsigned,
            exported_qty int(20) unsigned,
            exported_price_percentage int(20) unsigned)
        '
        );
        $ret2 = (bool)Db::getInstance()->Execute(
            'create table if not exists ' . _DB_PREFIX_ . $module->config_name . '_products_combos (
            id int(20) unsigned primary key AUTO_INCREMENT,
            product_id int(10) unsigned,
            combo_id int(10) unsigned,
            exported_qty int(20) unsigned,
            exported_price_percentage int(20) unsigned)
        '
        );

        return $ret && $ret2;
    }

    /**
     * remove the table from database
     *
     * @return bool
     */
    public static function uninstall()
    {
        $module = Module::getInstanceByName('fyndiqmerchant');
        $ret = (bool)Db::getInstance()->Execute(
            'drop table ' . _DB_PREFIX_ . $module->config_name . '_products'
        );

        return $ret;
    }


    public static function saveFile()
    {
        $module = Module::getInstanceByName('fyndiqmerchant');
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . $module->config_name . '_products';
        $products = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        if ($products != false) {
            $return_array = array();
            foreach ($products as $product) {

                $magarray = FmProduct::get($product["product_id"]);
                $real_array = array();

                $real_array["product-id"] = $product["product_id"];
                $real_array["article-quantity"] = $product["exported_qty"];
                $real_array["product-price"] = $magarray["price"] - ($magarray["price"] * ($product["exported_price_percentage"] / 100));
                if (isset($product["image"])) {
                    $real_array["product-image-1"] = $product["image"];
                }
                $real_array["product-title"] = $magarray["name"];
                $return_array[] = $real_array;
            }
            $first_array = array_values($return_array)[0];
            $key_values = array_keys($first_array);
            array_unshift($return_array, $key_values);
            $filehandler = new FmFileHandler("w+");
            foreach ($return_array as $product_array) {
                $filehandler->appendToFile($product_array);
            }
        } else {
            return false;
        }
    }
}
