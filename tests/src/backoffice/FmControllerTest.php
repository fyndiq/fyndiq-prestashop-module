<?php

class FmControllerTest extends PHPUnit_Framework_TestCase
{
    function testHandleRequest()
    {
        $module = $this->getMockBuilder('stdClass')
            ->setMethods(array('get', 'display'))
            ->getMock();

        $module->name = 'testmodule';
        $module->context = new stdClass();
        $module->context->smarty = $this->getMockBuilder('stdClass')
            ->setMethods(array('assign', 'registerPlugin'))
            ->getMock();

        $module->method('get')->willReturn('test');
        $module->method('display')->willReturn(true);

        $prestashop = $this->getMockBuilder('FmPrestashop')
            ->getMock();

        $config = $this->getMockBuilder('FmConfig')
            ->disableOriginalConstructor()
            ->getMock();

        $controller = new FmController($module, $config, $prestashop);
        $result = $controller->handleRequest();
        $this->assertTrue($result);
    }
}
