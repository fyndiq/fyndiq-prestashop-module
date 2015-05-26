<?php

class FmConfigTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->fmPrestashop = $this->getMockBuilder('FmPrestashop')
            ->disableOriginalConstructor()
            ->getMock();
        $this->config = new FmConfig($this->fmPrestashop);
    }

    public function testDelete() {
        $key = 'key';
        $this->fmPrestashop->expects($this->once())
            ->method('configurationDeleteByName')
            ->with(
                $this->equalTo(FmConfig::CONFIG_NAME . '_' . $key)
            )
            ->willReturn(true);

        $result = $this->config->delete($key);
        $this->assertTrue($result);
    }

    public function testGet() {
        $key = 'key';
        $this->fmPrestashop->expects($this->once())
            ->method('configurationGet')
            ->with(
                $this->equalTo(FmConfig::CONFIG_NAME . '_' . $key)
            )
            ->willReturn(true);

        $result = $this->config->get($key);
        $this->assertTrue($result);
    }

    public function testSet() {
        $key = 'key';
        $value = 'value';
        $this->fmPrestashop->expects($this->once())
            ->method('configurationUpdateValue')
            ->with(
                $this->equalTo(FmConfig::CONFIG_NAME . '_' . $key),
                $this->equalTo($value)
            )
            ->willReturn(true);

        $result = $this->config->set($key, $value);
        $this->assertTrue($result);
    }

    public function testIsAuthorized() {
        $this->fmPrestashop->method('configurationGet')->willReturn(true);
        $result = $this->config->isAuthorized();
        $this->assertTrue($result);
    }

    public function testIsSetUp() {
        $this->fmPrestashop->method('configurationGet')->willReturn(true);
        $result = $this->config->isSetUp();
        $this->assertTrue($result);
    }
}
