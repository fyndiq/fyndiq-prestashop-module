<?php

class FmCart extends Cart
{

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

    public function getOrderTotal($with_taxes = true, $type = Cart::BOTH, $products = null, $id_carrier = null, $use_cache = true)
    {
        $total = 0;
        if (!in_array($type, array(Cart::ONLY_PRODUCTS, Cart::BOTH))) {
            return $total;
        }
        $search = array();
        if (is_array($products)) {
            foreach ($products as $row) {
                $search[] = $row['id_product'] . '-' . $row['id_product_attribute'];
            }
        }
        foreach ($this->getProducts() as $product) {
            if ($search && !in_array($product['id_product'] . '-' . $product['id_product_attribute'], $search)) {
                continue;
            }
            $total += $with_taxes ? $product['total_wt'] : $product['total'];
        }
        return (float)$total;
    }
}
