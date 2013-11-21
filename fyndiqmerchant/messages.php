<?php

class FmMessages {
    private static $messages = array(
        'not-authenticated-warning' => 'You have not connected to your Fyndiq merchant account yet.',
        'uninstall-confirm' => 'Are you sure you want to uninstall this module?',
        'empty-username-token' => 'Please specify a Username and API token.',
        'switch-language-confirm' => 'Are you sure you want to use a different language?',
        'disconnect-confirm' => 'Are you sure you want to disconnect from your Fyndiq merchant account?',
        'account-disconnected' => 'You have disconnected from your Fyndiq merchant account.',
        'json-encode-fail' => 'Could not encode response json.',
        'empty-language-choice' => 'You have to select a language.',

        'api-call-error' => 'Error when calling api',
        'api-incorrect-credentials' => 'Incorrect Username or API token. Please double check your provided values.',
        'api-invalid-data' => 'Error processing data',
        'api-network-error' => 'Network error, cannot connect to Fyndiq API.',
        'api-too-many-requests' => 'You have sent too many requests. Calm down!',

        'service-call-fail-head' => 'Connection failed',
        'service-call-fail-message' => 'Could not connect to the module service.'
    );

    public static function get($name) {
        return self::$messages[$name];
    }

    public static function get_all() {
        return self::$messages;
    }
}
