<?php

require_once('./service_init.php');
require_once('./helpers.php');
require_once('./models/product_export.php');
require_once('./models/category.php');
require_once('./models/product.php');
require_once('./models/product_info.php');
require_once('./models/config.php');
require_once('./models/order.php');

class FmAjaxService
{

    /**
     * Structure the response back to the client
     *
     * @param string $data
     */
    private function response($data = '')
    {
        $response = array(
            'fm-service-status' => 'success',
            'data' => $data
        );
        $json = json_encode($response);
        if (json_last_error() != JSON_ERROR_NONE) {
            $this->responseError(
                FyndiqTranslation::get('unhandled-error-title'),
                FyndiqTranslation::get('unhandled-error-message')
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
    private function responseError($title, $message)
    {
        $response = array(
            'fm-service-status' => 'error',
            'title' => $title,
            'message' => $message,
        );
        $json = json_encode($response);
        echo $json;
    }

    /**
     * Handle incoming ajax request
     *
     * @param $params
     */
    public function handleRequest($params)
    {
        $action = isset($params['action']) ? $params['action'] : false;
        $args = isset($params['args']) ? $params['args'] : false;

        if ($action) {
            # call function on self with name of the value provided in $action
            if (method_exists($this, $action)) {
                $this->$action($args);
            }
        }
    }

    ### views ###

    /**
     * Get the categories.
     *
     * @param $args
     */
    private function get_categories($args)
    {
        $categories = FmCategory::getSubcategories(intval($args['category_id']));
        $this->response($categories);
    }

    /**
     * Get the products.
     *
     * @param $args
     */
    private function get_products($args)
    {
        $products = array();

        // get currency
        $currentCurrency = Currency::getDefaultCurrency()->iso_code;

        $page = (isset($args['page']) AND $args['page'] > 0) ? intval($args['page']) : 1;
        $rows = FmProduct::getByCategory($args['category'], $page, FyndiqUtils::PAGINATION_ITEMS_PER_PAGE);

        $discountPercentage = FmConfig::get('price_percentage');

        foreach ($rows as $row) {
            $product = FmProduct::get($row['id_product']);
            // Don't show deactivated products
            if (empty($product)) {
                continue;
            }

            $product['currency'] = $currentCurrency;
            $product['fyndiq_quantity'] = $product['quantity'];
            $product['fyndiq_status'] = 'noton';

            $fynProduct = FmProductExport::getProduct($row['id_product']);
            if ($fynProduct) {
                $discountPercentage = $fynProduct['exported_price_percentage'];
                $product['fyndiq_exported'] = true;
                switch ($fynProduct['state']) {
                    case 'FOR_SALE' : $product['fyndiq_status'] = 'on'; break;
                    default: $product['fyndiq_status'] = 'pending';
                }
            }

            $product['fyndiq_percentage'] = $discountPercentage;
            $product['expected_price'] = number_format(
                (float)FyndiqUtils::getFyndiqPrice($product['price'], $discountPercentage),
                2,
                '.',
                ''
            );
            $products[] = $product;
        }
        $object = new stdClass();
        $object->products = $products;

        // Setup pagination
        $page = isset($args['page']) ? intval($args['page']) : 1;
        $total = FmProduct::getAmount($args['category']);
        $object->pagination = FyndiqUtils::getPaginationHTML($total, $page, FyndiqUtils::PAGINATION_ITEMS_PER_PAGE,
            FyndiqUtils::PAGINATION_PAGE_FRAME);
        $this->response($object);
    }


    private function load_orders($args)
    {
        $page = (isset($args['page']) AND $args['page'] > 0) ? $args['page']: 1;
        $orders = FmOrder::getImportedOrders($page, FyndiqUtils::PAGINATION_ITEMS_PER_PAGE);

        $object = new stdClass();
        $object->orders = $orders;

        // Setup pagination
        $page = isset($args['page']) ? intval($args['page']) : 1;
        $total = FmOrder::getAmount();
        $object->pagination = FyndiqUtils::getPaginationHTML($total, $page, FyndiqUtils::PAGINATION_ITEMS_PER_PAGE,
            FyndiqUtils::PAGINATION_PAGE_FRAME);
        $this->response($object);
    }

    private function update_order_status($args)
    {
        if(isset($args['orders']) && is_array($args['orders'])) {
            $doneState = '';
            foreach($args['orders'] as $order) {
                if (is_numeric($order)) {
                    $doneState = FmOrder::markOrderAsDone($order);
                }
            }
            return $this->response($doneState);
        }
        $this->response(false);
    }

    /**
     * Getting the orders to be saved in PrestaShop.
     *
     * @param $args
     * @throws PrestaShopException
     */
    private function import_orders(/*$args*/)
    {
        $url = 'orders/';
        $date = FmConfig::get('import_date');
        if (!empty($date)) {
            $url .= '?min_date=' . urlencode($date);
        }
        try {
            $ret = FmHelpers::callApi('GET', $url);
            foreach ($ret['data'] as $order) {
                if (!FmOrder::orderExists($order->id)) {
                    FmOrder::create($order);
                }
            }
            $newDate = date('Y-m-d H:i:s');
            FmConfig::set('import_date', $newDate);
            $time = date('G:i:s', strtotime($newDate));
            $this->response($time);
        } catch (Exception $e) {
            $this->responseError(
                FyndiqTranslation::get('unhandled-error-title'),
                FyndiqTranslation::get('unhandled-error-message') . ' (' . $e->getMessage() . ')'
            );
        }
    }

    /**
     * Exporting the products from PrestaShop
     *
     * @param $args
     */
    private function export_products($args)
    {
        // Getting all data
        foreach ($args['products'] as $row) {
            $product = $row['product'];

            if (FmProductExport::productExist($product['id'])) {
                FmProductExport::updateProduct($product['id'], $product['fyndiq_percentage']);
            } else {
                FmProductExport::addProduct($product['id'], $product['fyndiq_percentage']);
            }
        }
        $this->response(true);
    }

    private function delete_exported_products($args)
    {
        foreach ($args['products'] as $row) {
            $product = $row['product'];
            FmProductExport::deleteProduct($product['id']);
        }
        $this->response(true);
    }

    private function update_product($args)
    {
        $result = false;
        if ( isset($args['product']) && is_numeric($args['product'])
            && isset($args['percentage']) && is_numeric($args['percentage'])) {
            $result = FmProductExport::updateProduct($args['product'], $args['percentage']);
        }
        $this->response($result);
    }

    private function get_delivery_notes($args)
    {
        if (isset($args['orders']) && is_array($args['orders'])) {
            echo FmHelpers::streamBackDeliveryNotes($args['orders']);
            return;
        }
        echo 'Please, pick at least one order';
    }

    private function update_product_status() {
        try {
            $pi = new FmProductInfo();
            $result = $pi->getAll();
            $this->response($result);
        } catch (Exception $e) {
            $this->responseError(
                FyndiqTranslation::get('unhandled-error-title'),
                FyndiqTranslation::get('unhandled-error-message') . ' (' . $e->getMessage() . ')'
            );
        }
    }
}

$cookie = new Cookie('psAdmin');
if ($cookie->id_employee) {
    $ajaxService = new FmAjaxService();
    $ajaxService->handleRequest($_POST);
    exit();
}
header('HTTP/1.0 401 Unauthorized');
