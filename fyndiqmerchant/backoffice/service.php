<?php

# import PrestaShop config, to enable use of PrestaShop classes, like Configuration
$configPath = dirname(dirname(dirname(dirname($_SERVER['SCRIPT_FILENAME'])))).'/config/config.inc.php';
if (file_exists($configPath)) {
    require_once($configPath);
} else {
    exit;
}

require_once('../messages.php');
require_once('./helpers.php');
require_once('./models/product_export.php');
require_once('./models/category.php');
require_once('./models/product.php');

class FmAjaxService {

    /**
     * Structure the response back to the client
     *
     * @param string $data
     */
    public static function response($data = '') {
        $response = array(
            'fm-service-status'=> 'success',
            'data'=> $data
        );
        $json = json_encode($response);
        if (json_last_error() != JSON_ERROR_NONE) {
            self::response_error(
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
    public static function response_error($title, $message) {
        $response = array(
            'fm-service-status'=> 'error',
            'title'=> $title,
            'message'=> $message,
        );
        $json = json_encode($response);
        echo $json;
    }

    # handle incoming ajax request
    /**
     *
     */
    public static function handle_request() {
        $action = false;
        $args = array();
        if (array_key_exists('action', $_POST)) {
            $action = $_POST['action'];
        }
        if (array_key_exists('args', $_POST)) {
            $args = $_POST['args'];
        }

        # call static function on self with name of the value provided in $action
        if (method_exists('FmAjaxService', $action)) {
            self::$action($args);
        }
    }

    ### views ###

    /**
     * Get the categories.
     *
     * @param $args
     */
    public static function get_categories($args) {
        $categories = FmCategory::get_all();
        self::response($categories);
    }

    /**
     * Get the products.
     *
     * @param $args
     */
    public static function get_products($args) {
        $products = array();

        $rows = FmProduct::get_by_category($args['category']);

        foreach ($rows as $row) {
            $products[] = FmProduct::get($row['id_product']);
        }

        self::response($products);
    }

    /**
     * Getting the orders to be saved in Prestashop.
     *
     * @param $args
     * @throws PrestaShopException
     */
    public static function import_orders($args) {

        $module_name = 'fyndiqmerchant';

        try {
            $ret = FmHelpers::call_api('GET', 'order/');
            $context = Context::getContext();

            foreach($ret["data"]->objects as $order) {

                $order_id = $order->id;
                $cart = new Cart();
                $row_ret = FmHelpers::call_api('GET', 'order_row/?order__exact='.$order_id);

                $context->cart = $cart;
                $context->cart->id_currency = Currency::getDefaultCurrency()->id;
                $context->currency = Currency::getDefaultCurrency();

                $context->id_lang = 1;
                $context->cart->id_lang = 1;

                $country_result = Db::getInstance()->ExecuteS("SELECT * FROM "._DB_PREFIX_."country WHERE iso_code='SE' LIMIT 1");

                $country = 18;

                foreach($country_result as $row) {
                    $country = $row["id_country"];
                }

                $customer = new Customer();
                $customer->getByEmail($order->customer_email);
                $checkcustomer = is_null($customer->firstname);

                if($checkcustomer) {
                // Create a customer.
                $customer = new Customer();
                $customer->firstname = $order->delivery_firstname;
                $customer->lastname = $order->delivery_lastname;
                $customer->email = $order->customer_email;
                $customer->passwd = "test12345";

                // Add it to the database.
                $customer->add();
                }

                if($checkcustomer) {
                // Create delivery address
                $delivery_address = new Address();
                $delivery_address->firstname = $order->delivery_firstname;
                $delivery_address->lastname = $order->delivery_lastname;
                $delivery_address->email = $order->customer_email;
                $delivery_address->phone = $order->customer_phone;
                $delivery_address->address1 = $order->delivery_address;
                $delivery_address->postcode = $order->delivery_postalcode;
                $delivery_address->city = $order->delivery_city;
                $delivery_address->company = $order->delivery_co;
                $delivery_address->id_country = $country;
                $delivery_address->id_customer = $customer->id;
                $delivery_address->alias = "Delivery"; // TODO: fix this!

                // Add it to the database.
                $delivery_address->add();

                // Create invoice address
                $invoice_address = new Address();
                $invoice_address->firstname = $order->invoice_firstname;
                $invoice_address->lastname = $order->invoice_lastname;
                $invoice_address->email = $order->customer_email;
                $invoice_address->phone = $order->customer_phone;
                $invoice_address->address1 = $order->invoice_address;
                $invoice_address->postcode = $order->invoice_postalcode;
                $invoice_address->city = $order->invoice_city;
                $invoice_address->company = $order->invoice_co;
                $invoice_address->id_country = $country;
                $invoice_address->id_customer = $customer->id;
                $invoice_address->alias = "Invoice"; // TODO: fix this!

                // Add it to the database.
                $invoice_address->add();
                }
                else {
                    $addresses = $customer->getAddresses($context->cart->id_lang);
                    foreach($addresses as $adrss) {
                        if($adrss["address1"] == $order->invoice_address AND $adrss["postcode"] == $order->invoice_postalcode AND $adrss["firstname"] == $order->invoice_firstname AND $adrss["lastname"] == $order->invoice_lastname) {
                            $invoice_address = new Address();
                            foreach ($adrss as $key => $value)
                            {
                                if($key == "id_address") {
                                    $invoice_address->id = $value;
                                }
                                else {
                                    $invoice_address->$key = $value;
                                }
                            }
                        }
                        else {
                            $delivery_address = new Address();
                            foreach ($adrss as $key => $value)
                            {
                                if($key == "id_address") {
                                    $delivery_address->id = $value;
                                }
                                else {
                                    $delivery_address->$key = $value;
                                }
                            }
                        }
                    }
                }
                // Create a order
                $presta_order = new Order();


                // create a internal reference for the order.
                $reference = Order::generateReference();
                $payment_method = 'Fyndiq';
                $secure_key = md5(uniqid(rand(), true));
                $amount_paid = 500;
                $id_order_state = (int)Configuration::get('PS_OS_PREPARATION');


                if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_delivery')
                {
                    $address = new Address($delivery_address->id);
                    $context->country = new Country($address->id_country, $context->cart->id_lang);
                    if (!$context->country->active)
                        throw new PrestaShopException('The delivery address country is not active.');
                }

                $carrier = null;
                if (!$context->cart->isVirtualCart() && isset($package['id_carrier']))
                {
                    $carrier = new Carrier($package['id_carrier'], $context->cart->id_lang);
                    $presta_order->id_carrier = (int)$carrier->id;
                    $id_carrier = (int)$carrier->id;
                }
                else
                {
                    $presta_order->id_carrier = 1;
                    $id_carrier = 1;
                }

                $context->cart->id_customer = $customer->id;
                $context->cart->id_address_invoice = (int)$invoice_address->id;
                $context->cart->id_address_delivery = (int)$delivery_address->id;

                $context->cart->add();


                foreach($row_ret["data"]->objects as $row) {
                    // Get article for order row
                    $article_id= $row->article;
                    //$row_article = FmHelpers::call_api('GET', 'article/'.$article_id.'/');

                    // get id of the product
                    // TODO: shall be from a table later (to conenct a product in prestashop with a id for a article in Fyndiq)
                    $product_id = 1;
                    $num_article = $row->num_articles;

                    //add product to the cart
                    $context->cart->updateQty($num_article, $product_id);
                }


                $presta_order->id_customer = (int)$customer->id;
                $presta_order->id_address_invoice = (int)$invoice_address->id;
                $presta_order->id_address_delivery = (int)$delivery_address->id;
                $presta_order->id_currency = $context->cart->id_currency;
                $presta_order->id_lang = (int)$context->cart->id_lang;
                $presta_order->id_cart = (int)$context->cart->id;
                $presta_order->reference = $reference;
                $presta_order->id_shop = (int)$context->shop->id;
                $presta_order->id_shop_group = (int)$context->shop->id_shop_group;

                $presta_order->secure_key = $secure_key;
                $presta_order->payment = $payment_method;

                $presta_order->module = "fyndiq";

                $presta_order->recyclable = $context->cart->recyclable;
                $presta_order->current_state = $id_order_state;
                $presta_order->gift = (int)$context->cart->gift;
                $presta_order->gift_message = $context->cart->gift_message;
                $presta_order->mobile_theme = $context->cart->mobile_theme;
                $presta_order->conversion_rate = (float)$context->currency->conversion_rate;

                $presta_order->total_products = (float)$context->cart->getOrderTotal(false, Cart::ONLY_PRODUCTS, $presta_order->product_list, $id_carrier);
                $presta_order->total_products_wt = (float)$context->cart->getOrderTotal(true, Cart::ONLY_PRODUCTS, $presta_order->product_list, $id_carrier);

                $presta_order->total_discounts_tax_excl = (float)abs($context->cart->getOrderTotal(false, Cart::ONLY_DISCOUNTS, $presta_order->product_list, $id_carrier));
                $presta_order->total_discounts_tax_incl = (float)abs($context->cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS, $presta_order->product_list, $id_carrier));
                $presta_order->total_discounts = $order->total_discounts_tax_incl;

                $presta_order->total_shipping_tax_excl = (float)$context->cart->getPackageShippingCost((int)$id_carrier, false, null, $presta_order->product_list);
                $presta_order->total_shipping_tax_incl = (float)$context->cart->getPackageShippingCost((int)$id_carrier, true, null, $presta_order->product_list);
                $presta_order->total_shipping = $order->total_shipping_tax_incl;

                if (!is_null($carrier) && Validate::isLoadedObject($carrier))
                    $presta_order->carrier_tax_rate = $carrier->getTaxesRate(new Address($context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')}));

                $presta_order->total_wrapping_tax_excl = (float)abs($context->cart->getOrderTotal(false, Cart::ONLY_WRAPPING, $presta_order->product_list, $id_carrier));
                $presta_order->total_wrapping_tax_incl = (float)abs($context->cart->getOrderTotal(true, Cart::ONLY_WRAPPING, $presta_order->product_list, $id_carrier));
                $presta_order->total_wrapping = $presta_order->total_wrapping_tax_incl;

                $presta_order->total_paid_tax_excl = (float)Tools::ps_round((float)$context->cart->getOrderTotal(false, Cart::BOTH, $presta_order->product_list, $id_carrier), 2);
                $presta_order->total_paid_tax_incl = (float)Tools::ps_round((float)$context->cart->getOrderTotal(true, Cart::BOTH, $presta_order->product_list, $id_carrier), 2);

                $presta_order->total_paid_real = $presta_order->total_products_wt;
                $presta_order->total_paid = $presta_order->total_products_wt;

                $presta_order->invoice_date = '0000-00-00 00:00:00';
                $presta_order->delivery_date = '0000-00-00 00:00:00';

                // Creating order
                $result = $presta_order->add();

                if (!$result)
                    throw new PrestaShopException('Can\'t save Order');

                // Insert new Order detail list using cart for the current order
                $order_detail = new OrderDetail(null, null, $context);
                $order_detail->createList($presta_order, $context->cart, $id_order_state, $context->cart->getProducts());

                // create state in history
                $order_history = new OrderHistory();
                $order_history->id_order = $presta_order->id;
                $order_history->id_order_state = $id_order_state;
                $order_history->add();

                // Adding an entry in order_carrier table
                if (!is_null($carrier))
                {
                    $order_carrier = new OrderCarrier();
                    $order_carrier->id_order = (int)$presta_order->id;
                    $order_carrier->id_carrier = (int)$id_carrier;
                    $order_carrier->weight = (float)$presta_order->getTotalWeight();
                    $order_carrier->shipping_cost_tax_excl = (float)$presta_order->total_shipping_tax_excl;
                    $order_carrier->shipping_cost_tax_incl = (float)$presta_order->total_shipping_tax_incl;
                    $order_carrier->add();
                }
            }

            self::response($ret);
        } catch (Exception $e) {
            self::response_error(
                FmMessages::get('unhandled-error-title'),
                FmMessages::get('unhandled-error-message').' ('.$e->getMessage().')'
            );
        }
    }

    /**
     * Exporting the products from Prestashop
     *
     * @param $args
     */
    public static function export_products($args) {
        $error = false;

        foreach ($args['products'] as $v) {
            $product = $v['product'];

            $result = array(
                'title'=> $product['name'],
                'description'=> 'asdf8u4389j34g98j34g98',
                'images'=> array($product['image']),
                'oldprice'=> '9999',
                'price'=> $product['price'],
                'moms_percent'=> '25',
                'articles'=> array()
            );

            // when posting empty array, it's removed completely from the request, so check for key
            if (array_key_exists('combinations', $v)) {
                $combinations = $v['combinations'];

                foreach ($combinations as $combination) {
                    $result['articles'][] = array(
                        'num_in_stock'=> '7',
                        'merchant_item_no'=> '2',
                        'description'=> 'asdfjeroijergo'
                    );
                }
            } else {
                $result['articles'][] = array(
                    'num_in_stock'=> '99',
                    'merchant_item_no'=> '99',
                    'description'=> 'qwer99qwer98referf'
                );
            }

            try {
                $result = FmHelpers::call_api('POST', 'products/', $result);
                if ($result['status'] != 201) {
                    $error = true;
                    self::response_error(
                        FmMessages::get('unhandled-error-title'),
                        FmMessages::get('unhandled-error-message')
                    );
                }
                FmProductExport::create($product['id'], $result['data']->id);
            } catch (FyndiqAPIBadRequest $e) {
                $error = true;
                $message = '';
                foreach (FyndiqAPI::$error_messages as $error_message) {
                    $message .= $error_message;
                }
                self::response_error(
                    FmMessages::get('products-bad-params-title'),
                    $message
                );
            } catch (Exception $e) {
                $error = true;
                self::response_error(
                    FmMessages::get('unhandled-error-title'),
                    $e->getMessage()
                );
            }

            if ($error) {
                break;
            }
        }

        if (!$error) {
            self::response();
        }
    }
}

FmAjaxService::handle_request();
