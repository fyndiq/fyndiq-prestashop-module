<?php

class FmProductExportTest extends PHPUnit_Framework_TestCase
{
    public function setUp() {

        $this->fmPrestashop = $this->getMockBuilder('FmPrestashop')
            ->disableOriginalConstructor()
            ->getMock();

        $this->db = $this->getMockBuilder('stdClass')
            ->setMethods(array('ExecuteS', 'insert', 'update'))
            ->getMock();

        $this->fmPrestashop->expects($this->once())
            ->method('dbGetInstance')
            ->willReturn($this->db);

        $this->fmPrestashop->expects($this->once())
            ->method('getModuleName')
            ->willReturn('test');

        $this->fmProductExport = new FMProductExport($this->fmPrestashop, null);
    }

    public function testProductExist() {
        $productId = 1;

        $this->db->method('ExecuteS')
            ->willReturn(1);

        $result = $this->fmProductExport->productExist($productId);
        $this->assertTrue($result);
    }

    public function testAddProduct() {
        $productId = 1;
        $expPricePercentage = 12;

        $this->db->method('insert')
            ->with(
                $this->equalTo('test_products'),
                $this->equalTo(array(
                    'product_id' => 1,
                    'exported_price_percentage' => 12
                ))
            )
            ->willReturn(true);

        $result = $this->fmProductExport->addProduct($productId, $expPricePercentage);
        $this->assertTrue($result);
    }

    public function testUpdateProduct() {
        $productId = 1;
        $expPricePercentage = 12;

        $this->db->method('update')
            ->with(
                $this->equalTo('test_products'),
                $this->equalTo(array(
                    'exported_price_percentage' => 12
                )),
                $this->equalTo('product_id = 1'),
                $this->equalTo(1)
            )
            ->willReturn(true);

        $result = $this->fmProductExport->updateProduct($productId, $expPricePercentage);
        $this->assertTrue($result);
    }
}
