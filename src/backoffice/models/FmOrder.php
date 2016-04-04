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

    /**
     * [getCustomer description]
     * @return [type] [description]
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

    private function iniCart($fyndiqOrder)
    {
        $context = $this->fmPrestashop->contextGetContext();
        $customer = $this->getCustomer();
        $id_customer = $customer->id;
        $context->customer = $customer;
        $id_cart = 0;
        if (!$id_cart) {
            $id_cart = $customer->getLastCart(false);
        }
        $context->cart = $this->fmPrestashop->newCart((int)$id_cart);

        if (!$context->cart->id) {
            $context->cart->recyclable = 0;
            $context->cart->gift = 0;
        }

        if (!$context->cart->id_customer) {
            $context->cart->id_customer = $id_customer;
        }
        if ($this->fmPrestashop->isObjectLoaded($context->cart) && $context->cart->OrderExists()) {
            return;
        }
        if (!$context->cart->secure_key) {
            $context->cart->secure_key = $context->customer->secure_key;
        }
        if (!$context->cart->id_shop) {
            $context->cart->id_shop = (int)$context->shop->id;
        }
        if (!$context->cart->id_lang) {
            $context->cart->id_lang = (($id_lang = (int)$context->language->id) ? $id_lang : Configuration::get('PS_LANG_DEFAULT'));
        }
        if (!$context->cart->id_currency) {
            $context->cart->id_currency = (($id_currency = (int)$context->currency->id) ? $id_currency : Configuration::get('PS_CURRENCY_DEFAULT'));
        }

        $addresses = $customer->getAddresses((int)$context->cart->id_lang);
        if (!$context->cart->id_address_invoice && isset($addresses[0])) {
            $context->cart->id_address_invoice = (int)$addresses[0]['id_address'];
        } else {
            $id_address_invoice = $this->getAddressId(
                $fyndiqOrder,
                $customer->id,
                $context->country->id,
                self::FYNDIQ_ORDERS_INVOICE_ADDRESS_ALIAS
            );
            $context->cart->id_address_invoice = (int)$id_address_invoice;
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

    public function addProductToCart($context, $productId, $attributeId, $quantity)
    {
        $response = array();
        if (!$context->cart->id) {
            $response['error'] = 'Cart not found';
            return $response;
        }
        if ($context->cart->OrderExists()) {
            $response['error'] = 'An order has already been placed with this cart.';
            return $response;
        }
        if (!($id_product = (int)$productId) || !($product = new Product((int)$id_product, true, $context->language->id))) {
            $response['error'] = 'Invalid Product';
            return $response;
        }
        if (!($qty = $quantity) || $qty == 0) {
            $response['error'] = 'Invalid Quantity';
            return $response;
        }

        $id_customization = 0;
        if (($id_product_attribute = $attributeId) != 0) {
            if (!Product::isAvailableWhenOutOfStock($product->out_of_stock) && !Attribute::checkAttributeQty((int)$id_product_attribute, (int)$qty)) {
                $response['error'] = 'There is not enough product in stock.';
                return $response;
            }
        }
        if (!$product->checkQty((int)$qty)) {
            $response['error'] = 'There is not enough product in stock.';
            return $response;
        }
        $context->cart->save();

        if ((int)$qty < 0) {
            $qty = str_replace('-', '', $qty);
            $operator = 'down';
        } else {
            $operator = 'up';
        }
        if (!($qty_upd = $context->cart->updateQty($qty, $id_product, (int)$id_product_attribute, (int)$id_customization, $operator))) {
            $response['error'] = 'You already have the maximum quantity available for this product.';
            return $response;
        }
        if ($qty_upd < 0) {
            $minimal_qty = $id_product_attribute ? Attribute::getAttributeMinimalQty((int)$id_product_attribute) : $product->minimal_quantity;
            $response['error'] = sprintf('You must add a minimum quantity of %d', $minimal_qty);
            return $response;
        }

        $response['error'] = false;
        $response['context'] = $context;
        return $response;
    }

    public function updateCustomProductPrice($context, $productId, $attributeId, $price)
    {
        SpecificPrice::deleteByIdCart((int)$context->cart->id, (int)$productId, (int)$attributeId);
        $specific_price = new SpecificPrice();
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
        $specific_price->add();
    }

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
        // Initilize the cart
        $context = $this->iniCart($fyndiqOrder);

        // add Product to a cart
        foreach ($fyndiqOrderRows as $key => $row) {
            $result = $this->addProductToCart($context, $row->productId, $row->combinationId, $row->quantity);
            if ($result['error']) {
                throw new FyndiqProductSKUNotFound(sprintf(
                    FyndiqTranslation::get($result['error']),
                    $row->sku,
                    $fyndiqOrder->id
                ));
                return false;
            }
            $context = $result['context'];
            $this->updateCustomProductPrice($result['context'], $row->productId, $row->combinationId, $row->unit_price_amount);
        }
        $this->createOrder($context->cart->id, $importState, $fyndiqOrder);
    }

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
                 throw new PrestaShopException(FyndiqTranslation::get('error-delivery-country-not-active'));
            } else {
                throw new PrestaShopException(FyndiqTranslation::get('error-invoice-country-not-active'));
            }
        } else {
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
        }
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

        // Initilize the cart
        $context = $this->iniCart($fyndiqOrder);

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
            if (!$result || $result === -1) {
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

    // Adapted from PaymentModule::validateOrder
    protected function createPS1516Orders($context, $fyndiqOrder, $cartId, $fyndiqOrderRows, $importState)
    {
        $context->cart = new FmCart($cartId);
        $context->cart->setOrderDetails($fyndiqOrderRows);

        $context->customer = new Customer((int)$context->cart->id_customer);
        $context->language = new Language($context->cart->id_lang);
        $context->shop = new Shop($context->cart->id_shop);
        $amount_paid = $context->cart->getOrderTotal(true, Cart::BOTH);

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

        $orderList = array();
        $orderDetailList = array();

        do {
            $reference = Order::generateReference();
        } while (Order::getByReference($reference)->count());

        $this->currentOrderReference = $reference;

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

        foreach ($package_list as $id_address => $packageByAddress) {
            foreach ($packageByAddress as $id_package => $package) {
                $order = new Order();
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

                $orderList[] = $order;

                // Insert new Order detail list using cart for the current order
                $order_detail = new OrderDetail(null, null, $context);
                $order_detail->createList($order, $context->cart, $importState, $order->product_list, 0, true, $package_list[$id_address][$id_package]['id_warehouse']);

                $orderDetailList[] = $order_detail;

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
            $this->rollBackCreatedOrder($orderList, $orderDetailList);
            throw new PrestaShopException('The order address country is not active.');
        }

        foreach ($orderDetailList as $key => $order_detail) {
            $order = $orderList[$key];
            if (isset($order->id)) {
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
                $this->rollBackCreatedOrder($orderList, $orderDetailList);
                throw new PrestaShopException('Order creation failed');
            }
        } // End foreach $orderDetailList
        return true;
    }

    /**
     * roleBackOrderCreation. role back orders which are not completed.
     * @param  array    $orderList          list of orders
     * @param  array    $orderDetailList    list of order details
     */
    private function rollBackCreatedOrder($orderList, $orderDetailList)
    {
        foreach ($orderDetailList as $key => $order_detail) {
            $order = $orderList[$key];
            /** re-inject product quantity to stock*/
            StockAvailable::updateQuantity(
                $order_detail->product_id,
                $order_detail->product_attribute_id,
                $order_detail->product_quantity,
                $order_detail->id_shop
            );
            /** delete order details from the order*/
            $order_detail->delete();
        }
        /** cancel the order and change the order status to cancel */
        $this->addOrderToHistory($order->id, $this->fmPrestashop->getCancelOrderStateId());
    }

    protected function createPrestashopOrders($context, $fyndiqOrder, $cart, $fyndiqOrderRows, $importState)
    {
        if ($this->fmPrestashop->configurationGet('PS_ADVANCED_STOCK_MANAGEMENT') && $this->fmPrestashop->isPs1516()) {
            return $this->createPS1516Orders($context, $fyndiqOrder, $cart->id, $fyndiqOrderRows, $importState);
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
        $this->addOrderLog($prestaOrder->id, $fyndiqOrder->id);
        return $this->removeFromQueue($fyndiqOrder->id);
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
        //$this->create($fyndiqOrder, $idOrderState, $taxAddressType, $skuTypeId);
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
