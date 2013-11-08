<?php

# import PrestaShop config, to enable use of PrestaShop classes, like Configuration
$configPath = dirname(dirname(dirname(dirname($_SERVER['SCRIPT_FILENAME'])))).'/config/config.inc.php';

if (file_exists($configPath)) {
    require_once($configPath);
} else {
    exit;
}

require_once('./helpers.php');

class FmAjaxService {

    # return a success response
    public static function response($data) {
        $response = array('fm-service-status' => 'success', 'data' => $data);
        $json = json_encode($response);
        if (json_last_error() != JSON_ERROR_NONE) {
            self::response_error(FmMessages::get('json-encode-fail'));
        } else {
            echo $json;
        }
    }

    # return an error response
    public static function response_error($msg) {
        $response = array('fm-service-status' => 'error', 'message' => $msg);
        $json = json_encode($response);
        echo $json;
    }

    # handle incoming ajax request
    public static function handle_request() {
        $action = $_POST['action'];
        if (array_key_exists('args', $_POST)) {
            $args = $_POST['args'];
        }

        if ($action == 'get_orders') {
            try {
                $ret = FmHelpers::call_api('orders/');
                self::response($ret);
            } catch (Exception $e) {
                self::response_error(FmMessages::get('api-call-error').': '.$e->getMessage());
            }
        }

        if ($action == 'get_products') {
            $id_lang = 1;
            $context = Context::getContext();

            $products = [];

            // fetch products per category manually,
            // Product::getProducts doesnt work in backoffice,
            // it's hard coded to work only with front office controllers
            $rows = Db::getInstance()->ExecuteS('
                select p.id_product
                from '._DB_PREFIX_.'product as p
                join '._DB_PREFIX_.'category_product as cp
                where p.id_product = cp.id_product
                and cp.id_category = '.$args['category'].'
            ');

            $image_types = ImageType::getImagesTypes();
            foreach ($image_types as $type) {
                if ($type['name'] == 'large_default') {
                    $image_type = $type;
                }
            }

            foreach ($rows as $row) {
                $product = new Product($row['id_product'], false, $context->language->id);
                $images = Image::getImages($context->language->id, $row['id_product']);
                $image_link = $context->link->getImageLink($product->link_rewrite, $images[0]['id_image']);
                $products[] = array('product' => $product, 'image' => $image_link);
                //$context->link->getImageLink($product->link_rewrite, $image['id_image'], $image_type);
            }
            self::response($products);
        }

        if ($action == 'get_categories') {
            $categories = Category::getCategories();
            self::response($categories);
        }
    }
}

FmAjaxService::handle_request();
