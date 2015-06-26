<?php

class FmProductExport extends FmModel
{

    public function __construct($fmPrestashop, $fmConfig)
    {
        parent::__construct($fmPrestashop, $fmConfig);
        $this->tableName = $this->fmPrestashop->getTableName(FmUtils::MODULE_NAME, '_products');
    }


    public function productExist($productId)
    {
        $sql = "SELECT product_id
        FROM " . $this->fmPrestashop->getTableName(FmUtils::MODULE_NAME, '_products', true) . "
        WHERE product_id='" . $productId . "' LIMIT 1";
        $data = $this->fmPrestashop->dbGetInstance()->ExecuteS($sql);
        return count($data) > 0;
    }

    public function addProduct($productId, $expPricePercentage)
    {
        $data = array(
            'product_id' => (int)$productId,
            'exported_price_percentage' => $expPricePercentage
        );
        return $this->fmPrestashop->dbInsert($this->tableName, $data);
    }

    public function updateProduct($productId, $expPricePercentage)
    {
        $data = array(
            'exported_price_percentage' => $expPricePercentage
        );
        return (bool)$this->fmPrestashop->dbUpdate(
            $this->tableName,
            $data,
            'product_id = "' . $productId . '"',
            1
        );
    }

    public function deleteProduct($productId)
    {
        return (bool)$this->fmPrestashop->dbDelete(
            $this->tableName,
            'product_id = ' . $productId,
            1
        );
    }

    public function getProduct($productId)
    {
        $sql = 'SELECT * FROM ' . $this->fmPrestashop->getTableName(FmUtils::MODULE_NAME, '_products', true) .
            ' WHERE product_id= ' . $productId;
        return $this->fmPrestashop->dbGetInstance()->getRow($sql);
    }

    /**
     * install table to database
     *
     * @return bool
     */
    public function install()
    {
        $tableName = $this->fmPrestashop->getTableName(FmUtils::MODULE_NAME, '_products', true);
        $sql = 'CREATE TABLE IF NOT EXISTS ' . $tableName .' (
            id int(20) unsigned primary key AUTO_INCREMENT,
            product_id int(10) unsigned,
            exported_price_percentage int(20) unsigned,
            state varchar(64) default NULL);';
        $ret = (bool)$this->fmPrestashop->dbGetInstance()->Execute($sql, false);

        $sql = 'CREATE UNIQUE INDEX productIndex
            ON ' . $tableName . ' (product_id);';
        $ret &= (bool)$this->fmPrestashop->dbGetInstance()->Execute($sql, false);

