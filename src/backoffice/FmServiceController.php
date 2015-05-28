<?php

class FmServiceController
{

    protected $fmPrestashop;
    protected $fmOutput;
    protected $fmConfig;

    public function __construct($fmPrestashop, $fmOutput, $fmConfig, $fmApiModel)
    {
        $this->fmPrestashop = $fmPrestashop;
        $this->fmOutput = $fmOutput;
        $this->fmConfig = $fmConfig;
        $this->fmApiModel = $fmApiModel;
    }

    public function handleRequest($params) {
        if (!isset($params['action'])) {
            return $this->fmOutput->showError(400, 'Bad Request', '400 Bad Request');
        }
        $action = $params['action'];
        $args = isset($params['args']) && is_array($params['args']) ? $params['args'] : array();
        try {
            switch($action) {
                case 'get_categories':
                    return $this->fmOutput->renderJSON($this->getCategories($args));
                case 'get_products':
                    return $this->fmOutput->renderJSON($this->getProducts($args));
                case 'export_products':
                    return $this->fmOutput->renderJSON($this->serviceExportProducts($args));
                case 'delete_exported_products':
                    return $this->fmOutput->renderJSON($this->serviceDeleteExportedProducts($args));
                case 'update_order_status':
                    return $this->fmOutput->renderJSON($this->serviceUpdateOrderStatus($args));
                case 'load_orders':
                    return $this->fmOutput->renderJSON($this->loadOrders($args));
                case 'get_delivery_notes':
                    return $this->fmOutput->renderJSON($this->serviceGetDeliveryNotes($args));
                case 'import_orders':
                    return $this->fmOutput->renderJSON($this->serviceImportOrders($args));
                case 'update_product_status':
                    return $this->fmOutput->renderJSON($this->serviceUpdateProductStatus($args));
                default:
                    return $this->fmOutput->showError(404, 'Not Found', '404 Not Found');
            }
        } catch (Exception $e) {
            return $this->fmOutput->responseError(
                FyndiqTranslation::get('unhandled-error-title'),
                FyndiqTranslation::get('unhandled-error-message') . ' (' . $e->getMessage() . ')'
            );
        }
    }

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

    ### views ###

    /**
     * Get the categories.
     *
     * @param $args
     */
    private function getCategories($args)
    {
        $languageId = $this->fmConfig->get('language');
        $fmCategory = new FmCategory($this->fmPrestashop, $this->fmConfig);
        return $fmCategory->getSubcategories($languageId, intval($args['category_id']));
    }

    /**
     * Get the products.
     *
     * @param $args
     */
    private function getProducts($args)
    {
        $products = array();
        $fmProduct = new FmProduct($this->fmPrestashop, $this->fmConfig);
        $fmProductExport = new FmProductExport($this->fmPrestashop, $this->fmConfig);
        // get currency
        $currentCurrency = $this->fmPrestashop->getDefaultCurrency();

        $page = (isset($args['page']) and $args['page'] > 0) ? intval($args['page']) : 1;
        $rows = $fmProduct->getByCategory($args['category'], $page, FyndiqUtils::PAGINATION_ITEMS_PER_PAGE);

        $fyndiqDiscountPercentage = $this->fmConfig->get('price_percentage');
        $languageId = $this->fmConfig->get('language');

        foreach ($rows as $row) {
            $discountPercentage = $fyndiqDiscountPercentage;
            $product = $fmProductExport->getStoreProduct($languageId, $row['id_product']);
            // Don't show deactivated products
            if (empty($product)) {
                continue;
            }

            $product['currency'] = $currentCurrency;
            $product['fyndiq_quantity'] = $product['quantity'];
            $product['fyndiq_status'] = 'noton';

            $fynProduct = $fmProductExport->getProduct($row['id_product']);
            if ($fynProduct) {
                $discountPercentage = $fynProduct['exported_price_percentage'];
                $product['fyndiq_exported'] = true;
                switch ($fynProduct['state']) {
                    case 'FOR_SALE': $product['fyndiq_status'] = 'on';
                        break;
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
        $page = isset($args['page']) ? intval($args['page']) : 1;
        $total = $fmProduct->getAmount($args['category']);

        $result = array(
            'products' => $products,
            'pagination' => FyndiqUtils::getPaginationHTML(
                $total,
                $page,
                FyndiqUtils::PAGINATION_ITEMS_PER_PAGE,
                FyndiqUtils::PAGINATION_PAGE_FRAME
            )
        );
        $this->response($result);
    }


    private function loadOrders($args)
    {
        $page = (isset($args['page']) and $args['page'] > 0) ? $args['page']: 1;
        $orders = FmOrder::getImportedOrders($page, FyndiqUtils::PAGINATION_ITEMS_PER_PAGE);

        $object = new stdClass();
        $object->orders = $orders;

        // Setup pagination
        $page = isset($args['page']) ? intval($args['page']) : 1;
        $total = FmOrder::getAmount();
        $object->pagination = FyndiqUtils::getPaginationHTML(
            $total,
            $page,
            FyndiqUtils::PAGINATION_ITEMS_PER_PAGE,
            FyndiqUtils::PAGINATION_PAGE_FRAME
        );
        $this->response($object);
    }

    private function update_order_status($args)
    {
        if (isset($args['orders']) && is_array($args['orders'])) {
            $doneState = '';
            foreach ($args['orders'] as $order) {
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
    private function import_orders()
    {
        try {
            $orderFetch = new FmOrderFetch();
            $orderFetch->getAll();
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
        if (isset($args['product']) && is_numeric($args['product'])
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

    private function update_product_status()
    {
        try {
            $module = $this->fmPrestashop->moduleGetInstanceByName(FmUtils::MODULE_NAME);
            $tableName = $module->config_name . '_products';
            $fmProduct = new FmProduct($this->fmPrestashop, $this->fmConfig);
            $productInfo = new FmProductInfo($fmProduct, $this->fmApiModel, $tableName);
            $result = $productInfo->getAll();
            $this->response($result);
        } catch (Exception $e) {
            $this->responseError(
                FyndiqTranslation::get('unhandled-error-title'),
                FyndiqTranslation::get('unhandled-error-message') . ' (' . $e->getMessage() . ')'
            );
        }
    }
}
