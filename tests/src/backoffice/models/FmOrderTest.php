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
                'unit_price_amount' => 3,
            ),
            (object)array(
                'sku' => 2,
                'quantity' => 4,
                'unit_price_amount' => 3,
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

    private function getContext()
    {
        $context = $this->getMockBuilder('stdClass')
            ->getMock();
        $this->fmPrestashop->method('contextGetContext')
            ->willReturn($context);

        $context->cart = $this->getMockBuilder('stdClass')
            ->setMethods(array('save', 'OrderExists', 'setNoMultishipping'))
            ->getMock();

        $context->cart->id_customer = 3;
        $context->cart->id_lang = 1;
        $context->cart->id_currency = 1;
        $context->cart->id_address_invoice = 4;
        $context->cart->id_address_delivery = 4;
        return $context;
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
        $expected->phone_mobile = $fyndiqOrder->delivery_phone;

        $address = $this->getAddress();
        $this->fmPrestashop->method('newAddress')->willReturn($address);

        $result = $this->fmOrder->fillAddress($fyndiqOrder, $customerId, $countryId, $alias);
        foreach ($result as $property => $value) {
            $this->assertEquals($expected->{$property}, $value);
        }
    }

    public function testcreatePrestaOrder()
    {
        $fyndiqOrder = $this->getFyndiqOrder();
        $importState = 1;
        $idCart = 1;

        $this->fmOrder = $this->getMockBuilder('fmOrder')
            ->setConstructorArgs(array($this->fmPrestashop, null))
            ->setMethods(array(
                'getOrderMessage',
                'addOrderLog',
                'newFmPaymentModule',
                'getOrderTotal'
            ))
            ->getMock();

        $paymentModule = $this->getMockBuilder('stdClass')
            ->setMethods(array('validateOrder'))
            ->getMock();

        $paymentModule->displayName = 'Fyndiq';
        $paymentModule->currentOrder = 1;

        $cart = $this->getMockBuilder('stdClass')
            ->setMethods(array('getOrderTotal'))
            ->getMock();

        $cart->id= 1;
        $cart->id_address_delivery = 1;
        $cart->id_address_invoice = 1;
        $cart->secure_key = 5;
        $cart->id_currency = 1;
        $cart->id_customer = 2;

        $this->fmOrder->method('newFmPaymentModule')
            ->willReturn($paymentModule);
        
        $this->fmOrder->method('getOrderTotal')
            ->willReturn(1);

        $context = $this->getMockBuilder('stdClass')
            ->getMock();

        $cartBoth = $this->getMockBuilder('stdClass')
            ->getMock();
        
        $this->fmPrestashop->method('newPrestashopOrder')
            ->willReturn($this->getMockBuilder('stdClass'));

        $this->fmPrestashop->method('contextGetContext')
            ->willReturn($context);

        $this->fmPrestashop->method('cartBoth')
            ->willReturn($cartBoth);

        $this->fmPrestashop->method('newCart')
               ->with(
                   $this->equalTo($idCart)
               )
            ->willReturn($cart);

        $this->fmPrestashop->method('getCurrency')
               ->with(
                   $this->equalTo(1)
               )
            ->willReturn(true);

        $this->fmPrestashop->method('newCustomer')
               ->with(
                   $this->equalTo(2)
               )
            ->willReturn(true);

        $this->fmPrestashop->method('isValidAddress')
               ->with(
                   $this->equalTo(1),
                   $this->equalTo(1)
               )
            ->willReturn(true);

        $cart->method('getOrderTotal')
            ->with(
                $this->equalTo(true),
                $this->equalTo($cartBoth)
            )
            ->willReturn(500);


        $this->fmOrder
            ->method('getOrderMessage')
            ->with(
                $this->equalTo($fyndiqOrder->id),
                $this->equalTo($fyndiqOrder->delivery_note)
            )
            ->willReturn("sampleMessage");

        $paymentModule->method('validateOrder')
            ->with(
                $this->equalTo(1),
                $this->equalTo(1),
                $this->equalTo(500),
                $this->equalTo('Fyndiq'),
                $this->equalTo('sampleMessage'),
                $this->equalTo(array()),
                $this->equalTo(null),
                $this->equalTo(false),
                $this->equalTo(5)
            )
            ->willReturn(true);

        $this->fmOrder
            ->method('addOrderLog')
            ->with(
                $this->equalTo(1),
                $this->equalTo($fyndiqOrder->id)
            )
            ->willReturn(true);
        
        $cartProducts = "";
        $createdDate = date('Y-m-d H:i:s',time());
        $result = $this->fmOrder->createPrestaOrder($cart, $context, $cartProducts, $createdDate, $importState);
        $this->assertNotNull($result);
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
}
