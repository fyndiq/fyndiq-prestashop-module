<?php

class FmProductExport
{

    const VAT_PERCENT = 25;

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

    /**
     *  Save the export feed
     *
     * @param $directory - Export directory
     * @return bool
     */
    public static function saveFile($directory)
    {
        // Database connection
        $module = Module::getInstanceByName('fyndiqmerchant');
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . $module->config_name . '_products';
        $fmProducts = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        if (empty($fmProducts)) {
            // Exit if there are no products
            return false;
        }

        $allProducts = array();
        $keys = array();

        // get current currency
        $currentCurrency = Currency::getDefaultCurrency()->iso_code;

        foreach ($fmProducts as $fmProduct) {
            $storeProduct = FmProduct::get($fmProduct['product_id']);

            $exportProduct = self::getProductData($storeProduct, $fmProduct, $currentCurrency);
            $exportProductCopy = $exportProduct;

            if (count($storeProduct['combinations']) > 0) {
                $first_array = array_shift($storeProduct['combinations']);
                $exportProductCopy['article-quantity'] = $first_array['quantity'];
                $exportProductCopy['product-oldprice'] = number_format((float)$first_array['price'], 2, '.', '');
                $imageId = 1;
                if (isset($storeProduct['image'])) {
                    $exportProductCopy['product-image-' . $imageId . '-url'] = addslashes(strval($storeProduct['image']));
                    $exportProductCopy['product-image-' . $imageId . '-identifier'] = addslashes(
                        substr(md5($fmProduct['product_id'] . '-' . strval($storeProduct['image'])), 0, 10)
                    );
                }
                $name = [];
                $id = 1;
                foreach ($first_array['attributes'] as $attr) {
                    $name[] = addslashes($attr['name'] . ': ' . $attr['value']);
                    $exportProductCopy['article‑property‑name‑' . $id] = $attr['name'];
                    $exportProductCopy['article‑property‑value‑' . $id] = $attr['value'];
                    $id++;
                }
                $exportProductCopy['article-name'] = implode(' ', $name);
                $keys = array_merge($keys, array_keys($exportProductCopy));
                $allProducts[] = $exportProductCopy;

                foreach ($storeProduct['combinations'] as $combo) {
                    $exportProduct['article-quantity'] = $combo['quantity'];

                    if (isset($combo["reference"]) AND $combo["reference"] != "") {
                        $exportProduct["article-sku"] = $combo["reference"];
                    } else {
                        $exportProduct["article-sku"] = $storeProduct["reference"] . "-" . $combo["id"];
                    }
                    $exportProduct["article-location"] = "test";
                    $exportProduct["product-oldprice"] = number_format((float)$combo["price"], 2, '.', '');

                    if (isset($combo["image"])) {
                        $exportProduct["product-image-" . $imageId . "-url"] = addslashes(strval($combo["image"]));
                        $exportProduct["product-image-" . $imageId . "-identifier"] = addslashes(
                            substr(md5($fmProduct["product_id"] . "-" . strval($combo["image"])), 0, 10)
                        );
                    }
                    $name = "";
                    $id = 1;
                    foreach ($combo["attributes"] as $attr) {
                        $name .= addslashes($attr["name"] . ": " . $attr["value"]);
                        $exportProduct["article‑property‑name‑" . $id] = $attr["name"];
                        $exportProduct["article‑property‑value‑" . $id] = $attr["value"];
                        $id++;
                    }
                    $exportProduct['article-name'] = $name;
                    $keys = array_merge($keys, array_keys($exportProduct));
                    $allProducts[] = $exportProduct;
                }
            } else {
                $keys = array_merge($keys, array_keys($exportProduct));
                $allProducts[] = $exportProduct;
            }
            // Don't allow $keys to grow too large
            $keys = array_unique($keys);
        }

        // Save products to CSV file
        $fileHandler = new FmFileHandler($directory, 'w+');
        $fileHandler->writeOverFile(array_unique($keys), $allProducts);

        return true;
    }

    /**
     * Collect export data for the product
     *
     * @param array $storeProduct - The product information from thr product
     * @param array $fmProduct - Reference table product
     * @param string $currentCurrency
     * @return array
     */


    private static function getProductData($storeProduct, $fmProduct, $currentCurrency)
    {
        $exportProduct = array();
        $exportProduct['product-id'] = $fmProduct['product_id'];
        $exportProduct ['product-currency'] = $currentCurrency;
        $exportProduct['article-quantity'] = 0;
        $exportProduct['product-description'] = $storeProduct['description'];
        $price = $storeProduct['price'] - ($storeProduct['price'] * ($fmProduct['exported_price_percentage'] / 100));
        $exportProduct['product-price'] = number_format((float)$price, 2, '.', '');
        $exportProduct['product-oldprice'] = number_format((float)$storeProduct['price'], 2, '.', '');
        $exportProduct['product-brand'] = 'test';
        $exportProduct['article-location'] = 'test';
        if (isset($storeProduct['image'])) {
            $exportProduct['product-image-1-url'] = addslashes(strval($storeProduct['image']));
            $exportProduct['product-image-1-identifier'] = addslashes(
                substr(md5($fmProduct['product_id'] . '-' . strval($storeProduct['image'])), 0, 10)
            );
        }
        $exportProduct['product-title'] = addslashes($storeProduct['name']);
        $exportProduct['product-vat-percent'] = self::VAT_PERCENT;

        return $exportProduct;
    }

    static function getL2Keys($array)
    {
        $result = array();
        foreach ($array as $sub) {
            $result = array_merge($result, $sub);
        }

        return array_keys($result);
    }
}
