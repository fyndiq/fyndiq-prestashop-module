<?php

class FmApiModel {

    const PLATFORM_NAME = 'Prestashop';

    private $username = '';
    private $apiToken = '';
    private $userAgent = '';

    public function __construct($username, $apiToken, $platformVersion)
    {
        $this->username = $username;
        $this->apiToken = $apiToken;
        $this->userAgent = FyndiqUtils::getUserAgentString(
            self::PLATFORM_NAME,
            $platformVersion,
            FmUtils::MODULE_NAME,
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
            $data,
            array('FyndiqAPI', 'call')
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
