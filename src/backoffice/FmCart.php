<?php

class FmCart extends Cart {

    protected $_orderDetails = array();

    public function setOrderDetails($orderDetails)
    {
        $this->_orderDetails = $orderDetails;
    }

    protected function updateProductPrices($products)
    {
       foreach ($this->_orderDetails as $row) {
            foreach ($products as $key => $product) {
                if ($product['id_product'] == $row->productId && $product['id_product_attribute'] == $row->combinationId) {
                    $product['quantity'] = $row->quantity;
                    $product['price'] = (float)Tools::ps_round(
                        (float)($row->unit_price_amount / ((100+intval($row->vat_percent)) / 100)),
                        2
                    );
                    $product['price_wt'] = floatval($row->unit_price_amount);
                    $product['total_wt'] = floatval(($row->unit_price_amount*$row->quantity));
                    $product['total'] = (float)Tools::ps_round(
                        floatval((($row->unit_price_amount / ((100+intval($row->vat_percent)) / 100))*$row->quantity)),
                        2
                    );
                    $product['rate'] = floatval($row->vat_percent);
                    $products[$key] = $product;
                }
            }
        }
        return $products;
    }

    public function getProducts($refresh = false, $id_product = false, $id_country = null)
    {
        $result = parent::getProducts($refresh, $id_product, $id_country);
        return $this->updateProductPrices($result);
    }
}
