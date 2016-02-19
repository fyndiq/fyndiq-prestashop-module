<?php

/**
 * Custom form settings generator
 *
 */

class FmFormSetting
{
    /** @var [Array] [description] */
    protected $form;

    /**
     * [__construct description]
     * @param [OBJECT] $module [description]
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
     * [setLegend description]
     * @param [String] $title [description]
     * @param [String] $icon  [description]
     * @return [OBJECT] [description]
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
     * [setDescriptions description]
     * @param String $description [description]
     * @return [OBJECT] [description]
     */
    public function setDescriptions($description)
    {
        $this->form['form']['description'] = $description;
        return $this;
    }

    /**
     * [setTextField description]
     * @param [String] $label [description]
     * @param [String] $name  [description]
     * @param String $description   [description]
     * @param String $class   [description]
     * @return [OBJECT] [description]
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
     * [setSelect description]
     * @param [String] $label      [description]
     * @param [String] $name       [description]
     * @param String $description        [description]
     * @param [Array] $dataSource [description]
     * @param [String] $key        [description]
     * @param [String] $text       [description]
     * @return [OBJECT] [description]
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
     * [setSwitch description]
     * @param [String] $label [description]
     * @param [String] $name  [description]
     * @param String $description   [description]
     * @return [OBJECT] [description]
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
     * [setSubmit description]
     * @param [String] $title [description]
     * @return [OBJECT] [description]
     */
    public function setSubmit($title)
    {
        $this->form['form']['submit'] = array(
                    'title' => $title
        );
        return $this;
    }

    /**
     * [getFormElementsSettings description]
     * @return [Array] [description]
     */
    public function getFormElementsSettings()
    {
        return $this->form;
    }
}
