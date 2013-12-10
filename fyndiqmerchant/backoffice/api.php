<?php

class FyndiqAPIConnectionFailed extends Exception {}
class FyndiqAPIAuthorizationFailed extends Exception {}
class FyndiqAPIDataInvalid extends Exception {}
class FyndiqAPIBadRequest extends Exception {}
class FyndiqAPITooManyRequests extends Exception {}
class FyndiqAPIUnsupportedStatus extends Exception {}

class FyndiqAPI {
    public static function call($user_agent, $username, $token, $method, $path, $data) {

        $request_body = json_encode($data);

        # if json encode failed
        if (json_last_error() != JSON_ERROR_NONE) {
            throw new FyndiqAPIDataInvalid('Error in request data.');
        }

        $curl_opts = array(
            CURLOPT_USERAGENT => $user_agent,
            CURLOPT_URL => (_PS_MODE_DEV_?'http':'https').'://fyndiq.se:8080/api/v2.0/'.$path,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER=> array('Content-type: application/json'),
            CURLOPT_POSTFIELDS => $request_body,

            CURLOPT_VERBOSE => true,
            CURLOPT_HEADER => true,

            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $username.':'.$token,

            #CURLOPT_SSLVERSION => 3,
            #CURLOPT_SSL_VERIFYPEER => true,
            #CURLOPT_SSL_VERIFYHOST => 2,

            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_RETURNTRANSFER => 1,
        );

        # make the call
        $ch = curl_init();
        curl_setopt_array($ch, $curl_opts);
        $response['data'] = curl_exec($ch);

        # if call failed
        if ($response['data'] === false) {
            throw new FyndiqAPIConnectionFailed('Curl error: '.curl_error($ch));
        }

        # extract different parts of the response
        $response['http_status'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $response['header_size'] = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $response['header'] = substr($response['data'], 0, $response['header_size']);
        $response['body'] = substr($response['data'], $response['header_size']);

        curl_close($ch);

        $result = json_decode($response['body']);

        # if json_decode failed
        if (json_last_error() != JSON_ERROR_NONE) {
            throw new FyndiqAPIDataInvalid('Error in response data.');
        }

        if ($response['http_status'] == 400) {
            $message = '';
            if (property_exists($result, 'error_messages')) {
                foreach ($result->error_messages as $error_message) {
                    $message .= ' '.$error_message;
                }
            }
            throw new FyndiqAPIBadRequest($message);
        }

        if ($response['http_status'] == 401) {
            throw new FyndiqAPIAuthorizationFailed();
        }

        if ($response['http_status'] == 429) {
            throw new FyndiqAPITooManyRequests();
        }

        if ($response['http_status'] != 200 ) {
            throw new FyndiqAPIUnsupportedStatus($response['http_status']);
        }

        return $result;
    }
}
