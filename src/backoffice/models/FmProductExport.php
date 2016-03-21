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

    public function exportProduct($productId, $storeId)
    {
        if (!$this->productExists($productId, $storeId)) {
            return $this->addProduct($productId, $storeId);
        }
    }

    public function productExists($productId, $storeId)
    {
        $sql = 'SELECT product_id
                FROM ' . $this->fmPrestashop->getTableName(FmUtils::MODULE_NAME, '_products', true) . '
                WHERE product_id="' . $productId . '"
                AND store_id=' . $storeId .'
                LIMIT 1';
        $data = $this->fmPrestashop->dbGetInstance()->ExecuteS($sql);
        return count($data) > 0;
    }

    public function addProduct($productId, $storeId, $name = null, $description = null)
    {
        $data = array(
            'store_id' => $storeId,
            'product_id' => $productId,
            'name' => $name,
            'description' => $description
        );
        return $this->fmPrestashop->dbInsert($this->tableName, $data);
    }

    public function updateProduct($productId, $fyndiqPrice, $storeId, $name = null, $description = null)
    {
        $data = array(
            'fyndiq_price' => $fyndiqPrice,
            'name' => $name,
            'description' => $description,
        );
        return (bool)$this->fmPrestashop->dbUpdate(
            $this->tableName,
            $data,
            'product_id = "' . $productId . '" AND store_id = '. $storeId,
            1
        );
    }

    public function removeProduct($productId, $storeId)
    {
        return (bool)$this->fmPrestashop->dbDelete(
            $this->tableName,
            'product_id = "' . $productId . '" AND store_id = '. $storeId,
            1
        );
    }

    public function getProduct($productId, $storeId)
    {
        $sql = 'SELECT *
                FROM ' . $this->fmPrestashop->getTableName(FmUtils::MODULE_NAME, '_products', true) . '
                WHERE product_id= ' . $productId . '
                AND store_id=' . $storeId;
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
                    store_id int(10) unsigned,
                    product_id int(10) unsigned,
                    fyndiq_price int(10) unsigned,
                    name varchar(128) NOT NULL DEFAULT "",
                    description text NOT NULL DEFAULT ""
                );';
        $ret = (bool)$this->fmPrestashop->dbGetInstance()->Execute($sql, false);

        $sql = 'CREATE UNIQUE INDEX productIndex
            ON ' . $tableName . ' (product_id, store_id);';
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
     * getContext returns cloned context
     * @return Context
     */
    public function getContext()
    {
        return $this->fmPrestashop->contextGetContext()->cloneContext();
    }

    /**
     * Returns the first category_id the product belongs to
     *
     * @param $categories
     * @return mixed
     */
    private function getCategoryId($product)
    {
        if (method_exists($product, 'getDefaultCategory')) {
            return $product->getDefaultCategory();
        }
        $categories = $product->getCategories();
        if (is_array($categories)) {
            return array_pop($categories);
        }
        return 0;
    }

    protected function getProductDescription($descriptionType, $product)
    {
        switch ($descriptionType) {
            case FmUtils::SHORT_DESCRIPTION:
                return $product->description_short;
            case FmUtils::SHORT_AND_LONG_DESCRIPTION:
                return $product->description_short . "\n\n" . $product->description;
            default:
                return $product->description;
        }
    }

    public function getProductSKU($skuTypeId, $product, $article = false)
    {
        switch ($skuTypeId) {
            case FmUtils::SKU_ID:
                if ($article) {
                    return $product->id . FmUtils::SKU_SEPARATOR . $article['id_product_attribute'];
                }
                return $product->id;
            case FmUtils::SKU_EAN:
                if ($article) {
                    return $article['ean13'];
                }
                return $product->ean13;
            default:
                if ($article) {
                    return $article['reference'];
                }
                return $product->reference;
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
    public function getStoreProduct($productId, $allProductIds, $settings, $context)
    {
        $groupId = $settings[FmFormSetting::SETTINGS_GROUP_ID];
        $storeId = $settings[FmFormSetting::SETTINGS_STORE_ID];
        $languageId = $settings[FmFormSetting::SETTINGS_LANGUAGE_ID];
        $product = $this->fmPrestashop->productNew($productId, false, $languageId, $storeId);
        if (empty($product->id) || !$product->active) {
            return false;
        }

        $result = array(
            'id' => $product->id,
            'name' => $product->name,
            'category_id' => $this->getCategoryId($product),
            'reference' => $this->getProductSKU($languageId, $product),
            'tax_rate' => $this->fmPrestashop->productGetTaxRate($product),
            'quantity' => $this->fmPrestashop->productGetQuantity($product->id),
            'price' => $this->fmPrestashop->getPrice($product, $context, $groupId),
            'oldprice' => $this->fmPrestashop->getBasePrice($product),
            'description' => $this->getMappedValue($settings[FmFormSetting::SETTINGS_MAPPING_DESCRIPTION], $product, $allProductIds, $settings),
            'description_short' => $product->description_short,
            'minimal_quantity' => intval($product->minimal_quantity),
            'manufacturer_name' => $this->fmPrestashop->manufacturerGetNameById(
                (int)$product->id_manufacturer
            ),
            'combinations' => array(),
            'brand' => $this->getMappedValue($settings[FmFormSetting::SETTINGS_MAPPING_BRAND], $product, $allProductIds, $settings),
            'ean' => $this->getMappedValue($settings[FmFormSetting::SETTINGS_MAPPING_EAN], $product, $allProductIds, $settings),
            'isbn' => $this->getMappedValue($settings[FmFormSetting::SETTINGS_MAPPING_ISBN], $product, $allProductIds, $settings),
            'mpn' => $this->getMappedValue($settings[FmFormSetting::SETTINGS_MAPPING_MPN], $product, $allProductIds, $settings),
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
        $productAttributesFixed = array();
        if ($productAttributes) {
            $combinationImages = $product->getCombinationImages($languageId);
            foreach ($productAttributes as $fixingAttribute) {
                $reference = $this->getProductSKU($settings[FmFormSetting::SETTINGS_MAPPING_SKU], $product, $fixingAttribute);
                if (!isset($productAttributesFixed[$reference])) {
                    $productAttributesFixed[$reference] = array();
                }
                $productAttributesFixed[$reference][] = $fixingAttribute;
            }
            foreach ($productAttributesFixed as $reference => $productAttribute) {
                FyndiqUtils::debug('$productAttribute', $productAttribute);
                $quantity = 0;
                $minQuantity = 0;
                $attributes = array();
                $id = $productAttribute[0]['id_product_attribute'];

                foreach ($productAttribute as $simpleAttr) {
                    $quantity = intval($simpleAttr['quantity']);
                    $minQuantity = intval($simpleAttr['minimal_quantity']);
                    $attributes[] = array(
                        'name' => $simpleAttr['group_name'],
                        'value' => $simpleAttr['attribute_name'],
                    );
                }

                $result['combinations'][$id] = array(
                    'id' => $id,
                    'reference' => $reference,
                    'price' => $this->fmPrestashop->getPrice($product, $context, $groupId),
                    'oldprice' => $this->fmPrestashop->getBasePrice($product, $id),
                    'quantity' => $quantity,
                    'minimal_quantity' => $minQuantity,
                    'attributes' => $attributes,
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
     * getArticleFieldValue returns the specified field's value from a product's combination.
     * If a field is not found on a combination, it returns a specified's field's value from a product.
     *
     * @param  $fieldKey field to look for
     * @param  Product $product product to search the field in
     * @param  Combination $combination combination to search the field in
     * @return object
     */
    protected function getArticleFieldValue($fieldKey, $product)
    {
        return $product->{$fieldKey};
    }

    protected function getProductFeatures($languageId, $productIds, $settings)
    {
        $features = array();
        $featureIds = array();
        foreach(array(FmFormSetting::SETTINGS_MAPPING_DESCRIPTION,
                    FmFormSetting::SETTINGS_MAPPING_SKU,
                    FmFormSetting::SETTINGS_MAPPING_EAN,
                    FmFormSetting::SETTINGS_MAPPING_ISBN,
                    FmFormSetting::SETTINGS_MAPPING_MPN,
                    FmFormSetting::SETTINGS_MAPPING_BRAND) as $mappingTarget) {
            $mapping = FmFormSetting::deserializeProductMappingValue($settings[$mappingTarget]);
            $mappingType = intval($mapping['product_mapping_type']);
            $mappingId = $mapping['product_mapping_key_id'];
            if($mappingType === FmFormSetting::MAPPING_TYPE_PRODUCT_FEATURE) {
                $featureIds[] = $mappingId;
            }
        }

        if(empty($featureIds) || empty($productIds)) {
            return $features;
        }

        $query = '
                SELECT pl.value, p.id_feature, p.id_product
                FROM ps_feature_product AS p WHERE p.id_product IN (' . implode(',', $productIds) . ')
                AND p.id_feature IN (' . implode(',', $featureIds) . ')
                LEFT JOIN ps_feature_value_lang AS pl ON (p.id_feature_value = pl.id_feature_value AND pl.id_lang = '. $languageId .')';

        $queryResults = $this->fmPrestashop->dbGetInstance()->ExecuteS($query);
        foreach($queryResults as $featureQueryResult) {
            $features[$featureQueryResult['id_product']][$featureQueryResult['id_feature']] = $featureQueryResult['value'];
        }
        return $features;
    }

    public function getProductFeature($languageId, $productId, $featureId, $productsIds, $settings)
    {
        static $features = null;
        if($features === null) {
            $features = $this->getProductFeatures($languageId, $productsIds, $settings);
        }
        if(isset($features[$productId]) && isset($features[$productId][$featureId])) {
            return $features[$productId][$featureId];
        }
        return '';
    }

    private function getMappedValue($fieldKey, $product, $allProductIds, $settings) {
        $mappedKey = FmFormSetting::deserializeProductMappingValue($fieldKey);
        $mappingType = intval($mappedKey['product_mapping_type']);
        $mappingId = $mappedKey['product_mapping_key_id'];

        if($mappingType === FmFormSetting::MAPPING_TYPE_PRODUCT_FEATURE) {
            return $this->getProductFeature($product->id, $mappingId, $allProductIds, $settings);
        }
        if($mappingType === FmFormSetting::MAPPING_TYPE_PRODUCT_FIELD) {
            return $this->getArticleFieldValue($mappingId, $product);
        }
        if($mappingType === FmFormSetting::MAPPING_TYPE_MANUFACTURER_NAME) {
            return $product->manufacturer_name;
        }
        if($mappingType === FmFormSetting::MAPPING_TYPE_SHORT_AND_LONG_DESCRIPTION) {
            return $product->description . "\n\n" . $product->description_short;
        }
        return '';
    }

    /**
     * saveFile saves the export feed to the provided feedWriter
     * @param  object $feedWriter
     * @param  array $settings
     * @return bool
     */
    public function saveFile($feedWriter, $settings)
    {
        $fmProducts = $this->getFyndiqProducts();
        FyndiqUtils::debug('$fmProducts', $fmProducts);

        $storeId = $settings[FmFormSetting::SETTINGS_STORE_ID];
        // get current currency
        $fyndiqCurrency = $this->fmConfig->get('currency', $storeId);
        $currentCurrency = $this->fmPrestashop->getSelectedCurrency($fyndiqCurrency);
        $market = $this->fmPrestashop->getCountryCode();

        $stockMin = $settings[FmFormSetting::SETTINGS_STOCK_MIN];
        $groupId = $settings[FmFormSetting::SETTINGS_GROUP_ID];

        FyndiqUtils::debug('$currentCurrency', $currentCurrency);
        FyndiqUtils::debug('$stockMin', $stockMin);

        // Creating customer and add it to the context so we can set a
        // specific discount customer group to the price.
        $customer = $this->fmPrestashop->newCustomer();
        $customer->id_default_group = $groupId;
        $customer->id_shop = $storeId;
        $context = $this->getContext();
        $context->cart = new Cart();
        $context->customer = $customer;

        // set fyndiq custom currency based on the module settings
        if ($this->fmPrestashop->isObjectLoaded($context->currency)) {
            $context->currency->id = $fyndiqCurrency ? $fyndiqCurrency->id : $this->fmPrestashop->currencyGetDefaultCurrency()->id;
        }

        $allProductIds = array_map(create_function('$p', 'return $p[\'product_id\'];'), $fmProducts);
        foreach ($fmProducts as $fmProduct) {
            $storeProduct = $this->getStoreProduct($fmProduct['product_id'], $allProductIds, $settings, $context);

            FyndiqUtils::debug('$storeProduct', $storeProduct);
            if (!$storeProduct) {
                // Product not found (maybe not in this store);
                continue;
            }
            if (count($storeProduct['combinations']) === 0 && $storeProduct['minimal_quantity'] > 1) {
                FyndiqUtils::debug('minimal_quantity > 1 SKIPPING PRODUCT', $storeProduct['minimal_quantity']);
                continue;
            }

            $fyndiqPrice = FyndiqUtils::getFyndiqPrice($storeProduct['price'], $fmProduct['exported_price_percentage']);

            $exportProductTitle = $fmProduct['name'] ? $fmProduct['name'] : $storeProduct['name'];
            $exportProductDescription = $fmProduct['description'] ? $fmProduct['description'] : $storeProduct['description'];

            $exportProduct = array(
                FyndiqFeedWriter::ID => $storeProduct['id'],
                FyndiqFeedWriter::PRODUCT_CATEGORY_ID => $storeProduct['category_id'],
                FyndiqFeedWriter::PRODUCT_CATEGORY_NAME =>
                    $this->fmPrestashop->getCategoryPath($storeProduct['category_id']),
                FyndiqFeedWriter::PRODUCT_CURRENCY => $currentCurrency,
                FyndiqFeedWriter::QUANTITY => $storeProduct['quantity'],
                FyndiqFeedWriter::PRODUCT_DESCRIPTION => $exportProductDescription,
                FyndiqFeedWriter::PRICE => $fyndiqPrice,
                FyndiqFeedWriter::OLDPRICE => $storeProduct['oldprice'],
                FyndiqFeedWriter::PRODUCT_TITLE => $exportProductTitle,
                FyndiqFeedWriter::PRODUCT_VAT_PERCENT => $storeProduct['tax_rate'],
                FyndiqFeedWriter::PRODUCT_MARKET => $market,
                FyndiqFeedWriter::SKU => $storeProduct['reference'],
                FyndiqFeedWriter::IMAGES => $storeProduct['images'],
                FyndiqFeedWriter::QUANTITY => $this->getExportQty(intval($storeProduct['quantity']), $stockMin),
                FyndiqFeedWriter::PRODUCT_BRAND_NAME => $storeProduct['brand'],
            );

            $articles = array();
            foreach ($storeProduct['combinations'] as $combination) {
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
                    FyndiqFeedWriter::OLDPRICE => $combination['oldprice'],
                    FyndiqFeedWriter::IMAGES => $combination['images'],
                    FyndiqFeedWriter::ARTICLE_NAME => $exportProductTitle,
                    FyndiqFeedWriter::ARTICLE_EAN => $storeProduct['ean'],
                    FyndiqFeedWriter::ARTICLE_ISBN => $storeProduct['isbn'],
                    FyndiqFeedWriter::ARTICLE_MPN => $storeProduct['mpn'],
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
        FyndiqUtils::debug('$feedWriter->getProductCount()', $feedWriter->getProductCount());
        FyndiqUtils::debug('$feedWriter->getArticleCount()', $feedWriter->getArticleCount());
        FyndiqUtils::debug('End');
        return $feedWriter->write();
    }
}
