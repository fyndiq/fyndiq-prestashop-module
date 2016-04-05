<?php
/**
 * Class FmOrder
 *
 * handles orders
 */

class FmOrder extends FmModel
{
    const FYNDIQ_ORDERS_EMAIL = 'orders_no_reply@fyndiq.com';
    const FYNDIQ_ORDERS_NAME_FIRST = 'Fyndiq';
    const FYNDIQ_ORDERS_NAME_LAST = 'Orders';

    const FYNDIQ_ORDERS_DELIVERY_ADDRESS_ALIAS = 'Delivery';
    const FYNDIQ_ORDERS_INVOICE_ADDRESS_ALIAS = 'Invoice';

    const FYNDIQ_ORDERS_MODULE = 'fyndiqmerchant';
    const FYNDIQ_PAYMENT_METHOD = 'Fyndiq';

    const ID_CARRIER = 1;

    /**
     * install the table in the database
     *
     * @return bool
     */
    public function install()
    {
        $res = true;
        $tableName = $this->fmPrestashop->getTableName(FmUtils::MODULE_NAME, '_orders', true);

        $sql = 'CREATE TABLE IF NOT EXISTS ' . $tableName . ' (
            id int(20) unsigned primary key AUTO_INCREMENT,
            fyndiq_orderid INT(10),
            order_id INT(10) DEFAULT null,
            status INT(10) DEFAULT 0,
            body TEXT DEFAULT null,
            created timestamp DEFAULT CURRENT_TIMESTAMP);';
        $res &= $this->fmPrestashop->dbGetInstance()->Execute($sql, false);

        try {
            // Index creation will fail if this is reinstall
            $sql = 'CREATE INDEX orderIndexNew ON ' . $tableName . ' (fyndiq_orderid);';
            $this->fmPrestashop->dbGetInstance()->Execute($sql, false);
        } catch (Exception $e) {
        }

