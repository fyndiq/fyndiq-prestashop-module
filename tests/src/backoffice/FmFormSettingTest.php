<?php

class FmFormSettingTest extends PHPUnit_Framework_TestCase
{

    protected function setUp()
    {
        parent::setUp();
        $this->fmFormSettings = new FmFormSetting();
    }

    public function testDeserializeMappingValueProvider()
    {
        return array(
            array(
                '',
                array(
                    'type' => 0,
                    'id' => ''
                )
            ),
            array(
                '1',
                array(
                    'type' => 1,
                    'id' => 'description'
                )
            ),
            array(
                '1;2',
                array(
                    'type' => 1,
                    'id' => '2'
                )
            ),
            array(
                '2;3;3',
                array(
                    'type' => 2,
                    'id' => '3'
                )
            ),
            array(
                FmUtils::SHORT_DESCRIPTION,
                array(
                    'type' => 1,
                    'id' => 'description_short',
                )
            ),
            array(
                FmUtils::SHORT_AND_LONG_DESCRIPTION,
                array(
                    'type' => 4,
                    'id' => '',
                )
            ),
        );
    }

    /**
     * testDeserializeMappingValue
     * @dataProvider testDeserializeMappingValueProvider
     */
    public function testDeserializeMappingValue($value, $expected)
    {
        $result = FmFormSetting::deserializeMappingValue($value);
        $this->assertEquals($expected, $result);
    }

    public function testSerializeMappingValueProvider()
    {
        return array(
            array(1, 2, '1;2'),
            array('1', 2, '1;2'),
            array('1', '2', '1;2'),
        );
    }

    /**
     * testSerializeMappingValue
     * @dataProvider testSerializeMappingValueProvider
     */
    public function testSerializeMappingValue($type, $value, $expected)
    {
        $result = FmFormSetting::serializeMappingValue($type, $value);
        $this->assertEquals($expected, $result);
    }

    public function testSetSelectProvider()
    {
        return array(
            array(
                '$label',
                '$name',
                '$description',
                '$dataSource',
                '$key',
                '$text',
                true,
                array(
                    'form' => array(
                        'legend' => array(),
                        'description' =>'',
                        'input' => array(
                            array(
                                'type' => 'select',
                                'label' => '$label',
                                'name' => '$name',
                                'desc' => '$description',
                                'options' => array(
                                    'query' => '$dataSource',
                                    'id' => '$key',
                                    'name' => '$text',
                                ),
                                'disabled' => true,
                            )
                        ),
                        'submit' => array(
                            'title' => ''
                        ),
                        'buttons' => array()
                    )
                ),
            ),
            array(
                '$label',
                '$name',
                '$description',
                '$dataSource',
                '$key',
                '$text',
                false,
                array(
                    'form' => array(
                        'legend' => array(),
                        'description' =>'',
                        'input' => array(
                            array(
                                'type' => 'select',
                                'label' => '$label',
                                'name' => '$name',
                                'desc' => '$description',
                                'options' => array(
                                    'query' => '$dataSource',
                                    'id' => '$key',
                                    'name' => '$text',
                                ),
                            )
                        ),
                        'submit' => array(
                            'title' => ''
                        ),
                        'buttons' => array()
                    )
                ),
            ),
        );
    }

    /**
     * testSetSelect
     * @dataProvider testSetSelectProvider
     */
    public function testSetSelect($label, $name, $description, $dataSource, $key, $text, $disabled, $expected)
    {
        $result = $this->fmFormSettings->setSelect($label, $name, $description, $dataSource, $key, $text, $disabled);
        $this->assertEquals($result->getFormElementsSettings(), $expected);
    }
}
