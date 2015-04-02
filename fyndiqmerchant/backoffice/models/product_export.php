<?php
class FmProductExport
{

    const SKU_PREFIX = '~';
    const SKU_SEPARATOR = '-';

    private static $skuList = array();

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
            exported_price_percentage int(20) unsigned);
            CREATE UNIQUE INDEX productIndex
            ON ' . _DB_PREFIX_ . $module->config_name . '_products (product_id);
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
     * @param $file - Export file handler
     * @return bool
     */
    public static function saveFile($file)
    {
        if (get_resource_type($file) !== 'stream') {
            return false;
        }
        $feedWriter = new FyndiqCSVFeedWriter($file);
        // Database connection
        $module = Module::getInstanceByName('fyndiqmerchant');
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . $module->config_name . '_products';
        $fmProducts = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        if (empty($fmProducts)) {
            // Exit if there are no products
            return false;
        }

        // Clear the SKU dictionary
        self::$skuList = array();

        // get current currency
        $currentCurrency = Currency::getDefaultCurrency()->iso_code;

        foreach ($fmProducts as $fmProduct) {
            $storeProduct = FmProduct::get($fmProduct['product_id']);

            // Don't export deactivated or products without SKU
            if (!$storeProduct || empty($storeProduct['reference'])) {
                continue;
            }
            $exportProduct = self::getProductData($storeProduct, $fmProduct, $currentCurrency);
            if (count($storeProduct['combinations']) === 0) {
                // Product without combinations

                // Complete Product with article data
                $exportProduct['article-sku'] = self::getSKU($storeProduct['reference'], array($storeProduct['id'], 0));
                $exportProduct['article-quantity'] = $storeProduct['quantity'];
                $exportProduct['article-name'] = $storeProduct['name'];
                $feedWriter->addProduct($exportProduct);
            } else {
                foreach ($storeProduct['combinations'] as $combination) {
                    // Copy the product data so we have clear slate for each combination
                    $exportProductCopy = $exportProduct;

                    $exportProductCopy['article-sku'] = self::getSKU($combination['reference'],
                         array($storeProduct['id'], $combination['id']));

                    $exportProductCopy['article-quantity'] = $combination['quantity'];
                    $exportProductCopy['product-oldprice'] = number_format((float)$combination['price'], 2, '.', '');

                    // Set combination image if present
                    $imageId = 1;
                    if (!empty($combination['image'])) {
                        $exportProductCopy['product-image-' . $imageId . '-url'] =
                            strval($combination['image']);
                        $exportProductCopy['product-image-' . $imageId . '-identifier'] =
                            $fmProduct['product_id'] . '-' . strval($combination['id']);
                    }

                    // Create combination name
                    $productName = array();
                    $id = 1;
                    foreach ($combination['attributes'] as $attribute) {
                        $productName[] = $attribute['name'] . ': ' . $attribute['value'];
                        $exportProductCopy['article‑property‑name‑' . $id] = $attribute['name'];
                        $exportProductCopy['article‑property‑value‑' . $id] = $attribute['value'];
                        $id++;
                    }
                    $exportProductCopy['article-name'] = implode(', ', $productName);

                    $feedWriter->addProduct($exportProductCopy);
                }
            }
        }
        return $feedWriter->write();
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
        $exportProduct['product-id'] = $fmProduct['id'];
        $exportProduct['product-currency'] = $currentCurrency;
        $exportProduct['article-quantity'] = 0;
        $exportProduct['product-description'] = $storeProduct['description'];

        $price = FyndiqUtils::getFyndiqPrice($storeProduct['price'], $fmProduct['exported_price_percentage']);
        $exportProduct['product-price'] = number_format((float)$price, 2, '.', '');
        $exportProduct['product-oldprice'] = number_format((float)$storeProduct['price'], 2, '.', '');
        $exportProduct['product-brand'] = $storeProduct['manufacturer_name'];
        $exportProduct['article-location'] = 'test';
        if (!empty($storeProduct['image'])) {
            $exportProduct['product-image-1-url'] = strval($storeProduct['image']);
            $exportProduct['product-image-1-identifier'] = $fmProduct['product_id'];
        }
        $exportProduct['product-title'] = $storeProduct['name'];
        $exportProduct['product-vat-percent'] = $storeProduct['tax_rate'];
        $exportProduct['product-market'] = Context::getContext()->country->iso_code;

        return $exportProduct;
    }

    private static function getSKU($sku, $backupFields = array()) {
        if (empty($sku) || in_array($sku, self::$skuList)) {
            $sku = implode(self::SKU_SEPARATOR, array_merge(array(self::SKU_PREFIX), $backupFields));
        }
        self::$skuList[] = $sku;
        return $sku;
    }
}
