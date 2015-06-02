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
            ->setMethods(array('add', 'update'))
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
        $orderHistory = $this->getMockBuilder('OrderHistory')
            ->setMethods(array('add'))
            ->getMock();
        return $orderHistory;
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
        $this->fmOrder = $this->getMockBuilder('fmOrder')
            ->setConstructorArgs(array($this->fmPrestashop, null))
            ->setMethods(array('getProductBySKU', 'getCart', 'addOrderLog'))
            ->getMock();

        $this->fmPrestashop->expects($this->once())
            ->method('getOrderContext')
            ->willReturn((object)array(
                'country' => (object)array(
                    'id' => 1
                ),
                'currency' => (object)array(
                    'id' => 2,
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


        $fmPrestaOrder = $this->getPrestaOrder();

        $message = $this->getMessage();

        $fmPrestaOrder->method('add')
            ->willReturn(true);

        $this->fmPrestashop->method('newPrestashopOrder')
            ->willReturn($fmPrestaOrder);

        $this->fmPrestashop->method('newCustomer')
            ->willReturn($customer);

        $this->fmPrestashop->method('newMessage')
            ->willReturn($message);

        $orderHistory = $this->getOrderHistory();
        $this->fmPrestashop->method('newOrderHistory')
            ->willReturn($orderHistory);

        $this->fmOrder->method('getProductBySKU')
            ->willReturn(array(1,2));

        $cart = $this->getCart();

        $cart->method('getOrderTotal')
            ->willReturn(6.66);

        $cart->method('getProducts')
            ->willReturn(array());

        $this->fmOrder->method('getCart')
            ->willReturn($cart);



        $fyndiqOrder = $this->getFyndiqOrder();
        $result = $this->fmOrder->create($fyndiqOrder, 16, 17);
        $this->assertTrue($result);
    }
}
