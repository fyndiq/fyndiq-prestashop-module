<?php

class FmProductTest extends PHPUnit_Framework_TestCase
{

    protected function setUp()
    {
        $this->fmPrestashop = $this->getMockBuilder('FmPrestashop')
            ->disableOriginalConstructor()
            ->getMock();

        $this->fmProduct = new FmProduct($this->fmPrestashop, null);
    }

    public function testGetByCategory()
    {
        $categoryId = 1;
        $page = 2;
        $perPage = 3;

        $data = array(
            array(1 => '2')
        );

        $db = $this->getMockBuilder('stdClass')
            ->setMethods(array('ExecuteS'))
            ->getMock();

        $db->expects($this->once())
            ->method('ExecuteS')
            ->willReturn($data);

        $this->fmPrestashop->expects($this->once())
            ->method('dbGetInstance')
            ->willReturn($db);

        $result = $this->fmProduct->getByCategory($categoryId, $page, $perPage);
        $this->assertEquals($data, $result);
    }

    public function testGetAmount()
    {
        $categoryId = 1;
        $data = 2;

        $db = $this->getMockBuilder('stdClass')
            ->setMethods(array('getValue'))
            ->getMock();

        $db->expects($this->once())
            ->method('getValue')
            ->willReturn($data);

        $this->fmPrestashop->expects($this->once())
            ->method('dbGetInstance')
            ->willReturn($db);

        $result = $this->fmProduct->getAmount($categoryId);
        $this->assertEquals($data, $result);
    }
}