        return (bool)$res;
    }

    /**
     * getCustomer description
     * @return Customer return customer object
     */
    private function getCustomer()
    {
        $customer = $this->fmPrestashop->newCustomer();
        $customer->getByEmail(self::FYNDIQ_ORDERS_EMAIL);

        if (is_null($customer->firstname)) {
            // Create a customer.
            $customer->firstname = self::FYNDIQ_ORDERS_NAME_FIRST;
            $customer->lastname = self::FYNDIQ_ORDERS_NAME_LAST;
            $customer->email = self::FYNDIQ_ORDERS_EMAIL;
            $customer->passwd = md5(uniqid(rand(), true));

            // Add it to the database.
            $customer->add();
        }
        return $customer;
    }

    /**
     * getAddressId description
     * @param  int  $fyndiqOrder Fyndiq Order Id
     * @param  int  $customerId  customer Id
     * @param  int  $countryId   Country Id
     * @param  string $alias
     * @return int             Address ID
     */
    public function getAddressId($fyndiqOrder, $customerId, $countryId, $alias)
    {
        // Create address
        $address = $this->fmPrestashop->newAddress();
        $address->firstname = $fyndiqOrder->delivery_firstname;
        $address->lastname = $fyndiqOrder->delivery_lastname;
        $address->phone = $fyndiqOrder->delivery_phone;
        $address->phone_mobile = $fyndiqOrder->delivery_phone;
        $address->address1 = $fyndiqOrder->delivery_address;
        $address->postcode = $fyndiqOrder->delivery_postalcode;
        $address->city = $fyndiqOrder->delivery_city;
        $address->company = $fyndiqOrder->delivery_co;
        $address->id_country = $countryId;
        $address->id_customer = $customerId;
        $address->alias = $alias;
        $address->add();
        return $address->id;
    }

    /**
     * initContext initializing the cart
     * @param  object $fyndiqOrder Fyndiq order object
     * @return Context
     */
    private function initContext($fyndiqOrder)
    {
        $context = $this->fmPrestashop->contextGetContext();
        $customer = $this->getCustomer();
        $context->customer = $customer;

        $cartId = $customer->getLastCart(false);
        $context->cart = $this->fmPrestashop->newCart((int)$cartId);

        if (!$context->cart->id) {
            $context->cart->recyclable = 0;
            $context->cart->gift = 0;
        }
        if (!$context->cart->id_customer) {
            $context->cart->id_customer = $customer->id;
        }
        if ($this->fmPrestashop->isObjectLoaded($context->cart) && $context->cart->OrderExists()) {
            throw new Exception(sprintf(
                FyndiqTranslation::get('Error in initializing the cart or order exists `%d`'),
                $fyndiqOrder->id
            ));
        }
        if (!$context->cart->secure_key) {
            $context->cart->secure_key = $context->customer->secure_key;
        }
        if (!$context->cart->id_shop) {
            $context->cart->id_shop = (int)$context->shop->id;
        }
        if (!$context->cart->id_lang) {
            $context->cart->id_lang = (($id_lang = (int)$context->language->id) ? $id_lang : $this->fmPrestashop->configurationGet('PS_LANG_DEFAULT'));
        }
        if (!$context->cart->id_currency) {
            $context->cart->id_currency = (($id_currency = (int)$context->currency->id) ? $id_currency : $this->fmPrestashop->configurationGet('PS_CURRENCY_DEFAULT'));
        }

        $addresses = $customer->getAddresses((int)$context->cart->id_lang);
        if (!$context->cart->id_address_invoice && isset($addresses[0])) {
            $context->cart->id_address_invoice = (int)$addresses[0]['id_address'];
        } else {
            $invoiceAddressId = $this->getAddressId(
                $fyndiqOrder,
                $customer->id,
                $context->country->id,
                self::FYNDIQ_ORDERS_INVOICE_ADDRESS_ALIAS
            );
            $context->cart->id_address_invoice = (int)$invoiceAddressId;
        }
        if (!$context->cart->id_address_delivery && isset($addresses[0])) {
            $context->cart->id_address_delivery = $addresses[0]['id_address'];
        } else {
            $id_address_delivery = $this->getAddressId(
                $fyndiqOrder,
                $customer->id,
                $context->country->id,
                self::FYNDIQ_ORDERS_DELIVERY_ADDRESS_ALIAS
            );
            $context->cart->id_address_delivery = (int)$id_address_delivery;
        }
        $context->cart->setNoMultishipping();
        $context->cart->save();
        $currency = $this->fmPrestashop->getCurrency((int)$context->cart->id_currency);
        $context->currency = $currency;

        return $context;
    }

    /**
     * addProductToCart Add and update price product to the cart
     * @param Context $context   Context
     * @param int $productId   Product ID
     * @param int $attributeId combination Id/ Attribute Id
     * @param int $qty    quantity
     * @param int $sku    Sku Id
     * @return array response array
     */
    private function addProductToCart($context, $productId, $attributeId, $qty, $sku)
    {
        if (!$context->cart->id) {
            throw new Exception(sprintf(
                FyndiqTranslation::get('Cart not found')
            ));
        }
        if ($context->cart->OrderExists()) {
            throw new Exception(sprintf(
                FyndiqTranslation::get('An order has already been placed with this cart.')
            ));
        }
        if (!($id_product = (int)$productId) ||
            !($product = new Product((int)$id_product, true, $context->language->id))) {
            throw new Exception(sprintf(
                FyndiqTranslation::get('Invalid Product, Product Sku = `%s`'),
                $sku
            ));
        }
        if (empty($qty)) {
            throw new Exception(sprintf(
                FyndiqTranslation::get('Invalid Quantity, Product Sku = `%s`'),
                $sku
            ));
        }
        $id_customization = 0;
        if (($id_product_attribute = $attributeId) != 0) {
            if (!Product::isAvailableWhenOutOfStock($product->out_of_stock) && !Attribute::checkAttributeQty((int)$id_product_attribute, (int)$qty)) {
                throw new Exception(sprintf(
                    FyndiqTranslation::get('There is not enough product in stock, Product Sku = `%s`'),
                    $sku
                ));
            }
        }
        if (!$product->checkQty((int)$qty)) {
            throw new Exception(sprintf(
                FyndiqTranslation::get('There is not enough product in stock, Product Sku = `%s`'),
                $sku
            ));
        }
        $context->cart->save();

        if ((int)$qty < 0) {
            $qty = str_replace('-', '', $qty);
            $operator = 'down';
        } else {
            $operator = 'up';
        }
        if (!($qty_upd = $context->cart->updateQty($qty, $id_product, (int)$id_product_attribute, (int)$id_customization, $operator))) {
            throw new Exception(sprintf(
                FyndiqTranslation::get('You already have the maximum quantity available for this product, Product Sku = `%s`'),
                $sku
            ));
        }
        if ($qty_upd < 0) {
            $minimal_qty = $id_product_attribute ? Attribute::getAttributeMinimalQty((int)$id_product_attribute) : $product->minimal_quantity;
            throw new Exception(sprintf(
                FyndiqTranslation::get('You must add a minimum quantity of %d, Product Sku = `%s`'),
                $minimal_qty,
                $sku
            ));
        }
        return $context;
    }

    /**
     * updateCustomProductPrice Update the Fyndiq price (custom) to the product
     * @param  Context $context    Context
     * @param  int $productId   Product Id
     * @param  int $attributeId combination Id/ Attribute Id
     * @param  int $price       product price
     */
    public function updateCustomProductPrice($context, $productId, $attributeId, $price)
    {
        $specific_price = $this->fmPrestashop->newSpecificPrice($context->cart->id, $productId, $attributeId);
        $specific_price->id_cart = (int)$context->cart->id;
        $specific_price->id_shop = 0;
        $specific_price->id_shop_group = 0;
        $specific_price->id_currency = 0;
        $specific_price->id_country = 0;
        $specific_price->id_group = 0;
        $specific_price->id_customer = (int)$context->customer->id;
        $specific_price->id_product = (int)$productId;
        $specific_price->id_product_attribute = (int)$attributeId;
        $specific_price->price = (float)$price;
        $specific_price->from_quantity = 1;
        $specific_price->reduction = 0;
        $specific_price->reduction_type = 'amount';
        $specific_price->from = '0000-00-00 00:00:00';
        $specific_price->to = '0000-00-00 00:00:00';
        return $specific_price->add();
    }

    /**
     * processOrder Process Fyndiq order to prestashop order
     * @param  int $fyndiqOrder    Fyndiq Order ID
     * @param  int $importState    Order status
     * @param  string $taxAddressType Type of address
     * @param  int $skuTypeId      Sku type ID
     * @return boolean
     */
    private function processOrder($fyndiqOrder, $importState, $taxAddressType, $skuTypeId)
    {
        $fyndiqOrderRows = $fyndiqOrder->order_rows;
        foreach ($fyndiqOrderRows as $key => $row) {
            list($productId, $combinationId) = $this->getProductBySKU($row->sku, $skuTypeId);
            if (!$productId) {
                throw new FyndiqProductSKUNotFound(sprintf(
                    FyndiqTranslation::get('error-import-product-not-found'),
                    $row->sku,
                    $fyndiqOrder->id
                ));

                return false;
            }
            $row->productId = $productId;
            $row->combinationId = $combinationId;
            $fyndiqOrderRows[$key] = $row;
        }
        // initialize the cart
        $context = $this->initContext($fyndiqOrder);

        // add Product to a cart
        foreach ($fyndiqOrderRows as $key => $row) {
            $context = $this->addProductToCart($context, $row->productId, $row->combinationId, $row->quantity, $row->sku);
            $this->updateCustomProductPrice($context, $row->productId, $row->combinationId, $row->unit_price_amount);
        }
        return $this->createOrder($context->cart->id, $importState, $fyndiqOrder);
    }

    /**
     * createOrder last step to create an order
     * @param  int $id_cart             cart ID
     * @param  int $id_order_state      Order status
     * @param  Object $fyndiqOrder      Fyndiq order Object
     * @return boolean
     */
    private function createOrder($id_cart, $id_order_state, $fyndiqOrder)
    {
        if (!$id_cart && !$id_order_state) {
            return false;
        }
        $payment_module = new FmPaymentModule();
        $cart = new Cart((int)$id_cart);
        Context::getContext()->currency = new Currency((int)$cart->id_currency);
        Context::getContext()->customer = new Customer((int)$cart->id_customer);

        $bad_delivery = false;
        if (($bad_delivery = (bool)!Address::isCountryActiveById((int)$cart->id_address_delivery))
            || !Address::isCountryActiveById((int)$cart->id_address_invoice)) {
            if ($bad_delivery) {
                throw new Exception(FyndiqTranslation::get('error-delivery-country-not-active'));
            }
            throw new Exception(FyndiqTranslation::get('error-invoice-country-not-active'));
        }
        $payment_module->validateOrder(
            (int)$cart->id,
            (int)$id_order_state,
            $cart->getOrderTotal(true, Cart::BOTH),
            $payment_module->displayName,
            $this->getOrderMessage($fyndiqOrder->id, $fyndiqOrder->delivery_note),
            array(),
            null,
            false,
            $cart->secure_key
        );
        //Update Order Id to Fyndiq order table
        $this->addOrderLog($payment_module->currentOrder, $fyndiqOrder->id);
        return true;
    }

    private function getOrderMessage($fyndiqOrderId, $fyndiqDeliveryNote)
    {
        $message = sprintf(FyndiqTranslation::get('Fyndiq order id: %s'), $fyndiqOrderId);
        $message .= PHP_EOL;
        $message .= sprintf(FyndiqTranslation::get('Fyndiq delivery note: %s'), $fyndiqDeliveryNote);
        $message .= PHP_EOL;
        $message .= FyndiqTranslation::get(
            'Copy the URL and paste it in the browser to download the delivery note.'
        );
        return $message;
    }

    /**
     * check if the order exists.
     *
     * @param $order_id
     * @return bool
     */
    public function orderExists($orderId)
    {
        $tableName = $this->fmPrestashop->getTableName(FmUtils::MODULE_NAME, '_orders', true);
        $orders = $this->fmPrestashop->dbGetInstance()->ExecuteS(
            'SELECT * FROM ' . $tableName . '
            WHERE fyndiq_orderid=' . $this->fmPrestashop->dbEscape($orderId) . ' LIMIT 1;'
        );
        return count($orders) > 0;
    }

    /**
     * check if the Fyndiq order exists.
     *
     * @param $order_id
     * @return Array
     */
    public function getFyndiqOrders($orderIds)
    {
        $tableName = $this->fmPrestashop->getTableName(FmUtils::MODULE_NAME, '_orders', true);
        $orders = $this->fmPrestashop->dbGetInstance()->ExecuteS(
            'SELECT * FROM ' . $tableName . '
            WHERE order_id IN ('. implode(', ', $orderIds).' );'
        );
        return $orders;
    }


    /**
     * Add the order to database. (to check what orders have already been added.
     *
     * @param int $orderId
     * @param int $fyndiqOrderId
     * @return bool
     */
    public function addOrderLog($orderId, $fyndiqOrderId)
    {
        $tableName = $this->fmPrestashop->getTableName(FmUtils::MODULE_NAME, '_orders');
        $data = array(
            'order_id' => $orderId,
            'fyndiq_orderid' => $fyndiqOrderId,
            'body' => ''
        );
        return (bool)$this->fmPrestashop->dbInsert(
            $tableName,
            $data
        );
    }

    public function getImportedOrders($page, $perPage)
    {
        $offset = $perPage * ($page - 1);
        $tableName = $this->fmPrestashop->getTableName(FmUtils::MODULE_NAME, '_orders', true);
        $sqlQuery = 'SELECT * FROM ' . $tableName . ' WHERE order_id > 0 ORDER BY id DESC LIMIT ' . $offset . ', ' . $perPage;
        $orders = $this->fmPrestashop->dbGetInstance()->ExecuteS($sqlQuery);
        return $orders;
    }

    public function getTotal()
    {
        $tableName = $this->fmPrestashop->getTableName(FmUtils::MODULE_NAME, '_orders', true);
        $sql = 'SELECT count(id) as amount FROM ' . $tableName .' WHERE order_id > 0';
        return $this->fmPrestashop->dbGetInstance()->getValue($sql);
    }

    /**
     * Try to match product by SKU
     *
     * @param string $productSKU
     * @return bool|array
     */
    public function getProductBySKU($sku, $skuTypeId)
    {
        if (empty($sku)) {
            return array(false, false);
        }
        switch ($skuTypeId) {
            case FmUtils::SKU_REFERENCE:
                return $this->getProductBySKUReference($sku);
            case FmUtils::SKU_EAN:
                return $this->getProductBySKUEAN($sku);
            case FmUtils::SKU_ID:
                return $this->getProductBySKUID($sku);
        }
        return array(false, false);
    }

    protected function getProductBySKUField($sku, $fieldName)
    {
        // Check products
        $sql = 'SELECT
                    id_product
                FROM ' . $this->fmPrestashop->globDbPrefix() . 'product' . '
                WHERE ' . $fieldName . ' = "'.$this->fmPrestashop->dbEscape($sku).'"
                ORDER BY id_product DESC';
        $productId = $this->fmPrestashop->dbGetInstance()->getValue($sql);
        if ($productId) {
            return array($productId, 0);
        }
        // Check combinations
        $sql = 'SELECT
                    id_product_attribute,
                    id_product
                FROM ' . $this->fmPrestashop->globDbPrefix() . 'product_attribute' . '
                WHERE ' . $fieldName . ' = "'.$this->fmPrestashop->dbEscape($sku).'"
                ORDER BY id_product_attribute DESC';
        $combinationRow = $this->fmPrestashop->dbGetInstance()->getRow($sql);
        if ($combinationRow) {
            return array($combinationRow['id_product'], $combinationRow['id_product_attribute']);
        }
        return array(false, false);
    }

    protected function getProductBySKUReference($sku)
    {
        return $this->getProductBySKUField($sku, 'reference');
    }

    protected function getProductBySKUEAN($sku)
    {
        return $this->getProductBySKUField($sku, 'ean13');
    }

    protected function getProductBySKUID($sku)
    {
        $parts = explode(FmUtils::SKU_SEPARATOR, $sku);
        if (count($parts) == 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
            return $parts;
        }
        if (count($parts) == 1 && is_numeric($sku)) {
            return array($sku, false);
        }
        return array(false, false);
    }

    public function markOrderAsDone($orderId, $orderDoneState)
    {
        return $this->fmPrestashop->markOrderAsDone($orderId, $orderDoneState);
    }

    public function orderQueued($fyndiqOrderId)
    {
        $tableName = $this->fmPrestashop->getTableName(FmUtils::MODULE_NAME, '_orders', true);
        $orders = $this->fmPrestashop->dbGetInstance()->ExecuteS(
            'SELECT * FROM ' . $tableName . '
            WHERE fyndiq_orderid=' . $this->fmPrestashop->dbEscape($fyndiqOrderId) . '
            AND order_id = 0
            LIMIT 1;'
        );
        return count($orders) > 0;
    }

    public function addToQueue($order)
    {
        $tableName = $this->fmPrestashop->getTableName(FmUtils::MODULE_NAME, '_orders');
        $data = array(
            'fyndiq_orderid' => intval($order->id),
            'body' => serialize($order),
            'status' => 0,
            'order_id' => 0,
        );
        return (bool)$this->fmPrestashop->dbInsert(
            $tableName,
            $data
        );
    }

    public function removeFromQueue($fyndiqOrderId)
    {
        $tableName = $this->fmPrestashop->getTableName(FmUtils::MODULE_NAME, '_orders');
        $data = array(
            'status' => 1
        );
        $where = 'fyndiq_orderid = ' . $this->fmPrestashop->dbEscape($fyndiqOrderId) . ' AND status = 0 AND order_id = 0';
        $this->fmPrestashop->dbUpdate($tableName, $data, $where);
    }

    public function processOrderQueueItem($fyndiqOrderId, $idOrderState, $taxAddressType, $skuTypeId)
    {
        $tableName = $this->fmPrestashop->getTableName(FmUtils::MODULE_NAME, '_orders', true);
        $sql = 'SELECT * FROM ' . $tableName . '
                WHERE fyndiq_orderid=' . $this->fmPrestashop->dbEscape($fyndiqOrderId) . '
                AND status = 0
                AND order_id = 0
                LIMIT 1';
        $rawOrders = $this->fmPrestashop->dbGetInstance()->ExecuteS($sql);
        if (!$rawOrders) {
            return;
        }
        $rawOrder = array_pop($rawOrders);
        $fyndiqOrder = unserialize($rawOrder['body']);
        $this->processOrder($fyndiqOrder, $idOrderState, $taxAddressType, $skuTypeId);
        return $this->removeFromQueue($fyndiqOrderId);
    }

    public function processFullOrderQueue($idOrderState, $taxAddressType, $skuTypeId)
    {
        $errors = array();
        $tableName = $this->fmPrestashop->getTableName(FmUtils::MODULE_NAME, '_orders', true);
        $sql = 'SELECT fyndiq_orderid FROM ' . $tableName . ' WHERE status=0 AND order_id=0';
        $rawOrders = $this->fmPrestashop->dbGetInstance()->ExecuteS($sql);
        if ($rawOrders) {
            foreach ($rawOrders as $row) {
                try {
                    $this->processOrderQueueItem($row['fyndiq_orderid'], $idOrderState, $taxAddressType, $skuTypeId);
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }
            if ($errors) {
                throw new Exception(implode("\n", $errors));
            }
        }
        return true;
    }
}
