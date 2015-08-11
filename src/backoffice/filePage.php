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
        $context = $fmPrestashop->contextGetContext();
        // Weirdly this is required
        $context->employee = 1;
    }

    public function handleRequest()
    {
        $username = $this->fmConfig->get('username');
        $apiToken = $this->fmConfig->get('api_token');
        if (!empty($username) && !empty($apiToken)) {
            $filePath = $this->fmPrestashop->getExportPath() . $this->fmPrestashop->getExportFileName();
            if (FyndiqUtils::mustRegenerateFile($filePath)) {
                // Write the file if it does not exist or is older than the interval
                $file = fopen($filePath, 'w+');
                $feedWriter = FmUtils::getFileWriter($file);
                $languageId = $this->fmConfig->get('language');
                $this->fmProductExport->saveFile($languageId, $feedWriter);
                fclose($file);
            }
            $lastModified = filemtime($filePath);

            $file = fopen($filePath, 'r');
            $this->fmOutput->header('Last-Modified: ' . date('r', $lastModified));
            $this->fmOutput->streamFile($file, 'feed.csv', 'text/csv', filesize($filePath));
            return fclose($file);
        } else {
            $this->fmOutput->showError(500, 'Internal Server Error', 'Module is not set up');
        }
    }
}

$fmPrestashop = new FmPrestashop(FmUtils::MODULE_NAME);
$fmConfig = new FmConfig($fmPrestashop);
$fmOutput = new FmOutput($fmPrestashop, null, null);
$fmProductExport = new FmProductExport($fmPrestashop, $fmConfig);
$filePageControoler = new FilePageController($fmPrestashop, $fmConfig, $fmOutput, $fmProductExport);
$filePageControoler->handleRequest();
