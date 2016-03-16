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

        $this->controller = $this->getMockBuilder('FmServiceController')
            ->setConstructorArgs(array(
                $this->fmPrestashop,
                $this->fmOutput,
                $this->fmConfig,
                $this->fmApiModel
            ))
            ->setMethods(array('loadModel', 'getTime'))
            ->getMock();
    }

    public function testHandleRequestNoAction()
    {
        $this->fmOutput->expects($this->once())
            ->method('responseError')
            ->with(
                $this->equalTo('Bad Request'),
                $this->equalTo('400 Bad Request')
            )
            ->willReturn(true);
        $result = $this->controller->handleRequest(array());
        $this->assertTrue($result);
    }

    public function testRouteRequestGetCategories()
    {

        $expected = array(
            'category_id' => 3
        );

        $fmCategory = $this->getMockBuilder('FmCategory')
            ->disableOriginalConstructor()
            ->getMock();

        $fmCategory->expects($this->once())
            ->method('getSubcategories')
            ->with(
                $this->equalTo(1),
                $this->equalTo(2)
            )
            ->willReturn($expected);

        $this->fmConfig->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo('language')
            )
            ->willReturn(1);

        $this->controller->expects($this->once())
            ->method('loadModel')
            ->willReturn($fmCategory);

        $result = $this->controller->routeRequest(
            'get_categories',
            array(
                'category_id' => 2
            )
        );
        $this->assertEquals($expected, $result);
    }

    public function testRouteRequestGetProducts()
    {
        $categoryId = 2;
        $currency = 'ZAM';
        $fyndiqPercentage = 23;

        $expected = array(
            'pagination' => '',
            'products' => array(
                array(
                    'price' => 4.44,
                    'currency' => $currency,
                    'fyndiq_quantity' => 2,
                    'fyndiq_status' => 'on',
                    'fyndiq_percentage' => 33,
                    'expected_price' => 4.44,
                    'quantity' => 2,
                    'fyndiq_exported' => true,
                    'name' => 'name1',
                    'images' => array(),
                ),
                array(
                    'price' => 4.44,
                    'currency' => $currency,
                    'fyndiq_quantity' => 2,
                    'fyndiq_status' => 'pending',
                    'fyndiq_percentage' => 44,
                    'expected_price' => 4.44,
                    'quantity' => 2,
                    'fyndiq_exported' => true,
                    'name' => 'name2',
                    'images' => array(),
                )
            )
        );
        $products = array(
            array(
                'id_product' => 1,
            ),
            array(
                'id_product' => 2,
            ),
            array(
                'id_product' => 3,
            ),
        );

        $this->fmConfig->expects($this->at(0))
            ->method('get')
            ->with(
                $this->equalTo('price_percentage')
            )
            ->willReturn($fyndiqPercentage);

        $this->fmPrestashop->expects($this->once())
            ->method('getDefaultCurrency')
            ->willReturn($currency);

        $fmProduct = $this->getMockBuilder('FmProduct')
            ->disableOriginalConstructor()
            ->getMock();

        $fmProduct->expects($this->once())
            ->method('getByCategory')
            ->with(
                $this->equalTo($categoryId),
                $this->equalTo(1),
                $this->equalTo(FyndiqUtils::PAGINATION_ITEMS_PER_PAGE)
            )
            ->willReturn($products);

        $fmProductExport = $this->getMockBuilder('FmProductExport')
            ->disableOriginalConstructor()
            ->getMock();

        $fmProductExport->method('getStoreProduct')
            ->will($this->onConsecutiveCalls(
                array(
                    'quantity' => 2,
                    'price' => 3.33,
                    'name' => 'name1',
                    'images' => array(),
                ),
                array(
                    'quantity' => 2,
                    'price' => 3.33,
                    'name' => 'name2',
                    'images' => array(),
                ),
                array()
            ));

        $fmProductExport->method('getProduct')
            ->will($this->onConsecutiveCalls(
                array(
                    'exported_price_percentage' => 33,
                    'state' => 'FOR_SALE',
                ),
                array(
                    'exported_price_percentage' => 44,
                    'state' => 'test',
                )
            ));

        $this->fmPrestashop->method('toolsPsRound')->willReturn(4.44);

        $this->controller->expects($this->at(0))
            ->method('loadModel')
            ->willReturn($fmProduct);

        $this->controller->expects($this->at(1))
            ->method('loadModel')
            ->willReturn($fmProductExport);

        $result = $this->controller->routeRequest(
            'get_products',
            array(
                'category' => $categoryId
            )
        );
        $this->markTestIncomplete(
            'This test has not been completed yet.'
        );
        $this->assertEquals($expected, $result);
    }

    public function testLoadOrders()
    {
        $expected = array(
            'orders' => array(
                array(
                    'order_id' => 1,
                    'created_at' => '2013-12-11',
                    'created_at_time' => '10:09:08',
                    'price' => 4.55,
                    'state' => null,
                    'total_products' => 1,
                    'is_done' => false,
                    'link' => 'index.php?controller=AdminOrders&id_order=1&vieworder=1',
                )
            ),
            'pagination' => '',
        );

        $this->fmPrestashop
            ->method('isPs1516')
            ->willReturn(true);

        $fmOrder = $this->getMockBuilder('FmOrder')
            ->disableOriginalConstructor()
            ->getMock();

        $fmOrder->expects($this->once())
            ->method('getImportedOrders')
            ->willReturn(array(
                array(
                    'order_id' => 1,
                ),
            ));

        $newOrder = $this->getMockBuilder('stdClass')
            ->setMethods(array('getProducts', 'getCurrentState'))
            ->getMock();

        $newOrder->date_add = '2013-12-11 10:09:08';
        $newOrder->total_paid_tax_incl = 30;

        $newOrder->expects($this->once())
            ->method('getProducts')
            ->willReturn(array(
                array(
                    'product_quantity' => 1
                )
            ));
        $newOrder->expects($this->any(2))
            ->method('getCurrentState')
            ->willReturn(1);

        $this->fmPrestashop->expects($this->once())
            ->method('newOrder')
            ->willReturn($newOrder);

        $this->fmPrestashop->method('toolsPsRound')->willReturn(4.55);

        $this->controller->expects($this->once())
            ->method('loadModel')
            ->willReturn($fmOrder);

        $result = $this->controller->routeRequest(
            'load_orders',
            array(
            )
        );
        $this->assertEquals($expected, $result);
    }

    public function testUpdateOrderStatus()
    {
        $doneState = 'test';

        $fmOrder = $this->getMockBuilder('FmOrder')
            ->disableOriginalConstructor()
            ->getMock();

        $fmOrder->method('markOrderAsDone')
            ->willReturn(true);

        $this->controller->expects($this->once())
            ->method('loadModel')
            ->willReturn($fmOrder);

        $this->fmPrestashop->method('getOrderStateName')->willReturn($doneState);

        $result = $this->controller->routeRequest(
            'update_order_status',
            array(
                'orders' => array(1,2)
            )
        );
        $this->assertEquals($doneState, $result);
    }

    public function testImportOrders()
    {
        $doneState = 'test';
        $expected = '21:21:18';
        $this->controller->method('getTime')->willReturn(12345678);

        $fmOrder = $this->getMockBuilder('FmOrder')
            ->disableOriginalConstructor()
            ->getMock();

        $idOrderState = 3;
        $taxAddressType = 'id_address_delivery';
        $skuTypeId = 0;
        $fmOrder->method('processFullOrderQueue')
            ->with(
                $this->equalTo($idOrderState),
                $this->equalTo($taxAddressType),
                $this->equalTo($skuTypeId)
            )
            ->willReturn(true);

        $this->controller->expects($this->once())
            ->method('loadModel')
            ->willReturn($fmOrder);

        $this->fmPrestashop->method('getOrderStateName')->willReturn($doneState);

        $result = $this->controller->routeRequest(
            'import_orders',
            array()
        );
        $this->markTestIncomplete(
            'This test has to be rewritten'
        );
        $this->assertEquals($expected, $result);
    }

    public function testExportProducts()
    {
        $fmProductExport = $this->getMockBuilder('FmProductExport')
            ->disableOriginalConstructor()
            ->getMock();

        $fmProductExport->method('productExist')
            ->will($this->onConsecutiveCalls(array(
                true,
                false,
            )));

        $fmProductExport->method('updateProduct')
            ->with(
                $this->equalTo(1),
                $this->equalTo(11)
            )
            ->willReturn(true);

        $fmProductExport->method('addProduct')
            ->with(
                $this->equalTo(2),
                $this->equalTo(22)
            )
            ->willReturn(true);

        $this->controller->expects($this->once())
            ->method('loadModel')
            ->willReturn($fmProductExport);


        $result = $this->controller->routeRequest(
            'export_products',
            array(
                'products' => array(
                    array(
                        'product' => array(
                            'id' => 1,
                            'fyndiq_percentage' => 11,
                        )
                    ),
                    array(
                        'product' => array(
                            'id' => 2,
                            'fyndiq_percentage' => 22,
                        )
                    ),
                )
            )
        );
        $this->markTestIncomplete(
            'This test has to be rewritten'
        );
        $this->assertTrue($result);
    }

    public function testDeleteExportedProducts()
    {

        $fmProductExport = $this->getMockBuilder('FmProductExport')
            ->disableOriginalConstructor()
            ->getMock();

        $fmProductExport->method('deleteProeduct')
            ->willReturn(true);

        $this->controller->expects($this->once())
            ->method('loadModel')
            ->willReturn($fmProductExport);

        $result = $this->controller->routeRequest(
            'delete_exported_products',
            array(
                'products' => array(
                    array(
                        'product' => array(
                            'id' => 1
                        )
                    ),
                    array(
                        'product' => array(
                            'id' => 2
                        )
                    ),
                )
            )
        );
        $this->assertTrue($result);
    }

    public function testGetDeliveryNotes()
    {
        $data = array(
            'orders' => array(1, 2, 3)
        );

        $response = array(
            'status' => 200
        );
        $this->fmApiModel->method('callApi')
            ->willReturn($response);

        $result = $this->controller->routeRequest('get_delivery_notes', $data);
        $this->assertNull($result);

        $data = array();
        $result = $this->controller->routeRequest('get_delivery_notes', $data);
        $this->assertNull($result);
    }


    public function testUpdateProductStatus()
    {
        $data = array();

        $fmProduct = $this->getMockBuilder('FmProduct')
            ->disableOriginalConstructor()
            ->getMock();

        $this->controller->expects($this->at(0))
            ->method('loadModel')
            ->willReturn($fmProduct);

        $result = $this->controller->routeRequest('update_product_status', $data);
        $this->assertFalse($result);
    }

    public function testWrongHandler()
    {
        $this->fmOutput->expects($this->once())
            ->method('responseError')
            ->with(
                $this->equalTo('Not Found'),
                $this->equalTo('Acion test could not be found')
            );
        $result = $this->controller->routeRequest('test', array());
        $this->assertNull($result);
    }
}
