<?php
class FmApiModel extends FmModel
{

    const PLATFORM_NAME = 'Prestashop';
    const MODULE_NAME = 'module';

    private $username = '';
    private $apiToken = '';
    private $userAgent = '';

    public function __construct($fmPrestashop, $fmConfig, $storeId)
    {
        parent::__construct($fmPrestashop, $fmConfig, $storeId);
        $this->username = $this->fmConfig->get('username', $this->storeId);
        $this->apiToken = $this->fmConfig->get('api_token', $this->storeId);
        $this->userAgent = FyndiqUtils::getUserAgentString(
            self::PLATFORM_NAME,
            $this->fmPrestashop->globalGetVersion(),
            self::MODULE_NAME,
            FmUtils::VERSION,
            FmUtils::COMMIT
        );
    }

    public function callApi($method, $path, $data = array(), $username = '', $apiToken = '')
    {
        $username = $username ? $username : $this->username;
        $apiToken = $apiToken ? $apiToken : $this->apiToken;
        return FyndiqAPICall::callApiRaw(
            $this->userAgent,
            $username,
            $apiToken,
            $method,
            $path,
            $data
        );
    }

    public function getDeliveryNotes($request)
    {
        return $this->callApi('POST', 'delivery_notes/', $request);
    }

    public function getUserAgent()
    {
        return $this->userAgent;
    }
}
