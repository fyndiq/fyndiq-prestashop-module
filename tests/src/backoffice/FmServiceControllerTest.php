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

    public function testRouteRequestGetCategories() {

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

    public function testRouteRequestGetProducts() {
        $categoryId = 2;
        $currency = 'ZAM';
        $fyndiqPercentage = 23;

        $expected = array(
            'pagination' => '',
            'products' => array(
                array(
                    'price' => 3.33,
                    'currency' => $currency,
                    'fyndiq_quantity' => 2,
                    'fyndiq_status' => 'on',
                    'fyndiq_percentage' => 33,
                    'expected_price' => '2.23',
                    'quantity' => 2,
                    'fyndiq_exported' => true,
                ),
                array(
                    'price' => 3.33,
                    'currency' => $currency,
                    'fyndiq_quantity' => 2,
                    'fyndiq_status' => 'pending',
                    'fyndiq_percentage' => 44,
                    'expected_price' => '1.86',
                    'quantity' => 2,
                    'fyndiq_exported' => true,
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
                ),
                array(
                    'quantity' => 2,
                    'price' => 3.33,
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
        $this->assertEquals($expected, $result);
    }

    public function testLoadOrders() {
        $expected = array(
            'orders' => array(
                array(1 => 1)
            ),
            'pagination' => '',
        );

        $fmOrder = $this->getMockBuilder('FmOrder')
            ->disableOriginalConstructor()
            ->getMock();

        $fmOrder->expects($this->once())
            ->method('getImportedOrders')
            ->willReturn(array(
                array(1 => 1),
            ));

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

    public function testUpdateOrderStatus() {
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
        $expected = '21:21:18';
        $this->controller->method('getTime')->willReturn(12345678);

        $result = $this->controller->routeRequest(
            'import_orders',
            array()
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
        $this->assertTrue($result);
    }

    public function testDeleteExportedProducts() {

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

        $result = $this->controller->handleRequest('get_delivery_notes', $data);
        $this->assertNull($result);
    }
}
