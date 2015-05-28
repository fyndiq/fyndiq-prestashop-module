<?php
/**
 * Class FmOrder
 *
 * handles orders
 */
class FmOrder extends FmModel
{

    const FYNDIQ_ORDERS_EMAIL = 'info@fyndiq.se';
    const FYNDIQ_ORDERS_NAME_FIRST = 'Fyndiq';
    const FYNDIQ_ORDERS_NAME_LAST = 'Orders';

    const FYNDIQ_ORDERS_DELIVERY_ADDRESS_ALIAS = 'Delivery';
    const FYNDIQ_ORDERS_INVOICE_ADDRESS_ALIAS = 'Invoice';

    const FYNDIQ_ORDERS_MODULE = 'fyndiqmerchant';
    const FYNDIQ_PAYMENT_METHOD = 'Fyndiq';

    const DEFAULT_LANGUAGE_ID = 1;

    /**
     * install the table in the database
     *
     * @return bool
     */
    public static function install()
    {
        $module = Module::getInstanceByName('fyndiqmerchant');
        $ret = (bool)Db::getInstance()->Execute(
            'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . $module->config_name . '_orders(
            id int(20) unsigned primary key AUTO_INCREMENT,
            fyndiq_orderid INT(10),
            order_id INT(10));
            CREATE UNIQUE INDEX orderIndex
            ON ' . _DB_PREFIX_ . $module->config_name . '_orders (fyndiq_orderid);
        '
        );

        return $ret;
    }

    private static function getContext()
    {
        // mock the context for PS 1.4
        $context = new stdClass();

        // if the Presta Shop 1.5 and 1.6 is used, use the context class.
        if (FMPSV == FMPSV15 or FMPSV == FMPSV16) {
            $context = Context::getContext();
        } else {
            $context->shop = new stdClass();
            $context->country = new stdClass();
            $context->currency = Currency::getDefaultCurrency();
            $context->country = (int)Country::getDefaultCountryId();

        }
        $context->currency = Currency::getDefaultCurrency();
        $context->id_lang = self::DEFAULT_LANGUAGE_ID;

        return $context;
    }

    private static function fillAddress($fyndiqOrder, $customerId, $countryId, $alias)
    {
        // Create address
        $address = new Address();
        $address->firstname = $fyndiqOrder->delivery_firstname;
        $address->lastname = $fyndiqOrder->delivery_lastname;
        $address->phone = $fyndiqOrder->delivery_phone;
        $address->address1 = $fyndiqOrder->delivery_address;
        $address->postcode = $fyndiqOrder->delivery_postalcode;
        $address->city = $fyndiqOrder->delivery_city;
        $address->company = $fyndiqOrder->delivery_co;
        $address->id_country = $countryId;
        $address->id_customer = $customerId;
        $address->alias = $alias;

        return $address;
    }

    private static function getCart($fyndiqOrder, $context)
    {
        // create a new cart to add the articles to
        $cart = new Cart();
        $cart->id_currency = $context->currency->id;
        $cart->id_lang = self::DEFAULT_LANGUAGE_ID;

        $customer = new Customer();
        $customer->getByEmail(self::FYNDIQ_ORDERS_EMAIL);

        if (is_null($customer->firstname)) {
            // Create a customer.
            $customer = new Customer();
            $customer->firstname = self::FYNDIQ_ORDERS_NAME_FIRST;
            $customer->lastname = self::FYNDIQ_ORDERS_NAME_LAST;
            $customer->email = self::FYNDIQ_ORDERS_EMAIL;
            $customer->passwd = md5(uniqid(rand(), true));

            // Add it to the database.
            $customer->add();

            $deliveryAddress = self::fillAddress(
                $fyndiqOrder,
                $customer->id,
                $context->country->id,
                self::FYNDIQ_ORDERS_DELIVERY_ADDRESS_ALIAS
            );
            $deliveryAddress->add();

            $invoiceAddress = self::fillAddress(
                $fyndiqOrder,
                $customer->id,
                $context->country->id,
                self::FYNDIQ_ORDERS_INVOICE_ADDRESS_ALIAS
            );
            $invoiceAddress->add();

        } else {
            $invoiceAddress = new Address();
            $deliveryAddress = new Address();
            $addresses = $customer->getAddresses($cart->id_lang);
            foreach ($addresses as $address) {
                $currentAddress = null;
                if ($address['alias'] === self::FYNDIQ_ORDERS_INVOICE_ADDRESS_ALIAS) {
                    $currentAddress = $invoiceAddress;
                }
                if ($address['alias'] === self::FYNDIQ_ORDERS_DELIVERY_ADDRESS_ALIAS) {
                    $currentAddress = $deliveryAddress;
                }
                foreach ($address as $key => $value) {
                    if ($key === 'id_address') {
                        $currentAddress->id = $value;
                    } else {
                        $currentAddress->$key = $value;
                    }
                }
            }
        }

        $cart->id_customer = $customer->id;
        $cart->id_address_invoice = (int)$invoiceAddress->id;
        $cart->id_address_delivery = (int)$deliveryAddress->id;

        return $cart;
    }

