<?php

# import PrestaShop config, to enable use of PrestaShop classes, like Configuration
$configPath = dirname(dirname(dirname(dirname($_SERVER['SCRIPT_FILENAME'])))) . '/config/config.inc.php';
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
require_once('./models/config.php');
require_once('./models/order.php');

class FmAjaxService
{

    /**
     * Structure the response back to the client
     *
     * @param string $data
     */
    public static function response($data = '')
    {
        $response = array(
            'fm-service-status' => 'success',
            'data' => $data
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
    public static function response_error($title, $message)
    {
        $response = array(
            'fm-service-status' => 'error',
            'title' => $title,
            'message' => $message,
        );
        $json = json_encode($response);
        echo $json;
    }

    # handle incoming ajax request
    /**
     *
     */
    public static function handle_request()
    {
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
    public static function get_categories($args)
    {
        $categories = FmCategory::get_all();
        self::response($categories);
    }

    /**
     * Get the products.
     *
     * @param $args
     */
    public static function get_products($args)
    {
        $products = array();

        $rows = FmProduct::get_by_category($args['category']);

        # if there is a configured precentage, set that value
        if (FmConfig::get('price_percentage')) {
            $typed_percentage = FmConfig::get('price_percentage');
        } else {
            # else set the default value of 10%.
            $typed_percentage = 10;
        }

        # if there is a configured quantity precentage, set that value
        if (FmConfig::get('quantity_percentage')) {
            $typed_quantity_percentage = FmConfig::get('quantity_percentage');
        } else {
            # else set the default value of 10%.
            $typed_quantity_percentage = 10;
        }

        foreach ($rows as $row) {
            $product = FmProduct::get($row['id_product']);
            $product["fyndiq_precentage"] = $typed_percentage;
            $product["fyndiq_quantity"] = $product["quantity"];
            $product["fyndiq_exported"] = FmProductExport::productExist($row['id_product']);
            $products[] = $product;
        }

        self::response($products);
    }

    /**
     * Getting the orders to be saved in Prestashop.
     *
     * @param $args
     * @throws PrestaShopException
     */
    public static function import_orders($args)
    {
        try {
            $ret = FmHelpers::call_api('GET', 'order/');

            foreach ($ret["data"]->objects as $order) {
                if(!FmOrder::orderExists($order->id)) {
                    FmOrder::create($order);
                }
            }
            self::response($ret);
        } catch (Exception $e) {
            self::response_error(
                FmMessages::get('unhandled-error-title'),
                FmMessages::get('unhandled-error-message') . ' (' . $e->getMessage() . ')'
            );
        }
    }

    /**
     * Exporting the products from Prestashop
     *
     * @param $args
     */
    public static function export_products($args)
    {
        $error = false;

        // Getting all data
        foreach ($args['products'] as $v) {
            $product = $v['product'];

            if(FmProductExport::productExist($product["id"])) {
                FmProductExport::updateProduct($product["id"], $product['quantity'], $product['fyndiq_precentage']);
            }
            else {
                FmProductExport::addProduct($product["id"],$product['quantity'], $product['fyndiq_precentage']);
            }
        }
        $result = FmProductExport::saveFile();

        if($result != false)
        {
            $result = true;
        }

        self::response($result);
    }
}

FmAjaxService::handle_request();
