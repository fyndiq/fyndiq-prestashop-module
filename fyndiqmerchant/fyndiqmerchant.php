<?php

if (!defined('_PS_VERSION_'))
    exit;

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

        # used as user agent string when calling the API
        $this->user_agent = $this->name.'-'.$this->version;

        parent::__construct();

        $this->displayName = $this->l('Fyndiq');
        $this->description = $this->l('dfasdf');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall this module?');

        if (Configuration::get($this->config_name.'_username') === false ||
            Configuration::get($this->config_name.'_api_token') === false)
        {
            $this->warning = $this->l('You have not connected to your Fyndiq merchant account yet.');
        }
    }

    public function install() {
        $ret = true;

        # do common module install
        $ret &= (bool)parent::install();

        return (bool)$ret;
    }

    public function uninstall() {
        $ret = true;

        # do common module uninstall
        $ret &= (bool)parent::uninstall();

        # do module specific uninstall
        $ret &= (bool)Configuration::deleteByName($this->config_name.'_username');
        $ret &= (bool)Configuration::deleteByName($this->config_name.'_api_token');

        return (bool)$ret;
    }

    public function getContent() {
        return FyndiqMerchantBackofficeControllers::main($this);
    }

    public function get($name) {
        return $this->$name;
    }
}
