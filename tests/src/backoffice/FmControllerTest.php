<?php

class FmControllerTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->fmPrestashop = $this->getMockBuilder('FmPrestashop')
            ->disableOriginalConstructor()
            ->getMock();

        $this->fmApiModel = $this->getMockBuilder('FmApiModel')
            ->disableOriginalConstructor()
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

        $this->controller = new FmController($this->fmPrestashop, $this->fmOutput, $this->fmConfig, $this->fmApiModel);
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

    public function testHandleRequestAuthenticate()
    {
        $this->fmPrestashop->method('toolsGetValue')->willReturn('authenticate');

        $this->fmOutput->expects($this->once())
            ->method('render')
            ->with(
                $this->equalTo('authenticate'),
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

    public function testHandleRequestAuthenticateSaveEmptyFields()
    {
        $this->fmPrestashop->expects($this->at(0))
            ->method('toolsGetValue')
            ->willReturn('authenticate');
        $this->fmPrestashop->expects($this->at(1))
            ->method('toolsGetValue')
            ->willReturn('');
        $this->fmPrestashop->expects($this->at(2))
            ->method('toolsGetValue')
            ->willReturn('');

        $this->fmPrestashop->method('toolsIsSubmit')->willReturn(true);

        $this->fmOutput->expects($this->once())
            ->method('showModuleError')
            ->with(
                $this->equalTo('NT: empty-username-token')
            )
            ->willReturn(true);

        $result = $this->controller->handleRequest();
        $this->assertTrue($result);
    }

    public function testHandleRequestAuthenticateSaveException()
    {
        $this->fmPrestashop->method('toolsGetValue')->willReturn('authenticate');
        $this->fmPrestashop->method('toolsIsSubmit')->willReturn(true);
        $this->fmApiModel->expects($this->once())
            ->method('callApi')
            ->with(
                $this->equalTo('PATCH'),
                $this->equalTo('settings/'),
                $this->equalTo(
                    array(
                    FyndiqUtils::NAME_PRODUCT_FEED_URL =>
                    'modules/fyndiqmerchant/backoffice/filePage.php',
                    FyndiqUtils::NAME_NOTIFICATION_URL =>
                    'modules/fyndiqmerchant/backoffice/notification_service.php?event=order_created',
                    FyndiqUtils::NAME_PING_URL =>
                    'modules/fyndiqmerchant/backoffice/notification_service.php?event=ping&token='
                    )
                ),
                $this->equalTo('authenticate'),
                $this->equalTo('authenticate')
            )
            ->will(
                $this->throwException(new Exception('Test Exception'))
            );

        $this->fmOutput->expects($this->once())
            ->method('showModuleError')
            ->with(
                $this->equalTo('Test Exception')
            )
            ->willReturn(true);

        $result = $this->controller->handleRequest();
        $this->assertTrue($result);
    }

    public function testHandleRequestAuthenticateSaveSuccess()
    {
        $this->fmPrestashop->method('toolsGetValue')->willReturn('authenticate');
        $this->fmPrestashop->method('toolsIsSubmit')->willReturn(true);
        $this->fmPrestashop->expects($this->once())->method('sleep')->willReturn(true);

        $this->fmApiModel->expects($this->once())
            ->method('callApi')
            ->with(
                $this->equalTo('PATCH'),
                $this->equalTo('settings/'),
                $this->equalTo(
                    array(
                    FyndiqUtils::NAME_PRODUCT_FEED_URL =>
                    'modules/fyndiqmerchant/backoffice/filePage.php',
                    FyndiqUtils::NAME_NOTIFICATION_URL =>
                    'modules/fyndiqmerchant/backoffice/notification_service.php?event=order_created',
                    FyndiqUtils::NAME_PING_URL =>
                    'modules/fyndiqmerchant/backoffice/notification_service.php?event=ping&token='
                    )
                ),
                $this->equalTo('authenticate'),
                $this->equalTo('authenticate')
            )
            ->willReturn(true);

        $this->fmOutput->expects($this->once())
            ->method('redirect')
            ->with(
                $this->equalTo('http://localhost/module')
            )
            ->willReturn(true);

        $result = $this->controller->handleRequest();
        $this->assertTrue($result);
    }

    public function testHandleRequestSettings()
    {
        $this->fmPrestashop->method('toolsGetValue')->willReturn('settings');
        $this->fmPrestashop->method('orderStateGetOrderStates')->willReturn(array(
            array('id_order_state' => 1)
        ));
        $this->fmPrestashop->method('orderStateInvoiceAvailable')->willReturn(true);
        $this->fmPrestashop->method('languageGetLanguages')->willReturn(array(1 => 'en'));


        $this->fmPrestashop->expects($this->once())->method('getDefaultCurrency')->willReturn('SEK');
        $this->fmPrestashop->expects($this->once())->method('getCountryCode')->willReturn('SE');

        $this->fmOutput->expects($this->once())
            ->method('render')
            ->with(
                $this->equalTo('settings'),
                $this->equalTo(array(
                    'json_messages' => '[]',
                    'messages' => array(),
                    'path' => 'http://localhost/module',
                    'languages' => array(1 => 'en'),
                    'price_percentage' => 10,
                    'selected_language' => null,
                    'order_states' => array(
                        array('id_order_state' => 1)
                    ),
                    'order_import_state' => 3,
                    'order_done_state' => 4,
                    'message' => array(),
                ))
            )
            ->willReturn(true);

        $result = $this->controller->handleRequest();
        $this->assertTrue($result);
    }

    public function testHandleRequestSettingsSaveSuccess()
    {
        $this->fmPrestashop->method('toolsGetValue')->willReturn('settings');
        $this->fmPrestashop->method('toolsIsSubmit')->willReturn(true);
        $this->fmConfig->method('set')->willReturn(true);
        $this->fmOutput->expects($this->once())
            ->method('redirect')
            ->willReturn(true);

        $result = $this->controller->handleRequest();
        $this->assertTrue($result);
    }

    public function testHandleRequestSettingsSaveFail()
    {
        $this->fmPrestashop->method('toolsGetValue')->willReturn('settings');
        $this->fmPrestashop->method('toolsIsSubmit')->willReturn(true);
        $this->fmConfig->method('set')->willReturn(false);
        $this->fmOutput->expects($this->once())
            ->method('showModuleError')
            ->with(
                $this->equalTo('NT: Error saving settings')
            )
            ->willReturn(true);

        $result = $this->controller->handleRequest();
        $this->assertTrue($result);
    }

    public function testHandleRequestOrders()
    {
        $this->fmPrestashop->method('toolsGetValue')->willReturn('orders');
        $this->fmConfig->method('get')->willReturn('2013-01-01 12:12:12');

        $this->fmOutput->expects($this->once())
            ->method('render')
            ->with(
                $this->equalTo('orders'),
                $this->equalTo(array(
                    'json_messages' => '[]',
                    'messages' => array(),
                    'path' => 'http://localhost/module',
                    'import_date' => '2013-01-01 12:12:12',
                    'isToday' => false,
                    'import_time' => '12:12:12',
                ))
            )
            ->willReturn(true);

        $result = $this->controller->handleRequest();
        $this->assertTrue($result);
    }

    public function testHandleRequestDisconnect()
    {
        $this->fmPrestashop->method('toolsGetValue')->willReturn('disconnect');
        $this->fmConfig->method('delete')->willReturn(true);

        $this->fmOutput->expects($this->once())
            ->method('redirect')
            ->willReturn(true);

        $result = $this->controller->handleRequest();
        $this->assertTrue($result);
    }

    public function testHandleRequestDisconnectNotSuccessful()
    {
        $this->fmPrestashop->method('toolsGetValue')->willReturn('disconnect');

        $this->fmOutput->expects($this->once())
            ->method('showModuleError')
            ->willReturn(true);

        $result = $this->controller->handleRequest();
        $this->assertTrue($result);
    }

    public function testHandleRequestBadAction()
    {
        $this->fmPrestashop->method('toolsGetValue')->willReturn('test');

        $this->fmOutput->expects($this->once())
            ->method('showModuleError')
            ->with(
                $this->equalTo('NT: Page not found')
            )
            ->willReturn(true);

        $result = $this->controller->handleRequest();
        $this->assertTrue($result);
    }

    public function testHandleRequestServiceUnauthorized()
    {
        $this->fmPrestashop->method('toolsGetValue')->willReturn('disconnect');


        $fmApiModel = $this->getMockBuilder('FmApiModel')
            ->disableOriginalConstructor()
            ->getMock();

        $fmApiModel->method('callApi')->will($this->throwException(new Exception('Unauthorized')));

        $this->controller = new FmController($this->fmPrestashop, $this->fmOutput, $this->fmConfig, $fmApiModel);

        $this->fmOutput->expects($this->once())
            ->method('render')
            ->with(
                $this->equalTo('authenticate'),
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

    public function testHandleRequestServiceNotOperational()
    {
        $this->fmPrestashop->method('toolsGetValue')->willReturn('disconnect');


        $fmApiModel = $this->getMockBuilder('FmApiModel')
            ->disableOriginalConstructor()
            ->getMock();

        $fmApiModel->method('callApi')->will($this->throwException(new Exception('Test Exception')));

        $this->controller = new FmController($this->fmPrestashop, $this->fmOutput, $this->fmConfig, $fmApiModel);

        $this->fmOutput->expects($this->once())
            ->method('render')
            ->with(
                $this->equalTo('api_unavailable'),
                $this->equalTo(array(
                    'json_messages' => '[]',
                    'messages' => array(),
                    'path' => 'http://localhost/module',
                    'message' => 'Test Exception',
                ))
            )
            ->willReturn(true);
        $result = $this->controller->handleRequest();
        $this->assertTrue($result);
    }
}
