<?php
# import PrestaShop config, to enable use of PrestaShop classes, like Configuration
$configPath = dirname(dirname(dirname(dirname($_SERVER['SCRIPT_FILENAME'])))) . '/config/config.inc.php';
if (file_exists($configPath)) {
    require_once($configPath);
} else {
    die("Error: Config file not found: {$configPath}");
}

require_once('./helpers.php');
require_once('./models/product_export.php');
require_once('./models/config.php');
require_once('./models/product.php');
require_once('./includes/fileHandler.php');
class FilePageController
{
    const FILE_PATH = '/files/feed.csv';

    public function __construct()
    {
        $this->username = FmConfig::get('username');
        $this->api_token = FmConfig::get('api_token');
    }
    public function getFile()
    {
        if (!empty($this->username) && !empty($this->api_token)) {

            //Check if feed file exist and if it is too old
            $fileHandler = new FmFileHandler(_PS_ROOT_DIR_, 'w+');
            $fileexists = $fileHandler->fileExists();

            if ($fileexists) {
                // If feed last modified date is older than 1 hour, create a new one
                if (filemtime(_PS_ROOT_DIR_.self::FILE_PATH) < strtotime('-1 hour', time())) {
                    FmProductExport::saveFile(_PS_ROOT_DIR_);
                }
            } else {
                //The file hasn't been created yet, create it.
                FmProductExport::saveFile(_PS_ROOT_DIR_);
            }
            //printing out the data
            $fileHandler->getContentfromFile();
        }
    }
}
$filecontroller = new FilePageController();
$filecontroller->getFile();