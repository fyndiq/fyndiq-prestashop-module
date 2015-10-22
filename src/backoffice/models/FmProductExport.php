<?php

class FmProductExport extends FmModel
{

    const PENDING = 'PENDING';
    const FOR_SALE = 'FOR_SALE';

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

    protected function getProductDescription($descriptionType, $product) {
        switch($descriptionType) {
            case FmUtils::SHORT_DESCRIPTION: return $product->description_short;
            case FmUtils::SHORT_AND_LONG_DESCRIPTION:
                return $product->description_short . "\n\n" . $product->description;
            default: return $product->description;
        }
    }

    /**
     * Returns single product with combinations or false if product is not active/found
     *
     * @param $languageId
     * @param $productId
     * @param $descriptionType
     * @return array|bool
     */
    public function getStoreProduct($languageId, $productId, $descriptionType)
    {

        $product = $this->fmPrestashop->productNew($productId, false, $languageId);
        if (empty($product->id) || !$product->active) {
            return false;
        }

        $result = array(
            'id' => $product->id,
            'name' => $product->name,
            'category_id' => $this->getCategoryId($product->getCategories()),
            'reference' => $product->reference,
            'tax_rate' => $this->fmPrestashop->productGetTaxRate($product),
            'quantity' => $this->fmPrestashop->productGetQuantity($product->id),
            'price' => $this->fmPrestashop->getPrice($product),
            'oldprice' => $this->fmPrestashop->getBasePrice($product),
            'description' => $this->getProductDescription($descriptionType, $product),
            'minimal_quantity' => intval($product->minimal_quantity),
            'manufacturer_name' => $this->fmPrestashop->manufacturerGetNameById(
                (int)$product->id_manufacturer
            ),
            'combinations' => array(),
        );

        // get the medium image type
        $imageType = $this->fmPrestashop->getImageType();

        // get images
        $images = $product->getImages($languageId);
        $result['images'] = array();
        foreach ($images as $image) {
            $imageId = $image['id_image'];
            if ($this->fmPrestashop->version === FmPrestashop::FMPSV14) {
                $imageId = $product->id . '-' . $image['id_image'];
            }
            $result['images'][] = $this->fmPrestashop->getImageLink(
                $product->link_rewrite,
                $imageId,
                $imageType['name']
            );
        }

        // handle combinations
        $productAttributes = $this->fmPrestashop->getProductAttributes($product, $languageId);
        if ($productAttributes) {
            $combinationImages = $product->getCombinationImages($languageId);
            foreach ($productAttributes as $productAttribute) {
                $id = $productAttribute['id_product_attribute'];
                $result['combinations'][$id] = array(
                    'id' => $id,
                    'reference' => $productAttribute['reference'],
                    'price' => $this->fmPrestashop->getPrice($product, $id),
                    'oldprice' => $this->fmPrestashop->getBasePrice($product, $id),
                    'quantity' => $productAttribute['quantity'],
                    'minimal_quantity' => intval($productAttribute['minimal_quantity']),
                    'attributes' => array(
                        array(
                            'name' => $productAttribute['group_name'],
                            'value' => $productAttribute['attribute_name'],
                        )
                    ),
                    'images' => array(),
                );
                if ($combinationImages && isset($combinationImages[$id])) {
                    foreach ($combinationImages[$id] as $combinationImage) {
                        $image = $this->fmPrestashop->getImageLink(
                            $product->link_rewrite,
                            $combinationImage['id_image'],
                            $imageType['name']
                        );
                        $result['combinations'][$id]['images'][] = $image;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * getExportQty returns the actual export qty for article
     *
     * @param  int $qty actual article qty
     * @param  int $stockMin minimum qty defined by the merchant
     * @return int
     */
    protected function getExportQty($qty, $stockMin)
    {
        $qty = $qty - $stockMin;
        return $qty < 0 ? 0 : $qty;
    }

    /**
     * saveFile saves the export feed to the provided feedWriter
     * @param  int $languageId
     * @param  object $feedWriter
     * @param  int $stockMin
     * @param  int $descriptionType
     * @return bool
     */
    public function saveFile($languageId, $feedWriter, $stockMin, $descriptionType)
    {
        $result = true;
        $fmProducts = $this->getFyndiqProducts();
        FyndiqUtils::debug('$fmProducts', $fmProducts);
        // get current currency
        $currentCurrency = $this->fmPrestashop->currencyGetDefaultCurrency()->iso_code;
        $market = $this->fmPrestashop->getCountryCode();
        FyndiqUtils::debug('$currentCurrency', $currentCurrency);
        FyndiqUtils::debug('$stockMin', $stockMin);

        foreach ($fmProducts as $fmProduct) {
            $storeProduct = $this->getStoreProduct($languageId, $fmProduct['product_id'], $descriptionType);

            FyndiqUtils::debug('$storeProduct', $storeProduct);

            if (count($storeProduct['combinations']) === 0 && $storeProduct['minimal_quantity'] > 1) {
                FyndiqUtils::debug('minimal_quantity > 1 SKIPPING PRODUCT', $storeProduct['minimal_quantity']);
                continue;
            }

            $fyndiqPrice = FyndiqUtils::getFyndiqPrice($storeProduct['price'], $fmProduct['exported_price_percentage']);

            $exportProduct = array(
                FyndiqFeedWriter::ID => $storeProduct['id'],
                FyndiqFeedWriter::PRODUCT_CATEGORY_ID => $storeProduct['category_id'],
                FyndiqFeedWriter::PRODUCT_CATEGORY_NAME =>
                    $this->fmPrestashop->getCategoryPath($storeProduct['category_id']),
                FyndiqFeedWriter::PRODUCT_CURRENCY => $currentCurrency,
                FyndiqFeedWriter::QUANTITY => $storeProduct['quantity'],
                FyndiqFeedWriter::PRODUCT_DESCRIPTION => $storeProduct['description'],
                FyndiqFeedWriter::PRICE => $fyndiqPrice,
                FyndiqFeedWriter::OLDPRICE => $storeProduct['oldprice'],
                FyndiqFeedWriter::PRODUCT_BRAND_NAME => $storeProduct['manufacturer_name'],
                FyndiqFeedWriter::PRODUCT_TITLE => $storeProduct['name'],
                FyndiqFeedWriter::PRODUCT_VAT_PERCENT => $storeProduct['tax_rate'],
                FyndiqFeedWriter::PRODUCT_MARKET => $market,
                FyndiqFeedWriter::SKU => $storeProduct['reference'],
                FyndiqFeedWriter::IMAGES => $storeProduct['images'],
                FyndiqFeedWriter::QUANTITY => $this->getExportQty(intval($storeProduct['quantity']), $stockMin),
            );

            $articles = array();
            foreach ($storeProduct['combinations'] as $combination) {
                if ($combination['reference'] == '') {
                    FyndiqUtils::debug('MISSING SKU', $combination);
                    continue;
                }
                if ($combination['minimal_quantity'] > 1) {
                    FyndiqUtils::debug('minimal_quantity > 1 SKIPPING ARTICLE', $combination['minimal_quantity']);
                    continue;
                }
                $fyndiqPrice = FyndiqUtils::getFyndiqPrice($combination['price'], $fmProduct['exported_price_percentage']);

                $article = array(
                    FyndiqFeedWriter::ID => $combination['id'],
                    FyndiqFeedWriter::SKU => $combination['reference'],
                    FyndiqFeedWriter::QUANTITY => $this->getExportQty(intval($combination['quantity']), $stockMin),
                    FyndiqFeedWriter::PRICE => $fyndiqPrice,
                    FyndiqFeedWriter::OLDPRICE => $combination['price'],
                    FyndiqFeedWriter::IMAGES => $combination['images'],
                    FyndiqFeedWriter::PRODUCT_TITLE => $storeProduct['name'],
                );
                $article[FyndiqFeedWriter::PROPERTIES] = array();

                foreach ($combination['attributes'] as $attribute) {
                    $article[FyndiqFeedWriter::PROPERTIES][] = array(
                        FyndiqFeedWriter::PROPERTY_NAME => $attribute['name'],
                        FyndiqFeedWriter::PROPERTY_VALUE => $attribute['value'],
                    );
                }
                $articles[] = $article;
            }
            FyndiqUtils::debug('$exportProduct, $articles', $exportProduct, $articles);
            if ($storeProduct['combinations'] && !$articles) {
                FyndiqUtils::debug('NO VALID ARTCLES FOR PRODUCT', $exportProduct, $articles);
                continue;
            }
            $feedWriter->addCompleteProduct($exportProduct, $articles);
            FyndiqUtils::debug('Any Validation Errors', $feedWriter->getLastProductErrors());
        }
        FyndiqUtils::debug('End');
        return $feedWriter->write();
    }
}
