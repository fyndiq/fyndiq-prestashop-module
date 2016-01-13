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

        $this->fmProductExport = new FMProductExport($this->fmPrestashop, null);
    }

    public function testProductExist()
    {
        $productId = 1;

        $this->db->method('ExecuteS')
            ->willReturn(1);

        $result = $this->fmProductExport->productExist($productId, $this->storeId);
        $this->assertTrue($result);
    }

    public function testAddProduct()
    {
        $productId = 1;
        $expPricePercentage = 12;

        $this->fmPrestashop->expects($this->once())
            ->method('dbInsert')
            ->with(
                $this->equalTo('test_products'),
                $this->equalTo(array(
                    'product_id' => 1,
                    'exported_price_percentage' => 12,
                    'store_id' => 1
                ))
            )
            ->willReturn(true);

        $result = $this->fmProductExport->addProduct($productId, $expPricePercentage, $this->storeId);
        $this->assertTrue($result);
    }

    public function testUpdateProduct()
    {
        $productId = 1;
        $expPricePercentage = 12;

        $this->fmPrestashop->expects($this->once())
            ->method('dbUpdate')
            ->with(
                $this->equalTo('test_products'),
                $this->equalTo(array(
                    'exported_price_percentage' => 12
                )),
                $this->equalTo('product_id = "1" AND store_id = 1'),
                $this->equalTo(1)
            )
            ->willReturn(true);

        $result = $this->fmProductExport->updateProduct($productId, $expPricePercentage, $this->storeId);
        $this->assertTrue($result);
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


    public function testGet()
    {
        $expected = array(
            'combinations' => array(
                1 => array(
                    'id' => 1,
                    'reference' => 'reference5',
                    'price' => 7.70,
                    'quantity' => 5,
                    'attributes' => array(
                        array(
                            'name' => 'group_name_7',
                            'value' => 'attribute_name_9',
                        )
                    ),
                    'images' => array(),
                    'minimal_quantity' => 1,
                    'oldprice' => null,
                ),
                2 => array(
                    'id' => 2,
                    'reference' => 'reference5',
                    'price' => 7.70,
                    'quantity' => 6,
                    'attributes' => array(
                        array(
                            'name' => 'group_name_8',
                            'value' => 'attribute_name_10',
                        )
                    ),
                    'images' => array(),
                    'minimal_quantity' => 1,
                    'oldprice' => null,
                ),
            ),
            'id' => 3,
            'name' => 'name4',
            'category_id' => 11,
            'reference' => 'reference5',
            'tax_rate' => 12,
            'quantity' => 13,
            'price' => 7.70,
            'description' => 'description7',
            'manufacturer_name' => 'manufaturerName',
            'images' => array(
                'image.jpg',
            ),
            'minimal_quantity' => 1,
            'oldprice' => null,
        );
        $languageId = 1;
        $productId = 2;
        $manufacturerId = 8;
        $descriptionType = FmUtils::LONG_DESCRIPTION;
        $manufaturerName = 'manufaturerName';

        $product = $this->getMockBuilder('stdClass')
            ->setMethods(array(
                'getCategories',
                'getTaxesRate',
                'getImages',
                'getCombinationImages'
            ))
            ->getMock();
        $product->id = 3;
        $product->active = true;
        $product->name = 'name4';
        $product->reference = 'reference5';
        $product->price = 6.66;
        $product->description = 'description7';
        $product->id_manufacturer = $manufacturerId;
        $product->link_rewrite = true;
        $product->minimal_quantity = 1;

        $product->method('getCategories')->willReturn(array(9, 10, 11));
        $product->method('getTaxesRate')->willReturn(12);
        $product->method('getImages')->willReturn(array(
            array('id_image' => 2)
        ));
        $product->method('getCombinationImages')->willReturn(array(
            array(
                array(
                    'id_product_attribute' => 1,
                    'id_image' => 12
                ),
            ),
        ));

        $this->fmPrestashop->method('productNew')
            ->willReturn($product);

        $this->fmPrestashop->method('getPrice')
            ->willReturn(7.70);

        $this->fmPrestashop->method('getImageLink')
            ->willReturn('image.jpg');

        $this->fmPrestashop->expects($this->once())
            ->method('productGetQuantity')
            ->willReturn(13);

        $this->fmPrestashop->expects($this->once())
            ->method('manufacturerGetNameById')
            ->with(
                $this->equalTo($manufacturerId)
            )
            ->willReturn($manufaturerName);

        $this->fmPrestashop->method('productGetTaxRate')->willReturn(12);

        $this->fmPrestashop->expects($this->once())
            ->method('getProductAttributes')
            ->with(
                $this->equalTo($product),
                $this->equalTo(1)
            )
            ->willReturn(array(
                array(
                    'id_product_attribute' => 1,
                    'price' => 3,
                    'quantity' => 5,
                    'reference' => 'reference5',
                    'group_name' => 'group_name_7',
                    'attribute_name' => 'attribute_name_9',
                    'minimal_quantity' => 1,
                ),
                array(
                    'id_product_attribute' => 2,
                    'price' => 4,
                    'quantity' => 6,
                    'reference' => 'reference5',
                    'group_name' => 'group_name_8',
                    'attribute_name' => 'attribute_name_10',
                    'minimal_quantity' => 1,
                ),
            ));

        $result = $this->fmProductExport->getStoreProduct($languageId, $productId, $descriptionType);
        $this->assertEquals($expected, $result);
    }


    public function testGetNoProduct()
    {
        $languageId = 1;
        $productId = 2;
        $descriptionType = FmUtils::LONG_DESCRIPTION;

        $result = $this->fmProductExport->getStoreProduct($languageId, $productId, $descriptionType);
        $this->assertFalse($result);
    }

    public function testSaveFile()
    {
        $languageId = 1;
        $descriptionType = FmUtils::LONG_DESCRIPTION;
        $products = array(
            array(
                'id' => 3,
                'product_id' => 1,
                'exported_price_percentage' => 77,
            ),
            array(
                'id' => 4,
                'product_id' => 2,
                'exported_price_percentage' => 88,
            ),
        );

        $this->fmProductExport = $this->getMockBuilder('FmProductExport')
            ->setMethods(array('getFyndiqProducts', 'getStoreProduct'))
            ->setConstructorArgs(array($this->fmPrestashop, null))
            ->getMock();

        $this->fmProductExport
            ->method('getStoreProduct')
            ->will($this->onConsecutiveCalls(
                array(
                    'id' => 13,
                    'reference' => '3',
                    'category_id' => 5,
                    'description' => 'description',
                    'price' => 6.66,
                    'oldprice' => 10.66,
                    'manufacturer_name' => 'manufacturer_name',
                    'name' => 'name',
                    'tax_rate' => 12,
                    'images' => array(
                        'image.jpg'
                    ),
                    'minimal_quantity' => 1,
                    'combinations' => array(
                        array(
                            'id' => 1,
                            'reference' => '15',
                            'quantity' => 16,
                            'price' => 18.18,
                            'attributes' => array(
                                array(
                                    'name' => 'name',
                                    'value' => 'value'
                                )
                            ),
                            'images' => array(),
                            'minimal_quantity' => 1,
                        ),
                        array(
                            'reference' => '',
                            'quantity' => 17,
                            'price' => 19.19,
                            'attributes' => array(),
                            'images' => array(),
                            'minimal_quantity' => 1,
                        )
                    ),
                    'quantity' => 14
                ),
                array(
                    'id' => 31,
                    'reference' => '33',
                    'category_id' => 35,
                    'quantity' => 36,
                    'description' => 'description3',
                    'price' => 36.66,
                    'oldprice' => 56.66,
                    'manufacturer_name' => 'manufacturer_name3',
                    'name' => 'name3',
                    'images' => array(
                        'image333.jpg',
                    ),
                    'tax_rate' => 312,
                    'minimal_quantity' => 1,
                    'combinations' => array(),
                )
            ));

        $this->fmProductExport->expects($this->once())
            ->method('getFyndiqProducts')
            ->willReturn($products);

        $feedWriter = $this->getMockBuilder('FyndiqCSVFeedWriter')
            ->disableOriginalConstructor()
            ->getMock();

        $feedWriter->expects($this->once())
            ->method('write')
            ->willReturn(true);

        $feedWriter->expects($this->at(0))
            ->method('addCompleteProduct')
            ->with(
                $this->equalTo(array(
                    'id' => 13,
                    'product-category-id' => 5,
                    'product-category-name' => 'category / path',
                    'product-currency' => 'ZAM',
                    'quantity' => 14,
                    'product-description' => 'description',
                    'product-brand-name' => 'manufacturer_name',
                    'product-title' => 'name',
                    'product-vat-percent' => 12,
                    'product-market' => 'BG',
                    'price' => 1.5317999999999996,
                    'oldprice' => 10.66,
                    'sku' => '3',
                    'images' => array(
                        'image.jpg'
                    ),
                )),
                $this->equalTo(array(
                    array(
                        'id' => 1,
                        'sku' => '15',
                        'quantity' => 16,
                        'price' => 4.1814,
                        'oldprice' => 18.18,
                        'images' => array(),
                        'article-name' => 'name',
                        'properties' => array(
                            array(
                                'name' => 'name',
                                'value' => 'value',
                            )
                        ),
                    ),
                ))
            );

        $currency = new stdClass();
        $currency->iso_code = 'ZAM';

        $this->fmPrestashop->method('currencyGetDefaultCurrency')
            ->willReturn($currency);

        $this->fmPrestashop->method('getCategoryName')
            ->willReturn('category');

        $this->fmPrestashop->method('getCountryCode')
            ->willReturn('BG');

        $this->fmPrestashop->method('getCategoryPath')
            ->willReturn('category / path');

        $result = $this->fmProductExport->saveFile($languageId, $feedWriter, 0, $descriptionType, 1);
        $this->assertTrue($result);
    }

    public function testGetFyndiqProducts()
    {

        $this->db->method('ExecuteS')
            ->willReturn(true);

        $result = $this->fmProductExport->getFyndiqProducts();
        $this->assertTrue($result);
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
