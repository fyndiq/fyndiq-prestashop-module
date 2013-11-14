<?php

class FmMessages {
    private static $messages = array(
        'not-authenticated-warning' => 'You have not connected to your Fyndiq merchant account yet.',
        'uninstall-confirm' => 'Are you sure you want to uninstall this module?',
        'api-call-error' => 'Error when calling api',
        'empty-username-token' => 'Please specify a Username and API token.',
        'authorization-fail' => 'Incorrect Username or API token. Please double check your provided values.',
        'data-processing-error' => 'Error processing data',
        'api-network-error' => 'Network error, cannot connect to Fyndiq API.',
        'disconnect-confirm' => 'Are you sure you want to disconnect from your Fyndiq merchant account?',
        'account-disconnected' => 'You have disconnected from your Fyndiq merchant account.',
        'json-encode-fail' => 'Could not encode response json.',
        'missing-category-argument' => 'This action requires a category argument'
    );

    public static function get($name) {
        return self::$messages[$name];
    }
}