    /**
     * create orders from Fyndiq orders
     *
     * @param $fyndiqOrder
     * @return bool
     * @throws FyndiqProductSKUNotFound
     * @throws PrestaShopException
     */
    public static function create($fyndiqOrder)
    {
        foreach ($fyndiqOrder->order_rows as &$row) {
            list($productId, $combinationId) = self::getProductBySKU($row->sku);
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
        }

        $context = self::getContext();

        $cart = self::getCart($fyndiqOrder, $context);
        // Save the cart
        $cart->add();

        $customer = new Customer();
        $customer->getByEmail(self::FYNDIQ_ORDERS_EMAIL);

        $context->customer = $customer;

        foreach ($fyndiqOrder->order_rows as $row) {
            $numArticle = (int)$row->quantity;
            $cart->updateQty($numArticle, $row->productId, $row->combinationId);
        }


        if (FMPSV == FMPSV15 or FMPSV == FMPSV16) {
            // create a internal reference for the order.
            $reference = Order::generateReference();
        }

        $idOrderState = FmConfig::get('import_state');
        $idOrderState = $idOrderState ? $idOrderState : (int)Configuration::get('PS_OS_PREPARATION');

        // Check address
        if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_delivery') {
            $address = new Address($cart->id_address_delivery);
            $country = new Country($address->id_country, $cart->id_lang);
            if (!$country->active) {
                throw new PrestaShopException(FyndiqTranslation::get('error-delivery-country-not-active'));
            }
        }

        // Create an order
        $prestaOrder = new Order();

        if (FMPSV == FMPSV15 or FMPSV == FMPSV16) {
            $prestaOrder->reference = $reference;
        }

        // get the carrier for the cart
        $carrier = null;
        $prestaOrder->id_carrier = 1;
        $idCarrier = 1;
        if (!$cart->isVirtualCart() && isset($package['id_carrier'])) {
            $carrier = new Carrier($package['id_carrier'], $cart->id_lang);
            $prestaOrder->id_carrier = (int)$carrier->id;
            $idCarrier = (int)$carrier->id;
        }
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

        $secureKey = md5(uniqid(rand(), true));
        $prestaOrder->secure_key = $secureKey;
        $prestaOrder->payment = self::FYNDIQ_PAYMENT_METHOD;
        $prestaOrder->module = self::FYNDIQ_ORDERS_MODULE;
        $prestaOrder->recyclable = $cart->recyclable;
        $prestaOrder->current_state = $idOrderState;
        $prestaOrder->gift = (int)$cart->gift;
        $prestaOrder->gift_message = $cart->gift_message;
        $prestaOrder->mobile_theme = $cart->mobile_theme;
        $prestaOrder->conversion_rate = (float)$context->currency->conversion_rate;

        $prestaOrder->total_products = (float)$cart->getOrderTotal(
            false,
            Cart::ONLY_PRODUCTS,
            $cart->getProducts(),
            $idCarrier
        );
        $prestaOrder->total_products_wt = (float)$cart->getOrderTotal(
            true,
            Cart::ONLY_PRODUCTS,
            $cart->getProducts(),
            $idCarrier
        );

        // Discounts and shipping tax settings
        $prestaOrder->total_discounts_tax_excl = 0.00;
        $prestaOrder->total_discounts_tax_incl = 0.00;
        $prestaOrder->total_discounts = 0.00;
        $prestaOrder->total_shipping = 0.00;

        if (FMPSV == FMPSV15 or FMPSV == FMPSV16) {
            $prestaOrder->total_shipping_tax_excl = 0.00;
            $prestaOrder->total_shipping_tax_incl = 0.00;
        }

