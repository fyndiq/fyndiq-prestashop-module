<?php
class FyndiqPageController extends ModuleAdminController
{
    public function __construct()
    {
        $this->name = "Fyndiq";
        $this->class_name = "FyndiqPage";
        $this->module = "fyndiqmerchant";
        $this->id_parent = 13; // Root tab
        $this->active = 1;

        $url  = 'index.php?controller=AdminModules&configure=fyndiqmerchant';
        $url .= '&token='.Tools::getAdminTokenLite('AdminModules');
        Tools::redirectAdmin($url);
    }
}
