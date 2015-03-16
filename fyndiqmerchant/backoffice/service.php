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

    const itemPerPage = 10;
    const pageFrame = 4;

    /**
     * Structure the response back to the client
     *
     * @param string $data
     */
    public function response($data = '')
    {
        $response = array(
            'fm-service-status' => 'success',
            'data' => $data
        );
        $json = json_encode($response);
        if (json_last_error() != JSON_ERROR_NONE) {
            $this->response_error(
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
    public function response_error($title, $message)
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
    public function handle_request()
    {
        $action = false;
        $args = array();
        if (array_key_exists('action', $_POST)) {
            $action = $_POST['action'];
        }
        if (array_key_exists('args', $_POST)) {
            $args = $_POST['args'];
        }

        # call function on self with name of the value provided in $action
        if (method_exists('FmAjaxService', $action)) {
            $this->$action($args);
        }
    }

    ### views ###

    /**
     * Get the categories.
     *
     * @param $args
     */
    public function get_categories($args)
    {
        $categories = FmCategory::get_subcategories(intval($args['category_id']));
        $this->response($categories);
    }

    /**
     * Get the products.
     *
     * @param $args
     */
    public function get_products($args)
    {
        $products = array();

        // get currency
        $current_currency = Currency::getDefaultCurrency()->iso_code;

        if (isset($args['page']) AND $args['page'] > 0) {
            $rows = FmProduct::get_by_category($args['category'], $args['page'], self::itemPerPage);
        } else {
            $rows = FmProduct::get_by_category($args['category'], 1, self::itemPerPage);
        }


        foreach ($rows as $row) {
            $product = FmProduct::get($row['id_product']);
            // Don't show deactivated products
            if (empty($product)) {
                continue;
            }
            # if there is a configured precentage, set that value
            if (FmProductExport::productExist($row['id_product'])) {
                $productexport = FmProductExport::getProduct($row['id_product']);
                $typed_percentage = $productexport['exported_price_percentage'];
            } else {
                # else set the default value of 10%.
                $typed_percentage = FmConfig::get('price_percentage');
            }

            $product['fyndiq_precentage'] = $typed_percentage;
            $product['fyndiq_quantity'] = $product['quantity'];
            $product['fyndiq_exported'] = FmProductExport::productExist($row['id_product']);
            $product['expected_price'] = number_format(
                (float)($product['price'] - (($typed_percentage / 100) * $product['price'])),
                2,
                '.',
                ''
            );
            $product['currency'] = $current_currency;
            $products[] = $product;
        }
        $object = new stdClass();
        $object->products = $products;
        if (!isset($args['page'])) {
            $object->pagination = $this->getPagerProductsHtml($args['category'], 1);
        } else {
            $object->pagination = $this->getPagerProductsHtml($args['category'], $args['page']);
        }
        $this->response($object);
    }


    public function load_orders($args)
    {
        if (isset($args['page']) AND $args['page'] > 0) {
            $orders = FmOrder::getImportedOrders($args['page'], self::itemPerPage);
        } else {
            $orders = FmOrder::getImportedOrders(1, self::itemPerPage);
        }

        $object = new stdClass();
        $object->orders = $orders;
        if (!isset($args['page'])) {
            $object->pagination = $this->getPagerordersHtml(1);
        } else {
            $object->pagination = $this->getPagerordersHtml($args['page']);
        }
        $this->response($object);
    }

    /**
     * Getting the orders to be saved in Prestashop.
     *
     * @param $args
     * @throws PrestaShopException
     */
    public function import_orders($args)
    {
        $url = "orders/";
        $date = FmConfig::get('import_date');
        if (!empty($date)) {
            $url .= '?min_date=' . urlencode($date);
        }
        try {
            $ret = FmHelpers::call_api('GET', $url);
            foreach ($ret['data'] as $order) {
                if (!FmOrder::orderExists($order->id)) {
                    FmOrder::create($order);
                }
            }
            $newdate = date('Y-m-d H:i:s');
            FmConfig::set('import_date', $newdate);
            $time = date('G:i:s', strtotime($newdate));
            $this->response($time);
        } catch (Exception $e) {
            $this->response_error(
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
    public function export_products($args)
    {
        $error = false;

        // Getting all data
        foreach ($args['products'] as $v) {
            $product = $v['product'];

            if (FmProductExport::productExist($product['id'])) {
                FmProductExport::updateProduct($product['id'], $product['fyndiq_percentage']);
            } else {
                FmProductExport::addProduct($product['id'], $product['fyndiq_percentage']);
            }
        }
        $result = FmProductExport::saveFile(_PS_ROOT_DIR_);

        $this->response($result);
    }

    public function delete_exported_products($args)
    {
        foreach ($args['products'] as $v) {
            $product = $v['product'];
            FmProductExport::deleteProduct($product['id']);
        }
        $result = FmProductExport::saveFile(_PS_ROOT_DIR_);

        $this->response($result);
    }

    public function update_product($args)
    {
        $result = false;
        if (FmProductExport::productExist($args['product'])) {
            $result = FmProductExport::updateProduct($args['product'], $args['percentage']);
        }
        $this->response($result);
    }

    public function get_delivery_notes($args)
    {
        try {
            $orders = new stdClass();
            $orders->orders = array();
            if (!isset($args['orders'])) {
                throw new Exception('Pick at least one order');
            }
            foreach ($args['orders'] as $order) {
                $object = new stdClass();
                $object->order = intval($order);
                $orders->orders[] = $object;
            }

            $ret = FmHelpers::call_api('POST', 'delivery_notes/', $orders, true);
            $fileName = 'delivery_notes-' . implode('-', $args['orders']) . '.pdf';

            if ($ret['status'] == 200) {
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . $fileName . '"');
                header('Content-Transfer-Encoding: binary');
                header('Content-Length: ' . strlen($ret['data']));
                header('Expires: 0');
                $fp = fopen('php://temp', 'wb+');
                // Saving data to file
                fputs($fp, $ret['data']);
                rewind($fp);
                fpassthru($fp);
                fclose($fp);
                die();
            }
            $this->response(true);
        } catch (Exception $e) {
            $this->response_error(
                FmMessages::get('unhandled-error-title'),
                FmMessages::get('unhandled-error-message') . ' (' . $e->getMessage() . ')'
            );
        }
    }

    /**
     * Get pagination
     *
     * @param $category
     * @param $currentpage
     * @return bool|string
     */
    private function getPagerProductsHtml($category, $currentpage)
    {
        $html = false;
        $amount = FmProduct::getAmount($category);
        if ($amount > self::itemPerPage) {
            $curPage = $currentpage;
            $pager = (int)($amount / self::itemPerPage);
            $count = ($amount % self::itemPerPage == 0) ? $pager : $pager + 1;
            $start = 1;
            $end = self::pageFrame;


            $html .= '<ol class="pageslist">';
            if (isset($curPage) && $curPage != 1) {
                $start = $curPage - 1;
                $end = $start + self::pageFrame;
            } else {
                $end = $start + self::pageFrame;
            }
            if ($end > $count) {
                $start = $count - (self::pageFrame - 1);
            } else {
                $count = $end - 1;
            }

            if ($curPage > $count - 1) {
                $html .= '<li><a href="#" data-page="' . ($curPage - 1) . '">&lt;</a></li>';
            }

            for ($i = $start; $i <= $count; $i++) {
                if ($i >= 1) {
                    if ($curPage) {
                        $html .= ($curPage == $i) ? '<li class="current">' . $i . '</li>' : '<li><a href="#" data-page="' . $i . '">' . $i . '</a></li>';
                    } else {
                        $html .= ($i == 1) ? '<li class="current">' . $i . '</li>' : '<li><a href="#" data-page="' . $i . '">' . $i . '</a></li>';
                    }
                }

            }

            if ($curPage < $count) {
                $html .= '<li><a href="#" data-page="' . ($curPage + 1) . '">&gt;</a></li>';
            }

            $html .= '</ol>';
        }

        return $html;
    }


    /**
     * Get pagination for orders
     *
     * @param $currentpage
     * @return bool|string
     */
    private function getPagerOrdersHtml($currentpage)
    {
        $html = false;
        $amount = FmOrder::getAmount();
        if ($amount > self::itemPerPage) {
            $curPage = $currentpage;
            $pager = (int)($amount / self::itemPerPage);
            $count = ($amount % self::itemPerPage == 0) ? $pager : $pager + 1;
            $start = 1;
            $end = self::pageFrame;


            $html .= '<ol class="pageslist">';
            if (isset($curPage) && $curPage != 1) {
                $start = $curPage - 1;
                $end = $start + self::pageFrame;
            } else {
                $end = $start + self::pageFrame;
            }
            if ($end > $count) {
                $start = $count - (self::pageFrame - 1);
            } else {
                $count = $end - 1;
            }

            if ($curPage > $count - 1) {
                $html .= '<li><a href="#" data-page="' . ($curPage - 1) . '">&lt;</a></li>';
            }

            for ($i = $start; $i <= $count; $i++) {
                if ($i >= 1) {
                    if ($curPage) {
                        $html .= ($curPage == $i) ? '<li class="current">' . $i . '</li>' : '<li><a href="#" data-page="' . $i . '">' . $i . '</a></li>';
                    } else {
                        $html .= ($i == 1) ? '<li class="current">' . $i . '</li>' : '<li><a href="#" data-page="' . $i . '">' . $i . '</a></li>';
                    }
                }

            }

            if ($curPage < $count) {
                $html .= '<li><a href="#" data-page="' . ($curPage + 1) . '">&gt;</a></li>';
            }

            $html .= '</ol>';
        }

        return $html;
    }
}

$ajaxService = new FmAjaxService();
$ajaxService->handle_request();
