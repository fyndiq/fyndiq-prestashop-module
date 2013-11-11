<?php

class FmProduct {
    public static function get($product_id) {
        $result = [];

        $context = Context::getContext();
        $product = new Product($product_id, false, $context->language->id);

        $image_types = ImageType::getImagesTypes();
        foreach ($image_types as $type) {
            if ($type['name'] == 'large_default') {
                $image_type = $type;
            }
        }

        // get all images for this product
        $images = Image::getImages($context->language->id, $product_id);
        // if product has any images
        if (count($images) > 0) {
            $image_link = $context->link->getImageLink($product->link_rewrite, $images[0]['id_image']);
            //$context->link->getImageLink($product->link_rewrite, $image['id_image'], $image_type);
        } else {
            $image_link = false;
        }
        $result['image'] = $image_link;

        $result['reference'] = $product->reference;
        $result['quantity'] = $product->quantity;
        $result['price'] = $product->price;

        if (startswith(_PS_VERSION_, '1.4.')) {
            $result['name'] = $product->name[1];
        }
        if (startswith(_PS_VERSION_, '1.5.')) {
            $result['name'] = $product->name;
        }

        return $result;
    }
}
