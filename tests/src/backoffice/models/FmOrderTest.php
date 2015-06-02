<?php

class FmOrderTest extends PHPUnit_Framework_TestCase
{

    protected function setUp()
    {
        $this->fmPrestashop = $this->getMockBuilder('FmPrestashop')
            ->disableOriginalConstructor()
            ->getMock();

        $this->db = $this->getMockBuilder('stdClass')
            ->setMethods(array('Execute', 'ExecuteS'))
            ->getMock();

        $this->fmPrestashop->method('dbGetInstance')->willReturn($this->db);

        $this->fmOrder = new FmOrder($this->fmPrestashop, null);
    }

    private function getFyndiqOrder()
    {
        $fyndiqOrder = new stdClass();
        $fyndiqOrder->id = 666;
        $fyndiqOrder->delivery_firstname = 'delivery_firstname';
        $fyndiqOrder->delivery_lastname = 'delivery_lastname';
        $fyndiqOrder->delivery_phone = 'delivery_phone';
        $fyndiqOrder->delivery_address = 'delivery_address';
        $fyndiqOrder->delivery_postalcode = 'delivery_postalcode';
        $fyndiqOrder->delivery_city = 'delivery_city';
        $fyndiqOrder->delivery_co = 'delivery_co';
        $fyndiqOrder->created = '2014-01-02 03:04:05';

        $fyndiqOrder->order_rows = array(
            (object)array(
                'sku' => 1,
                'quantity' => 3,
            ),
            (object)array(
                'sku' => 2,
                'quantity' => 4,
            )
        );
        return $fyndiqOrder;
    }

    private function getPrestaOrder()
    {
        $prestaOrder = $this->getMockBuilder('stdClass')
            ->setMethods(array('add', 'update', 'addOrderPayment'))
            ->getMock();
        $prestaOrder->id = 1;
        return $prestaOrder;
    }

    private function getMessage()
    {
        $message = $this->getMockBuilder('stdClass')
            ->setMethods(array('add'))
            ->getMock();
        return $message;
    }

    private function getCart()
    {
        $cart = $this->getMockBuilder('stdClass')
            ->setMethods(array('add', 'updateQty', 'isVirtualCart', 'getOrderTotal', 'getProducts'))
            ->getMock();
        $cart->id_customer = 10;
        $cart->id_address_invoice = 11;
        $cart->id_address_delivery = 12;
        $cart->id_currency = 13;
        $cart->id_lang = 14;
        $cart->id = 15;
        $cart->recyclable = true;
        $cart->gift = false;
        $cart->gift_message = 'gift_message';
        $cart->mobile_theme = false;
        return $cart;
    }

    private function getOrderHistory()
    {
        $orderHistory = $this->getMockBuilder('stdClass')
            ->setMethods(array('add'))
            ->getMock();
        return $orderHistory;
    }

    private function getOrderDetail()
    {
        $orderDetail = $this->getMockBuilder('stdClass')
            ->setMethods(array('createList', 'add'))
            ->getMock();
        return $orderDetail;
    }

    private function getAddress()
    {
        $address = $this->getMockBuilder('stdClass')
            ->setMethods(array('add'))
            ->getMock();
        $address->id_country = 1;
        return $address;
    }

    private function getCountry()
    {
        $country = $this->getMockBuilder('stdClass')
            ->getMock();
        $country->active = true;
        return $country;
    }

    public function testInstall()
    {
        $this->fmPrestashop->method('getTableName')
            ->with(
                $this->equalTo(FmUtils::MODULE_NAME),
                $this->equalTo('_orders'),
                $this->equalTo(true)
            )
            ->willReturn('table');

        $this->db->method('Execute')->willReturn(true);

        $result = $this->fmOrder->install();
        $this->assertTrue($result);
    }

    public function testFillAddress()
    {
        $customerId = 1;
        $countryId = 2;
        $alias = 'alias';

        $fyndiqOrder = $this->getFyndiqOrder();

        $expected = new stdClass();
        $expected->firstname = $fyndiqOrder->delivery_firstname;
        $expected->lastname = $fyndiqOrder->delivery_lastname;
        $expected->phone = $fyndiqOrder->delivery_phone;
        $expected->address1 = $fyndiqOrder->delivery_address;
        $expected->postcode = $fyndiqOrder->delivery_postalcode;
        $expected->city = $fyndiqOrder->delivery_city;
        $expected->company = $fyndiqOrder->delivery_co;
        $expected->id_country = $countryId;
        $expected->id_customer = $customerId;
        $expected->alias = $alias;

        $this->fmPrestashop->expects($this->once())
            ->method('newAddress')
            ->willReturn(new stdClass());

        $result = $this->fmOrder->fillAddress($fyndiqOrder, $customerId, $countryId, $alias);
        $this->assertEquals($expected, $result);
    }

