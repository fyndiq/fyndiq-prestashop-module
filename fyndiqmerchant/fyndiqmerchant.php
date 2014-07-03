<?php

if (!defined('_PS_VERSION_'))
    exit;

require_once('messages.php');
require_once('backoffice/models/config.php');
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

        if (FmHelpers::api_connection_exists($this)) {
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
        $hook_name = array(
            FMPSV14 => 'updateproduct',
            FMPSV15 => 'actionProductUpdate'
        );
        $ret &= (bool)$this->registerHook($hook_name[FMPSV]);

        return (bool)$ret;
    }

    public function uninstall() {
        $ret = true;

        $ret &= (bool)parent::uninstall();

        # delete configuration
        $ret &= (bool)FmConfig::delete('username');
        $ret &= (bool)FmConfig::delete('api_token');
        $ret &= (bool)FmConfig::delete('language');
        $ret &= (bool)FmConfig::delete('currency');
        $ret &= (bool)FmConfig::delete('auto_import');
        $ret &= (bool)FmConfig::delete('auto_export');


        return (bool)$ret;
    }

    # 1.4
    public function hookupdateproduct($data) {
        // $product = $data['product'];
        // $quantity = $product->quantity;
        // $id = $product->id;
        // $ts = date('Y-m-d H:i:s', time());
        // file_put_contents('test14.apa', $ts.' | '.$quantity.' | '.$id);
    }

    # 1.5
    public function hookActionProductUpdate($data) {
        // ob_start();
        // var_dump($data);
        // $s = ob_get_contents();
        // ob_end_clean();
        // file_put_contents('test.apa', $s);

        // $product = $data['product'];
        // $quantity = $product->quantity;
        // $id = $product->id;
        // $ts = date('Y-m-d H:i:s', time());
        // file_put_contents('test15.apa', $ts.' | '.$quantity.' | '.$id);
    }

    public function getContent() {
        return FmBackofficeControllers::main($this);
    }

    public function get($name) {
        return $this->$name;
    }
}
