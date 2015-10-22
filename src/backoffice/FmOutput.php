<?php

class FmOutput extends FyndiqOutput
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

    public function render($name, $args = array(), $error = false)
    {
        $modulePath = $this->fmPrestashop->getModulePath();
        // Templates path, relative to admin
        $templatesPath = dirname(__FILE__) . '/includes/shared/frontend/templates/js_templates.tpl';
        $this->smarty->assign(array_merge(
            $args,
            array(
                'version' => strtolower($this->fmPrestashop->version),
                'module_version' => FyndiqUtils::getVersionLabel(FmUtils::VERSION, FmUtils::COMMIT),
                'server_path' => $this->fmPrestashop->globalPsRootDir() . '/modules/' . $this->module->name,
                'module_path' => $modulePath,
                'shared_path' => $modulePath . 'backoffice/includes/shared/',
                'service_path' => $modulePath . 'backoffice/service.php',
                'js_templates' => $templatesPath,
                'repository_path' => FmUtils::REPOSITORY_PATH,
                'module_verion' => FmUtils::VERSION,
                'disable_update_check' =>FmUtils::DISABLE_UPDATE_CHECK,
            )
        ));
        $this->smarty->registerPlugin('function', 'fi18n', array('FmOutput', 'fi18n'));
        if($error != false) {
            return $this->module->displayError($error) . $this->module->display($this->module->name, 'backoffice/frontend/templates/' . $name . '.tpl');
        }
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

    public function showModuleError($message)
    {
        return $this->module->displayError($message);
    }
}
