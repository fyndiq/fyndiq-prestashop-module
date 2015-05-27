<?php

class FmProduct extends FmModel
{

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
    public function get($languageId, $productId)
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
        $result['category_id'] = self::getCategoryId($product);
        $result['reference'] = $product->reference;
        $result['tax_rate'] = $product->getTaxesRate();
        $result['quantity'] = $this->fmPrestashop->productGetQuantity($product->id);
        $result['price'] = $this->fmPrestashop->getPrice($product->price);
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
            $result['combinations'][$id]['reference'] = $comboProduct->reference;
            $result['combinations'][$id]['price'] =
                $this->fmPrestashop->getPrice($product->price + $productAttribute['price']);
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
