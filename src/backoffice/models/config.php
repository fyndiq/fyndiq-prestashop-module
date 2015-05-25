<?php

class FmConfig {

    const CONFIG_NAME = 'FYNDIQMERCHANT';

    private static function key($name) {
        return self::CONFIG_NAME . '_' . $name;
    }

    public static function delete($name) {
        return Configuration::deleteByName(self::key($name));
    }

    public static function get($name) {
        return Configuration::get(self::key($name));
    }

    public static function set($name, $value) {
        return Configuration::updateValue(self::key($name), $value);
    }
}
