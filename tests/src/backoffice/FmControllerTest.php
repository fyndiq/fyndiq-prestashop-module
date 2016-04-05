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


        $this->helperForm = $this->getMockBuilder('stdClass')
            ->setMethods(array('generateForm'))
            ->getMock();

        $this->helperForm->method('generateForm')
            ->willReturn('<form>');

        $this->fmPrestashop->method('getCurrency')->willReturn('ZWL');
        $this->fmPrestashop->method('getModuleUrl')->willReturn('http://localhost/module');
        $this->fmPrestashop->method('getCurrentUrlIndex')->willReturn('index.php?controller=AdminModules');
        $this->fmPrestashop->method('productGetFields')->willReturn(array('a', 'b', 'c'));
        $this->fmPrestashop->method('combinationGetFields')->willReturn(array('d', 'e', 'f'));
        $this->fmPrestashop->method('getHelperForm')->willReturn($this->helperForm);
        $this->fmPrestashop->method('languageGetLanguages')
            ->willReturn(array(
                array(
                    'id_lang' => '1',
                    'iso_code' => 'en',
                    'name' => 'English',
                ),
            ));
        $this->fmPrestashop->method('orderStateGetOrderStates')
            ->willReturn(array(
                array(
                    'id_order_state' => 1,
                )
            ));
        $this->fmPrestashop->method('fetureGetAllForLanguage')
            ->willReturn(array(
                array(
                    'id_feature' => 1,
                    'name' => 'Feature name',
                )
            ));

        $this->module = $this->getMockBuilder('stdClass')
            ->setMethods(array('__'))
            ->getMock();

        $this->module->name = 'fyndiqmerchant';
        $this->module->displayName = 'Fyndiq';

        $this->fmPrestashop->method('moduleGetInstanceByName')->willReturn($this->module);

        $this->fmConfig = $this->getMockBuilder('FmConfig')
            ->disableOriginalConstructor()
            ->getMock();

        $this->fmConfig->method('isAuthorized')->willReturn(true);
        $this->fmConfig->method('isSetUp')->willReturn(true);

        $this->fmOutput = $this->getMockBuilder('FmOutput')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockController = $this->getMockBuilder('FmController')
            ->setConstructorArgs(array(
                $this->fmPrestashop,
                $this->fmOutput,
                $this->fmConfig,
                $this->fmApiModel
            ))
            ->setMethods(array('renderForm', 'postProcess'))
            ->getMock();

        $this->controller = new FmController(
            $this->fmPrestashop,
            $this->fmOutput,
            $this->fmConfig,
            $this->fmApiModel
        );
    }

    /**
     * [testHandleRequestWithoutPost rendering only form
     * @return [type] [description]
     */
    public function testHandleRequestWithoutPost()
    {
        $expected = '<f>';
        $this->fmPrestashop->method('toolsIsSubmit')->willReturn(false);
        $this->fmPrestashop->method('getHelperForm')->willReturn($this->module);

        $this->mockController->expects($this->once())
            ->method('renderForm')
            ->with(
                $this->equalTo(0),
                $this->equalTo(0)
            )
            ->willReturn($expected);

        $result = $this->mockController->handleRequest();
        $this->assertEquals($expected, $result);
    }

    public function testHandleRequestWithPost()
    {
        $expected = '<form>';
        $this->fmPrestashop->method('toolsIsSubmit')->willReturn(true);
        $result = $this->controller->handleRequest();
        $this->assertEquals($expected, $result);
    }

    public function testHandleRequestAuthenticateSaveSuccess()
    {
        $this->markTestSkipped('This test has to be redone');

        $this->fmPrestashop->method('toolsIsSubmit')->willReturn(true);
        $this->fmPrestashop->method('toolsGetValue')->willReturn('authenticate');

        $this->fmConfig->method('isAuthorized')->willReturn(true);
        $this->fmConfig->method('isSetUp')->willReturn(true);

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
        $expected = '<form>';
        $this->assertEquals($expected, $result);
    }

    public function testHandleRequestSettings()
    {
        $this->markTestSkipped('This test has to be redone');
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
                    'stock_min' => null,
                    'message' => array(),
                    'probes' => '[{"label":"Checking file permissions","action":"probe_file_permissions"},{"label":"Checking database","action":"probe_database"},{"label":"Module integrity","action":"probe_module_integrity"},{"label":"Connection to Fyndiq","action":"probe_connection"}]',
                    'description_type_id' => 1,
                    'description_types' => array(
                        array(
                            'id' => FmUtils::LONG_DESCRIPTION,
                            'name' => FyndiqTranslation::get('Description'),
                        ),
                        array(
                            'id' => FmUtils::SHORT_DESCRIPTION,
                            'name' => FyndiqTranslation::get('Short description'),
                        ),
                        array(
                            'id' => FmUtils::SHORT_AND_LONG_DESCRIPTION,
                            'name' => FyndiqTranslation::get('Short and long description'),
                        ),
                    ),
                    'orders_enabled' => true,
                ))
            )
            ->willReturn(true);

        $result = $this->controller->handleRequest();
        $expected = '<form>';
        $this->assertEquals($expected, $result);
    }

    public function testHandleRequestSettingsSaveSuccess()
    {
        $this->markTestSkipped('This test has to be redone');
        $this->fmPrestashop->method('toolsGetValue')->willReturn('settings');
        $this->fmPrestashop->method('toolsIsSubmit')->willReturn(true);
        $this->fmConfig->method('set')->willReturn(true);
        $this->fmOutput->expects($this->once())
            ->method('redirect')
            ->willReturn(true);

        $result = $this->controller->handleRequest();
        $expected = '<form>';
        $this->assertEquals($expected, $result);
    }

    public function testHandleRequestSettingsSaveFail()
    {
        $this->markTestSkipped('This test has to be redone');
        $this->fmPrestashop->method('toolsGetValue')->willReturn('settings');
        $this->fmPrestashop->method('toolsIsSubmit')->willReturn(true);
        $this->fmConfig->method('set')->willReturn(false);
        $this->fmOutput->expects($this->once())
            ->method('showModuleError')
            ->with(
                $this->equalTo('Error saving settings')
            )
            ->willReturn(true);

        $result = $this->controller->handleRequest();
        $this->assertTrue($result);
    }

    public function testHandleRequestDisconnect()
    {
        $this->markTestSkipped('Disconnect was removed and has to be reimplemented');
        $this->fmPrestashop->method('toolsGetValue')->willReturn('disconnect');
        $this->fmConfig->method('delete')->willReturn(true);

        $this->fmOutput->expects($this->once())
            ->method('redirect')
            ->willReturn(true);

        $result = $this->controller->handleRequest();
        $expected = '<form>';
        $this->assertEquals($expected, $result);
    }

    public function testHandleRequestDisconnectNotSuccessful()
    {
        $this->markTestSkipped('Disconnect was removed and has to be reimplemented');
        $this->fmPrestashop->method('toolsGetValue')->willReturn('disconnect');

        $this->fmOutput->expects($this->once())
            ->method('showModuleError')
            ->willReturn(true);

        $result = $this->controller->handleRequest();
        $expected = '<form>';
        $this->assertEquals($expected, $result);
    }

    public function testHandleRequestBadAction()
    {
        $this->markTestSkipped('This test has to be redone');
        $this->fmPrestashop->method('toolsGetValue')->willReturn('test');

        $this->fmOutput->expects($this->once())
            ->method('showModuleError')
            ->with(
                $this->equalTo('Page not found')
            )
            ->willReturn(true);

        $result = $this->controller->handleRequest();
        $expected = '<form>';
        $this->assertEquals($expected, $result);
    }

    public function testHandleRequestServiceUnauthorized()
    {
        $this->markTestSkipped('This test has to be redone');
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
                    'orders_enabled' => true,
                ))
            )
            ->willReturn(true);
        $result = $this->controller->handleRequest();
        $expected = '<form>';
        $this->assertEquals($expected, $result);
    }

    public function testHandleRequestServiceNotOperational()
    {
        $this->markTestSkipped('This test has to be redone');
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
                    'orders_enabled' => true,
                ))
            )
            ->willReturn(true);
        $result = $this->controller->handleRequest();
        $expected = '<form>';
        $this->assertEquals($expected, $result);
    }
}
