<?php

if (!defined('_PS_VERSION_'))
    exit;

require_once('messages.php');
require_once('backoffice/api.php');
require_once('backoffice/helpers.php');
require_once('backoffice/controllers.php');

class FyndiqMerchant extends Module {
    public function __construct() {

        $this->config_name = 'FYNDIQMERCHANT';
        $this->name = 'fyndiqmerchant';
        $this->tab = 'market_place';
        $this->version = '0.1';
        $this->author = 'Fyndiq AB';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.5.0', 'max' => '1.5.7');

        parent::__construct();

        $this->displayName = $this->l('Fyndiq');
        $this->description = $this->l('dfasdf');
        $this->confirmUninstall = $this->l(FmMessages::get('uninstall-confirm'));

        if (Configuration::get($this->config_name.'_username') === false ||
            Configuration::get($this->config_name.'_api_token') === false)
        {
            $this->warning = $this->l(FmMessages::get('not-authenticated-warning'));
        }

        ## custom properties specific to this module
        # determines which prestashop language should be used when getting from database
        $this->language_id = 1;
        # used as user agent string when calling the API
        $this->user_agent = $this->name.'-'.$this->version;
    }

    public function install() {
        $ret = true;

        $ret &= (bool)parent::install();

        # hook to product update
        $hook_name = [
            FMPSV14 => 'updateproduct',
            FMPSV15 => 'actionProductUpdate'
        ];
        $ret &= (bool)$this->registerHook($hook_name[FMPSV]);

        return (bool)$ret;
    }

    public function uninstall() {
        $ret = true;

        $ret &= (bool)parent::uninstall();

        # delete configuration
        $ret &= (bool)Configuration::deleteByName($this->config_name.'_username');
        $ret &= (bool)Configuration::deleteByName($this->config_name.'_api_token');

        return (bool)$ret;
    }

    # 1.4
    public function hookupdateproduct($data) {
    }

    # 1.5
    public function hookActionProductUpdate($data) {
    }

    public function getContent() {
        return FmBackofficeControllers::main($this);
    }

    public function get($name) {
        return $this->$name;
    }
}
