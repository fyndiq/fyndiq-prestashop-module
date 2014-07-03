<?php

class FmCombination {
    public static function get() {
        $result[$id]['id'] = $id;
        $result[$id]['price'] = $combination_price;
        $result[$id]['quantity'] = $product_attribute['quantity'];
        $result[$id]['attributes'][] = [
            'name' => $product_attribute['group_name'],
            'value' => $product_attribute['attribute_name']
        ];

        $combination_images = $product->getCombinationImages($module->language_id);

    }
}
