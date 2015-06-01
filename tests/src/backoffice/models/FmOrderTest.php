<?php

class FmOrderTest extends PHPUnit_Framework_TestCase
{

    protected function setUp()
    {
        $this->fmPrestashop = $this->getMockBuilder('FmPrestashop')
            ->disableOriginalConstructor()
            ->getMock();

        $this->db = $this->getMockBuilder('stdClass')
            ->setMethods(array('Execute'))
            ->getMock();

        $this->fmOrder = new FmOrder($this->fmPrestashop, null);
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

        $this->fmPrestashop->method('dbGetInstance')
            ->willReturn($this->db);
        $this->db->method('Execute')->willReturn(true);

        $result = $this->fmOrder->install();
        $this->assertTrue($result);
    }

    public function testFillAddress() {
        $customerId = 1;
        $countryId = 2;
        $alias = 'alias';

        $fyndiqOrder = new stdClass();
        $fyndiqOrder->delivery_firstname = 'delivery_firstname';
        $fyndiqOrder->delivery_lastname = 'delivery_lastname';
        $fyndiqOrder->delivery_phone = 'delivery_phone';
        $fyndiqOrder->delivery_address = 'delivery_address';
        $fyndiqOrder->delivery_postalcode = 'delivery_postalcode';
        $fyndiqOrder->delivery_city = 'delivery_city';
        $fyndiqOrder->delivery_co = 'delivery_co';

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
}
