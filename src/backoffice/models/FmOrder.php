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

    const ID_CARRIER = 1;

    /**
     * install the table in the database
     *
     * @return bool
     */
    public function install()
    {
        $tableName = $this->fmPrestashop->getTableName(FmUtils::MODULE_NAME, '_orders', true);
        return (bool)$this->fmPrestashop->dbGetInstance()->Execute(
            'CREATE TABLE IF NOT EXISTS ' . $tableName . ' (
            id int(20) unsigned primary key AUTO_INCREMENT,
            fyndiq_orderid INT(10),
            order_id INT(10));
            CREATE UNIQUE INDEX orderIndex
            ON ' . $tableName . ' (fyndiq_orderid);'
        );
    }

    public function fillAddress($fyndiqOrder, $customerId, $countryId, $alias)
    {
        // Create address
        $address = $this->fmPrestashop->newAddress();
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

        } else {
            $invoiceAddress = $this->fmPrestashop->newAddress();
            $deliveryAddress = $this->fmPrestashop->newAddress();
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

    protected function getSecureKey()
    {
        return md5(uniqid(rand(), true));
    }

    public function createPrestaOrder($cart, $context, $createdDate, $importState)
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
        $prestaOrder->mobile_theme = $cart->mobile_theme;
        $prestaOrder->conversion_rate = (float)$context->currency->conversion_rate;

        $prestaOrder->total_products = (float)$cart->getOrderTotal(
            false,
            $this->fmPrestashop->cartOnlyProducts(),
            $cart->getProducts(),
            self::ID_CARRIER
        );
        $prestaOrder->total_products_wt = (float)$cart->getOrderTotal(
            true,
            $this->fmPrestashop->cartOnlyProducts(),
            $cart->getProducts(),
            self::ID_CARRIER
        );

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
        $prestaOrder->total_paid_tax_excl = 0;
        $prestaOrder->total_paid_tax_incl = $this->fmPrestashop->toolsPsRound(
            (float)$cart->getOrderTotal(
                true,
                $this->fmPrestashop->cartBoth(),
                $cart->getProducts(),
                self::ID_CARRIER
            ),
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

    public function insertOrderDetail($prestaOrder, $cart, $importState)
    {
        // Insert new Order detail list using cart for the current order
        if ($this->fmPrestashop->isPs1516()) {
            $orderDetail = $this->fmPrestashop->newOrderDetail();
            return $orderDetail->createList(
                $prestaOrder,
                $cart,
                $importState,
                $cart->getProducts()
            );
        }
        if ($this->fmPrestashop->version === FmPrestashop::FMPSV14) {
            $result = true;
            foreach ($cart->getProducts() as $product) {
                $orderDetail = $this->fmPrestashop->newOrderDetail();
                $orderDetail->id_order = $prestaOrder->id;
                $orderDetail->product_name = $product['name'];
                $orderDetail->product_quantity = $product['quantity'];
                $orderDetail->product_price = $product['price'];
                $orderDetail->tax_rate = $product['rate'];
                $result &= $orderDetail->add();
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

    public function addOrderMessage($prestaOrderId, $fyndiqOrderId)
    {
        // add Fyndiq delivery note as a message to the order
        $orderMessage = $this->fmPrestashop->newMessage();
        $orderMessage->id_order = $prestaOrderId;
        $orderMessage->private = true;
        $modulePath = $this->fmPrestashop->getModulePath(FmUtils::MODULE_NAME);
        $url = $this->fmPrestashop->getShopUrl() . $modulePath . 'backoffice/delivery_note.php?order_id=' . $fyndiqOrderId;
        $orderMessage->message = 'Fyndiq delivery note: ' . $url . PHP_EOL . 'just copy URL and paste in the browser to download the delivery note.';
        return $orderMessage->add();
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
        $context = $this->fmPrestashop->getOrderContext();

        foreach ($fyndiqOrder->order_rows as &$row) {
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

        foreach ($fyndiqOrder->order_rows as $row) {
            $numArticle = (int)$row->quantity;
            $cart->updateQty($numArticle, $row->productId, $row->combinationId);
        }

        $prestaOrder = $this->createPrestaOrder(
            $cart,
            $context,
            strtotime($fyndiqOrder->created),
            $importState
        );

        // Creating order
        $result = $prestaOrder->add();

        // if result is false the add didn't work and it will throw a exception.
        if (!$result) {
            throw new PrestaShopException(FyndiqTranslation::get('error-save-order'));
        }

        $this->insertOrderDetail($prestaOrder, $cart, $importState);

        if ($this->fmPrestashop->isPs1516()) {
            // create payment in order because Fyndiq handles the payment - so it looks already paid in PrestaShop
            $prestaOrder->addOrderPayment($prestaOrder->total_products_wt, self::FYNDIQ_PAYMENT_METHOD);
        }

        $this->addOrderToHistory($prestaOrder->id, $importState);
        $this->addOrderMessage($prestaOrder->id, $fyndiqOrder->id);

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
        return (bool)$this->fmPrestashop->dbGetInstance()->insert(
            $tableName,
            $data
        );
    }

    public function getImportedOrders($page, $perPage)
    {
        $offset = $perPage * ($page - 1);
        $tableName = $this->fmPrestashop->getTableName(FmUtils::MODULE_NAME, '_orders', true);
        $sqlQuery = 'SELECT * FROM ' . $tableName . ' LIMIT ' . $offset . ', ' . $perPage;
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
        // Check products
        $query = $this->fmPrestashop->newDbQuery();
        $query->select('p.id_product');
        $query->from('product', 'p');
        $query->where('p.reference = \'' . $this->fmPrestashop->dbEscape($productSKU) . '\'');
        $productId = $this->fmPrestashop->dbGetInstance()->getValue($query);
        if ($productId) {
            return array($productId, 0);
        }
        // Check combinations
        $query = $this->fmPrestashop->newDbQuery();
        $query->select('id_product_attribute, id_product');
        $query->from('product_attribute');
        $query->where('reference = \'' . $this->fmPrestashop->dbEscape($productSKU) . '\'');
        $combinationRow = $this->fmPrestashop->dbGetInstance()->getRow($query);
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
