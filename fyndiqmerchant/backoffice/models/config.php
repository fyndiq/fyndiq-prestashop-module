<?php

class FmConfig {

    private static $config_name = 'FYNDIQMERCHANT';

    public static function delete($name) {
        return Configuration::deleteByName(self::$config_name.'_'.$name);
    }

    public static function get($name) {
        return Configuration::get(self::$config_name.'_'.$name);
    }

    public static function set($name, $value) {
        return Configuration::updateValue(self::$config_name.'_'.$name, $value);
    }
}
