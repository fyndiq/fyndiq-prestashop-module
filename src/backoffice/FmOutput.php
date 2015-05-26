<?php

class FmOutput
{

    protected $module;
    protected $smarty;
    protected $fmPrestashop;

    public function __construct($fmPrestashop, $module, $smarty)
    {
        $this->fmPrestashop = $fmPrestashop;
        $this->module = $module;
        $this->smarty = $smarty;
    }

    public function render($name, $args = array())
    {
        $this->smarty->assign(array_merge(
            $args,
            array(
                'server_path' => $this->fmPrestashop->globalPsRootDir() . '/modules/' . $this->module->name,
                'module_path' => $this->module->get('_path'),
                'shared_path' => $this->module->get('_path') . 'backoffice/includes/shared/',
                'service_path' => $this->module->get('_path') . 'backoffice/service.php',
            )
        ));
        $this->smarty->registerPlugin('function', 'fi18n', array('FmOutput', 'fi18n'));

        return $this->module->display($this->module->name, 'backoffice/frontend/templates/' . $name . '.tpl');
    }

    public static function fi18n($params)
    {
        return FyndiqTranslation::get($params['s']);
    }

    public function redirect($url)
    {
        return $this->fmPrestashop->toolsRedirect($url);
    }

    public function showError($message)
    {
        return $this->module->displayError($message);
    }
}
