<?php

class FmProduct {
    private static function get_image_link($link_rewrite, $id_image, $image_type) {
        if (FMPSV == FMPSV14) {
            $link = new Link();
            $image = $link->getImageLink($link_rewrite, $id_image, $image_type);
        }
        if (FMPSV == FMPSV15) {
            $context = Context::getContext();
            $image = $context->link->getImageLink($link_rewrite, $id_image, $image_type);
        }
        return $image;
    }

    public static function get($product_id) {

        $module = Module::getInstanceByName('fyndiqmerchant');

        $result = [];

        $product = new Product($product_id, false, $module->language_id);

        $result['name'] = $product->name;
        $result['reference'] = $product->reference;
        $result['quantity'] = $product->quantity;
        $result['price'] = $product->price;

        ### get the medium image type
        $image_type_name = [
            FMPSV15 => 'medium_default',
            FMPSV14 => 'medium'
        ];
        $image_types = ImageType::getImagesTypes();
        foreach ($image_types as $type) {
            if ($type['name'] == $image_type_name[FMPSV]) {
                $image_type = $type;
            }
        }

        ### get images
        $images = $product->getImages($module->language_id);

        # assign main product image
        if (count($images) > 0) {
            $result['image'] = self::get_image_link(
                $product->link_rewrite, $images[0]['id_image'], $image_type['name']);
        }

        ### handle combinations
        $result['combinations'] = [];

        $get_attribute_combinations_func = [
            FMPSV14 => 'getAttributeCombinaisons',
            FMPSV15 => 'getAttributeCombinations'
        ];

        # get combinations and combination images
        $combinations = $product->$get_attribute_combinations_func[FMPSV]($module->language_id);
        $combination_images = $product->getCombinationImages($module->language_id);

        if ($combination_images) {
            foreach ($combinations as $combination) {
                $combination_result = [];

                $combination_result['price'] = $combination['price'];

                foreach ($combination_images as $combination_image) {

                    # data array is stored in another array with only one key: 0. I have no idea why
                    $combination_image = $combination_image[0];

                    # if combination image belongs to the same product attribute mapping as the current combinationn
                    if ($combination_image['id_product_attribute'] == $combination['id_product_attribute']) {

                        # get product image link for this combination image, and store it in the combination result
                        $combination_result['image'] = self::get_image_link(
                            $product->link_rewrite, $combination_image['id_image'], $image_type['name']);
                    }
                }

                $result['combinations'][] = $combination_result;
            }
        }

        return $result;
    }
}
