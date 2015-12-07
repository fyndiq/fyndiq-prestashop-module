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

        $sql = 'CREATE UNIQUE INDEX orderIndexNew ON ' . $tableName . ' (fyndiq_orderid);';
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

    /**
     * Create orders from Fyndiq orders
     *
     * @param $fyndiqOrder
     * @return bool
     * @throws FyndiqProductSKUNotFound
     * @throws PrestaShopException
     */
    public function create($fyndiqOrder, $importState, $taxAddressType, $skuTypeId)
    {
        $context = $this->fmPrestashop->contextGetContext();

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

        $cart = $this->getCart($fyndiqOrder, $context->currency->id, $context->country->id);
        $cart->setOrderDetails($fyndiqOrderRows);

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

        foreach ($fyndiqOrderRows as $newRow) {
            $numArticle = (int)$newRow->quantity;
            $result = $cart->updateQty($numArticle, $newRow->productId, $newRow->combinationId);

            if (!$result) {
                $cart->delete();
                throw new PrestaShopException(
                    sprintf(FyndiqTranslation::get(
                        'Error adding product with SKU: `%s` (%s-%s) to cart. Possible reasons: not for sale or not enough stock left'
                    ), $newRow->sku, $newRow->productId, $newRow->combinationId)
                );
            }
        }

        return $this->createPrestashopOrders(
            $context,
            $fyndiqOrder,
            $cart,
            $fyndiqOrderRows,
            $importState
        );
    }

    protected function createPS1516Orders($context, $fyndiqOrder, $cart, $fyndiqOrderRows, $importState)
    {
        $context->cart = new FmCart($cart->id);
        $context->cart->setOrderDetails($fyndiqOrderRows);
        $amount_paid = $context->cart->getOrderTotal(true, Cart::BOTH);
error_log($amount_paid);
        $context->customer = new Customer((int)$context->cart->id_customer);
        $context->customer = new Customer($context->cart->id_customer);
        $context->language = new Language($context->cart->id_lang);
        $context->shop = new Shop($context->cart->id_shop);

        ShopUrl::resetMainDomainCache();

        $id_currency = (int)$context->cart->id_currency;
        $context->currency = new Currency($id_currency, null, $context->shop->id);
        if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_delivery') {
            $context_country = $context->country;
        }

        $order_status = new OrderState((int)$importState, (int)$context->language->id);
        if (!Validate::isLoadedObject($order_status)) {
            throw new PrestaShopException('Can\'t load Order status');
        }

        // Does order already exists ?
        if (!Validate::isLoadedObject($context->cart) || $context->cart->OrderExists() == true) {
            throw  new PrestaShopException('Cart cannot be loaded or an order has already been placed using this cart');
        }

        // For each package, generate an order
        $delivery_option_list = $context->cart->getDeliveryOptionList();
        $package_list = $context->cart->getPackageList();
        $cart_delivery_option = $context->cart->getDeliveryOption();

        // If some delivery options are not defined, or not valid, use the first valid option
        foreach ($delivery_option_list as $id_address => $package) {
            if (!isset($cart_delivery_option[$id_address]) || !array_key_exists($cart_delivery_option[$id_address], $package)) {
                foreach ($package as $key => $val) {
                    $cart_delivery_option[$id_address] = $key;
                    break;
                }
            }
        }

        $order_list = array();
        $order_detail_list = array();

        do {
            $reference = Order::generateReference();
        } while (Order::getByReference($reference)->count());

        $this->currentOrderReference = $reference;

        $order_creation_failed = false;
        $cart_total_paid = (float)Tools::ps_round((float)$context->cart->getOrderTotal(true, Cart::BOTH), 2);
        foreach ($cart_delivery_option as $id_address => $key_carriers) {
            foreach ($delivery_option_list[$id_address][$key_carriers]['carrier_list'] as $id_carrier => $data) {
                foreach ($data['package_list'] as $id_package) {
                // Rewrite the id_warehouse
                    $package_list[$id_address][$id_package]['id_warehouse'] = (int)$context->cart->getPackageIdWarehouse($package_list[$id_address][$id_package], (int)$id_carrier);
                    $package_list[$id_address][$id_package]['id_carrier'] = $id_carrier;
                }
            }
        }

        // Make sure CarRule caches are empty
        CartRule::cleanCache();

        $cart_rules = $context->cart->getCartRules();
        foreach ($cart_rules as $cart_rule) {
            if (($rule = new CartRule((int)$cart_rule['obj']->id)) && Validate::isLoadedObject($rule)) {
                if ($error = $rule->checkValidity($context, true, true)) {
                    $context->cart->removeCartRule((int)$rule->id);
                    }
            }
        }

        foreach ($package_list as $id_address => $packageByAddress) {
            foreach ($packageByAddress as $id_package => $package) {
                $order = new Order();
error_log(json_encode($package['product_list']));
                $order->product_list = $package['product_list'];

                if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_delivery') {
                    $address = new Address($id_address);
                    $context->country = new Country($address->id_country, $context->cart->id_lang);
                    if (!$context->country->active) {
                        throw new PrestaShopException('The delivery address country is not active.');
                    }
                }
                $carrier = null;
                if (!$context->cart->isVirtualCart() && isset($package['id_carrier'])) {
                    $carrier = new Carrier($package['id_carrier'], $context->cart->id_lang);
                    $order->id_carrier = (int)$carrier->id;
                    $id_carrier = (int)$carrier->id;
                } else {
                    $order->id_carrier = 0;
                    $id_carrier = 0;
                }

                $order->id_customer = (int)$context->cart->id_customer;
                $order->id_address_invoice = (int)$context->cart->id_address_invoice;
                $order->id_address_delivery = (int)$id_address;
                $order->id_currency = $context->currency->id;
                $order->id_lang = (int)$context->cart->id_lang;
                $order->id_cart = (int)$context->cart->id;
                $order->reference = $reference;
                $order->id_shop = (int)$context->shop->id;
                $order->id_shop_group = (int)$context->shop->id_shop_group;

                $order->secure_key = pSQL($context->customer->secure_key);
                $order->payment = self::FYNDIQ_PAYMENT_METHOD;
                $order->module = self::FYNDIQ_ORDERS_MODULE;
                $order->recyclable = $context->cart->recyclable;
                $order->gift = (int)$context->cart->gift;
                $order->gift_message = $context->cart->gift_message;
                $order->mobile_theme = $context->cart->mobile_theme;
                $order->conversion_rate = $context->currency->conversion_rate;
                $amount_paid = Tools::ps_round((float)$amount_paid, 2);
                $order->total_paid_real = 0;


                $order->total_products = (float)$context->cart->getOrderTotal(false, Cart::ONLY_PRODUCTS, $order->product_list, $id_carrier);
                $order->total_products_wt = (float)$context->cart->getOrderTotal(true, Cart::ONLY_PRODUCTS, $order->product_list, $id_carrier);

                $order->total_discounts_tax_excl = (float)abs($context->cart->getOrderTotal(false, Cart::ONLY_DISCOUNTS, $order->product_list, $id_carrier));
                $order->total_discounts_tax_incl = (float)abs($context->cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS, $order->product_list, $id_carrier));
                $order->total_discounts = $order->total_discounts_tax_incl;

                $order->total_shipping_tax_excl = (float)$context->cart->getPackageShippingCost((int)$id_carrier, false, null, $order->product_list);
                $order->total_shipping_tax_incl = (float)$context->cart->getPackageShippingCost((int)$id_carrier, true, null, $order->product_list);
                $order->total_shipping = $order->total_shipping_tax_incl;

                if (!is_null($carrier) && Validate::isLoadedObject($carrier)) {
                    $order->carrier_tax_rate = $carrier->getTaxesRate(new Address($context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')}));
                }

                $order->total_wrapping_tax_excl = (float)abs($context->cart->getOrderTotal(false, Cart::ONLY_WRAPPING, $order->product_list, $id_carrier));
                $order->total_wrapping_tax_incl = (float)abs($context->cart->getOrderTotal(true, Cart::ONLY_WRAPPING, $order->product_list, $id_carrier));
                $order->total_wrapping = $order->total_wrapping_tax_incl;

                $order->total_paid_tax_excl = (float)Tools::ps_round((float)$context->cart->getOrderTotal(false, Cart::BOTH, $order->product_list, $id_carrier), 2);
                $order->total_paid_tax_incl = (float)Tools::ps_round((float)$context->cart->getOrderTotal(true, Cart::BOTH, $order->product_list, $id_carrier), 2);
                $order->total_paid = $order->total_paid_tax_incl;

                $order->invoice_date = '0000-00-00 00:00:00';
                $order->delivery_date = '0000-00-00 00:00:00';

                // Creating order
                $result = $order->add();
                if (!$result) {
                    throw new PrestaShopException('Can\'t save Order');
                }

                $order_list[] = $order;

                // Insert new Order detail list using cart for the current order
                $order_detail = new OrderDetail(null, null, $context);
                $order_detail->createList($order, $context->cart, $importState, $order->product_list, 0, true, $package_list[$id_address][$id_package]['id_warehouse']);

                $order_detail_list[] = $order_detail;

                // Adding an entry in order_carrier table
                if (!is_null($carrier)) {
                    $order_carrier = new OrderCarrier();
                    $order_carrier->id_order = (int)$order->id;
                    $order_carrier->id_carrier = (int)$id_carrier;
                    $order_carrier->weight = (float)$order->getTotalWeight();
                    $order_carrier->shipping_cost_tax_excl = (float)$order->total_shipping_tax_excl;
                    $order_carrier->shipping_cost_tax_incl = (float)$order->total_shipping_tax_incl;
                    $order_carrier->add();
                }
            }
        }

        // The country can only change if the address used for the calculation is the delivery address, and if multi-shipping is activated
        if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_delivery') {
            $context->country = $context_country;
        }

        if (!$context->country->active) {
            throw new PrestaShopException('The order address country is not active.');
        }
        // Register Payment only if the order status validate the order
        if ($order_status->logable) {
            // $order is the last order loop in the foreach
            // The method addOrderPayment of the class Order make a create a paymentOrder
            //     linked to the order reference and not to the order id
            if (isset($extra_vars['transaction_id'])) {
                $transaction_id = $extra_vars['transaction_id'];
            } else {
                $transaction_id = null;
            }

            if (!$order->addOrderPayment($amount_paid, null, $transaction_id)) {
                throw new PrestaShopException('Can\'t save Order Payment');
            }
        }

        // Make sure CarRule caches are empty
        CartRule::cleanCache();
        foreach ($order_detail_list as $key => $order_detail) {
            $order = $order_list[$key];
            if (!$order_creation_failed && isset($order->id)) {
                $this->addOrderMessage($order->id, $fyndiqOrder->id, $fyndiqOrder->delivery_note);

                // Hook validate order
                Hook::exec('actionValidateOrder', array(
                    'cart' => $context->cart,
                    'order' => $order,
                    'customer' => $context->customer,
                    'currency' => $context->currency,
                    'orderStatus' => $order_status
                ));

                foreach ($context->cart->getProducts() as $product) {
                    if ($order_status->logable) {
                        ProductSale::addProductSale((int)$product['id_product'], (int)$product['cart_quantity']);
                    }
                }

                // updates stock in shops
                if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                    $product_list = $order->getProducts();
                    foreach ($product_list as $product) {
                        // if the available quantities depends on the physical stock
                        if (StockAvailable::dependsOnStock($product['product_id'])) {
                            // synchronizes
                            StockAvailable::synchronize($product['product_id'], $order->id_shop);
                        }
                    }
                }
                $this->addOrderToHistory($order->id, $importState);
                $this->addOrderLog($order->id, $fyndiqOrder->id);
                unset($order_detail);
            } else {
                throw new PrestaShopException('Order creation failed');
            }
        } // End foreach $order_detail_list
        return true;
    }


    protected function createPrestashopOrders($context, $fyndiqOrder, $cart, $fyndiqOrderRows, $importState)
    {
        if ($this->fmPrestashop->isPs1516()) {
            return $this->createPS1516Orders($context, $fyndiqOrder, $cart, $fyndiqOrderRows, $importState);
        }

        $cartProducts = $cart->getProducts();

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

        $this->fmPrestashop->insertOrderDetails($prestaOrder, $cart, $importState, $cartProducts);

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

    public function reserve($fyndiqOrderId)
    {
        $tableName = $this->fmPrestashop->getTableName(FmUtils::MODULE_NAME, '_orders');
        $data = array(
            'order_id' => 0,
            'fyndiq_orderid' => $fyndiqOrderId,
        );
        return (bool)$this->fmPrestashop->dbInsert($tableName, $data);
    }

    public function unreserve($fyndiqOrderId)
    {
        $tableName = $this->fmPrestashop->getTableName(FmUtils::MODULE_NAME, '_orders');
        return (bool)$this->fmPrestashop->dbDelete($tableName, 'fyndiq_orderid = ' . $fyndiqOrderId);
    }

    public function clearReservations()
    {
        $tableName = $this->fmPrestashop->getTableName(FmUtils::MODULE_NAME, '_orders');
        return (bool)$this->fmPrestashop->dbDelete($tableName, 'order_id = 0');
    }
}
