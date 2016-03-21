<?php

/**
 * Custom form settings generator
 *
 */

class FmFormSetting
{
    const SKU_DEFAULT = 0;
    const DESCRIPTION_DEFAULT = '1;description';
    const EAN_DEFAULT = '1;ean13';
    const ISBN_DEFAULT = '0;';
    const MPN_DEFAULT = '0;';
    const BRAND_DEFAULT = '3;';

    const MAPPING_TYPE_NO_MAPPING = 0;
    const MAPPING_TYPE_PRODUCT_FIELD = 1;
    const MAPPING_TYPE_PRODUCT_FEATURE = 2;
    const MAPPING_TYPE_MANUFACTURER_NAME = 3;
    const MAPPING_TYPE_SHORT_AND_LONG_DESCRIPTION = 4;
    const MAPPING_TYPE_DELMITER = ';';

    const SETTINGS_LANGUAGE_ID = 8;
    const SETTINGS_STOCK_MIN = 16;
    const SETTINGS_GROUP_ID = 32;
    const SETTINGS_STORE_ID = 64;
    const SETTINGS_MAPPING_DESCRIPTION = 128;
    const SETTINGS_MAPPING_SKU = 256;
    const SETTINGS_MAPPING_EAN = 512;
    const SETTINGS_MAPPING_ISBN = 1024;
    const SETTINGS_MAPPING_MPN = 2048;
    const SETTINGS_MAPPING_BRAND = 4096;

    /** @var array [form settings array] */
    protected $form;

    public static function serializeProductMappingValue($productMappingType, $productMappingValue)
    {
        return $productMappingType . FmFormSetting::MAPPING_TYPE_DELMITER . $productMappingValue;
    }

    public static function deserializeProductMappingValue($serializedProductMappingValue)
    {
        if ($serializedProductMappingValue === FmUtils::SHORT_DESCRIPTION) {
            return array(
                'product_mapping_type' => FmFormSetting::MAPPING_TYPE_PRODUCT_FIELD,
                'product_mapping_key_id' => 'description_short',
            );
        }
        if ($serializedProductMappingValue === FmUtils::LONG_DESCRIPTION) {
            return array(
                'product_mapping_type' => FmFormSetting::MAPPING_TYPE_PRODUCT_FIELD,
                'product_mapping_key_id' => 'description',
            );
        }
        if ($serializedProductMappingValue === FmUtils::SHORT_AND_LONG_DESCRIPTION) {
            return array(
                'product_mapping_type' => FmFormSetting::MAPPING_TYPE_SHORT_AND_LONG_DESCRIPTION,
                'product_mapping_key_id' => '',
            );
        }
        $productMapping = explode(FmFormSetting::MAPPING_TYPE_DELMITER, $serializedProductMappingValue);
        return array(
            'product_mapping_type' => $productMapping[0],
            'product_mapping_key_id' => $productMapping[1],
        );
    }


    /**
     * initialize the default form settings
     */
    public function __construct()
    {
        $this->form = array(
            'form' => array(
                'legend' => array(),
                'description' =>'',
                'input' => array(),
                'submit' => array(
                    'title' => ''
                )
            )
        );
    }

    /**
     * setLegend, it sets form title and form icon
     * @param string $title     pass the form title
     * @param string $icon      pass the form icon
     * @return FmFormSetting    return class object
     */
    public function setLegend($title, $icon)
    {
        $this->form['form']['legend'] = array(
            'title' => $title,
            'icon' => $icon,
        );
        return $this;
    }

    /**
     * setDescriptions, set the form description]
     * @param string $description   pass form description
     * @return FmFormSetting        return class object
     */
    public function setDescriptions($description)
    {
        $this->form['form']['description'] = $description;
        return $this;
    }

    /**
     * setTextField, add textField elements to the form
     * @param string $label         set textfield label
     * @param string $name          set textfield name
     * @param string $description   set textfield description
     * @param string $class         set textfield css class
     * @return FmFormSetting        return class object
     */
    public function setTextField($label, $name, $description, $class)
    {
        $this->form['form']['input'][] = array(
                        'type' => 'text',
                        'label'=> $label,
                        'name' => $name,
                        'class' => $class,
                        'desc' => $description ? $description : '',
        );
        return $this;
    }

    /**
     * setSelect, add select elements to the form
     * @param String $label         set select lebel
     * @param string $name          set select name
     * @param String $description   set select description
     * @param array $dataSource     set the select option datasource
     * @param string $key           set the key of the option
     * @param string $text          set the value of the option
     * @return FmFormSetting        return class object
     */
    public function setSelect($label, $name, $description, $dataSource, $key, $text, $disabled = false)
    {
        $this->form['form']['input'][] = array(
                        'type' => 'select',
                        'label' => $label,
                        'name' => $name,
                        'desc' => $description? $description : '',
                        'options' => array(
                            'query' => $dataSource,
                            'id' => $key,
                            'name' => $text
                        ),
                        'disabled' => $disabled,
        );
        return $this;
    }

    /**
     * setSwitch, add radio button elements to the form as a switch
     * @param string $label         set switch lebel
     * @param string $name          set switch name
     * @param String $description   set switch description
     * @return FmFormSetting        return class object
     */
    public function setSwitch($label, $name, $description, $disabled = false)
    {
        $this->form['form']['input'][] = array(
                        'type' => 'switch',
                        'label'=> $label,
                        'name' => $name,
                        'is_bool'=> true,
                        'desc' => $description? $description : '',
                        'values'=> array(
                                array(
                                    'id' => 'active_on',
                                    'value' => 1,
                                    'label' => 'Enabled'
                                ),
                                array(
                                    'id' => 'active_off',
                                    'value' => 0,
                                    'label' => 'Disabled'
                                )
                            ),
                        'disabled' => $disabled,
        );
        return $this;
    }

    /**
     * setSubmit, add form submit button title
     * @param string $title  set the title of the submit button
     * @return FmFormSetting return class object
     */
    public function setSubmit($title)
    {
        $this->form['form']['submit'] = array(
                    'title' => $title
        );
        return $this;
    }

    /**
     * getFormElementsSettings, generates form settings
     * @return array return entire form configs
     */
    public function getFormElementsSettings()
    {
        return $this->form;
    }
}
