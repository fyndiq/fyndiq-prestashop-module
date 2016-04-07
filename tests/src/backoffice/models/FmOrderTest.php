<?php

class FmOrderTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->fmPrestashop = $this->getMockBuilder('FmPrestashop')
            ->disableOriginalConstructor()
            ->getMock();

        $this->db = $this->getMockBuilder('stdClass')
            ->setMethods(array('Execute', 'ExecuteS', 'insert', 'getValue', 'getRow'))
            ->getMock();

        $this->dbQuery = $this->getMockBuilder('stdClass')
            ->setMethods(array('select', 'from', 'where'))
            ->getMock();

        $this->fmPrestashop->method('dbGetInstance')->willReturn($this->db);
        $this->fmPrestashop->method('newDbQuery')->willReturn($this->dbQuery);

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
        $fyndiqOrder->delivery_note = 'http://example.com/delivery_note.pdf';

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
            ->setMethods(array('add', 'updateQty', 'isVirtualCart', 'getOrderTotal', 'getProducts', 'delete', 'setOrderDetails'))
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

    public function testCreateAddress()
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
        $expected->phone_mobile = $fyndiqOrder->delivery_phone;

        $address = $this->getAddress();
        $this->fmPrestashop->method('newAddress')->willReturn($address);

        $result = $this->fmOrder->createAddress($fyndiqOrder, $customerId, $countryId, $alias);
        $this->assertEquals($expected->id_country, $result->id_country);
    }
    // public function testInitContext()
    // {
    //     $currencyId = 1;
    //     $countryId = 2;

    //     $expected = new stdClass();
    //     $expected->id_currency = 1;
    //     $expected->id_lang = 1;
    //     $expected->id_customer = 3;
    //     $expected->id_address_invoice = 4;
    //     $expected->id_address_delivery = 4;

    //     $customer = $this->getMockBuilder('stdClass')
    //         ->setMethods(array('getLastCart','getAddresses','getByEmail'))
    //         ->getMock();
    //     $customer->method('getLastCart')->willReturn(1);
    //     $customer->method('getAddresses')->willReturn(array());
    //     $customer->method('getByEmail')->willReturn(true);
    //     $customer->id = 3;

    //     $this->fmOrder = $this->getMockBuilder('fmOrder')
    //         ->setConstructorArgs(array($this->fmPrestashop, null))
    //         ->setMethods(array(
    //             'getCustomer',
    //             'createAddress',
    //         ))
    //         ->getMock();

    //     $this->fmOrder->method('getCustomer')
    //         ->willReturn($customer);

    //     $address = $this->getAddress();
    //     $address->id = 4;
    //     $address->method('add')->willReturn(true);

    //     $this->fmPrestashop->expects($this->once())
    //         ->method('newAddress')
    //         ->willReturn($address);

    //     $this->fmOrder->method('createAddress')
    //         ->willReturn($address);

    //     $context = $this->getMockBuilder('stdClass')
    //         ->getMock();
    //     $this->fmPrestashop->method('contextGetContext')
    //         ->willReturn($context);

    //     $context->cart = $this->getMockBuilder('stdClass')
    //         ->setMethods(array('save','OrderExists','setNoMultishipping'))
    //         ->getMock();

    //     $this->fmPrestashop->method('newCart')
    //         ->willReturn($context->cart);

    //     $this->fmPrestashop->method('isObjectLoaded')
    //         ->willReturn(true);

    //     $context->cart->method('OrderExists')
    //         ->willReturn(false);

    //     $context->country = $this->getCountry();

    //     $context->cart->recyclable = 0;
    //     $context->cart->gift = 0;
    //     $context->cart->id_customer = 3;
    //     $context->cart->secure_key = 'asdas';
    //     $context->cart->id_shop = 1;
    //     $context->cart->id_lang = 1;
    //     $context->cart->id = 0;
    //     $context->cart->id_currency = $currencyId;
    //     $context->cart->id_address_invoice = 4;
    //     $context->cart->id_address_delivery = 4;
    //     $context->currency = $currencyId;
    //     $context->country->id =2;

    //     $this->fmPrestashop->method('configurationGet')
    //         ->willReturn(true);

    //    $context->cart = $this->getMockBuilder('stdClass')
    //         ->setMethods(array('save','OrderExists'))
    //         ->getMock();

    //     $createAddress = $this->getMockBuilder('stdClass')
    //         ->setMethods(array('createAddress'))
    //         ->getMock();
    //     $createAddress->method('createAddress')
    //         ->willReturn(true);

    //     $this->fmPrestashop->method('getCurrency')
    //         ->willReturn(true);

    //     $fyndiqOrder = $this->getFyndiqOrder();
    //     $result = $this->fmOrder->initContext($fyndiqOrder);
    //     $this->assertEquals($expected, $result);
    // }

    // public function testGetNewCustomer()
    // {
    //     $expected = new stdClass();
    //     $expected->id = 3;
    //     $expected->firstname = 'firstname';

    //     $customer = $this->getMockBuilder('stdClass')
    //         ->setMethods(array('getByEmail', 'add'))
    //         ->getMock();

    //     $customer->firstname = 'firstname';
    //     $customer->id = 3;

    //     $customer->expects($this->once())->method('getByEmail')->willReturn(false);
    //     $this->fmPrestashop->method('newCustomer')
    //         ->willReturn($customer);

    //     $result = $this->fmOrder->getCustomer();
    //     $this->assertEquals($expected, $result);
    // }

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

    public function testCreateOrder()
    {
        $this->markTestIncomplete(
            'This test has to be rewritten'
        );
        $fyndiqOrder = $this->getFyndiqOrder();
        $countryId = 1;
        $currencyId = 2;
        $secureKey = 'secure_key';
        $product1Id = 1;
        $product1Comb = 3;
        $product2Id = 2;
        $product2Comb = 4;
        $totalWoTax = 122;
        $skuTypeId = 0;

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
                'updateProductsPrices',
            ))
            ->getMock();

        $this->fmPrestashop->expects($this->once())
            ->method('contextGetContext')
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

        $cart->expects($this->at(2))
            ->method('updateQty')
            ->with(
                $this->equalTo($fyndiqOrder->order_rows[0]->quantity),
                $this->equalTo($product1Id),
                $this->equalTo($product1Comb)
            );

        $cart->expects($this->at(3))
            ->method('updateQty')
            ->willReturn(true);

        $cart->expects($this->any())
            ->method('getProducts')
            ->willReturn(array());

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

        $this->fmOrder->expects($this->once())
            ->method('updateProductsPrices')
            ->with(
                $this->equalTo($fyndiqOrder->order_rows),
                $this->equalTo($cart->getProducts())
            );

        $result = $this->fmOrder->create($fyndiqOrder, 16, 'id_address_delivery', $skuTypeId);

        $this->assertTrue($result);
    }

    public function testAddOrderLog()
    {
        $this->fmPrestashop->expects($this->once())
            ->method('dbInsert')
            ->willReturn(true);
        $result = $this->fmOrder->addOrderLog(1, 2);
        $this->assertTrue($result);
    }

    public function testGetImportedOrders()
    {
        $this->db->expects($this->once())->method('ExecuteS')->willReturn(true);
        $result = $this->fmOrder->getImportedOrders(1, 10);
        $this->assertTrue($result);
    }

    public function testGetTotal()
    {
        $expected = 444;
        $this->fmPrestashop->expects($this->once())
            ->method('getTableName')
            ->willReturn('table_name');
        $this->db->expects($this->once())
            ->method('getValue')
            ->willReturn($expected);
        $result = $this->fmOrder->getTotal();
        $this->assertEquals($expected, $result);
    }

    public function testMarkOrderAsDone()
    {
        $this->fmPrestashop->expects($this->once())
            ->method('markOrderAsDone')
            ->willReturn(true);
        $result = $this->fmOrder->markOrderAsDone(1, 2);
        $this->assertTrue($result);
    }

    public function testGetProductBySKUProduct()
    {
        $sku = 'SKU';
        $skuTypeId = 0;
        $productId = 66;
        $expected = array($productId, 0);

        $this->db->expects($this->once())
            ->method('getValue')
            ->willReturn($productId);

        $result = $this->fmOrder->getProductBySKU($sku, $skuTypeId);
        $this->assertEquals($expected, $result);
    }


    public function testGetProductBySKUCombination()
    {
        $sku = 'SKU';
        $skuTypeId = 0;
        $productId = 66;
        $combinationId = 77;
        $expected = array($productId, $combinationId);

        $this->db->expects($this->once())
            ->method('getValue')
            ->willReturn(false);

        $this->db->expects($this->once())
            ->method('getRow')
            ->willReturn(array(
                'id_product' => $productId,
                'id_product_attribute' => $combinationId
            ));

        $result = $this->fmOrder->getProductBySKU($sku, $skuTypeId);
        $this->assertEquals($expected, $result);
    }


    public function testGetProductBySKUNone()
    {
        $sku = 'SKU';
        $skuTypeId = 0;
        $this->db->expects($this->once())
            ->method('getValue')
            ->willReturn(false);

        $this->db->expects($this->once())
            ->method('getRow')
            ->willReturn(false);
        $this->markTestIncomplete(
            'This test has to be rewritten'
        );
        $result = $this->fmOrder->getProductBySKU($sku, $skuTypeId);
        $this->assertFalse($result);
    }
}
