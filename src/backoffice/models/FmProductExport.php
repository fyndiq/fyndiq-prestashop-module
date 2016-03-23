<?php

class FmProductExport extends FmModel
{

    const PENDING = 'PENDING';
    const FOR_SALE = 'FOR_SALE';

    private $productFeatures = array();

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

    public function updateProduct($productId, $storeId, $name = null, $description = null)
    {
        $data = array(
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
     * getStoreProduct returns single product with combinations
     * or false if product is not active/found
     * @param  int $productId ProductId
     * @param  array $settings  Settings array
     * @param  Context $context Context object
     * @return array|bool
     */
    public function getStoreProduct($productId, $settings, $context)
    {
        $groupId = $settings[FmFormSetting::SETTINGS_GROUP_ID];
        $storeId = $settings[FmFormSetting::SETTINGS_STORE_ID];
        $languageId = $settings[FmFormSetting::SETTINGS_LANGUAGE_ID];
        $percentageDiscount = $settings[FmFormSetting::SETTINGS_PERCENTAGE_DISCOUNT];
        $priceDiscount = $settings[FmFormSetting::SETTINGS_PRICE_DISCOUNT];
        $product = $this->fmPrestashop->productNew($productId, false, $languageId, $storeId);
        if (empty($product->id) || !$product->active) {
            return array();
        }

        $price = $this->fmPrestashop->getPrice($product, $context, $groupId);
        $fyndiqPrice = FyndiqUtils::getFyndiqPrice($price, $percentageDiscount, $priceDiscount);
        FyndiqUtils::debug('$price', $price);
        FyndiqUtils::debug('$fyndiqPrice', $fyndiqPrice);

        $result = array(
            'id' => $product->id,
            'name' => $product->name,
            'category_id' => $this->getCategoryId($product),
            'reference' => $this->getProductSKU(
                $settings[FmFormSetting::SETTINGS_MAPPING_SKU],
                $product
            ),
            'tax_rate' => $this->fmPrestashop->productGetTaxRate($product),
            'quantity' => $this->fmPrestashop->productGetQuantity($product->id),
            'price' => $fyndiqPrice,
            'oldprice' => $this->fmPrestashop->getBasePrice($product),
            'description_short' => $product->description_short,
            'minimal_quantity' => intval($product->minimal_quantity),
            'manufacturer_name' => $this->fmPrestashop->manufacturerGetNameById(
                (int)$product->id_manufacturer
            ),
            'combinations' => array(),
            'description' => $this->getMappedValue(
                $settings[FmFormSetting::SETTINGS_MAPPING_DESCRIPTION],
                $product
            ),
            'brand' => $this->getMappedValue(
                $settings[FmFormSetting::SETTINGS_MAPPING_BRAND],
                $product
            ),
            'ean' => $this->getMappedValue(
                $settings[FmFormSetting::SETTINGS_MAPPING_EAN],
                $product
            ),
            'isbn' => $this->getMappedValue(
                $settings[FmFormSetting::SETTINGS_MAPPING_ISBN],
                $product
            ),
            'mpn' => $this->getMappedValue(
                $settings[FmFormSetting::SETTINGS_MAPPING_MPN],
                $product
            ),
        );

        // get the medium image type
        $imageType = $this->fmPrestashop->getImageType();

        // get images
        $images = $product->getImages($languageId);
        $result['images'] = array();
        foreach ($images as $image) {
            // PrestaShop recommends to use the legacy format for image Id
            $imageId = $product->id . '-' . $image['id_image'];
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
                $reference = $this->getProductSKU(
                    $settings[FmFormSetting::SETTINGS_MAPPING_SKU],
                    $product,
                    $fixingAttribute
                );
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

                $price = $this->fmPrestashop->getPrice($product, $context, $groupId, $id);
                $fyndiqPrice = FyndiqUtils::getFyndiqPrice($price, $percentageDiscount, $priceDiscount);

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
                    'price' => $fyndiqPrice,
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
     * getArticleFieldValue returns the specified field's value from a product
     * @param string $fieldKey Property name
     * @param Product $product Product object
     * @return mixed
     */
    protected function getArticleFieldValue($fieldKey, $product)
    {
        return $product->{$fieldKey};
    }

    /**
     * getProductFeatures Returns an array of all used product features for exported products
     * @param  array $settings Settings
     * @param  array $productIds list of exported product id-s
     * @return array
     */
    protected function getProductFeatures($settings, $productIds)
    {
        $features = array();
        if (empty($productIds)) {
            return $features;
        }

        $featureIds = array();
        $mappings = array(
            FmFormSetting::SETTINGS_MAPPING_DESCRIPTION,
            FmFormSetting::SETTINGS_MAPPING_SKU,
            FmFormSetting::SETTINGS_MAPPING_EAN,
            FmFormSetting::SETTINGS_MAPPING_ISBN,
            FmFormSetting::SETTINGS_MAPPING_MPN,
            FmFormSetting::SETTINGS_MAPPING_BRAND,
        );

        foreach ($mappings as $mappingTarget) {
            $mapping = FmFormSetting::deserializeMappingValue($settings[$mappingTarget]);
            $mappingType = intval($mapping['type']);
            $mappingId = $mapping['id'];
            if ($mappingType === FmFormSetting::MAPPING_TYPE_PRODUCT_FEATURE) {
                $featureIds[] = $mappingId;
            }
        }

        if (empty($featureIds)) {
            return $features;
        }

        $languageId = $settings[FmFormSetting::SETTINGS_LANGUAGE_ID];

        // Note: There is a limit for query size in MySQL so this may hit it eventually
        $sql = 'SELECT pl.value, p.id_feature, p.id_product
            FROM ' . $this->fmPrestashop->globDbPrefix(). 'feature_product AS p
            LEFT JOIN ' . $this->fmPrestashop->globDbPrefix() . '_feature_value_lang AS pl ON
            p.id_feature_value = pl.id_feature_value AND pl.id_lang = '. $languageId .'
            WHERE p.id_product IN (' . implode(',', $productIds) . ')
            AND p.id_feature IN (' . implode(',', $featureIds) . ')';

        $query = $this->fmPrestashop->dbGetInstance()->ExecuteS($sql);

        foreach ($query as $row) {
            $productId = intval($row['id_product']);
            $featureId = intval($row['id_feature']);
            if (!isset($features[$productId])) {
                $features[$productId] = array();
            }
            if (!isset($features[$productId][$featureId])) {
                $features[$productId][$featureId] = array();
            }
            $features[$productId][$featureId] = $row['value'];
        }
        return $features;
    }

    /**
     * getProductFeature returns product feature value if it is set
     * @param  int $productId ProductId
     * @param  int $featureId FeatureId
     * @return string
     */
    public function getProductFeature($productId, $featureId)
    {
        if (isset($this->productFeatures[$productId]) &&
            isset($this->productFeatures[$productId][$featureId])
        ) {
            return $this->productFeatures[$productId][$featureId];
        }
        return '';
    }

    /**
     * getMappedValue returns mapped value for product
     * @param  string $fieldKey Field key
     * @param  Product $product Product object
     * @return string
     */
    private function getMappedValue($fieldKey, $product)
    {
        $mappedKey = FmFormSetting::deserializeMappingValue($fieldKey);
        $mappingType = intval($mappedKey['type']);
        $mappingId = $mappedKey['id'];

        if ($mappingType === FmFormSetting::MAPPING_TYPE_PRODUCT_FEATURE) {
            return $this->getProductFeature($product->id, $mappingId);
        }
        if ($mappingType === FmFormSetting::MAPPING_TYPE_PRODUCT_FIELD) {
            return $this->getArticleFieldValue($mappingId, $product);
        }
        if ($mappingType === FmFormSetting::MAPPING_TYPE_MANUFACTURER_NAME) {
            return $this->fmPrestashop->manufacturerGetNameById(
                (int)$product->id
            );
        }
        if ($mappingType === FmFormSetting::MAPPING_TYPE_SHORT_AND_LONG_DESCRIPTION) {
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

        // set Fyndiq custom currency based on the module settings
        if ($this->fmPrestashop->isObjectLoaded($context->currency)) {
            $context->currency->id = $fyndiqCurrency ? $fyndiqCurrency : $this->fmPrestashop->currencyGetDefaultCurrency()->id;
        }

        $allProductIds = array();
        foreach ($fmProducts as $row) {
            $allProductIds = intval($row['product_id']);
        }

        $this->productFeatures = $this->getProductFeatures($settings, $allProductIds);

        foreach ($fmProducts as $fmProduct) {
            $storeProduct = $this->getStoreProduct(
                intval($fmProduct['product_id']),
                $settings,
                $context
            );

            FyndiqUtils::debug('$storeProduct', $storeProduct);
            if (!$storeProduct) {
                // Product not found (maybe not in this store);
                continue;
            }
            if (count($storeProduct['combinations']) === 0 && $storeProduct['minimal_quantity'] > 1) {
                FyndiqUtils::debug('minimal_quantity > 1 SKIPPING PRODUCT', $storeProduct['minimal_quantity']);
                continue;
            }

            $fyndiqPrice = FyndiqUtils::getFyndiqPrice($storeProduct['price'], $settings[FmFormSetting::SETTINGS_PERCENTAGE_DISCOUNT]);

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
                $fyndiqPrice = FyndiqUtils::getFyndiqPrice($combination['price'], $settings[FmFormSetting::SETTINGS_PERCENTAGE_DISCOUNT]);

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
