<?php

class FmCategoryTest extends PHPUnit_Framework_TestCase
{

    public function processCategoriesProvider() {
        return array(
            array(
                array(),
                array(),
            )
        );
    }

    /*
     * @dataProvider processCategoriesProvider
     */
    public function testProcessCategories()
    {
        $categories = array();
        $expected = array();
        $result = FmCategory::processCategories($categories);
        $this->assertEqual($expected, $result);
    }
}
