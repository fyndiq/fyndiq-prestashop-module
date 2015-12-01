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
        // Init Translations
        $languageId = $this->fmPrestashop->getLanguageId();
        FyndiqTranslation::init($this->fmPrestashop->languageGetIsoById($languageId));
    }

    public function handleRequest($params)
    {
        if (!isset($params['action'])) {
            return $this->fmOutput->responseError('Bad Request', '400 Bad Request');
        }
        $action = $params['action'];
        $args = isset($params['args']) && is_array($params['args']) ? $params['args'] : array();
        $response = $this->routeRequest($action, $args);
        if (!is_null($response)) {
            return $this->fmOutput->renderJSON($response);
        }
        return true;
    }

    public function routeRequest($action, $args)
    {
        try {
            $storeId = $this->fmPrestashop->getStoreId();
            switch ($action) {
                case 'get_categories':
                    return $this->getCategories($args, $storeId);
                case 'get_products':
                    return $this->getProducts($args, $storeId);
                case 'export_products':
                    return $this->exportProducts($args, $storeId);
                case 'delete_exported_products':
                    return $this->deleteExportedProducts($args, $storeId);
                case 'update_order_status':
                    return $this->updateOrderStatus($args, $storeId);
                case 'load_orders':
                    return $this->loadOrders($args, $storeId);
                case 'get_delivery_notes':
                    return $this->getDeliveryNotes($args, $storeId);
                case 'import_orders':
                    return $this->importOrders($args, $storeId);
                case 'update_product_status':
                    return $this->updateProductStatus($args, $storeId);
                case 'probe_file_permissions':
                    return $this->probeFilePermissions($args, $storeId);
                case 'probe_database';
                    return $this->probeDatabase($args, $storeId);
                case 'probe_module_integrity';
                    return $this->probeModuleIntegrity($args, $storeId);
                case 'probe_connection';
                    return $this->probeConnection($args, $storeId);
                case 'probe_products';
                    return $this->probeProducts($args, $storeId);
                default:
                    return $this->fmOutput->responseError(
                        'Not Found',
                        'Acion ' . $action . ' could not be found'
                    );
            }
        } catch (Exception $e) {
            $this->fmOutput->responseError(
                FyndiqTranslation::get('unhandled-error-title'),
                FyndiqTranslation::get('unhandled-error-message') . ' (' . $e->getMessage() . ')'
            );
            return null;
        }
    }

    protected function loadModel($modelName)
    {
        if (class_exists($modelName)) {
            return new $modelName($this->fmPrestashop, $this->fmConfig);
        }
        throw new Exception('Model ' . $modelName . ' is not defined');
    }

    /**
     * Get the categories.
     *
     * @param $args
     */
    private function getCategories($args, $storeId)
    {
        $languageId = $this->fmConfig->get('language', $storeId);
        $fmCategory = $this->loadModel('FmCategory');
        return $fmCategory->getSubcategories($languageId, intval($args['category_id']));
    }

    /**
     * Get the products.
     *
     * @param $args
     */
    private function getProducts($args, $storeId)
    {
        $products = array();
        $fmProduct = $this->loadModel('FmProduct');
        $fmProductExport = $this->loadModel('FmProductExport');
        // get currency
        $currentCurrency = $this->fmPrestashop->getDefaultCurrency();

        $page = (isset($args['page']) and $args['page'] > 0) ? intval($args['page']) : 1;
        $rows = $fmProduct->getByCategory($args['category'], $page, FyndiqUtils::PAGINATION_ITEMS_PER_PAGE);

        $fyndiqDiscountPercentage = $this->fmConfig->get('price_percentage', $storeId);
        $languageId = $this->fmConfig->get('language', $storeId);
        $descriptionType = intval($this->fmConfig->get('description_type', $storeId));
        $skuTypeId = intval($this->fmConfig->get('sku_type_id', $storeId));
        foreach ($rows as $row) {
            $discountPercentage = $fyndiqDiscountPercentage;
            $product = $fmProductExport->getStoreProduct($languageId, $row['id_product'], $descriptionType, $skuTypeId);
            // Don't show deactivated products
            if (empty($product)) {
                continue;
            }

            if (FyndiqFeedWriter::isColumnTooLong('product-title', $product['name'])) {
                $product['name_short'] = FyndiqFeedWriter::sanitizeColumn('product-title', $product['name']);
            }

            if ($product['images']) {
                $product['image'] = array_shift($product['images']);
            }
            $product['currency'] = $currentCurrency;
            $product['fyndiq_quantity'] = $product['quantity'];
            $product['fyndiq_status'] = 'noton';

            $fynProduct = $fmProductExport->getProduct($row['id_product'], $storeId);
            if ($fynProduct) {
                $discountPercentage = $fynProduct['exported_price_percentage'];
                $product['fyndiq_exported'] = true;
                switch ($fynProduct['state']) {
                    case FmProductExport::FOR_SALE: $product['fyndiq_status'] = 'on';
                        break;
                    default: $product['fyndiq_status'] = 'pending';
                }
            }
            $product['fyndiq_percentage'] = $discountPercentage;
            $product['expected_price'] = $this->fmPrestashop->toolsPsRound(
                (float)FyndiqUtils::getFyndiqPrice($product['price'], $discountPercentage),
                $this->fmPrestashop->globPricePrecision()
            );
            $product['price'] = $this->fmPrestashop->toolsPsRound(
                $product['price'],
                $this->fmPrestashop->globPricePrecision()
            );
            $products[] = $product;
        }
        $page = isset($args['page']) ? intval($args['page']) : 1;
        $total = $fmProduct->getAmount($args['category']);

        return array(
            'products' => $products,
            'pagination' => FyndiqUtils::getPaginationHTML(
                $total,
                $page,
                FyndiqUtils::PAGINATION_ITEMS_PER_PAGE,
                FyndiqUtils::PAGINATION_PAGE_FRAME
            )
        );
    }

    protected function prepareOrders($orders, $storeId)
    {
        $orderDoneState = $this->fmConfig->get('done_state', $storeId);

        $result = array();
        foreach ($orders as $order) {
            $orderArray = $order;
            $newOrder = $this->fmPrestashop->newOrder((int)$order['order_id']);
            $products = $newOrder->getProducts();
            $currentStateName = $this->fmPrestashop->getOrderStateName($newOrder->getCurrentState());
            $quantity = 0;
            foreach ($products as $product) {
                $quantity += $product['product_quantity'];
            }
            $tabcontroller = $this->fmPrestashop->isPs1516() ? 'controller' : 'tab';
            $urlarray = array(
                $tabcontroller => 'AdminOrders',
                'id_order' => $order['order_id'],
                'vieworder' => 1,
                'token' => $this->fmPrestashop->getAdminTokenLite('AdminOrders')
            );
            $url = 'index.php?' . http_build_query($urlarray);

            $orderArray['created_at'] = date('Y-m-d', strtotime($newOrder->date_add));
            $orderArray['created_at_time'] = date('G:i:s', strtotime($newOrder->date_add));
            $orderArray['price'] = $this->fmPrestashop->toolsPsRound(
                $newOrder->total_paid_tax_incl,
                $this->fmPrestashop->globPricePrecision()
            );
            $orderArray['state'] = $currentStateName;
            $orderArray['total_products'] = $quantity;
            $orderArray['is_done'] = $newOrder->getCurrentState() == $orderDoneState;
            $orderArray['link'] = $url;
            $result[] = $orderArray;
        }
        return $result;
    }

    private function loadOrders($args, $storeId)
    {
        $fmOrder = $this->loadModel('FmOrder');
        $total = $fmOrder->getTotal();
        $page = (isset($args['page']) && $args['page'] > 0) ? $args['page']: 1;
        return array(
            'orders' => $this->prepareOrders(
                $fmOrder->getImportedOrders($page, FyndiqUtils::PAGINATION_ITEMS_PER_PAGE),
                $this->fmPrestashop->getStoreId()
            ),
            'pagination' => FyndiqUtils::getPaginationHTML(
                $total,
                $page,
                FyndiqUtils::PAGINATION_ITEMS_PER_PAGE,
                FyndiqUtils::PAGINATION_PAGE_FRAME
            )
        );
    }

    private function updateOrderStatus($args, $storeId)
    {
        $doneStateName = '';
        if (isset($args['orders']) && is_array($args['orders'])) {
            $doneState = '';
            $doneState = $this->fmConfig->get('done_state', $storeId);
            $fmOrder = $this->loadModel('FmOrder');
            foreach ($args['orders'] as $order) {
                if (is_numeric($order)) {
                    $fmOrder->markOrderAsDone($order, $doneState);
                }
            }
            $doneStateName = $this->fmPrestashop->getOrderStateName($doneState);
        }
        return $doneStateName;
    }

    protected function getTime()
    {
        return time();
    }

    /**
     * Getting the orders to be saved in PrestaShop.
     *
     * @param $args
     * @throws PrestaShopException
     */
    private function importOrders($args, $storeId)
    {
        $importOrdersStatus = $this->fmConfig->get('disable_orders', $storeId);
        if ($importOrdersStatus == FmUtils::ORDERS_DISABLED) {
            return false;
        }
        $fmOrder = $this->loadModel('FmOrder');
        // Clear any remaining reservations
        $fmOrder->clearReservations();
        $orderFetch = new FmOrderFetch(
            $this->fmPrestashop,
            $this->fmConfig,
            $fmOrder,
            $this->fmApiModel
        );
        $orderFetch->getAll();
        $time = $this->getTime();
        $newDate = date('Y-m-d H:i:s', $time);
        $this->fmConfig->set('import_date', $newDate, $storeId);
        return date('G:i:s', $time);
    }

    /**
     * Exporting the products from PrestaShop
     *
     * @param mixed $args
     */
    private function exportProducts($args)
    {
        $result = true;
        if (isset($args['products']) && is_array($args['products'])) {
            $storeId = $this->fmPrestashop->getStoreId();
            $fmProductExport = $this->loadModel('FmProductExport');
            foreach ($args['products'] as $row) {
                $product= $row['product'];
                if ($fmProductExport->productExist($product['id'], $storeId)) {
                    $result &= $fmProductExport->updateProduct($product['id'], $product['fyndiq_percentage'], $storeId);
                    continue;
                }
                $result &= $fmProductExport->addProduct($product['id'], $product['fyndiq_percentage'], $storeId);
            }
        }
        return (bool)$result;
    }

    private function deleteExportedProducts($args)
    {
        $result = true;
        if (isset($args['products']) && is_array($args['products'])) {
            $storeId = $this->fmPrestashop->getStoreId();
            $fmProductExport = $this->loadModel('FmProductExport');
            foreach ($args['products'] as $row) {
                $product = $row['product'];
                $fmProductExport->deleteProduct($product['id'], $storeId);
            }
        }
        return $result;
    }

    private function getDeliveryNotes($args)
    {
        if (isset($args['orders']) && is_array($args['orders'])) {
            $orderIds = $args['orders'];
            $request = array(
                'orders' => array()
            );
            foreach ($orderIds as $orderId) {
                $request['orders'][] = array('order' => intval($orderId));
            }
            try {
                $ret = $this->fmApiModel->callApi('POST', 'delivery_notes/', $request);
                $fileName = 'delivery_notes-' . implode('-', $orderIds) . '.pdf';

                if ($ret['status'] == 200) {
                    $file = fopen('php://temp', 'wb+');
                    // Saving data to file
                    fputs($file, $ret['data']);
                    $this->fmOutput->streamFile($file, $fileName, 'application/pdf', strlen($ret['data']));
                    fclose($file);
                    return null;
                }
                return FyndiqTranslation::get('unhandled-error-message');
            } catch (Exception $e) {
                $this->fmOutput->output($e->getMessage());
                return null;
            }
        }
        $this->fmOutput->output('Please, pick at least one order');
        return null;
    }

    private function updateProductStatus()
    {
        $tableName = $this->fmPrestashop->getTableName(FmUtils::MODULE_NAME, '_products');
        $fmProduct = $this->loadModel('FmProduct');
        $productInfo = new FmProductInfo($fmProduct, $this->fmApiModel, $tableName);
        return $productInfo->getAll();
    }

    private function probeFilePermissions($args)
    {
        $messages = array();
        $testMessage = time();
        try {
            $fileName = $this->fmPrestashop->getExportPath() . $this->fmPrestashop->getExportFileName();
            $exists =  file_exists($fileName) ?
                FyndiqTranslation::get('exists') :
                FyndiqTranslation::get('does not exist');
            $messages[] = sprintf(FyndiqTranslation::get('Feed file name: `%s` (%s)'), $fileName, $exists);
            $tempFileName = FyndiqUtils::getTempFilename(dirname($fileName));
            if (dirname($tempFileName) !== dirname($fileName)) {
                throw new Exception(sprintf(
                    FyndiqTranslation::get('Cannot create file. Please make sure that the server can create new files in `%s`'),
                    dirname($fileName)
                ));
            }
            $messages[] = sprintf(FyndiqTranslation::get('Trying to create temporary file: `%s`'), $tempFileName);
            $file = fopen($tempFileName, 'w+');
            if (!$file) {
                throw new Exception(sprintf(FyndiqTranslation::get('Cannot create file: `%s`'), $tempFileName));
            }
            fwrite($file, $testMessage);
            fclose($file);
            $content = file_get_contents($tempFileName);
            if ($testMessage == $content) {
                $messages[] = sprintf(FyndiqTranslation::get('File `%s` successfully read.'), $tempFileName);
            }
            FyndiqUtils::deleteFile($tempFileName);
            $messages[] = sprintf(FyndiqTranslation::get('Successfully deleted temp file `%s`'), $tempFileName);
            return implode('<br />', $messages);
        } catch (Exception $e) {
            $messages[] = $e->getMessage();
            $this->fmOutput->responseError('', implode('<br />', $messages));
            return null;
        }
    }

    private function probeDatabase($args)
    {
        $messages = array();
        try {
            $tables = array(
                $this->fmPrestashop->getTableName(FmUtils::MODULE_NAME, '_products', true),
                $this->fmPrestashop->getTableName(FmUtils::MODULE_NAME, '_orders', true),
            );
            $missing = array();

            $fmModel = $this->loadModel('FmModel');
            $allTables = $fmModel->getAllTables();
            foreach ($tables as $tableName) {
                $exists = in_array($tableName, $allTables);
                if (!$exists) {
                    $missing[] = $tableName;
                    continue;
                }
                $messages[] = sprintf(FyndiqTranslation::get('Table `%s` is present.'), $tableName);
            }

            if ($missing) {
                throw new Exception(sprintf(
                    FyndiqTranslation::get('Required tables `%s` are missing.'),
                    implode(', ', $missing)
                ));
            }
            return implode('<br />', $messages);
        } catch (Exception $e) {
            $messages[] = $e->getMessage();
            $this->fmOutput->responseError('', implode('<br />', $messages));
            return null;
        }
    }

    private function probeModuleIntegrity($args)
    {
        $messages = array();
        $missing = array();
        $checkClasses = array(
            'FyndiqAPI',
            'FyndiqAPICall',
            'FyndiqCSVFeedWriter',
            'FyndiqFeedWriter',
            'FyndiqOutput',
            'FyndiqPaginatedFetch',
            'FyndiqTranslation',
            'FyndiqUtils',
        );
        try {
            foreach ($checkClasses as $className) {
                if (class_exists($className)) {
                    $messages[] = sprintf(FyndiqTranslation::get('Class `%s` is found.'), $className);
                    continue;
                }
                $messages[] = sprintf(FyndiqTranslation::get('Class `%s` is NOT found.'), $className);
            }
            if ($missing) {
                throw new Exception(sprintf(
                    FyndiqTranslation::get('Required classes `%s` are missing.', implode(',', $missing))
                ));
            }
            return implode('<br />', $messages);
        } catch (Exception $e) {
            $messages[] = $e->getMessage();
            $this->fmOutput->responseError('', implode('<br />', $messages));
            return null;
        }
    }

    private function probeConnection($args)
    {
        $messages = array();
        try {
            try {
                $this->fmApiModel->callApi('GET', 'settings/');
            } catch (Exception $e) {
                if ($e instanceof FyndiqAPIAuthorizationFailed) {
                    throw new Exception(FyndiqTranslation::get('Module is not authorized.'));
                }
            }
            $messages[] = FyndiqTranslation::get('Connection to Fyndiq successfully tested');
            return implode('<br />', $messages);
        } catch (Exception $e) {
            $messages[] = $e->getMessage();
            $this->fmOutput->responseError('', implode('<br />', $messages));
            return null;
        }
    }

    protected function getProductLink($productId) {
        $tabcontroller = $this->fmPrestashop->isPs1516() ? 'controller' : 'tab';
        $urlarray = array(
            $tabcontroller => 'AdminProducts',
            'id_product' => $productId,
            'updateproduct' => 1,
            'token' => $this->fmPrestashop->getAdminTokenLite('AdminProducts')
        );
        return 'index.php?' . http_build_query($urlarray);
    }

    private function probeProducts($args)
    {
        $messages = array();
        try {
            $fmProduct = $this->loadModel('FmProduct');
            $skuTypeId = $this->fmPrestashop->toolsGetValue('sku_type_id');
            $skuTypeId = $skuTypeId ? intval($skuTypeId) : FmUtils::SKU_DEFAULT;
            $duplicates = $fmProduct->checkProducts($skuTypeId);
            foreach($duplicates as $duplicate) {
                if ($duplicate['parent']) {
                    $messages[] = sprintf(
                        FyndiqTranslation::get('Combination %d in product <a href="%s">%d</a> with SKU `%s`'),
                        $duplicate['id_product'],
                        $this->getProductLink($duplicate['parent']),
                        $duplicate['parent'],
                        $duplicate['ref']
                    );
                    continue;
                }
                $messages[] = sprintf(
                    FyndiqTranslation::get('Product <a href="%s">%d</a> with SKU `%s`'),
                    $this->getProductLink($duplicate['id_product']),
                    $duplicate['id_product'],
                    $duplicate['ref']
                );
            }
            if ($messages) {
                return implode('<br />', $messages);
            }
            return FyndiqTranslation::get('No issues detected.');
        } catch (Exception $e) {
            $messages[] = $e->getMessage();
            $this->fmOutput->responseError('', implode('<br />', $messages));
            return null;
        }
    }
}
