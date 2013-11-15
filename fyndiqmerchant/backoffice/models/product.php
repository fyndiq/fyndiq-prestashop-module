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

        $result['id'] = $product->id;
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

        # get this products attributes and combination images
        $product_attributes = $product->$get_attribute_combinations_func[FMPSV]($module->language_id);
        $combination_images = $product->getCombinationImages($module->language_id);

        foreach ($product_attributes as $product_attribute) {
            $id = $product_attribute['id_product_attribute'];

            $result['combinations'][$id]['id'] = $id;
            $result['combinations'][$id]['price'] = $product_attribute['price'];
            $result['combinations'][$id]['quantity'] = $product_attribute['quantity'];
            $result['combinations'][$id]['attributes'][] = [
                'name' => $product_attribute['group_name'],
                'value' => $product_attribute['attribute_name']
            ];

            # if this combination has no image yet
            if (empty($result['combinations'][$id]['image'])) {

                # if this combination has any images
                if ($combination_images) {
                    foreach ($combination_images as $combination_image) {

                        # data array is stored in another array with only one key: 0. I have no idea why
                        $combination_image = $combination_image[0];

                        # if combination image belongs to the same product attribute mapping as the current combinationn
                        if ($combination_image['id_product_attribute'] == $product_attribute['id_product_attribute']) {

                            $image = $combination_result['image'] = self::get_image_link(
                                $product->link_rewrite, $combination_image['id_image'], $image_type['name']);

                            $result['combinations'][$id]['image'] = $image;
                        }
                    }
                }
            }
        }

        return $result;
    }

    public static function get_by_category($category_id) {
        # fetch products per category manually,
        # Product::getProducts doesnt work in backoffice,
        # it's hard coded to work only with front office controllers
        $rows = Db::getInstance()->ExecuteS('
            select p.id_product
            from '._DB_PREFIX_.'product as p
            join '._DB_PREFIX_.'category_product as cp
            where p.id_product = cp.id_product
            and cp.id_category = '.FmHelpers::db_escape($category_id).'
        ');
        return $rows;
    }
}
