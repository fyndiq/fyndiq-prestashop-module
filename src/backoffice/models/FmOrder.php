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
            order_id INT(10));';
        $res &= $this->fmPrestashop->dbGetInstance()->Execute($sql, false);

        $sql = 'CREATE UNIQUE INDEX orderIndex ON ' . $tableName . ' (fyndiq_orderid);';
        $res &= $this->fmPrestashop->dbGetInstance()->Execute($sql, false);

        return (bool)$res;
    }

    public function fillAddress($fyndiqOrder, $customerId, $countryId, $alias)
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
        return $address;
    }

    public function getCart($fyndiqOrder, $currencyId, $countryId)
    {
        // create a new cart to add the articles to
        $cart = $this->fmPrestashop->newCart();
        $cart->id_currency = $currencyId;
        $cart->id_lang = fmPrestashop::DEFAULT_LANGUAGE_ID;

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

        $deliveryAddress = $this->fillAddress(
            $fyndiqOrder,
            $customer->id,
            $countryId,
            self::FYNDIQ_ORDERS_DELIVERY_ADDRESS_ALIAS
        );
        $deliveryAddress->add();

        $invoiceAddress = $this->fillAddress(
            $fyndiqOrder,
            $customer->id,
            $countryId,
            self::FYNDIQ_ORDERS_INVOICE_ADDRESS_ALIAS
        );
        $invoiceAddress->add();

        $cart->id_customer = $customer->id;
        $cart->id_address_invoice = (int)$invoiceAddress->id;
        $cart->id_address_delivery = (int)$deliveryAddress->id;

        return $cart;
    }

    protected function getSecureKey()
    {
        return md5(uniqid(rand(), true));
    }

    public function createPrestaOrder($cart, $context, $cartProducts, $createdDate, $importState)
    {
        // Create an order
        $prestaOrder = $this->fmPrestashop->newPrestashopOrder();
        $prestaOrder->id_carrier = self::ID_CARRIER;

        // create the order
        $prestaOrder->id_customer = (int)$cart->id_customer;
        $prestaOrder->id_address_invoice = $cart->id_address_invoice;
        $prestaOrder->id_address_delivery = $cart->id_address_delivery;
        $prestaOrder->id_currency = $cart->id_currency;
        $prestaOrder->id_lang = (int)$cart->id_lang;
        $prestaOrder->id_cart = (int)$cart->id;

        // Setup more settings for the order
        $prestaOrder->id_shop = (int)$context->shop->id;
        $prestaOrder->id_shop_group = (int)$context->shop->id_shop_group;

        $prestaOrder->secure_key = $this->getSecureKey();
        $prestaOrder->payment = self::FYNDIQ_PAYMENT_METHOD;
        $prestaOrder->module = self::FYNDIQ_ORDERS_MODULE;
        $prestaOrder->recyclable = $cart->recyclable;
        $prestaOrder->current_state = $importState;
        $prestaOrder->gift = (int)$cart->gift;
        $prestaOrder->gift_message = $cart->gift_message;
        if ($this->fmPrestashop->isPs1516()) {
            $prestaOrder->mobile_theme = $cart->mobile_theme;
        }
        $prestaOrder->conversion_rate = (float)$context->currency->conversion_rate;
        $prestaOrder->total_products = $this->getOrderTotal($cartProducts, false);
        $prestaOrder->total_products_wt = $this->getOrderTotal($cartProducts);

        // Discounts and shipping tax settings
        $prestaOrder->total_discounts_tax_excl = 0.00;
        $prestaOrder->total_discounts_tax_incl = 0.00;
        $prestaOrder->total_discounts = 0.00;
        $prestaOrder->total_shipping = 0.00;

        if ($this->fmPrestashop->isPs1516()) {
            $prestaOrder->total_shipping_tax_excl = 0.00;
            $prestaOrder->total_shipping_tax_incl = 0.00;
        }

        // Wrapping settings
        $prestaOrder->total_wrapping_tax_excl = 0;
        $prestaOrder->total_wrapping_tax_incl = 0;
        $prestaOrder->total_wrapping = $prestaOrder->total_wrapping_tax_incl;

        //Taxes
        $prestaOrder->total_paid_tax_excl = $this->fmPrestashop->toolsPsRound(
            $this->getOrderTotal($cartProducts, false),
            2
        );
        $prestaOrder->total_paid_tax_incl = $this->fmPrestashop->toolsPsRound(
            $this->getOrderTotal($cartProducts),
            2
        );

        // Set total paid
        $prestaOrder->total_paid_real = $prestaOrder->total_products_wt;
        $prestaOrder->total_paid = $prestaOrder->total_products_wt;

        // Set invoice date (needed to make order to work in prestashop 1.4
        $prestaOrder->invoice_date = date('Y-m-d H:i:s', $createdDate);
        $prestaOrder->delivery_date = date('Y-m-d H:i:s', $this->fmPrestashop->time());
        return $prestaOrder;
    }

    public function insertOrderDetail($prestaOrder, $cart, $cartProducts, $importState)
    {
        // Insert new Order detail list using cart for the current order
        if ($this->fmPrestashop->isPs1516()) {
            $orderDetail = $this->fmPrestashop->newOrderDetail();
            return $orderDetail->createList(
                $prestaOrder,
                $cart,
                $importState,
                $cartProducts
            );
        }
        if ($this->fmPrestashop->version === FmPrestashop::FMPSV14) {
            $result = true;
            foreach ($cartProducts as $product) {
                $orderDetail = $this->fmPrestashop->newOrderDetail();
                $orderDetail->id_order = $prestaOrder->id;
                $orderDetail->product_id = $product['id_product'];
                $orderDetail->product_attribute_id = $product['id_product_attribute'];
                $orderDetail->product_name = $product['name'];
                $orderDetail->product_quantity = $product['quantity'];
                $product['cart_quantity'] = $product['quantity'];
                $orderDetail->product_price = $product['price'];
                $orderDetail->tax_rate = $product['rate'];
                $result &= $orderDetail->add();
                $this->fmPrestashop->productUpdateQuantity($product);
            }
            return (bool)$result;
        }
        return false;
    }

    public function addOrderToHistory($prestaOrderId, $importState)
    {
        // create state in history
        $orderHistory = $this->fmPrestashop->newOrderHistory();
        $orderHistory->id_order = $prestaOrderId;
        $orderHistory->id_order_state = $importState;
        return $orderHistory->add();
    }

    public function addOrderMessage($prestaOrderId, $fyndiqOrderId, $fyndiqDeliveryNote)
    {
        // add Fyndiq delivery note as a message to the order
        $orderMessage = $this->fmPrestashop->newMessage();
        $orderMessage->id_order = $prestaOrderId;
        $orderMessage->private = true;
        $message = sprintf(FyndiqTranslation::get('Fyndiq order id: %s'), $fyndiqOrderId);
        $message .= PHP_EOL;
        $message .= sprintf(FyndiqTranslation::get('Fyndiq delivery note: %s'), $fyndiqDeliveryNote);
        $message .= PHP_EOL;
        $message .= FyndiqTranslation::get(
            'Copy the URL and paste it in the browser to download the delivery note.'
        );

        $orderMessage->message = $message;
        return $orderMessage->add();
    }

    public function updateProductsPrices($orderRows, $products)
    {
        foreach ($orderRows as $row) {
            foreach ($products as $key => $product) {
                if ($product['id_product'] == $row->productId && $product['id_product_attribute'] == $row->combinationId) {
                    $product['quantity'] = $row->quantity;
                    $product['price'] = $this->fmPrestashop->toolsPsRound(
                        (float)($row->unit_price_amount / ((100+intval($row->vat_percent)) / 100)),
                        2
                    );
                    $product['price_wt'] = floatval($row->unit_price_amount);
                    $product['total_wt'] = floatval(($row->unit_price_amount*$row->quantity));
                    $product['total'] = $this->fmPrestashop->toolsPsRound(floatval((($row->unit_price_amount / ((100+intval($row->vat_percent)) / 100))*$row->quantity)), 2);
                    $product['rate'] = floatval($row->vat_percent);
                    $products[$key] = $product;
                }
            }
        }
        return $products;
    }

    /**
     * create orders from Fyndiq orders
     *
     * @param $fyndiqOrder
     * @return bool
     * @throws FyndiqProductSKUNotFound
     * @throws PrestaShopException
     */
    public function create($fyndiqOrder, $importState, $taxAddressType)
    {
        $context = $this->fmPrestashop->contextGetContext();

        $fyndiqorderRows = $fyndiqOrder->order_rows;

        foreach ($fyndiqorderRows as $key => $row) {
            list($productId, $combinationId) = $this->getProductBySKU($row->sku);
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
            $fyndiqorderRows[$key] = $row;
        }

        $cart = $this->getCart($fyndiqOrder, $context->currency->id, $context->country->id);

        // Check address
        if ($taxAddressType == 'id_address_delivery') {
            $address = $this->fmPrestashop->newAddress($cart->id_address_delivery);
            $country = $this->fmPrestashop->newCountry($address->id_country, $cart->id_lang);
            if (!$country->active) {
                throw new PrestaShopException(FyndiqTranslation::get('error-delivery-country-not-active'));
            }
        }

        // Save the cart
        $cart->add();

        $customer = $this->fmPrestashop->newCustomer();
        $customer->getByEmail(self::FYNDIQ_ORDERS_EMAIL);
        $context->customer = $customer;

        foreach ($fyndiqorderRows as $newrow) {
            $numArticle = (int)$newrow->quantity;
            $cart->updateQty($numArticle, $newrow->productId, $newrow->combinationId);
        }

        $cartProducts = $this->updateProductsPrices($fyndiqorderRows, $cart->getProducts());

        $prestaOrder = $this->createPrestaOrder(
            $cart,
            $context,
            $cartProducts,
            strtotime($fyndiqOrder->created),
            $importState
        );

        // Creating order
        $result = $prestaOrder->add();

        // if result is false the add didn't work and it will throw a exception.
        if (!$result) {
            throw new PrestaShopException(FyndiqTranslation::get('error-save-order'));
        }

        $this->insertOrderDetail($prestaOrder, $cart, $cartProducts, $importState);

        if ($this->fmPrestashop->isPs1516()) {
            // create payment in order because Fyndiq handles the payment - so it looks already paid in PrestaShop
            $prestaOrder->addOrderPayment($prestaOrder->total_products_wt, self::FYNDIQ_PAYMENT_METHOD);
        }

        $this->addOrderToHistory($prestaOrder->id, $importState);
        $this->addOrderMessage($prestaOrder->id, $fyndiqOrder->id, $fyndiqOrder->delivery_note);

        // set order as valid
        $prestaOrder->valid = true;
        $prestaOrder->update();

        //Add order to log (PrestaShop database) so it doesn't get added again next time this is run
        return $this->addOrderLog($prestaOrder->id, $fyndiqOrder->id);
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
        $sqlQuery = 'SELECT * FROM ' . $tableName . ' ORDER BY id DESC LIMIT ' . $offset . ', ' . $perPage;
        $orders = $this->fmPrestashop->dbGetInstance()->ExecuteS($sqlQuery);
        return $orders;
    }

    public function getTotal()
    {
        $tableName = $this->fmPrestashop->getTableName(FmUtils::MODULE_NAME, '_orders', true);
        $sql = 'SELECT count(id) as amount FROM ' . $tableName;
        return $this->fmPrestashop->dbGetInstance()->getValue($sql);
    }

    /**
     * remove table from database.
     *
     * @return bool
     */
    public function uninstall()
    {
        $tableName = $this->fmPrestashop->getTableName(FmUtils::MODULE_NAME, '_orders', true);
        return $this->fmPrestashop->dbGetInstance()->Execute('DROP TABLE ' . $tableName);
    }

    /**
     * Try to match product by SKU
     *
     * @param string $productSKU
     * @return bool|array
     */
    public function getProductBySKU($productSKU)
    {
        if (!isset($productSKU)) {
            return false;
        }
        // Check products
        $sql = 'SELECT id_product FROM ' . $this->fmPrestashop->globDbPrefix() . 'product' . '
            WHERE reference = "'.$this->fmPrestashop->dbEscape($productSKU).'"';
        $productId = $this->fmPrestashop->dbGetInstance()->getValue($sql);
        if ($productId) {
            return array($productId, 0);
        }
        // Check combinations
        $sql = 'SELECT id_product_attribute, id_product
            FROM ' . $this->fmPrestashop->globDbPrefix() . 'product_attribute' . '
            WHERE reference = "'.$this->fmPrestashop->dbEscape($productSKU).'"';
        $combinationRow = $this->fmPrestashop->dbGetInstance()->getRow($sql);
        if ($combinationRow) {
            return array($combinationRow['id_product'], $combinationRow['id_product_attribute']);
        }
        return false;
    }

    public function getOrderTotal($products, $tax = true)
    {
        $total = 0;
        foreach ($products as $product) {
            $total += $tax ? $product['total_wt'] : $product['total'];
        }
        return (float)$total;
    }

    public function markOrderAsDone($orderId, $orderDoneState)
    {
        return $this->fmPrestashop->markOrderAsDone($orderId, $orderDoneState);
    }
}
