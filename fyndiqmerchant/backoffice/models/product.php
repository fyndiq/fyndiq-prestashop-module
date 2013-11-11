<?php

class FmProduct {
    public static function get($product_id) {

        $module = Module::getInstanceByName('fyndiqmerchant');

        $result = [];

        $product = new Product($product_id, false, $module->language_id);

        $result['name'] = $product->name;
        $result['reference'] = $product->reference;
        $result['quantity'] = $product->quantity;
        $result['price'] = $product->price;

        $image_types = ImageType::getImagesTypes();
        $images = $product->getImages($module->language_id);

        if (count($images) > 0) {
            if (startswith(_PS_VERSION_, '1.4.')) {
                foreach ($image_types as $type) {
                    if ($type['name'] == 'medium') {
                        $image_type = $type;
                    }
                }
                $link = new Link();
                $result['image'] = $link->getImageLink(
                    $product->link_rewrite, $images[0]['id_image'], $image_type['name']);
            }

            if (startswith(_PS_VERSION_, '1.5.')) {
                foreach ($image_types as $type) {
                    if ($type['name'] == 'medium_default') {
                        $image_type = $type;
                    }
                }
                $context = Context::getContext();
                $result['image'] = $context->link->getImageLink(
                    $product->link_rewrite, $images[0]['id_image'], $image_type['name']);
            }
        }

        return $result;
    }
}
