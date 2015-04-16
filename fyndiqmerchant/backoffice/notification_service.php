<?php
/*
This file handles incoming requests from the automated notification system at Fyndiq.
*/

require_once('./service_init.php');
require_once('./helpers.php');
require_once('./models/config.php');
require_once('./models/order.php');

class FmNotificationService {

    /**
     * Handle request
     *
     * @param array $params GET Params
     * @return mixed
     */
    public function handleRequest($params) {
        $eventName = isset($params['event']) ? $params['event'] : false;
        if ($eventName) {
            if (method_exists($this, $eventName)) {
                return $this->$eventName($params);
            }
        }
        header('HTTP/1.0 400 Bad Request');
        die('400 Bad Request');
    }

    /**
     * Processes new order notifications
     *
     * @param array $params
     * @return bool
     */
    private function order_created($params) {
        $orderId = isset($params['order_id']) && is_numeric($params['order_id']) ? $params['order_id'] : 0;
        if ($orderId) {
            $url = 'orders/' . $orderId . '/';
            try {
                $ret = FmHelpers::callApi('GET', $url);
                $order = $ret['data'];
                if (!FmOrder::orderExists($order->id)) {
                    FmOrder::create($order);
                }
            } catch (Exception $e) {
                header('HTTP/1.0 500 Internal Server Error');
                die('500 Internal Server Error');
            }
            return true;
        }
        header('HTTP/1.0 400 Bad Request');
        die('400 Bad Request');
    }

    /**
     * Generate feed
     *
     * @param $params
     */
    private function ping($params) {
        $token = isset($params['token']) ? $params['token'] : null;
        if (is_null($token) || $token != FmConfig::get('ping_token')) {
            header('HTTP/1.0 400 Bad Request');
            return die('400 Bad Request');
        }
        echo 'OK';
        flush();
        $locked = false;
        $lastPing = FmConfig::get('ping_time');
        if ($lastPing && $lastPing > strtotime('15 minutes ago')) {
            $locked = true;
        }
        if (!$locked) {
            FmConfig::set('ping_time', time());
            $filePath = FmHelpers::getExportPath() . FmHelpers::getExportFileName();
            try {
                $file = fopen($filePath, 'w+');
                FmProductExport::saveFile($file);
                fclose($file);
            } catch (Exception $e) {
                die($e->getMessage());
            }
        }
    }
}

$notifications = new FmNotificationService();
$notifications->handleRequest($_GET);
