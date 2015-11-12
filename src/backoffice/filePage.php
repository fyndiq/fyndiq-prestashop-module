<?php
# import PrestaShop config, to enable use of PrestaShop classes, like Configuration

$configPath = dirname(dirname(dirname(dirname($_SERVER['SCRIPT_FILENAME'])))) . '/config/config.inc.php';

function exitWithError($message)
{
    header('HTTP/1.1 500 Internal Server Error');
    die($message);
}

if (file_exists($configPath)) {
    require_once($configPath);
} else {
    exitWithError('Error: Config file not found: ' . $configPath);
}

require_once('./FmConfig.php');
require_once('./includes/shared/src/FyndiqOutput.php');
require_once('./FmOutput.php');
require_once('./FmPrestashop.php');
require_once('./FmUtils.php');
require_once('./models/FmModel.php');
require_once('./models/FmProductExport.php');
require_once('./models/FmProduct.php');
require_once('./includes/shared/src/FyndiqFeedWriter.php');
require_once('./includes/shared/src/FyndiqCSVFeedWriter.php');

class FilePageController
{

    public function __construct($fmPrestashop, $fmConfig, $fmOutput, $fmProductExport)
    {
        $this->fmPrestashop = $fmPrestashop;
        $this->fmConfig = $fmConfig;
        $this->fmOutput = $fmOutput;
        $this->fmProductExport = $fmProductExport;
        $this->fmPrestashop->initHeadlessScript();
    }

    public function handleRequest($get)
    {
        try {
            if (isset($get['store_id']) && $get['store_id']) {
                $storeId = intval($get['store_id']);
                $this->fmPrestashop->setStoreId($storeId);
            }
            $storeId = $storeId->getStoreId();
            $username = $this->fmConfig->get('username', $storeId);
            $apiToken = $this->fmConfig->get('api_token', $storeId);
            if (!empty($username) && !empty($apiToken)) {
                $fileName = $this->fmPrestashop->getExportPath() . $this->fmPrestashop->getExportFileName();
                $tempFileName = FyndiqUtils::getTempFilename(dirname($fileName));

                if (FyndiqUtils::mustRegenerateFile($fileName)) {
                    // Write the file if it does not exist or is older than the interval
                    $file = fopen($tempFileName, 'w+');
                    $feedWriter = FmUtils::getFileWriter($file);
                    $languageId = $this->fmConfig->get('language', $storeId);
                    $stockMin = $this->fmConfig->get('stock_min', $storeId);
                    $descriptionType = intval($this->fmConfig->get('description_type', $storeId));
                    $result = $this->fmProductExport->saveFile($languageId, $feedWriter, $stockMin, $descriptionType, $storeId);
                    fclose($file);
                    if ($result) {
                        FyndiqUtils::moveFile($tempFileName, $fileName);
                    } else {
                        FyndiqUtils::deleteFile($tempFileName);
                    }
                }
                $lastModified = filemtime($fileName);

                $file = fopen($fileName, 'r');
                $this->fmOutput->header('Last-Modified: ' . gmdate('D, d M Y H:i:s T', $lastModified));
                $this->fmOutput->streamFile($file, 'feed.csv', 'text/csv', filesize($fileName));
                return fclose($file);
            } else {
                $this->fmOutput->showError(500, 'Internal Server Error', 'Module is not set up');
            }
        } catch (Exception $e) {
            $file = false;
            FyndiqUtils::debug('UNHANDLED ERROR ' . $e->getMessage());
        }
    }
}

$fmPrestashop = new FmPrestashop(FmUtils::MODULE_NAME);
$fmConfig = new FmConfig($fmPrestashop);
$fmOutput = new FmOutput($fmPrestashop, null, null);
$fmProductExport = new FmProductExport($fmPrestashop, $fmConfig);
$filePageControoler = new FilePageController($fmPrestashop, $fmConfig, $fmOutput, $fmProductExport);
$filePageControoler->handleRequest($_GET);
