<?php
/**
 * Created by PhpStorm.
 * User: confact
 * Date: 04/08/14
 * Time: 08:42
 */

/**
 * Class FmOrder
 *
 * handles orders
 */
class FmOrder
{

    const FYNDIQ_ORDERS_EMAIL = 'info@fyndiq.se';
    const FYNDIQ_ORDERS_NAME_FIRST = 'Fyndiq';
    const FYNDIQ_ORDERS_NAME_LAST = 'Orders';

    const FYNDIQ_ORDERS_DELIVERY_ADDRESS_ALIAS = 'Delivery';
    const FYNDIQ_ORDERS_INVOICE_ADDRESS_ALIAS = 'Invoice';

    const FYNDIQ_ORDERS_MODULE = 'fyndiq';
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
        '
        );

        return $ret;
    }

    private static function getContext() {
        // mock the context for PS 1.4
        $context = new stdClass();

        // if the Presta Shop 1.5 and 1.6 is used, use the context class.
        if (FMPSV == FMPSV15 OR FMPSV == FMPSV16) {
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

    private static function fillAddress($fyndiq_order, $customerId, $countryId, $alias) {
        // Create address
        $address = new Address();
        $address->firstname = $fyndiq_order->delivery_firstname;
        $address->lastname = $fyndiq_order->delivery_lastname;
        $address->phone = $fyndiq_order->delivery_phone;
        $address->address1 = $fyndiq_order->delivery_address;
        $address->postcode = $fyndiq_order->delivery_postalcode;
        $address->city = $fyndiq_order->delivery_city;
        $address->company = $fyndiq_order->delivery_co;
        $address->id_country = $countryId;
        $address->id_customer = $customerId;
        $address->alias = $alias;
        return $address;
    }

    /**
     * create orders from Fyndiq orders
     *
     * @param $fyndiq_order
     * @return bool
     * @throws FyndiqProductSKUNotFound
     * @throws PrestaShopException
     */
    public static function create($fyndiq_order)
    {
        foreach ($fyndiq_order->order_rows as &$row) {
            list($productId, $combinationId) = self::getProductBySKU($row->sku);
            if (!$productId) {
                throw new FyndiqProductSKUNotFound(sprintf('Product with SKU "%s", from order #%d cannot be found.',
                    $row->sku, $fyndiq_order->id));
                return false;
            } else {
                $row->productId = $productId;
                $row->combinationId = $combinationId;
            }
        }

        $context = self::getContext();

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

            $deliveryAddress = fillAddress($fyndiq_order, $customer->id, $context->country->id,
                self::FYNDIQ_ORDERS_DELIVERY_ADDRESS_ALIAS);
            $deliveryAddress->add();

            $invoiceAddress = fillAddress($fyndiq_order, $customer->id, $context->country->id,
                self::FYNDIQ_ORDERS_INVOICE_ADDRESS_ALIAS);
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

        // Create an order
        $presta_order = new Order();

        if (FMPSV == FMPSV15 OR FMPSV == FMPSV16) {
            // create a internal reference for the order.
            $reference = Order::generateReference();
        }

        $secure_key = md5(uniqid(rand(), true));
        $id_order_state = (int)Configuration::get('PS_OS_PREPARATION');

        // Check address
        if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_delivery') {
            $address = new Address($deliveryAddress->id);
            $country = new Country($address->id_country, $cart->id_lang);
            if (!$country->active) {
                throw new PrestaShopException('The delivery address country is not active.');
            }
        }

        // get the carrier for the cart
        $carrier = null;
        if (!$cart->isVirtualCart() && isset($package['id_carrier'])) {
            $carrier = new Carrier($package['id_carrier'], $cart->id_lang);
            $presta_order->id_carrier = (int)$carrier->id;
            $id_carrier = (int)$carrier->id;
        } else {
            $presta_order->id_carrier = 1;
            $id_carrier = 1;
        }

        $cart->id_customer = $customer->id;
        $cart->id_address_invoice = (int)$invoiceAddress->id;
        $cart->id_address_delivery = (int)$deliveryAddress->id;

        // Save the cart
        $cart->add();

        foreach ($fyndiq_order->order_rows as $row) {
            $num_article = (int)$row->quantity;
            //add product to the cart
            $cart->updateQty($num_article, $row->productId, $row->combinationId);
        }

        // create the order
        $presta_order->id_customer = (int)$customer->id;
        $presta_order->id_address_invoice = (int)$invoiceAddress->id;
        $presta_order->id_address_delivery = (int)$deliveryAddress->id;
        $presta_order->id_currency = $cart->id_currency;
        $presta_order->id_lang = (int)$cart->id_lang;
        $presta_order->id_cart = (int)$cart->id;

        if (FMPSV == FMPSV15 OR FMPSV == FMPSV16) {
            $presta_order->reference = $reference;
        }

        // Setup more settings for the order
        $presta_order->id_shop = (int)$context->shop->id;
        $presta_order->id_shop_group = (int)$context->shop->id_shop_group;

        $presta_order->secure_key = $secure_key;
        $presta_order->payment = self::FYNDIQ_PAYMENT_METHOD;
        $presta_order->module = self::FYNDIQ_ORDERS_MODULE;
        $presta_order->recyclable = $cart->recyclable;
        $presta_order->current_state = $id_order_state;
        $presta_order->gift = (int)$cart->gift;
        $presta_order->gift_message = $cart->gift_message;
        $presta_order->mobile_theme = $cart->mobile_theme;
        $presta_order->conversion_rate = (float)$context->currency->conversion_rate;

        $presta_order->total_products = (float)$cart->getOrderTotal(
            false,
            Cart::ONLY_PRODUCTS,
            $cart->getProducts(),
            $id_carrier
        );
        $presta_order->total_products_wt = (float)$cart->getOrderTotal(
            true,
            Cart::ONLY_PRODUCTS,
            $cart->getProducts(),
            $id_carrier
        );

        // Discounts and shipping tax settings
        $presta_order->total_discounts_tax_excl = 0.00;
        $presta_order->total_discounts_tax_incl = 0.00;
        $presta_order->total_discounts = 0.00;

        if (FMPSV == FMPSV15 OR FMPSV == FMPSV16) {
            $presta_order->total_shipping_tax_excl = 0.00;
            $presta_order->total_shipping_tax_incl = 0.00;
            $presta_order->total_shipping = 0.00;
        } else {
            if (FMPSV == FMPSV14) {
                $presta_order->total_shipping = 0.00;
            }
        }

        if (!is_null($carrier) && Validate::isLoadedObject($carrier)) {
            $presta_order->carrier_tax_rate = 0.00;
        }

        // Wrapping settings
        $presta_order->total_wrapping_tax_excl = 0;
        $presta_order->total_wrapping_tax_incl = 0;
        $presta_order->total_wrapping = $presta_order->total_wrapping_tax_incl;

        //Taxes
        $presta_order->total_paid_tax_excl = 0;
        $presta_order->total_paid_tax_incl = (float)Tools::ps_round(
            (float)$cart->getOrderTotal(true, Cart::BOTH, $cart->getProducts(), $id_carrier),
            2
        );

        // Set total paid
        $presta_order->total_paid_real = $presta_order->total_products_wt;
        $presta_order->total_paid = $presta_order->total_products_wt;

        // Set invoice date (needed to make order to work in prestashop 1.4
        if (FMPSV == FMPSV15 OR FMPSV == FMPSV16) {
            $presta_order->invoice_date = date('Y-m-d H:i:s', strtotime($fyndiq_order->created));
            $presta_order->delivery_date = date('Y-m-d H:i:s');
        } else {
            if (FMPSV == FMPSV14) {
                $presta_order->invoice_date = date('Y-m-d H:i:s', strtotime($fyndiq_order->created));
                $presta_order->delivery_date = date('Y-m-d H:i:s');
            }
        }

        // Creating order
        $result = $presta_order->add();

        // if result is false the add didn't work and it will throw a exception.
        if (!$result) {
            throw new PrestaShopException('Can\'t save Order');
        }

        // Insert new Order detail list using cart for the current order
        if (FMPSV == FMPSV15 OR FMPSV == FMPSV16) {
            $order_detail = new OrderDetail();
            $order_detail->createList(
                $presta_order,
                $cart,
                $id_order_state,
                $cart->getProducts()
            );
        } else {
            if (FMPSV == FMPSV14) {
                foreach ($cart->getProducts() as $product) {
                    $order_detail = new OrderDetail();
                    $order_detail->id_order = $presta_order->id;
                    $order_detail->product_name = $product["name"];
                    $order_detail->product_quantity = $product["quantity"];
                    $order_detail->product_price = $product["price"];
                    $order_detail->tax_rate = $product["rate"];
                    $order_detail->add();
                }
            }
        }

        if (FMPSV == FMPSV15 OR FMPSV == FMPSV16) {
            // create payment in order because fyndiq handles the payment - so it looks already paid in prestashop
            $order_payment = new OrderPayment();
            $order_payment->id_currency = $cart->id_currency;
            $order_payment->amount = $presta_order->total_products_wt;
            $order_payment->payment_method = self::FYNDIQ_PAYMENT_METHOD;
            $order_payment->order_reference = $reference;
            $order_payment->add();
        }

        // create state in history
        $order_history = new OrderHistory();
        $order_history->id_order = $presta_order->id;
        $order_history->id_order_state = $id_order_state;
        $order_history->add();


        //add fyndiq delivery note as a message to the order
        $order_message = new Message();
        $order_message->id_order = $presta_order->id;
        $order_message->private = true;
        // TODO: FIX the url!
        $order_message->message = "Fyndiq delivery note: http://fyndiq.se" . $fyndiq_order->delivery_note . " \n just copy url and paste in the browser to download the delivery note.";
        $order_message->add();

        // set order as valid
        $presta_order->valid = true;
        $presta_order->update();

        //Add order to log (prestashop database) so it doesn't get added again next time this is run
        self::addOrderLog($presta_order->id, $fyndiq_order->id);

        // Adding an entry in order_carrier table
        if (!is_null($carrier)) {
            $order_carrier = new OrderCarrier();
            $order_carrier->id_order = (int)$presta_order->id;
            $order_carrier->id_carrier = (int)$id_carrier;
            $order_carrier->weight = (float)$presta_order->getTotalWeight();
            $order_carrier->shipping_cost_tax_excl = (float)$presta_order->total_shipping_tax_excl;
            $order_carrier->shipping_cost_tax_incl = (float)$presta_order->total_shipping_tax_incl;
            $order_carrier->add();
        }
    }

    /**
     * check if the order exists.
     *
     * @param $order_id
     * @return bool
     */
    public static function orderExists($order_id)
    {
        $module = Module::getInstanceByName('fyndiqmerchant');
        $orders = Db::getInstance()->ExecuteS(
            'SELECT * FROM ' . _DB_PREFIX_ . $module->config_name . '_orders
        WHERE fyndiq_orderid=' . FmHelpers::db_escape($order_id) . '
        LIMIT 1;
        '
        );

        if (count($orders) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Add the order to database. (to check what orders have already been added.
     *
     * @param $order_id
     * @param $fyndiq_orderid
     * @return bool
     */
    public static function addOrderLog($order_id, $fyndiq_orderid)
    {
        $module = Module::getInstanceByName('fyndiqmerchant');
        $ret = (bool)Db::getInstance()->Execute(
            'INSERT INTO ' . _DB_PREFIX_ . $module->config_name . '_orders (order_id,fyndiq_orderid) VALUES (' . FmHelpers::db_escape(
                $order_id
            ) . ',' . FmHelpers::db_escape($fyndiq_orderid) . ')'
        );

        return $ret;
    }

    public static function getImportedOrders()
    {
        $module = Module::getInstanceByName('fyndiqmerchant');
        $orders = Db::getInstance()->ExecuteS(
            'SELECT * FROM ' . _DB_PREFIX_ . $module->config_name . '_orders;
        '
        );
        $return = array();

        foreach ($orders as $order) {
            $orderarray = $order;
            $neworder = new Order((int)$order['order_id']);
            $products = $neworder->getProducts();
            $quantity = 0;
            foreach ($products as $product) {
                $quantity += $product['product_quantity'];
            }
            $orderarray['created_at'] = date('Y-m-d', strtotime($neworder->date_add));
            $orderarray['created_at_time'] = date('G:i:s', strtotime($neworder->date_add));
            $orderarray['price'] = $neworder->total_paid_real;
            $orderarray['total_products'] = $quantity;
            $return[] = $orderarray;

        }

        return $return;
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
        $query->where('p.reference = \'' . pSQL($productSKU) . '\'');
        $productId = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
        if ($productId) {
            return array($productId, 0);
        }
        // Check combinations
        $query = new DbQuery();
        $query->select('id_product_attribute, id_product');
        $query->from('product_attribute');
        $query->where('reference = \'' . pSQL($productSKU) . '\'');
        $combinationRow = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($query);
        if ($combinationRow) {
            return array($combinationRow['id_product'], $combinationRow['id_product_attribute']);
        }

        return false;
    }
}
