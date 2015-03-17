<?php
# import PrestaShop config, to enable use of PrestaShop classes, like Configuration

$configPath = dirname(dirname(dirname(dirname($_SERVER['SCRIPT_FILENAME'])))) . '/config/config.inc.php';

function exitWithError($message) {
    header('HTTP/1.1 500 Internal Server Error');
    die($message);
}

if (file_exists($configPath)) {
    require_once($configPath);
} else {
    exitWithError('Error: Config file not found: ' . $configPath);
}

require_once('./helpers.php');
require_once('./models/product_export.php');
require_once('./models/config.php');
require_once('./models/product.php');
require_once('./includes/fileHandler.php');

class FilePageController
{

    public static function getFile()
    {
        $username = FmConfig::get('username');
        $api_token = FmConfig::get('api_token');
        if (!empty($username) && !empty($api_token)) {

            $filePath = _PS_ROOT_DIR_.'/files/' . FmHelpers::getExportFileName();

            if (!file_exists($filePath) || filemtime($filePath) < strtotime('-1 hour', time())) {
                // Write the file if it does not exist or is older than the interval
                $file = fopen($filePath, 'w+');
                FmProductExport::saveFile($file);
                fclose($file);
            }
            $file = fopen($filePath, 'r');
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename=feed.csv');
            header('Pragma: no-cache');
            header('Expires: 0');
            fpassthru($file);
            fclose($file);
        } else {
            exitWithError('Module is not set up');
        }
    }
}
FilePageController::getFile();
