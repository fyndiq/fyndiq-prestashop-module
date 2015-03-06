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

    static function addProduct($product_id, $exported_price_percentage)
    {
        $module = Module::getInstanceByName('fyndiqmerchant');
        $data = array(
            'product_id' => (int)$product_id,
            'exported_price_percentage' => (int)$exported_price_percentage
        );
        $return = Db::getInstance()->insert($module->config_name . "_products", $data);

        return $return;
    }

    public static function updateProduct($product_id, $exported_price_percentage)
    {
        $module = Module::getInstanceByName('fyndiqmerchant');
        $data = array('exported_price_percentage' => $exported_price_percentage);

        return (bool)Db::getInstance()->update(
            $module->config_name . "_products",
            $data,
            "product_id = '{$product_id}'",
            1
        );
    }

    public static function deleteProduct($product_id)
    {
        $module = Module::getInstanceByName('fyndiqmerchant');

        return (bool)Db::getInstance()->delete($module->config_name . "_products", "product_id = '{$product_id}'", 1);
    }

    public static function getProduct($product_id)
    {
        $module = Module::getInstanceByName('fyndiqmerchant');
        $sql = "SELECT * FROM " . _DB_PREFIX_ . $module->config_name . "_products WHERE product_id='{$product_id}' LIMIT 1";
        $products = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        return reset($products);
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
            exported_price_percentage int(20) unsigned)
        '
        );

        return $ret;
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

            // get current currency
            $current_currency = Currency::getDefaultCurrency()->iso_code;

            foreach ($products as $product) {

                $magarray = FmProduct::get($product["product_id"]);
                $real_array = array();

                $real_array = self::getProductData($magarray,$product);
                $real_array['product-currency'] = $current_currency;

                if (count($magarray['combinations']) > 0) {
                    $first_array = array_shift($magarray['combinations']);
                    $real_array["article-quantity"] = $first_array["quantity"];
                    $real_array["product-price"] = $first_array["price"] - ($first_array["price"] * ($product["exported_price_percentage"] / 100));
                    $real_array["product-oldprice"] = number_format((float)$first_array["price"], 2, '.', '');
                    $name = "";
                    $id=1;
                    foreach($first_array["attributes"] as $attr) {
                        $name .= addslashes($attr["name"] . ": " . $attr["value"]);
                        $real_array["article‑property‑name‑".$id] = $attr["name"];
                        $real_array["article‑property‑value‑".$id] = $attr["value"];
                        $id++;
                    }
                    $real_array["article-name"] = $name;
                    $return_array[] = $real_array;
                    $imageid = 2;
                    foreach($magarray["combinations"] as $combo) {
                        $real_array = self::getProductData($magarray,$product);
                        $real_array["article-quantity"] = $combo["quantity"];
                        $real_array["article-location"] =
                        $real_array["product-price"] = $combo["price"] - ($combo["price"] * ($product["exported_price_percentage"] / 100));
                        $real_array["product-oldprice"] = number_format((float)$combo["price"], 2, '.', '');

                        if (isset($combo["image"])) {
                            $real_array["product-image-".$imageid."-url"] = addslashes(strval($combo["image"]));
                            $real_array["product-image-".$imageid."-identifier"] = addslashes(substr(md5($product["product_id"] . "-".strval($combo["image"])),0,10));
                        }
                        $name = "";
                        $id=1;
                        foreach($combo["attributes"] as $attr) {
                            $name .= addslashes($attr["name"] . ": " . $attr["value"]);
                            $real_array["article‑property‑name‑".$id] = $attr["name"];
                            $real_array["article‑property‑value‑".$id] = $attr["value"];
                            $id++;
                        }
                        $real_array["article-name"] = $name;
                        $return_array[] = $real_array;
                        $imageid++;
                    }
                }
                else {
                    $return_array[] = $real_array;
                }
            }

            $first_array = array_values($return_array)[1];
            $key_values = array_keys($first_array);
            array_unshift($return_array, $key_values);
            $filehandler = new FmFileHandler("w+");
            foreach ($return_array as $product_array) {
                $filehandler->appendToFile($product_array);
            }
            return true;
        } else {
            return false;
        }
    }
    private static function getProductData($magarray, $product) {
        $real_array = array();
        $real_array["product-id"] = $product["product_id"];
        $real_array["article-quantity"] = 0;
        $real_array["product-price"] = $magarray["price"] - ($magarray["price"] * ($product["exported_price_percentage"] / 100));
        $real_array["product-oldprice"] = number_format((float)$magarray["price"], 2, '.', '');
        $real_array["product-brand"] = "test";
        if (isset($product["image"])) {
            $real_array["product-image-1-url"] = addslashes(strval($product["image"]));
            $real_array["product-image-1-identifier"] = addslashes(substr(md5($product["product_id"] . "-".strval($product["image"])),0,10));
        }
        $real_array["product-title"] = addslashes($magarray["name"]);
        $real_array["product-vat-percent"] = "25";
        return $real_array;
    }
    static function getL2Keys($array)
    {
        $result = array();
        foreach($array as $sub) {
            $result = array_merge($result, $sub);
        }
        return array_keys($result);
    }
}
