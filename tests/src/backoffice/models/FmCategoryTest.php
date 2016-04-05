<?php

class FmCategoryTest extends PHPUnit_Framework_TestCase
{

    protected function setUp()
    {
        $this->fmPrestashop = $this->getMockBuilder('FmPrestashop')
            ->disableOriginalConstructor()
            ->getMock();

        $this->fmCategory = new FmCategory($this->fmPrestashop, null);
    }

    public function testGetSubcategories()
    {
        $expected = array(
            array(
                'id' => 3,
                'name' => 'name4'
            )
        );
        $categoryId = 1;
        $languageId = 2;

        $this->fmPrestashop->expects($this->once())
            ->method('categoryGetChildren')
            ->with(
                $this->equalTo($languageId),
                $this->equalTo($categoryId)
            )
            ->willReturn(array(
                array('id_category' => 3, 'name' => 'name4')
            ));

        $result = $this->fmCategory->getSubcategories($categoryId, $languageId);
        $this->assertEquals($expected, $result);
    }
}
