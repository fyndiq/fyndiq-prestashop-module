<?php
/*
This file handles incoming requests from the automated notification system at Fyndiq.
*/

# import PrestaShop config, to enable use of PrestaShop classes, like Configuration
$configPath = dirname(dirname(dirname(dirname($_SERVER['SCRIPT_FILENAME'])))).'/config/config.inc.php';
if (file_exists($configPath)) {
    require_once($configPath);
} else {
    exit;
}

require_once('./helpers.php');


class FmNotificationService {
    public static function main() {
        pd($_GET);
        pd($_POST);
        $path_info = explode('/', $_SERVER['PATH_INFO']);
        $shop_id = $path_info[1];
        $passkey = $path_info[2];
    }
}


FmNotificationService::main();