    public function testGetCart()
    {
        $currencyId = 1;
        $countryId = 2;

        $expected = new stdClass();
        $expected->id_currency = 1;
        $expected->id_lang = 1;
        $expected->id_customer = 3;
        $expected->id_address_invoice = 4;
        $expected->id_address_delivery = 4;

        $address = $this->getAddress();

        $address->id = 4;
        $address->method('add')->willReturn(true);

        $this->fmPrestashop->method('newCart')
            ->willReturn(new stdClass());

        $this->fmPrestashop->method('newAddress')
            ->willReturn($address);

        $customer = $this->getMockBuilder('stdClass')
            ->setMethods(array('getByEmail', 'add',))
            ->getMock();

        $customer->firstname = null;
        $customer->id = 3;

        $customer->expects($this->once())->method('getByEmail')->willReturn(false);
        $this->fmPrestashop->method('newCustomer')
            ->willReturn($customer);

        $fyndiqOrder = $this->getFyndiqOrder();
        $result = $this->fmOrder->getCart($fyndiqOrder, $currencyId, $countryId);
        $this->assertEquals($expected, $result);
    }

    public function testGetCartNewCustomer()
    {
        $currencyId = 1;
        $countryId = 2;

        $expected = new stdClass();
        $expected->id_currency = 1;
        $expected->id_lang = 1;
        $expected->id_customer = 3;
        $expected->id_address_invoice = 6;
        $expected->id_address_delivery = 6;

        $address = $this->getMockBuilder('stdClass')
            ->setMethods(array('add'))
            ->getMock();

        $address->id = 4;
        $address->method('add')->willReturn(true);

        $this->fmPrestashop->method('newCart')
            ->willReturn(new stdClass());

        $this->fmPrestashop->method('newAddress')
            ->willReturn($address);

        $customer = $this->getMockBuilder('stdClass')
            ->setMethods(array('getByEmail', 'add', 'getAddresses'))
            ->getMock();

        $customer->firstname = 'firstname';
        $customer->id = 3;
        $customer->expects($this->once())
            ->method('getAddresses')
            ->willReturn(array(
                array(
                    'id_address' => 6,
                    'alias' => FmOrder::FYNDIQ_ORDERS_DELIVERY_ADDRESS_ALIAS
                ),
                array(
                    'alias' => FmOrder::FYNDIQ_ORDERS_INVOICE_ADDRESS_ALIAS
                ),
            ));
        $customer->expects($this->once())->method('getByEmail')->willReturn(false);
        $this->fmPrestashop->method('newCustomer')
            ->willReturn($customer);

        $fyndiqOrder = $this->getFyndiqOrder();
        $result = $this->fmOrder->getCart($fyndiqOrder, $currencyId, $countryId);
        $this->assertEquals($expected, $result);
    }

    public function testOrderExists()
    {
        $this->fmPrestashop->expects($this->once())
            ->method('getTableName')
            ->willReturn('table');

        $this->db->expects($this->once())
            ->method('ExecuteS')
            ->willReturn(true);
        $result = $this->fmOrder->orderExists(3);
        $this->assertTrue($result);
    }

