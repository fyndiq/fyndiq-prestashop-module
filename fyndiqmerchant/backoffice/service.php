<?php

# import PrestaShop config, to enable use of PrestaShop classes, like Configuration
$configPath = dirname(dirname(dirname(dirname($_SERVER['SCRIPT_FILENAME'])))).'/config/config.inc.php';
if (file_exists($configPath)) {
    require_once($configPath);
} else {
    exit;
}

require_once('../messages.php');
require_once('./helpers.php');
require_once('./models/product_export.php');
require_once('./models/category.php');
require_once('./models/product.php');

class FmAjaxService {

    # return a success response
    /**
     * Structure the response back to the client
     *
     * @param string $data
     */
    public static function response($data = '') {
        $response = array(
            'fm-service-status'=> 'success',
            'data'=> $data
        );
        $json = json_encode($response);
        if (json_last_error() != JSON_ERROR_NONE) {
            self::response_error(
                FmMessages::get('unhandled-error-title'),
                FmMessages::get('unhandled-error-message')
            );
        } else {
            echo $json;
        }
    }

    # return an error response
    /**
     * create a error to be send back to client.
     *
     * @param $title
     * @param $message
     */
    public static function response_error($title, $message) {
        $response = array(
            'fm-service-status'=> 'error',
            'title'=> $title,
            'message'=> $message,
        );
        $json = json_encode($response);
        echo $json;
    }

    # handle incoming ajax request
    /**
     *
     */
    public static function handle_request() {
        $action = false;
        $args = array();
        if (array_key_exists('action', $_POST)) {
            $action = $_POST['action'];
        }
        if (array_key_exists('args', $_POST)) {
            $args = $_POST['args'];
        }

        # call static function on self with name of the value provided in $action
        if (method_exists('FmAjaxService', $action)) {
            self::$action($args);
        }
    }

    ### views ###

    /**
     * Get the categories.
     *
     * @param $args
     */
    public static function get_categories($args) {
        $categories = FmCategory::get_all();
        self::response($categories);
    }

    /**
     * Get the products.
     *
     * @param $args
     */
    public static function get_products($args) {
        $products = array();

        $rows = FmProduct::get_by_category($args['category']);

        foreach ($rows as $row) {
            $products[] = FmProduct::get($row['id_product']);
        }

        self::response($products);
    }

    /**
     * Getting the orders to be saved in Prestashop.
     *
     * @param $args
     */
    public static function import_orders($args) {
        try {
            $ret = FmHelpers::call_api('GET', 'order/');
            self::response($ret);
        } catch (Exception $e) {
            self::response_error(
                FmMessages::get('unhandled-error-title'),
                FmMessages::get('unhandled-error-message').' ('.$e->getMessage().')'
            );
        }
    }

    /**
     * Exporting the products from Prestashop
     *
     * @param $args
     */
    public static function export_products($args) {
        $error = false;

        foreach ($args['products'] as $v) {
            $product = $v['product'];

            $result = array(
                'title'=> $product['name'],
                'description'=> 'asdf8u4389j34g98j34g98',
                'images'=> array($product['image']),
                'oldprice'=> '9999',
                'price'=> $product['price'],
                'moms_percent'=> '25',
                'articles'=> array()
            );

            // when posting empty array, it's removed completely from the request, so check for key
            if (array_key_exists('combinations', $v)) {
                $combinations = $v['combinations'];

                foreach ($combinations as $combination) {
                    $result['articles'][] = array(
                        'num_in_stock'=> '7',
                        'merchant_item_no'=> '2',
                        'description'=> 'asdfjeroijergo'
                    );
                }
            } else {
                $result['articles'][] = array(
                    'num_in_stock'=> '99',
                    'merchant_item_no'=> '99',
                    'description'=> 'qwer99qwer98referf'
                );
            }

            try {
                $result = FmHelpers::call_api('POST', 'products/', $result);
                if ($result['status'] != 201) {
                    $error = true;
                    self::response_error(
                        FmMessages::get('unhandled-error-title'),
                        FmMessages::get('unhandled-error-message')
                    );
                }
                FmProductExport::create($product['id'], $result['data']->id);
            } catch (FyndiqAPIBadRequest $e) {
                $error = true;
                $message = '';
                foreach (FyndiqAPI::$error_messages as $error_message) {
                    $message .= $error_message;
                }
                self::response_error(
                    FmMessages::get('products-bad-params-title'),
                    $message
                );
            } catch (Exception $e) {
                $error = true;
                self::response_error(
                    FmMessages::get('unhandled-error-title'),
                    $e->getMessage()
                );
            }

            if ($error) {
                break;
            }
        }

        if (!$error) {
            self::response();
        }
    }
}

FmAjaxService::handle_request();
