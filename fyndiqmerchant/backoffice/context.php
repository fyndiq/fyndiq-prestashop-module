<?php

class Context
{
    protected static $instance;
    public $cart;
    public $customer;
    public $link;
    public $country;
    public $language;
    public $currency;
    public $shop;
    public static function &getContext()
    {
        if (!isset(self::$instance)) self::$instance = new Context();
        return self::$instance;
    }
    public function cloneContext() { return clone($this); }
}
