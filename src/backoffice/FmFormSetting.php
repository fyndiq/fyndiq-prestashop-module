<?php

/**
 * Custom form settings generator
 *
 */

class FmFormSetting
{
    /** @var array [form settings array] */
    protected $form;

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
    public function setSelect($label, $name, $description, $dataSource, $key, $text)
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
                        )
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
    public function setSwitch($label, $name, $description)
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
