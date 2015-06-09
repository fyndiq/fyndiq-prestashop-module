<?php

class FyndiqProductSKUNotFound extends Exception
{
}

class FmUtils
{
    const MODULE_NAME = 'fyndiqmerchant';
    const VERSION = '1.0.0';

    public static function getFileWriter($file)
    {
        return new FyndiqCSVFeedWriter($file);
    }

    public static function debug()
    {
        if (defined('FYNDIQ_DEBUG') && FYNDIQ_DEBUG) {
            $arguments = func_get_args();
            $name = array_shift($arguments);
            echo '<b>' . $name. '</b>' . ':<br/>';
            foreach($arguments as $argument) {
                if (gettype($argument) == 'string') {
                    echo '<br/ ><pre>' . $argument . '</pre>';
                    continue;
                }
                var_dump($argument);
            }
            echo '<hr />';
        }
    }
}