        if (!is_null($carrier) && Validate::isLoadedObject($carrier)) {
            $prestaOrder->carrier_tax_rate = 0.00;
        }

        // Wrapping settings
        $prestaOrder->total_wrapping_tax_excl = 0;
        $prestaOrder->total_wrapping_tax_incl = 0;
        $prestaOrder->total_wrapping = $prestaOrder->total_wrapping_tax_incl;

        //Taxes
        $prestaOrder->total_paid_tax_excl = 0;
        $prestaOrder->total_paid_tax_incl = (float)Tools::ps_round(
            (float)$cart->getOrderTotal(true, Cart::BOTH, $cart->getProducts(), $idCarrier),
            2
        );

        // Set total paid
        $prestaOrder->total_paid_real = $prestaOrder->total_products_wt;
        $prestaOrder->total_paid = $prestaOrder->total_products_wt;

        // Set invoice date (needed to make order to work in prestashop 1.4
        $prestaOrder->invoice_date = date('Y-m-d H:i:s', strtotime($fyndiqOrder->created));
        $prestaOrder->delivery_date = date('Y-m-d H:i:s');

        // Creating order
        $result = $prestaOrder->add();

        // if result is false the add didn't work and it will throw a exception.
        if (!$result) {
            throw new PrestaShopException(FyndiqTranslation::get('error-save-order'));
        }

        // Insert new Order detail list using cart for the current order
        if (FMPSV == FMPSV15 or FMPSV == FMPSV16) {
            $orderDetail = new OrderDetail();
            $orderDetail->createList(
                $prestaOrder,
                $cart,
                $idOrderState,
                $cart->getProducts()
            );
        } else {
            if (FMPSV == FMPSV14) {
                foreach ($cart->getProducts() as $product) {
                    $orderDetail = new OrderDetail();
                    $orderDetail->id_order = $prestaOrder->id;
                    $orderDetail->product_name = $product['name'];
                    $orderDetail->product_quantity = $product['quantity'];
                    $orderDetail->product_price = $product['price'];
                    $orderDetail->tax_rate = $product['rate'];
                    $orderDetail->add();
                }
            }
        }

        if (FMPSV == FMPSV15 or FMPSV == FMPSV16) {
            // create payment in order because fyndiq handles the payment - so it looks already paid in prestashop
            $prestaOrder->addOrderPayment($prestaOrder->total_products_wt, self::FYNDIQ_PAYMENT_METHOD);
        }

        // create state in history
        $orderHistory = new OrderHistory();
        $orderHistory->id_order = $prestaOrder->id;
        $orderHistory->id_order_state = $idOrderState;
        $orderHistory->add();


        //add fyndiq delivery note as a message to the order
        $orderMessage = new Message();
        $orderMessage->id_order = $prestaOrder->id;
        $orderMessage->private = true;
        $module = Module::getInstanceByName('fyndiqmerchant');
        $url = FmHelpers::getShopUrl() . $module->get('_path') . 'backoffice/delivery_note.php?order_id=' . $fyndiqOrder->id;
        $orderMessage->message = 'Fyndiq delivery note: ' . $url . PHP_EOL . 'just copy url and paste in the browser to download the delivery note.';
        $orderMessage->add();

        // set order as valid
        $prestaOrder->valid = true;
        $prestaOrder->update();

        //Add order to log (prestashop database) so it doesn't get added again next time this is run
        self::addOrderLog($prestaOrder->id, $fyndiqOrder->id);

