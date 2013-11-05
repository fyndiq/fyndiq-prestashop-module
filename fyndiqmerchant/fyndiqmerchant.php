<?php

if (!defined('_PS_VERSION_'))
    exit;

require_once('backoffice/api.php');
require_once('backoffice/controllers.php');
require_once('backoffice/forms.php');

function pd($v) {
    echo '<pre>';
    var_dump($v);
    echo '</pre>';
}

class FyndiqMerchant extends Module {
    public function __construct() {
        $this->config_name = 'FYNDIQMERCHANT';
        $this->name = 'fyndiqmerchant';
        $this->tab = 'market_place';
        $this->version = '0.1';
        $this->author = 'Fyndiq AB';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.5.0', 'max' => '1.5.6');

        # used as user agent string when calling the API
        $this->user_agent = $this->name.'-'.$this->version;

        parent::__construct();

        $this->displayName = $this->l('Fyndiq');
        $this->description = $this->l('dfasdf');
        $this->confirmUninstall = $this->l('adf');

        if (!Configuration::get($this->config_name.'_username') ||
            !Configuration::get($this->config_name.'_api_token'))
        {
            $this->warning = $this->l('Not authenticated with Fyndiq API.');
        }
    }

    public function install() {
        $ret = true;

        # do common module install
        $ret &= parent::install();

        return $ret;
    }

    public function uninstall() {
        $ret = true;

        # do common module uninstall
        $ret &= parent::uninstall();

        # do module specific uninstall
        $ret &= Configuration::deleteByName($this->config_name.'_username');
        $ret &= Configuration::deleteByName($this->config_name.'_api_token');

        return $ret;
    }

    public function getContent() {
        return FyndiqMerchantBackofficeControllers::main($this);
    }

    public function get($name) {
        return $this->$name;
    }
}
