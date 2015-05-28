<?php

class FmServiceControllerTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->fmPrestashop = $this->getMockBuilder('FmPrestashop')
            ->disableOriginalConstructor()
            ->getMock();
        $this->fmOutput = $this->getMockBuilder('FmOutput')
            ->disableOriginalConstructor()
            ->getMock();
        $this->fmConfig = $this->getMockBuilder('FmConfig')
            ->disableOriginalConstructor()
            ->getMock();
        $this->fmApiModel = $this->getMockBuilder('FmApiModel')
            ->disableOriginalConstructor()
            ->getMock();

        $this->controller = new FmServiceController(
            $this->fmPrestashop,
            $this->fmOutput,
            $this->fmConfig,
            $this->fmApiModel
        );
    }

    public function testHandleRequestNoAction() {
        $this->fmOutput->expects($this->once())
            ->method('showError')
            ->with(
                $this->equalTo(400),
                $this->equalTo('Bad Request'),
                $this->equalTo('400 Bad Request')
            )
            ->willReturn(true);
        $result = $this->controller->handleRequest(array());
        $this->assertTrue($result);
    }
}
