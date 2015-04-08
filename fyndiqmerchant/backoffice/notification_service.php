<?php
/*
This file handles incoming requests from the automated notification system at Fyndiq.
*/

require_once('./service_init.php');
require_once('./helpers.php');
require_once('./models/config.php');
require_once('./models/order.php');

class FmNotificationService {

    public static function main($params) {
        $event = isset($params['event']) ? $params['event'] : false;
        if ($event) {
            if (method_exists('FmNotificationService', $event)) {
                return self::$event($params);
            }
        }
        header('HTTP/1.0 400 Bad Request');
    }

    /**
     * Processes new order notifications
     *
     * @param $params
     * @return bool
     * @throws PrestaShopException
     */
    private static function order_created($params) {
        $orderId = isset($params['order_id']) && is_numeric($params['order_id']) ? $params['order_id'] : 0;
        if ($orderId) {
            $url = 'orders/' . $orderId . '/';
            try {
                $ret = FmHelpers::call_api('GET', $url);
                $order = $ret['data'];
                if (!FmOrder::orderExists($order->id)) {
                    FmOrder::create($order);
                }
            } catch (Exception $e) {
                header('HTTP/1.0 500 Interna
                l Server Error');
            }
            return true;
        }
        header('HTTP/1.0 400 Bad Request');
    }
}


FmNotificationService::main($_GET);
