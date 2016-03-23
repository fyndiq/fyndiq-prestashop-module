<?php

class FmformSettingTest extends PHPUnit_Framework_TestCase
{

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
}
