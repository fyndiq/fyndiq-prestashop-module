<?php

class FmProductExportTest extends PHPUnit_Framework_TestCase
{
    public function setUp() {

        $this->fmPrestashop = $this->getMockBuilder('FmPrestashop')
            ->disableOriginalConstructor()
            ->getMock();

        $this->fmProductExport = new FMProductExport($this->fmPrestashop, null);
    }

    public function testProductExist() {
        $productId = 1;

        $db = $this->getMockBuilder('stdClass')
            ->setMethods(array('ExecuteS'))
            ->getMock();

        $db->method('ExecuteS')
            ->with(
                $this->equalTo('SELECT product_id FROM test_products WHERE product_id=\'1\' LIMIT 1')
            )
            ->willReturn(1);

        $this->fmPrestashop->expects($this->once())
            ->method('dbGetInstance')
            ->willReturn($db);

        $this->fmPrestashop->expects($this->once())
            ->method('getModuleName')
            ->willReturn('test');

        $result = $this->fmProductExport->productExist($productId);
        $this->assertTrue($result);
    }
}
