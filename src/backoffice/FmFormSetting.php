<?php

/**
 * Custom form settings generator
 *
 */

class FmFormSetting
{
    /** @var [Array] [description] */
    /** @var [Obj] [description] */
    protected $_form;
    protected $_module;

    /**
     * [__construct description]
     * @param [OBJECT] $module [description]
     */
    public function __construct($module)
    {
        $this->_module = $module;
        $this->_form = array(
            'form' => array(
                'legend' => array(),
                'description' =>'',
                'input' => array(),
                'submit' => array(
                    'title' => $this->_module->__('Save')
                )
            )
        );
    }

    /**
     * [setLegend description]
     * @param [String] $title [description]
     * @param [String] $icon  [description]
     */
    public function setLegend($title, $icon)
    {
        $this->_form['form']['legend'] = array(
            'title' => $this->_module->__($title),
            'icon' => $icon,
        );
    }

    /**
     * [setDescriptions description]
     * @param [String] $des [description]
     */
    public function setDescriptions($des)
    {
        $this->_form['form']['description'] = $this->_module->__($des);
    }

    /**
     * [setTextField description]
     * @param [String] $label [description]
     * @param [String] $name  [description]
     * @param [String] $des   [description]
     * @param String $class [description]
     */
    public function setTextField($label, $name, $des, $class)
    {
        $this->_form['form']['input'][] = array(
                        'type' => 'text',
                        'label'=> $this->_module->__($label),
                        'name' => $name,
                        'class' => $class,
                        'desc' => $des? $this->_module->__($des):'',
        );
    }

    /**
     * [setSelect description]
     * @param [String] $label      [description]
     * @param [String] $name       [description]
     * @param [String] $des        [description]
     * @param [Array] $dataSource [description]
     * @param [String] $key        [description]
     * @param [String] $text       [description]
     */
    public function setSelect($label, $name, $des, $dataSource, $key, $text)
    {
        $this->_form['form']['input'][] = array(
                        'type' => 'select',
                        'label' => $this->_module->__($label),
                        'name' => $name,
                        'desc' => $des? $this->_module->__($des):'',
                        'options' => array(
                            'query' => $dataSource,
                            'id' => $key,
                            'name' => $text
                        )
        );
    }

    /**
     * [setSwitch description]
     * @param [String] $label [description]
     * @param [String] $name  [description]
     * @param [String] $des   [description]
     */
    public function setSwitch($label, $name, $des)
    {
        $this->_form['form']['input'][] = array(
                        'type' => 'switch',
                        'label'=> $this->_module->__($label),
                        'name' => $name,
                        'is_bool'=> true,
                        'desc' => $des? $this->_module->__($des):'',
                        'values'=> array(
                                array(
                                    'id' => 'active_on',
                                    'value' => 1,
                                    'label' => $this->_module->__('Enabled')
                                ),
                                array(
                                    'id' => 'active_off',
                                    'value' => 0,
                                    'label' => $this->_module->__('Disabled')
                                )
                            ),
        );
    }

    /**
     * [setSubmit description]
     * @param [String] $title [description]
     */
    public function setSubmit($title)
    {
        $this->_form['form']['submit'] = array(
                    'title' => $this->_module->__($title)
        );
    }

    /**
     * [getFormElementsSettings description]
     * @return [Array] [description]
     */
    public function getFormElementsSettings()
    {
        return $this->_form;
    }
}
