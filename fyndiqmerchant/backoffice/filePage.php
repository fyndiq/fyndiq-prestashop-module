<?php
# import PrestaShop config, to enable use of PrestaShop classes, like Configuration
$configPath = dirname(dirname(dirname(dirname($_SERVER['SCRIPT_FILENAME'])))) . '/config/config.inc.php';
if (file_exists($configPath)) {
    require_once($configPath);
} else {
    echo "Error: Config file not found: {$configPath}";
    exit;
}

require_once('./helpers.php');
require_once('./models/product_export.php');
require_once('./models/config.php');
require_once('./models/product.php');
require_once('./includes/fileHandler.php');
class FilePageController
{
    const FILE_PATH = "/files/feed.csv";

    public function __construct()
    {
        $this->username = FmConfig::get('username');
        $this->api_token = FmConfig::get('api_token');
    }
    public function getFile()
    {
        $result = "";
        if ($this->username != "" && $this->api_token != "") {

            //Check if feed file exist and if it is too old
            $fileHandler = new FmFileHandler(_PS_ROOT_DIR_);
            try {
                $fileexists = $fileHandler->getContentfromFile();
            } catch (Exception $e) {
                $fileexists = false;
            }

            if ($fileexists) {
                // If feed last modified date is older than 1 hour, create a new one
                if (filemtime(_PS_ROOT_DIR_.FILE_PATH) < strtotime('-1 hour', time())) {
                    FmProductExport::saveFile(_PS_ROOT_DIR_);
                }
                else {
                    print($fileexists);
                    exit;
                }
            } else {
                //The file hasn't been created yet, create it.
                FmProductExport::saveFile(_PS_ROOT_DIR_);
            }
            $result = $fileHandler->getContentfromFile();
        }
        //printing out the content from feed file to the visitor.
        print($result);
    }
}
$filecontroller = new FilePageController();
$filecontroller->getFile();