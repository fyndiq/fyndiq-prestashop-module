<?php

# import PrestaShop config, to enable use of PrestaShop classes, like Configuration
$configPath = dirname(dirname(dirname(dirname($_SERVER['SCRIPT_FILENAME'])))).'/config/config.inc.php';
if (file_exists($configPath)) {
    require_once($configPath);
} else {
    exit;
}

require_once('./helpers.php');
require_once('./models/product.php');

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
        $action = false;
        $args = [];
        if (array_key_exists('action', $_POST)) {
            $action = $_POST['action'];
        }
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
            if (array_key_exists('category', $args)) {
                $products = [];

                $rows = FmProduct::get_by_category($args['category']);

                foreach ($rows as $row) {
                    $products[] = FmProduct::get($row['id_product']);
                }

                self::response($products);
            } else {
                self::response_error(FmMessages::get('missing-category-argument'));
            }
        }

        if ($action == 'get_categories') {
            $categories = Category::getCategories();
            self::response($categories);
        }
    }
}

FmAjaxService::handle_request();