    public function testCreate()
    {
        $fyndiqOrder = $this->getFyndiqOrder();
        $countryId = 1;
        $currencyId = 2;
        $secureKey = 'secure_key';
        $product1Id = 1;
        $product1Comb = 3;
        $product2Id = 2;
        $product2Comb = 4;
        $totalWoTax = 122;


        $this->fmOrder = $this->getMockBuilder('fmOrder')
            ->setConstructorArgs(array($this->fmPrestashop, null))
            ->setMethods(array(
                'getProductBySKU',
                'getCart',
                'createPrestaOrder',
                'insertOrderDetail',
                'addOrderToHistory',
                'addOrderMessage',
                'addOrderLog',
            ))
            ->getMock();

        $this->fmPrestashop->expects($this->once())
            ->method('getOrderContext')
            ->willReturn((object)array(
                'country' => (object)array(
                    'id' => $countryId
                ),
                'currency' => (object)array(
                    'id' => $currencyId,
                    'conversion_rate' => 1.2
                ),
                'shop' => (object)array(
                    'id' => 3,
                    'id_shop_group' => 4,
                )
            ));

        $customer = $this->getMockBuilder('stdClass')
            ->setMethods(array('getByEmail'))
            ->getMock();

        $customer->method('getByEmail')
            ->with(
                $this->equalTo(FmOrder::FYNDIQ_ORDERS_EMAIL)
            );

        $prestaOrder = $this->getPrestaOrder();
        $prestaOrder->total_products_wt = $totalWoTax;

        $country = $this->getCountry();

        $cart = $this->getCart();

        $cart->expects($this->at(1))
            ->method('updateQty')
            ->with(
                $this->equalTo($fyndiqOrder->order_rows[0]->quantity),
                $this->equalTo($product1Id),
                $this->equalTo($product1Comb)
            );

        $cart->expects($this->at(2))
            ->method('updateQty')
            ->willReturn(true);

        // $cart->expects($this->once())
        //     ->method('getProducts')
        //     ->willReturn(array());

        $this->fmOrder->expects($this->once())
            ->method('getCart')
            ->with(
                $this->equalTo($fyndiqOrder),
                $this->equalTo($currencyId),
                $this->equalTo($countryId)
            )
            ->willReturn($cart);

        $prestaOrder->expects($this->once())
            ->method('add')
            ->willReturn(true);

        $this->fmPrestashop->method('isPs1516')
            ->willReturn(true);

        $this->fmPrestashop->expects($this->once())
            ->method('newCustomer')
            ->willReturn($customer);

        $address = $this->getAddress();

        $this->fmPrestashop->expects($this->once())
            ->method('newAddress')
            ->with(
                $this->equalTo($cart->id_address_delivery)
            )
            ->willReturn($address);

        $this->fmPrestashop->expects($this->once())
            ->method('newCountry')
            ->with(
                $this->equalTo($address->id_country),
                $this->equalTo($cart->id_lang)
            )
            ->willReturn($country);

        $this->fmOrder->expects($this->at(0))
            ->method('getProductBySKU')
            ->with(
                $this->equalTo($fyndiqOrder->order_rows[0]->sku)
            )
            ->willReturn(array($product1Id, $product1Comb));

        $this->fmOrder->expects($this->at(1))
            ->method('getProductBySKU')
            ->with(
                $this->equalTo($fyndiqOrder->order_rows[1]->sku)
            )
            ->willReturn(array($product2Id, $product2Comb));


        $this->fmOrder->expects($this->once())
            ->method('createPrestaOrder')
            ->willReturn($prestaOrder);

        $this->fmOrder->expects($this->once())
            ->method('addOrderLog')
            ->with(
                $this->equalTo(1),
                $this->equalTo(666)
            )
            ->willReturn(true);

        $result = $this->fmOrder->create($fyndiqOrder, 16, 'id_address_delivery');
        $this->assertTrue($result);
    }


