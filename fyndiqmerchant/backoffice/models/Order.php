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

    /**
     * create orders from Fyndiq orders
     *
     * @param $fyndiq_order
     * @throws PrestaShopException
     */
    public static function create($fyndiq_order)
    {
        // if the prestashop 1.5 and 1.6 is used, use the context class.
        if (FMPSV == FMPSV15 OR FMPSV == FMPSV16) {
            $context = Context::getContext();
        }
        // create a new cart to add the articles to
        $cart = new Cart();

        // Get order_rows (articles inside the order) for this specific order.
        $order_id = $fyndiq_order->id;
        $fyndiq_order_infos = FmHelpers::call_api('GET', 'order_row/?order__exact=' . $order_id);

        $cart->id_currency = Currency::getDefaultCurrency()->id;
        $cart->id_lang = 1;
        if (FMPSV == FMPSV15 OR FMPSV == FMPSV16) {
            $context->currency = Currency::getDefaultCurrency();
            $context->id_lang = 1;
        }

        $country_result = Db::getInstance()->ExecuteS(
            "SELECT * FROM " . _DB_PREFIX_ . "country WHERE iso_code='SE' LIMIT 1"
        );

        $country = 18;

        foreach ($country_result as $row) {
            $country = $row["id_country"];
        }

        $customer = new Customer();
        $customer->getByEmail($fyndiq_order->customer_email);
        $checkcustomer = is_null($customer->firstname);

        if ($checkcustomer) {
            // Create a customer.
            $customer = new Customer();
            $customer->firstname = $fyndiq_order->delivery_firstname;
            $customer->lastname = $fyndiq_order->delivery_lastname;
            $customer->email = $fyndiq_order->customer_email;
            $customer->passwd = "test12345";

            // Add it to the database.
            $customer->add();
        }

        if ($checkcustomer) {
            // Create delivery address
            $delivery_address = new Address();
            $delivery_address->firstname = $fyndiq_order->delivery_firstname;
            $delivery_address->lastname = $fyndiq_order->delivery_lastname;
            $delivery_address->email = $fyndiq_order->customer_email;
            $delivery_address->phone = $fyndiq_order->customer_phone;
            $delivery_address->address1 = $fyndiq_order->delivery_address;
            $delivery_address->postcode = $fyndiq_order->delivery_postalcode;
            $delivery_address->city = $fyndiq_order->delivery_city;
            $delivery_address->company = $fyndiq_order->delivery_co;
            $delivery_address->id_country = $country;
            $delivery_address->id_customer = $customer->id;
            $delivery_address->alias = "Delivery"; // TODO: fix this!

            // Add it to the database.
            $delivery_address->add();

            // Create invoice address
            $invoice_address = new Address();
            $invoice_address->firstname = $fyndiq_order->invoice_firstname;
            $invoice_address->lastname = $fyndiq_order->invoice_lastname;
            $invoice_address->email = $fyndiq_order->customer_email;
            $invoice_address->phone = $fyndiq_order->customer_phone;
            $invoice_address->address1 = $fyndiq_order->invoice_address;
            $invoice_address->postcode = $fyndiq_order->invoice_postalcode;
            $invoice_address->city = $fyndiq_order->invoice_city;
            $invoice_address->company = $fyndiq_order->invoice_co;
            $invoice_address->id_country = $country;
            $invoice_address->id_customer = $customer->id;
            $invoice_address->alias = "Invoice"; // TODO: fix this!

            // Add it to the database.
            $invoice_address->add();
        } else {
            $addresses = $customer->getAddresses($cart->id_lang);
            foreach ($addresses as $adrss) {
                if ($adrss["address1"] == $fyndiq_order->invoice_address AND $adrss["postcode"] == $fyndiq_order->invoice_postalcode AND $adrss["firstname"] == $fyndiq_order->invoice_firstname AND $adrss["lastname"] == $fyndiq_order->invoice_lastname) {
                    $invoice_address = new Address();
                    foreach ($adrss as $key => $value) {
                        if ($key == "id_address") {
                            $invoice_address->id = $value;
                        } else {
                            $invoice_address->$key = $value;
                        }
                    }
                } else {
                    $delivery_address = new Address();
                    foreach ($adrss as $key => $value) {
                        if ($key == "id_address") {
                            $delivery_address->id = $value;
                        } else {
                            $delivery_address->$key = $value;
                        }
                    }
                }
            }
        }
        // Create a order
        $presta_order = new Order();

        if (FMPSV == FMPSV15 OR FMPSV == FMPSV16) {
            // create a internal reference for the order.
            $reference = Order::generateReference();
        }
        $payment_method = 'Fyndiq';
        $secure_key = md5(uniqid(rand(), true));
        $amount_paid = 500;
        $id_order_state = (int)Configuration::get('PS_OS_PREPARATION');


        // Check address
        if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_delivery') {
            $address = new Address($delivery_address->id);
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
        $cart->id_address_invoice = (int)$invoice_address->id;
        $cart->id_address_delivery = (int)$delivery_address->id;

        // Save the cart
        $cart->add();


        foreach ($fyndiq_order_infos["data"]->objects as $row) {
            // Get article for order row
            $article_id = $row->article;
            //$row_article = FmHelpers::call_api('GET', 'article/'.$article_id.'/');

            // get id of the product
            // TODO: shall be from a table later (to conenct a product in prestashop with a id for a article in Fyndiq)
            $product_id = 1;
            $num_article = $row->num_articles;

            //add product to the cart
            $cart->updateQty($num_article, $product_id);
        }

        // create the order
        $presta_order->id_customer = (int)$customer->id;
        $presta_order->id_address_invoice = (int)$invoice_address->id;
        $presta_order->id_address_delivery = (int)$delivery_address->id;
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
        $presta_order->payment = $payment_method;

        $presta_order->module = "fyndiq";

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
        $presta_order->total_discounts_tax_excl = (float)abs(
            $cart->getOrderTotal(false, Cart::ONLY_DISCOUNTS, $cart->getProducts(), $id_carrier)
        );
        $presta_order->total_discounts_tax_incl = (float)abs(
            $cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS, $cart->getProducts(), $id_carrier)
        );
        $presta_order->total_discounts = $fyndiq_order->total_discounts_tax_incl;

        if (FMPSV == FMPSV15 OR FMPSV == FMPSV16) {
            $presta_order->total_shipping_tax_excl = (float)$cart->getPackageShippingCost(
                (int)$id_carrier,
                false,
                null,
                $cart->getProducts()
            );
            $presta_order->total_shipping_tax_incl = (float)$cart->getPackageShippingCost(
                (int)$id_carrier,
                true,
                null,
                $cart->getProducts()
            );
            $presta_order->total_shipping = $fyndiq_order->total_shipping_tax_incl;
        } else {
            if (FMPSV == FMPSV14) {
                $presta_order->total_shipping = (float)($cart->getOrderShippingCost());
            }
        }

        if (!is_null($carrier) && Validate::isLoadedObject($carrier)) {
            $presta_order->carrier_tax_rate = $carrier->getTaxesRate(
                new Address($cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')})
            );
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
            $presta_order->invoice_date = '0000-00-00 00:00:00';
            $presta_order->delivery_date = '0000-00-00 00:00:00';
        } else {
            if (FMPSV == FMPSV14) {
                $presta_order->invoice_date = date("Y-m-d H:i:s", strtotime($fyndiq_order->created_at));
                $presta_order->delivery_date = date("Y-m-d H:i:s");
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
            $order_payment->payment_method = $payment_method;
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
}