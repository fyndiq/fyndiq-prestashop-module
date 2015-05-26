<?php

class FmProduct extends FmModel
{
    private function getImageLink($linkRewrite, $idImage, $imageType)
    {
        if (FMPSV == FMPSV14) {
            $link = new Link();
            $image = $link->getImageLink($linkRewrite, $idImage, $imageType);
        }
        if (FMPSV == FMPSV15 or FMPSV == FMPSV16) {
            $context = Context::getContext();
            $image = $context->link->getImageLink($linkRewrite, $idImage, $imageType);
        }
        return $image;
    }

    private function getPrice($price)
    {
        // $tax_rules_group = new TaxRulesGroup($product->id_tax_rules_group);
        $module = Module::getInstanceByName('fyndiqmerchant');
        $currency = new Currency(Configuration::get($module->config_name . '_currency'));
        $convertedPrice = $price * $currency->conversion_rate;

        return Tools::ps_round($convertedPrice, 2);
    }

    private function getImageType()
    {
        ### get the medium image type
        $imageTypeName = array(
            FMPSV16 => 'large_default',
            FMPSV15 => 'large_default',
            FMPSV14 => 'large'
        );
        $imageTypes = ImageType::getImagesTypes();
        foreach ($imageTypes as $type) {
            if ($type['name'] == $imageTypeName[FMPSV]) {
                return  $type;
            }
        }
        return '';
    }

    private function getProductAttributes($product, $languageId)
    {
        $getAttrCombinations = array(
            FMPSV14 => 'getAttributeCombinaisons',
            FMPSV15 => 'getAttributeCombinations',
            FMPSV16 => 'getAttributeCombinations'
        );

        # get this products attributes and combination images
        return $product->$getAttrCombinations[FMPSV]($languageId);
    }

    /**
     * Returns the first category_id the product belongs to
     *
     * @param $product
     * @return mixed
     */
    private function getCategoryId($product)
    {
        $categories = $product->getCategories();
        return array_pop($categories);
    }

    /**
     * Returns single product with combinations or false if product is not active/found
     *
     * @param $productId
     * @return array|bool
     */
    public function get($productId)
    {

        $result = array(
            'combinations' => array()
        );

        $languageId = FmConfig::get('language');

        $product = new Product($productId, false, $languageId);

        if (empty($product->id) || !$product->active) {
            return false;
        }

        $result['id'] = $product->id;
        $result['name'] = $product->name;
        $result['category_id'] = self::getCategoryId($product);

        $result['reference'] = $product->reference;
        $result['tax_rate'] = $product->getTaxesRate();
        $result['quantity'] = Product::getQuantity($product->id);
        $result['price'] = self::getPrice($product->price);
        $result['description'] = $product->description;
        $result['manufacturer_name'] = Manufacturer::getNameById((int)$product->id_manufacturer);

        ### get the medium image type
        $imageType = self::getImageType();

        ### get images
        $images = $product->getImages($languageId);

        # assign main product image
        if (count($images) > 0) {
            $result['image'] = self::getImageLink(
                $product->link_rewrite,
                $images[0]['id_image'],
                $imageType['name']
            );
        }

        ### handle combinations
        $productAttributes = self::getProductAttributes($product, $languageId);
        $combinationImages = $product->getCombinationImages($languageId);

        foreach ($productAttributes as $productAttribute) {
            $id = $productAttribute['id_product_attribute'];
            $comboProduct = new Product($id, false, $languageId);

            $result['combinations'][$id]['id'] = $id;
            $result['combinations'][$id]['reference'] = $comboProduct->reference;
            $result['combinations'][$id]['price'] = self::getPrice($product->price + $productAttribute['price']);
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

                        // if combination image belongs to the same product attribute mapping as the current combinationn
                        if ($combinationImage['id_product_attribute'] == $productAttribute['id_product_attribute']) {
                            $image = self::getImageLink(
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

    public function getAmount($categoryId)
    {
        $sqlQuery = '
            SELECT count(p.id_product) AS amount
            FROM ' . $this->fmPrestashop->globDbPrefix() . 'product as p
            JOIN ' . $this->fmPrestashop->globDbPrefix() . 'category_product as cp
            WHERE p.id_product = cp.id_product
            AND cp.id_category = ' . $this->fmPrestashop->dbEscape($categoryId) . ';';
        return $this->fmPrestashop->dbGetInstance()->getValue($sqlQuery);
    }

    public function getByCategory($categoryId, $page, $perPage)
    {
        // fetch products per category manually,
        // Product::getProducts doesnt work in backoffice,
        // it's hard coded to work only with front office controllers
        $offset = $perPage * ($page - 1);
        $sqlQuery = '
            SELECT p.id_product
            FROM ' . $this->fmPrestashop->globDbPrefix() . 'product p
            JOIN ' . $this->fmPrestashop->globDbPrefix() . 'category_product as cp
            WHERE p.id_product = cp.id_product
            AND cp.id_category = ' . $this->fmPrestashop->dbEscape($categoryId) . '
            LIMIT ' . $offset . ', ' . $perPage;
        $rows = $this->fmPrestashop->dbGetInstance()->ExecuteS($sqlQuery);
        return $rows;
    }

    /**
     * Update product status
     *
     * @param DbCore $dbConn
     * @param string $tableName
     * @param string $id
     * @param string $status
     * @return bool
     */
    public function updateProductStatus($dbConn, $tableName, $productId, $status)
    {
        $where = 'id=' . $dbConn->escape($productId);
        return $dbConn->update($tableName, array('state' => $status), $where);
    }
}
