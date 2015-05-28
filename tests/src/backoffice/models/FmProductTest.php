<?php

class FmProductTest extends PHPUnit_Framework_TestCase
{

    protected function setUp()
    {
        $this->fmPrestashop = $this->getMockBuilder('FmPrestashop')
            ->disableOriginalConstructor()
            ->getMock();

        $this->fmProduct = new FmProduct($this->fmPrestashop, null);
    }

    public function testGetByCategory()
    {
        $categoryId = 1;
        $page = 2;
        $perPage = 3;

        $data = array(
            array(1 => '2')
        );

        $db = $this->getMockBuilder('stdClass')
            ->setMethods(array('ExecuteS'))
            ->getMock();

        $db->expects($this->once())
            ->method('ExecuteS')
            ->willReturn($data);

        $this->fmPrestashop->expects($this->once())
            ->method('dbGetInstance')
            ->willReturn($db);

        $result = $this->fmProduct->getByCategory($categoryId, $page, $perPage);
        $this->assertEquals($data, $result);
    }

    public function testGetAmount()
    {
        $categoryId = 1;
        $data = 2;

        $db = $this->getMockBuilder('stdClass')
            ->setMethods(array('getValue'))
            ->getMock();

        $db->expects($this->once())
            ->method('getValue')
            ->willReturn($data);

        $this->fmPrestashop->expects($this->once())
            ->method('dbGetInstance')
            ->willReturn($db);

        $result = $this->fmProduct->getAmount($categoryId);
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
                    'image' => 'image.jpg',
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
                    )
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
            'image' => 'image.jpg',
        );
        $languageId = 1;
        $productId = 2;
        $manufacturerId = 8;
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
                    'group_name' => 'group_name_7',
                    'attribute_name' => 'attribute_name_9',
                ),
                array(
                    'id_product_attribute' => 2,
                    'price' => 4,
                    'quantity' => 6,
                    'group_name' => 'group_name_8',
                    'attribute_name' => 'attribute_name_10',
                ),
            ));

        $result = $this->fmProduct->get($languageId, $productId);
        $this->assertEquals($expected, $result);
    }


    public function testGetNoProduct()
    {
        $languageId = 1;
        $productId = 2;

        $result = $this->fmProduct->get($languageId, $productId);
        $this->assertFalse($result);
    }

    public function testUpdateProductStatus()
    {
        $tableName = 'test_table';
        $productId = 1;
        $status = 3;

        $db = $this->getMockBuilder('stdClass')
            ->setMethods(array('update'))
            ->getMock();

        $db->expects($this->once())
            ->method('update')
            ->with(
                $this->equalTo($tableName),
                $this->equalTo(array(
                    'state' => $status
                )),
                $this->equalTo('id=1')
            )
            ->willReturn(true);

        $this->fmPrestashop->expects($this->once())
            ->method('dbGetInstance')
            ->willReturn($db);

        $this->fmPrestashop->expects($this->once())
            ->method('dbEscape')
            ->will($this->returnArgument(0));

        $result = $this->fmProduct->updateProductStatus($tableName, $productId, $status);
        $this->assertTrue($result);
    }
}
