<?php

class FmProductExportTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->storeId = 1;

        $this->fmPrestashop = $this->getMockBuilder('FmPrestashop')
            ->disableOriginalConstructor()
            ->getMock();

        $this->db = $this->getMockBuilder('stdClass')
            ->setMethods(array('ExecuteS', 'insert', 'update', 'delete', 'getRow', 'Execute'))
            ->getMock();

        $this->fmPrestashop
            ->method('dbGetInstance')
            ->willReturn($this->db);

        $this->fmPrestashop->method('getTableName')
            ->willReturn('test_products');

        $this->fmProductExport = $this->getMockBuilder('FmProductExport')
            ->setMethods(array('getFyndiqProducts', 'getContext'))
            ->setConstructorArgs(array($this->fmPrestashop, null))
            ->getMock();
    }

    public function testDeleteProduct()
    {
        $productId = 1;

        $this->fmPrestashop->expects($this->once())
            ->method('dbDelete')
            ->willReturn(true);

        $result = $this->fmProductExport->deleteProduct($productId, $this->storeId);
        $this->assertTrue($result);
    }

    public function testGetProduct()
    {
        $productId = 1;
        $data = array('test' => 'one');

        $this->db->method('getRow')
            ->willReturn($data);

        $result = $this->fmProductExport->getProduct($productId, $this->storeId);
        $this->assertEquals($data, $result);
    }

    public function testInstall()
    {
        $path = '/tmp/';
        $this->db->method('Execute')
            ->willReturn(true);

        $this->fmPrestashop->expects($this->once())
            ->method('getExportPath')
            ->willReturn($path);

        $this->fmPrestashop->expects($this->once())
            ->method('forceCreateDir')
            ->with(
                $this->equalTo($path),
                $this->equalTo(0775)
            )
            ->willReturn(true);

        $result = $this->fmProductExport->install();
        $this->assertTrue($result);
    }

    public function testUninstall()
    {
        $this->db->method('Execute')
            ->willReturn(true);

        $result = $this->fmProductExport->uninstall();
        $this->assertTrue($result);
    }
}
