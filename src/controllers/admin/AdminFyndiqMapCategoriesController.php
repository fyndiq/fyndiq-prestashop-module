<?php

class AdminFyndiqMapCategoriesController extends ModuleAdminController {

    public function __construct()
    {
        parent::__construct();
        error_log('KARAMBA');
    }

    public function initContent()
    {
        parent::initContent();
        $this->setTemplate('category_mapping.tpl');
    }

    public function createTemplate($tpl_name)
    {
        $template = _PS_ROOT_DIR_ . '/modules/'.$this->module->name.'/views/templates/admin/'.$tpl_name;
        if (file_exists($template) ) {
            return $this->context->smarty->createTemplate($template, $this->context->smarty);
        }
        return parent::createTemplate($tpl_name);
    }
}
