<?php

# import PrestaShop config, to enable use of PrestaShop classes, like Configuration
$configPath = dirname(dirname(dirname(dirname($_SERVER['SCRIPT_FILENAME'])))).'/config/config.inc.php';

if (file_exists($configPath)) {
    require_once($configPath);
} else {
    exit;
}

require_once('./helpers.php');

class FyndiqMerchantAjaxService {

    # return a success response
    public static function response($data) {
        $response = array('status' => 'success', 'data' => $data);
        $json = json_encode($response);
        if (json_last_error() != JSON_ERROR_NONE) {
            self::response_error('Could not encode response json.');
        } else {
            echo $json;
        }
    }

    # return an error response
    public static function response_error($msg) {
        $response = array('status'=> 'error', 'message'=> $msg);
        $json = json_encode($response);
        echo $json;
    }

    # handle incoming ajax request
    public static function handle_request() {
        $action = $_POST['action'];

        if ($action == 'get_orders') {
            try {
                $ret = FyndiqMerchantHelpers::call_api('orders/');
                self::response($ret);
            } catch (Exception $e) {
                self::response_error('Error when calling API: '.$e->getMessage());
            }
        }

        if ($action == 'get_products') {
            $products = Product::getProducts(1, 0, 10, 'name', 'desc');
            self::response($products);
        }
    }
}

FyndiqMerchantAjaxService::handle_request();
