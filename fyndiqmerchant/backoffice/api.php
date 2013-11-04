<?php

class FyndiqAPIConnectionFailed extends Exception {}
class FyndiqAPIAuthorizationFailed extends Exception {}
class FyndiqAPIDataInvalid extends Exception {}

class FyndiqAPI {
    public static function call($user_agent, $username, $token, $data) {

        $path = 'account/';

        $body = json_encode($data);
        if (json_last_error() != JSON_ERROR_NONE) {
            throw new FyndiqAPIDataInvalid('Error in request data.');
        }

        $curl_opts = array(
            CURLOPT_USERAGENT => $user_agent,
            CURLOPT_URL => 'http://fyndiq.se:8080/api/v2.0/'.$path,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_POSTFIELDS => $body,

            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $username.':'.$token,

            #CURLOPT_SSLVERSION => 3,
            #CURLOPT_SSL_VERIFYPEER => true,
            #CURLOPT_SSL_VERIFYHOST => 2,

            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_RETURNTRANSFER => 1,
        );

        # make the call
        $ch = curl_init();
        curl_setopt_array($ch, $curl_opts);
        $data = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        # if call failed
        if ($data === false) {
            throw new FyndiqAPIConnectionFailed('Curl error: '.curl_error($ch));
        }

        if ($http_status == 401) {
            throw new FyndiqAPIAuthorizationFailed();
        }

        $result = json_decode($data);

        # if json_decode failed
        if (json_last_error() != JSON_ERROR_NONE) {
            throw new FyndiqAPIDataInvalid('Error in response data.');
        }

        return array($http_status, $result);
    }
}

?>