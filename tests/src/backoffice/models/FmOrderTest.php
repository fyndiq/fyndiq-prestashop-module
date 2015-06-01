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

}