    function testCreatePrestaOrder()
    {
        $countryId = 1;
        $currencyId = 2;
        $importState = 3;
        $secureKey = 'secret_key';

        $expected = (object)array(
            'id_carrier' => FmOrder::ID_CARRIER,
            'id_customer' => 10,
            'id_address_invoice' => 11,
            'id_address_delivery' => 12,
            'id_currency' => 13,
            'id_lang' => 14,
            'id_cart' => 15,
            'id_shop' => 3,
            'id_shop_group' => 4,
            'secure_key' => 'secret_key',
            'payment' => 'Fyndiq',
            'module' => FmUtils::MODULE_NAME,
            'recyclable' => true,
            'current_state' => 3,
            'gift' => 0,
            'gift_message' => 'gift_message',
            'mobile_theme' => false,
            'conversion_rate' => 1.2,
            'total_products' => 0.0,
            'total_products_wt' => 0.0,
            'total_discounts_tax_excl' => 0.0,
            'total_discounts_tax_incl' => 0.0,
            'total_discounts' => 0.0,
            'total_shipping' => 0.0,
            'total_wrapping_tax_excl' => 0,
            'total_wrapping_tax_incl' => 0,
            'total_wrapping' => 0,
            'total_paid_tax_excl' => 0,
            'total_paid_tax_incl' => null,
            'total_paid_real' => 0.0,
            'total_paid' => 0.0,
            'total_shipping_tax_excl' => 0.0,
            'total_shipping_tax_incl' => 0.0,
            'invoice_date' => '2000-01-02 03:04:05',
            'delivery_date' => '2001-02-03 04:05:06'
        );

        $context = (object)array(
            'country' => (object)array(
                'id' => $countryId
            ),
            'currency' => (object)array(
                'id' => $currencyId,
                'conversion_rate' => 1.2
            ),
            'shop' => (object)array(
                'id' => 3,
                'id_shop_group' => 4,
            )
        );
        $createdDate = strtotime('2000-01-02 03:04:05');

        $prestaOrder = new stdClass();
        $this->fmPrestashop->method('newPrestashopOrder')
            ->willReturn($prestaOrder);

        $cart = $this->getCart();

        $this->fmOrder = $this->getMockBuilder('fmOrder')
            ->setConstructorArgs(array($this->fmPrestashop, null))
            ->setMethods(array('getSecureKey'))
            ->getMock();
        $this->fmOrder->expects($this->once())
            ->method('getSecureKey')
            ->willReturn($secureKey);

        $this->fmPrestashop->method('isPs1516')
            ->willReturn(true);

        $this->fmPrestashop->expects($this->once())
            ->method('time')
            ->willReturn(strtotime('2001-02-03 04:05:06'));

        $result = $this->fmOrder->createPrestaOrder($cart, $context, $createdDate, $importState);
        $this->assertEquals($expected, $result);
    }

    function testInsertOrderDetail()
    {
        $prestaOrder = $this->getPrestaOrder();
        $cart = $this->getCart();
        $importState = 'import_state';
        $products = array(1, 2, 3);

        $cart->expects($this->once())
            ->method('getProducts')
            ->willReturn($products);

        $orderDetail = $this->getOrderDetail();

        $orderDetail->expects($this->once())
            ->method('createList')
            ->with(
                $this->equalTo($prestaOrder),
                $this->equalTo($cart),
                $this->equalTo($importState),
                $this->equalTo($products)
            )
            ->willReturn(true);

        $this->fmPrestashop->expects($this->once())
            ->method('newOrderDetail')
            ->willReturn($orderDetail);

        $this->fmPrestashop->expects($this->once())
            ->method('isPs1516')
            ->willReturn(true);

        $result = $this->fmOrder->insertOrderDetail($prestaOrder, $cart, $importState);
        $this->assertTrue($result);
    }

    function testInsertOrderDetailPS14()
    {
        $prestaOrder = $this->getPrestaOrder();
        $cart = $this->getCart();
        $importState = 'import_state';
        $products = array(1);

        $cart->expects($this->once())
            ->method('getProducts')
            ->willReturn($products);

        $orderDetail = $this->getOrderDetail();

        $orderDetail->expects($this->once())
            ->method('add')
            ->willReturn(true);

        $this->fmPrestashop->expects($this->once())
            ->method('newOrderDetail')
            ->willReturn($orderDetail);

        $this->fmPrestashop->expects($this->once())
            ->method('isPs1516')
            ->willReturn(false);
        $this->fmPrestashop->version = FmPrestashop::FMPSV14;

        $result = $this->fmOrder->insertOrderDetail($prestaOrder, $cart, $importState);
        $this->assertTrue($result);
    }

    function testInsertOrderDetailWrongVersion()
    {
        $result = $this->fmOrder->insertOrderDetail(null, null, null);
        $this->assertFalse($result);
    }

    function testAddOrderToHistory()
    {
        $orderHistory = $this->getOrderHistory();
        $orderHistory->expects($this->once())
            ->method('add')
            ->willReturn(true);

        $this->fmPrestashop->expects($this->once())
            ->method('newOrderHistory')
            ->willReturn($orderHistory);

        $result = $this->fmOrder->addOrderToHistory(1, 2);
        $this->assertTrue($result);
    }

    function testAddOrderMessage()
    {
        $message = $this->getMessage();

        $message->expects($this->once())
            ->method('add')
            ->willReturn(true);

        $this->fmPrestashop->expects($this->once())
            ->method('newMessage')
            ->willReturn($message);

        $result = $this->fmOrder->addOrderMessage(1, 2);
        $this->assertTrue($result);
    }
}
