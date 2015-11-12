<?php

class FmConfigTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->fmPrestashop = $this->getMockBuilder('FmPrestashop')
            ->disableOriginalConstructor()
            ->getMock();
        $this->fmPrestashop->method('getStoreId')
            ->willReturn(1);
        $this->config = new FmConfig($this->fmPrestashop);
    }

    public function testDelete()
    {
        $key = 'key';
        $this->fmPrestashop->expects($this->once())
            ->method('configurationDeleteByName')
            ->with(
                $this->equalTo(FmConfig::CONFIG_NAME . '_' . $key)
            )
            ->willReturn(true);

        $result = $this->config->delete($key, 1);
        $this->assertTrue($result);
    }

    public function testGet()
    {
        $key = 'key';
        $this->fmPrestashop->expects($this->once())
            ->method('configurationGet')
            ->with(
                $this->equalTo(FmConfig::CONFIG_NAME . '_' . $key)
            )
            ->willReturn(true);

        $result = $this->config->get($key, 1);
        $this->assertTrue($result);
    }

    public function testSet()
    {
        $key = 'key';
        $value = 'value';
        $this->fmPrestashop->expects($this->once())
            ->method('configurationUpdateValue')
            ->with(
                $this->equalTo(FmConfig::CONFIG_NAME . '_' . $key),
                $this->equalTo($value)
            )
            ->willReturn(true);

        $result = $this->config->set($key, $value, 1);
        $this->assertTrue($result);
    }

    public function testIsAuthorized()
    {
        $this->fmPrestashop->method('configurationGet')->willReturn(true);
        $result = $this->config->isAuthorized(1);
        $this->assertTrue($result);
    }

    public function testIsSetUp()
    {
        $this->fmPrestashop->method('configurationGet')->willReturn(true);
        $result = $this->config->isSetUp(1);
        $this->assertTrue($result);
    }
}
