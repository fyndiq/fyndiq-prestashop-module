<?php

class AdminPageController extends ModuleAdminController {
    public function __construct()
    {
        $this->table = 'product';
        $this->bootstrap = true;

        $this->title = 'Products';
        $this->lang = false;

        // Building the list of records stored within the "test" table
        $this->fields_list = array();
		$this->fields_list['id_product'] = array(
            'title' => $this->l('ID'),
            'align' => 'center',
            'type' => 'int',
            'width' => 40
        );
		$this->fields_list['image'] = array(
            'title' => $this->l('Photo'),
            'align' => 'center',
            'image' => 'p',
            'width' => 70,
            'orderby' => false,
            'filter' => false,
            'search' => false
        );
		$this->fields_list['name'] = array(
            'title' => $this->l('Name'),
            'filter_key' => 'b!name'
        );
		$this->fields_list['reference'] = array(
            'title' => $this->l('Reference'),
            'align' => 'left',
            'width' => 80
        );
		$this->fields_list['price'] = array(
            'title' => $this->l('Base price'),
            'width' => 90,
            'type' => 'price',
            'align' => 'right',
            'filter_key' => 'a!price'
        );
		$this->fields_list['price_final'] = array(
            'title' => $this->l('Final price'),
            'width' => 90,
            'type' => 'price',
            'align' => 'right',
            'havingFilter' => true,
            'orderby' => false
        );
		if (Configuration::get('PS_STOCK_MANAGEMENT'))
            $this->fields_list['quantity'] = array(
                'title' => $this->l('Quantity'),
                'width' => 90,
                'type' => 'int',
                'align' => 'right',
                'filter_key' => 'quantity',
                'orderby' => true,
                'hint' => $this->l('This is the quantity available in the current shop/group.'),
            );
		$this->fields_list['active'] = array(
            'title' => $this->l('Status'),
            'width' => 70,
            'active' => 'status',
            'filter_key' => '!active',
            'align' => 'center',
            'type' => 'bool',
            'orderby' => false
        );

        // This adds a multiple deletion button
        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?')
            )
        );

        parent::__construct();
    }

    public function initContent() {
        if (Tools::getValue('export'.$this->table))
        {
            $this->display = 'export';
            $this->action = 'export';
        }
        parent::initContent();
    }

    public function displayListContent()
    {
        if (isset($this->fields_list['position']))
        {
            if (isset($this->tpl_vars['id_current_category']) && !empty($this->tpl_vars['id_current_category'])) {
                $id_category = (int)$this->tpl_vars['id_current_category']; //MAIN REASON: ajax position
            } else {
                if ($this->position_identifier)
                    $id_category = (int)Tools::getValue('id_'.($this->is_cms ? 'cms_' : '').'category', ($this->is_cms ? '1' : Category::getRootCategory()->id ));
                else
                    $id_category = Category::getRootCategory()->id;
            }

            $positions = array_map(create_function('$elem', 'return (int)($elem[\'position\']);'), $this->_list);
            sort($positions);
        }
    }

    public function ajaxProcessUpdatePositions()
    {
        if ($this->tabAccess['edit'] === '1') {
            $way = (int)(Tools::getValue('way'));
            $id_filter = (int)(Tools::getValue('id_product')); //emulate own param id_filter via id_product
            $id_category = (int)(Tools::getValue('id_category'));
            $positions = Tools::getValue('product');

            if (is_array($positions)) {
                foreach ($positions as $position => $value) {
                    $pos = explode('_', $value);

                    if ((isset($pos[1]) && isset($pos[2])) && ($pos[1] == $id_category && (int)$pos[2] === $id_filter)) {
                        if ($filter = new BelvgSearchProFilters((int)$pos[2])) {
                            if (isset($position) && $filter->updatePosition($way, $position)) {
                                echo 'ok position ' . (int)$position . ' (way=' . $way . ') for filter ' . (int)$pos[2] . "\r\n";
                            } else {
                                echo '{"hasError" : true, "errors" : "Can not update filter ' . (int)$id_filter . ' to position ' . (int)$position . ' (way=' . $way . ') "}';
                            }
                        } else {
                            echo '{"hasError" : true, "errors" : "This filter (' . (int)$id_filter . ') can t be loaded"}';
                        }

                        break;
                    }
                }
            }
        }
    }

    public function setHelperDisplay(Helper $helper)
    {
        if ($this->id_current_category && !strpos(self::$currentIndex, 'id_category')) {
            self::$currentIndex .= '&id_category=' . (int)$this->id_current_category;
        }

        parent::setHelperDisplay($helper);
    }

    // This method generates the list of results
    public function renderList()
    {
        // Adds an Edit button for each result
        $this->addRowAction('export');

        return parent::renderList();
    }

    public function displayExportLink($token, $id)
    {
        //$tpl = $this->createTemplate(__FILE__, 'list_action_view.tpl');

        /**$tpl->assign(array(
                'href' => self::$currentIndex.'&token='.$this->token.'&
                     '.$this->identifier.'='.$id.'&export'.$this->table.'=1',
                'action' => $this->l('Export')
            ));*/

        //return $tpl->fetch();
    }

    // This method generates the Add/Edit form
    public function renderForm()
    {
        // Building the Add/Edit form
        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('Test')
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('name test:'),
                    'name' => 'name',
                    'size' => 33,
                    'required' => true,
                    'desc' => $this->l('A description'),
                )
            ),
            'submit' => array(
                'title' => $this->l('    Save   '),
                'class' => 'button'
            )
        );

        return parent::renderForm();
    }
}
