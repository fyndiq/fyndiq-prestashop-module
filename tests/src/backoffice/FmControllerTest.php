<?php

class FmControllerTest extends PHPUnit_Framework_TestCase
{
    protected function setUp(){
        $this->fmPrestashop = $this->getMockBuilder('FmPrestashop')
            ->getMock();

        $this->fmPrestashop->method('getCurrency')->willReturn('ZWL');
        $this->fmPrestashop->method('getModuleUrl')->willReturn('http://localhost/module');

        $this->fmConfig = $this->getMockBuilder('FmConfig')
            ->disableOriginalConstructor()
            ->getMock();

        $this->fmConfig->method('isAuthorized')->willReturn(true);
        $this->fmConfig->method('isSetUp')->willReturn(true);


        $this->fmOutput = $this->getMockBuilder('FmOutput')
            ->disableOriginalConstructor()
            ->getMock();

        $this->controller = new FmController($this->fmPrestashop, $this->fmOutput, $this->fmConfig);
    }

    public function testHandleRequestMain()
    {
        $this->fmPrestashop->method('toolsGetValue')->willReturn('main');

        $this->fmOutput->expects($this->once())
            ->method('render')
            ->with(
                $this->equalTo('main'),
                $this->equalTo(array(
                    'json_messages' => '[]',
                    'messages' => array(),
                    'path' => 'http://localhost/module',
                    'currency' => 'ZWL',
                ))
            )
            ->willReturn(true);

        $result = $this->controller->handleRequest();
        $this->assertTrue($result);
    }

    public function testHandleRequestApiUnavailable()
    {
        $this->fmPrestashop->method('toolsGetValue')->willReturn('api_unavailable');

        $this->fmOutput->expects($this->once())
            ->method('render')
            ->with(
                $this->equalTo('api_unavailable'),
                $this->equalTo(array(
                    'json_messages' => '[]',
                    'messages' => array(),
                    'path' => 'http://localhost/module',
                ))
            )
            ->willReturn(true);

        $result = $this->controller->handleRequest();
        $this->assertTrue($result);
    }
}