        // Adding an entry in order_carrier table
        if (!is_null($carrier)) {
            $orderCarrier = new OrderCarrier();
            $orderCarrier->id_order = (int)$prestaOrder->id;
            $orderCarrier->id_carrier = (int)$idCarrier;
            $orderCarrier->weight = (float)$prestaOrder->getTotalWeight();
            $orderCarrier->shipping_cost_tax_excl = (float)$prestaOrder->total_shipping_tax_excl;
            $orderCarrier->shipping_cost_tax_incl = (float)$prestaOrder->total_shipping_tax_incl;
            $orderCarrier->add();
        }
    }

    /**
     * check if the order exists.
     *
     * @param $order_id
     * @return bool
     */
    public static function orderExists($orderId)
    {
        $module = Module::getInstanceByName('fyndiqmerchant');
        $orders = Db::getInstance()->ExecuteS(
            'SELECT * FROM ' . _DB_PREFIX_ . $module->config_name . '_orders
        WHERE fyndiq_orderid=' . FmHelpers::dbEscape($orderId) . ' LIMIT 1;'
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
    public static function addOrderLog($orderId, $fyndiqOrderId)
    {
        $module = Module::getInstanceByName('fyndiqmerchant');
        $ret = (bool)Db::getInstance()->Execute(
            'INSERT INTO ' . _DB_PREFIX_ . $module->config_name . '_orders (order_id,fyndiq_orderid) VALUES (' . FmHelpers::dbEscape(
                $orderId
            ) . ',' . FmHelpers::dbEscape($fyndiqOrderId) . ')'
        );

        return $ret;
    }

    public function getImportedOrders($page, $perPage)
    {
        $module = Module::getInstanceByName('fyndiqmerchant');

        $offset = $perPage * ($page - 1);
        $sqlQuery = 'SELECT * FROM ' . _DB_PREFIX_ . $module->config_name . '_orders LIMIT ' . $offset . ', ' . $perPage;

        $orders = Db::getInstance()->ExecuteS($sqlQuery);
        $return = array();
        $orderDoneState = $this->fmConfig->get('done_state');

        foreach ($orders as $order) {
            $orderArray = $order;
            $newOrder = new Order((int)$order['order_id']);
            $products = $newOrder->getProducts();
            $quantity = 0;
            $currentState = new OrderState($newOrder->getCurrentState());
            foreach ($products as $product) {
                $quantity += $product['product_quantity'];
            }
            $url = 'index.php?controller=AdminOrders&id_order=' . $order['order_id'] . '&vieworder';
            $url .= '&token='.Tools::getAdminTokenLite('AdminOrders');
            $orderArray['created_at'] = date('Y-m-d', strtotime($newOrder->date_add));
            $orderArray['created_at_time'] = date('G:i:s', strtotime($newOrder->date_add));
            $orderArray['price'] = $newOrder->total_paid_real;
            $orderArray['state'] = $currentState->name[1];
            $orderArray['total_products'] = $quantity;
            $orderArray['is_done'] = $newOrder->getCurrentState() == $orderDoneState;
            $orderArray['link'] = $url;
            $return[] = $orderArray;
        }
        return $return;
    }

    public static function getAmount()
    {
        $module = Module::getInstanceByName('fyndiqmerchant');
        $sqlquery = 'SELECT count(id) as amount FROM ' . _DB_PREFIX_ . $module->config_name . '_orders';
        return Db::getInstance()->getValue($sqlquery);
    }

    /**
     * remove table from database.
     *
     * @return bool
     */
    public static function uninstall()
    {
        $module = Module::getInstanceByName('fyndiqmerchant');
        $ret = (bool)Db::getInstance()->Execute(
            'drop table ' . _DB_PREFIX_ . $module->config_name . '_orders'
        );

        return $ret;
    }

    /**
     * Try to match product by SKU
     *
     * @param string $productSKU
     * @return bool|array
     */
    private static function getProductBySKU($productSKU)
    {
        if (strpos($productSKU, FmProductExport::SKU_PREFIX) === 0) {
            // Auto-generated SKU format is PREFIX-priduct_id-combination_id
            $segments = explode(FmProductExport::SKU_SEPARATOR, $productSKU);
            // It must be three segment SKU
            if (count($segments) === 3) {
                return array((int)$segments[1], (int)$segments[2]);
            }
        }

        // Check products
        $query = new DbQuery();
        $query->select('p.id_product');
        $query->from('product', 'p');
        $query->where('p.reference = \'' . FmHelpers::dbEscape($productSKU) . '\'');
        $productId = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
        if ($productId) {
            return array($productId, 0);
        }
        // Check combinations
        $query = new DbQuery();
        $query->select('id_product_attribute, id_product');
        $query->from('product_attribute');
        $query->where('reference = \'' . FmHelpers::dbEscape($productSKU) . '\'');
        $combinationRow = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($query);
        if ($combinationRow) {
            return array($combinationRow['id_product'], $combinationRow['id_product_attribute']);
        }

        return false;
    }

    public function markOrderAsDone($orderId, $orderDoneState)
    {
        return $this->fmPrestashop->markOrderAsDone($orderId, $orderDoneState);
    }
}
