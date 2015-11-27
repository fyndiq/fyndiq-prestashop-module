<?php

class FmProduct extends FmModel
{

    const ALL_PRODUCTS_CATEGORY_ID = -1;

    public function getAmount($categoryId)
    {
        $sqlQuery = '
            SELECT count(p.id_product) AS amount
            FROM ' . $this->fmPrestashop->globDbPrefix() . 'product as p
            JOIN ' . $this->fmPrestashop->globDbPrefix() . 'category_product as cp
            WHERE p.id_product = cp.id_product
            AND cp.id_category = ' . $this->fmPrestashop->dbEscape($categoryId) . ';';
        if ($categoryId == self::ALL_PRODUCTS_CATEGORY_ID) {
            $sqlQuery = '
                SELECT count(p.id_product) AS amount
                FROM ' . $this->fmPrestashop->globDbPrefix() . 'product as p;';
        }
        return $this->fmPrestashop->dbGetInstance()->getValue($sqlQuery);
    }

    public function getByCategory($categoryId, $page, $perPage)
    {
        // fetch products per category manually,
        // Product::getProducts doesn't work in backoffice,
        // it's hard coded to work only with front office controllers
        $offset = $perPage * ($page - 1);
        $sqlQuery = '
            SELECT p.id_product
            FROM ' . $this->fmPrestashop->globDbPrefix() . 'product p
            JOIN ' . $this->fmPrestashop->globDbPrefix() . 'category_product as cp
            WHERE p.id_product = cp.id_product
            AND cp.id_category = ' . $this->fmPrestashop->dbEscape($categoryId) . '
            LIMIT ' . $offset . ', ' . $perPage;
        if ($categoryId == self::ALL_PRODUCTS_CATEGORY_ID) {
            $sqlQuery = '
                SELECT p.id_product
                FROM ' . $this->fmPrestashop->globDbPrefix() . 'product p
                LIMIT ' . $offset . ', ' . $perPage;
        }
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
    public function updateProductStatus($tableName, $productId, $status)
    {
        $where = 'product_id=' . $this->fmPrestashop->dbEscape($productId);
        return $this->fmPrestashop->dbUpdate($tableName, array('state' => $status), $where);
    }

    /**
     * Set all products' statuses to $newStats
     *
     * @param  string $tableName
     * @param  string $newStatus
     * @return bool
     */
    public function updateAllProductStatus($tableName, $newStatus)
    {
        return $this->fmPrestashop->dbUpdate($tableName, array('state' => $newStatus));
    }


    protected function getSKUFieldName($skuTypeId, $isArticle = false)
    {
        switch ($skuTypeId) {
            case FmUtils::SKU_ID: return 'id_product';
            case FmUtils::SKU_EAN: return 'ean13';
        }
        return 'reference';
    }

    public function checkProducts($skuTypeId) {
        if ($skuTypeId == FmUtils::SKU_ID) {
            return array();
        }
        $fieldName = $this->getSKUFieldName($skuTypeId);
        $sql = 'SELECT id_product, 0 AS parent, '. $fieldName .' as ref
                FROM ' . $this->fmPrestashop->globDbPrefix() . 'product
                UNION ALL
                SELECT id_product_attribute as id_product, id_product AS parent, ' . $fieldName . ' as ref
                FROM ' . $this->fmPrestashop->globDbPrefix() . 'product_attribute';
        $db = $this->fmPrestashop->dbGetInstance();
        $query = $db->query($sql);
        $all = array();
        $duplicates = array();
        $addedDuplicates = array();
        while ($row = $db->nextRow($query)) {
            $ref = $row['ref'];
            if (isset($all[$ref])) {
                // process duplicate
                if (!in_array($ref, $addedDuplicates)){
                    // add the first occurrence of the duplicate
                    $duplicates[] = $all[$ref];
                    $addedDuplicates[] = $ref;
                }
                $duplicates[] = $row;
                continue;
            }
            $all[$ref] = $row;
        }
        return $duplicates;
    }
}