        $exportPath = $this->fmPrestashop->getExportPath();
        $ret &= $this->fmPrestashop->forceCreateDir($exportPath, 0775);
        return (bool)$ret;
    }

    /**
     * remove the table from database
     *
     * @return bool
     */
    public function uninstall()
    {
        return (bool)$this->fmPrestashop->dbGetInstance()->Execute(
            'DROP TABLE ' . $this->fmPrestashop->getTableName(FmUtils::MODULE_NAME, '_products', true)
        );
    }

    public function getFyndiqProducts()
    {
        $sql = 'SELECT * FROM ' . $this->fmPrestashop->getTableName(FmUtils::MODULE_NAME, '_products', true);
        return $this->fmPrestashop->dbGetInstance()->executeS($sql);
    }

    /**
     * Returns the first category_id the product belongs to
     *
     * @param $categories
     * @return mixed
     */
    private function getCategoryId($categories)
    {
        if (is_array($categories)) {
            return array_pop($categories);
        }
        return 0;
    }

    /**
     * Returns single product with combinations or false if product is not active/found
     *
     * @param $productId
     * @return array|bool
     */
    public function getStoreProduct($languageId, $productId)
    {
        $result = array(
            'combinations' => array()
        );

        $product = $this->fmPrestashop->productNew($productId, false, $languageId);

        if (empty($product->id) || !$product->active) {
            return false;
        }

        $result['id'] = $product->id;
        $result['name'] = $product->name;
        $result['category_id'] = $this->getCategoryId($product->getCategories());
        $result['reference'] = $product->reference;
        $result['tax_rate'] = $this->fmPrestashop->productGetTaxRate($product);
        $result['quantity'] = $this->fmPrestashop->productGetQuantity($product->id);
        $result['price'] = $this->fmPrestashop->getPrice($product);
        $result['description'] = $product->description;
        $result['manufacturer_name'] = $this->fmPrestashop->manufacturerGetNameById(
            (int)$product->id_manufacturer
        );

        // get the medium image type
        $imageType = $this->fmPrestashop->getImageType();

        // get images
        $images = $product->getImages($languageId);

        // assign main product image
        if (count($images) > 0) {
            $result['image'] = $this->fmPrestashop->getImageLink(
                $product->link_rewrite,
                $images[0]['id_image'],
                $imageType['name']
            );
        }

        // handle combinations
        $productAttributes = $this->fmPrestashop->getProductAttributes($product, $languageId);
        $combinationImages = $product->getCombinationImages($languageId);

        foreach ($productAttributes as $productAttribute) {
            $id = $productAttribute['id_product_attribute'];
            $comboProduct = $this->fmPrestashop->productNew($id, false, $languageId);
            $result['combinations'][$id]['id'] = $id;
            $result['combinations'][$id]['reference'] = $productAttribute['reference'];
            $result['combinations'][$id]['price'] =
                $this->fmPrestashop->getPrice($product, $productAttribute['price']);
            $result['combinations'][$id]['quantity'] = $productAttribute['quantity'];
            $result['combinations'][$id]['attributes'][] = array(
                'name' => $productAttribute['group_name'],
                'value' => $productAttribute['attribute_name']
            );

            // if this combination has no image yet
            if (empty($result['combinations'][$id]['image'])) {
                // if this combination has any images
                if ($combinationImages) {
                    foreach ($combinationImages as $combinationImage) {
                        // data array is stored in another array with only one key: 0. I have no idea why
                        $combinationImage = $combinationImage[0];

                        // if combination image belongs to the same product attribute mapping as the current combination
                        if ($combinationImage['id_product_attribute'] == $productAttribute['id_product_attribute']) {
                            $image = $this->fmPrestashop->getImageLink(
                                $product->link_rewrite,
                                $combinationImage['id_image'],
                                $imageType['name']
                            );

                            $result['combinations'][$id]['image'] = $image;
                            // We are getting single image only, no need to loop further
                            break;
                        }
                    }
                }
            }
        }
        return $result;
    }

    /**
     *  Save the export feed
     *
     * @param $file - Export file handler
     * @return bool
     */
    public function saveFile($languageId, $feedWriter)
    {
        $fmProducts = $this->getFyndiqProducts();
        if (empty($fmProducts)) {
            // Exit if there are no products
            return false;
        }
        FmUtils::debug('$fmProducts', $fmProducts);
        // get current currency
        $currentCurrency = $this->fmPrestashop->currencyGetDefaultCurrency()->iso_code;
        FmUtils::debug('$currentCurrency', $currentCurrency);
        foreach ($fmProducts as $fmProduct) {
            $storeProduct = $this->getStoreProduct($languageId, $fmProduct['product_id']);
            FmUtils::debug('$storeProduct', $storeProduct);
            // Don't export deactivated or products without SKU
            if (!$storeProduct || empty($storeProduct['reference'])) {
                continue;
            }
            $exportProduct = self::getProductData($storeProduct, $fmProduct, $currentCurrency);
            if (count($storeProduct['combinations']) === 0) {
                // Product without combinations

                // Complete Product with article data
                $exportProduct['article-quantity'] = $storeProduct['quantity'];
                $exportProduct['article-name'] = $storeProduct['name'];
                FmUtils::debug('$exportProduct', $exportProduct);
                $feedWriter->addProduct($exportProduct);
                continue;
            }

            $i = 0;
            // Deal with combinations
            foreach ($storeProduct['combinations'] as $combination) {
                // Copy the product data so we have clear slate for each combination
                $exportProductCopy = $exportProduct;
                if ($combination['reference'] == '') {
                    continue;
                }
                $exportProductCopy['article-sku'] = $combination['reference'];
                $exportProductCopy['article-quantity'] = $combination['quantity'];
                $exportProductCopy['product-oldprice'] = FyndiqUtils::formatPrice($combination['price']);

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
                    $exportProductCopy['article-property-' . $id . '-name'] = $attribute['name'];
                    $exportProductCopy['article-property-' . $id . '-value'] = $attribute['value'];
                    $id++;
                }
                $exportProductCopy['article-name'] = implode(', ', $productName);
                FmUtils::debug('$exportProductCopy', $exportProductCopy);
                $feedWriter->addProduct($exportProductCopy);
                $i++;
            }
        }
        FmUtils::debug('End');
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
    private function getProductData($storeProduct, $fmProduct, $currentCurrency)
    {
        $exportProduct = array();
        $exportProduct['product-id'] = $fmProduct['id'];
        $exportProduct['product-category-id'] = $storeProduct['category_id'];
        $exportProduct['product-category-name'] =
            $this->fmPrestashop->getCategoryName($storeProduct['category_id']);
        $exportProduct['product-currency'] = $currentCurrency;
        $exportProduct['article-quantity'] = 0;
        $exportProduct['product-description'] = $storeProduct['description'];

        $price = FyndiqUtils::getFyndiqPrice($storeProduct['price'], $fmProduct['exported_price_percentage']);
        $exportProduct['product-price'] = FyndiqUtils::formatPrice($price);
        $exportProduct['product-oldprice'] = FyndiqUtils::formatPrice($storeProduct['price']);
        $exportProduct['product-brand-name'] = $storeProduct['manufacturer_name'];
        $exportProduct['article-location'] = 'test';
        if (!empty($storeProduct['image'])) {
            $exportProduct['product-image-1-url'] = strval($storeProduct['image']);
            $exportProduct['product-image-1-identifier'] = $fmProduct['product_id'];
        }
        $exportProduct['product-title'] = $storeProduct['name'];
        $exportProduct['product-vat-percent'] = $storeProduct['tax_rate'];
        $exportProduct['product-market'] = $this->fmPrestashop->getCountryCode();
        $exportProduct['article-sku'] = $storeProduct['reference'];
        return $exportProduct;
    }
}
