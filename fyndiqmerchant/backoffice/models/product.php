<?php

require_once('config.php');

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

    private static function get_price($price) {
        // $tax_rules_group = new TaxRulesGroup($product->id_tax_rules_group);
        $module = Module::getInstanceByName('fyndiqmerchant');
        $currency = new Currency(Configuration::get($module->config_name.'_currency'));
        $converted_price = $price * $currency->conversion_rate;
        return Tools::ps_round($converted_price, 2);
    }

    public static function get($product_id) {

        $result = array();

        $language_id = FmConfig::get('language');

        $product = new Product($product_id, false, $language_id);

        $result['id'] = $product->id;
        $result['name'] = $product->name;
        $result['reference'] = $product->reference;
        $result['quantity'] = $product->quantity;
        $result['price'] = self::get_price($product->price);

        ### get the medium image type
        $image_type_name = array(
            FMPSV15 => 'large_default',
            FMPSV14 => 'large'
        );
        $image_types = ImageType::getImagesTypes();
        foreach ($image_types as $type) {
            if ($type['name'] == $image_type_name[FMPSV]) {
                $image_type = $type;
            }
        }

        ### get images
        $images = $product->getImages($language_id);

        # assign main product image
        if (count($images) > 0) {
            $result['image'] = self::get_image_link(
                $product->link_rewrite, $images[0]['id_image'], $image_type['name']);
        }

        ### handle combinations
        $result['combinations'] = array();

        $get_attribute_combinations_func = array(
            FMPSV14 => 'getAttributeCombinaisons',
            FMPSV15 => 'getAttributeCombinations'
        );

        # get this products attributes and combination images
        $product_attributes = $product->$get_attribute_combinations_func[FMPSV]($language_id);
        $combination_images = $product->getCombinationImages($language_id);

        foreach ($product_attributes as $product_attribute) {
            $id = $product_attribute['id_product_attribute'];

            $result['combinations'][$id]['id'] = $id;
            $result['combinations'][$id]['price'] = self::get_price($product->price + $product_attribute['price']);
            $result['combinations'][$id]['quantity'] = $product_attribute['quantity'];
            $result['combinations'][$id]['attributes'][] = array(
                'name' => $product_attribute['group_name'],
                'value' => $product_attribute['attribute_name']
            );

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
