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

    public function renderJSON($data)
    {
        if ($data != null) {
            $this->header('Content-Type: application/json');
            return $this->output(json_encode(
                array(
                    'fm-service-status' => 'success',
                    'data' => $data
                )
            ));
        }
        return true;
    }

    /**
     * create a error to be send back to client.
     *
     * @param $title
     * @param $message
     */
    public function responseError($title, $message)
    {
        $response = array(
            'fm-service-status' => 'error',
            'title' => $title,
            'message' => $message,
        );
        $json = json_encode($response);
        $this->output($json);
        return null;
    }

    public function header($content)
    {
        return header($content);
    }

    public function output($output)
    {
        echo $output;
        return true;
    }

    public function streamFile($file, $fileName, $contentType, $size)
    {
        $this->header('Content-Type: ' . $contentType);
        $this->header('Content-Disposition: attachment; filename="' . $fileName . '"');
        $this->header('Content-Transfer-Encoding: binary');
        $this->header('Content-Length: ' . $size);
        $this->header('Expires: 0');
        rewind($file);
        return fpassthru($file);
    }
}
