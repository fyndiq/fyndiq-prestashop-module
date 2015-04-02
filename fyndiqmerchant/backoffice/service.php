<?php

$storeRoot = dirname(dirname(dirname(dirname($_SERVER['SCRIPT_FILENAME']))));

$timer_start = microtime(true);

// NOTE: This root is wrong but config relies on these constants to be set to populate the proper context
if (!defined('_PS_ADMIN_DIR_')){
    define('_PS_ADMIN_DIR_', $storeRoot);
}
if (!defined('PS_ADMIN_DIR')) {
    define('PS_ADMIN_DIR', _PS_ADMIN_DIR_);
}

# import PrestaShop config, to enable use of PrestaShop classes, like Configuration
$configPath = $storeRoot . '/config/config.inc.php';

if (file_exists($configPath)) {
    require_once($configPath);
} else {
    exit;
}

// Set the correct shop context

$shop_id = '';
Shop::setContext(Shop::CONTEXT_ALL);
if ($context->cookie->shopContext)
{
    $split = explode('-', $context->cookie->shopContext);
    if (count($split) == 2)
    {
        if ($split[0] == 'g')
        {
            if ($context->employee->hasAuthOnShopGroup($split[1]))
                Shop::setContext(Shop::CONTEXT_GROUP, $split[1]);
            else
            {
                $shop_id = $context->employee->getDefaultShopID();
                Shop::setContext(Shop::CONTEXT_SHOP, $shop_id);
            }
        }
        elseif ($context->employee->hasAuthOnShop($split[1]))
        {
            $shop_id = $split[1];
            Shop::setContext(Shop::CONTEXT_SHOP, $shop_id);
        }
        else
        {
            $shop_id = $context->employee->getDefaultShopID();
            Shop::setContext(Shop::CONTEXT_SHOP, $shop_id);
        }
    }
}
// Replace existing shop if necessary
if (!$shop_id)
    $context->shop = new Shop(Configuration::get('PS_SHOP_DEFAULT'));
else if ($context->shop->id != $shop_id)
    $context->shop = new Shop($shop_id);


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
                $discountPercentage = $productexport['exported_price_percentage'];
            } else {
                # else set the default value of 10%.
                $discountPercentage = FmConfig::get('price_percentage');
            }

            $product['fyndiq_precentage'] = $discountPercentage;
            $product['fyndiq_quantity'] = $product['quantity'];
            $product['fyndiq_exported'] = FmProductExport::productExist($row['id_product']);
            $product['expected_price'] = number_format(
                (float)FyndiqUtils::getFyndiqPrice($product['price'], $discountPercentage),
                2,
                '.',
                ''
            );
            $product['currency'] = $current_currency;
            $products[] = $product;
        }
        $object = new stdClass();
        $object->products = $products;

        // Setup pagination
        $page = isset($args['page']) ? intval($args['page']) : 1;
        $total = FmProduct::getAmount($args['category']);
        $object->pagination = FyndiqUtils::getPaginationHTML($total, $page);
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

        // Setup pagination
        $page = isset($args['page']) ? intval($args['page']) : 1;
        $total = FmOrder::getAmount();
        $object->pagination = FyndiqUtils::getPaginationHTML($total, $page);
        $this->response($object);
    }

    public function update_order_status($args)
    {
        if(isset($args['orders']) && is_array($args['orders'])) {
            $donestate = "";
            foreach($args['orders'] as $order) {
                if (is_numeric($order)) {
                   $donestate = FmOrder::markOrderAsDone($order);
                }
            }
            $this->response($donestate);
        }
        else {
            $this->response(false);
        }
    }

    /**
     * Getting the orders to be saved in Prestashop.
     *
     * @param $args
     * @throws PrestaShopException
     */
    public function import_orders($args)
    {
        $url = 'orders/';
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
            $newDate = date('Y-m-d H:i:s');
            FmConfig::set('import_date', $newDate);
            $time = date('G:i:s', strtotime($newDate));
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
        $result = $this->saveFeed();
        $this->response($result);
    }

    /**
     * Save the feed to file
     *
     * @return bool
     */
    private function saveFeed() {
        $fileName = _PS_ROOT_DIR_ . '/files/' . FmHelpers::getExportFileName();
        $file = @fopen($fileName, 'w+');
        if ($file === false) {
            return false;
        }
        $result = FmProductExport::saveFile($file);
        fclose($file);
        return $result;
    }

    public function delete_exported_products($args)
    {
        foreach ($args['products'] as $v) {
            $product = $v['product'];
            FmProductExport::deleteProduct($product['id']);
        }
        $result = $this->saveFeed();
        $this->response($result);
    }

    public function update_product($args)
    {
        $result = false;
        if ( isset($args['product']) && is_numeric($args['product'])
            && isset($args['percentage']) && is_numeric($args['percentage'])) {
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
            echo FmMessages::get('unhandled-error-message');
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function update_product_status($args) {
        try {
            FmProduct::updateProductStatus();
            $this->response(true);
        } catch (Exception $e) {
            $this->response_error(
                FmMessages::get('unhandled-error-title'),
                FmMessages::get('unhandled-error-message') . ' (' . $e->getMessage() . ')'
            );
        }
    }
}

$ajaxService = new FmAjaxService();
$ajaxService->handle_request();
