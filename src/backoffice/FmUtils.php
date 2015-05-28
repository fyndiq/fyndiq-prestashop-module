<?php

class FyndiqProductSKUNotFound extends Exception
{
}

class FmUtils
{
    const MODULE_NAME = 'fyndiqmerchant';
    const VERSION = '1.0.0';

    const EXPORT_FILE_NAME_PATTERN = 'feed-%d.csv';

    /**
     * Returns export file name depending on the shop context
     *
     * @return string export file name
     */
    public static function getExportFileName()
    {
        if (Shop::getContext() === Shop::CONTEXT_SHOP) {
            return sprintf(self::EXPORT_FILE_NAME_PATTERN, self::getCurrentShopId());
        }
        // fallback to 0 for non-multistore setups
        return sprintf(self::EXPORT_FILE_NAME_PATTERN, 0);
    }

    /**
     * Returns the export filename path
     *
     * @return string
     */
    public static function getExportPath()
    {
        return _PS_CACHE_DIR_ . 'fyndiqmerchant/';
    }

    /**
     * Returns the current shop id
     *
     * @return int
     */
    public static function getCurrentShopId()
    {
        $context = Context::getContext();
        if (Shop::isFeatureActive() && $context->cookie->shopContext) {
            $split = explode('-', $context->cookie->shopContext);
            if (count($split) === 2) {
                return intval($split[1]);
            }
        }
        return intval($context->shop->id);
    }

    public static function streamBackDeliveryNotes($orderIds)
    {
        $request = array(
            'orders' => array()
        );
        foreach ($orderIds as $orderId) {
            $request['orders'][] = array('order' => $orderId);
        }
        try {
            $ret = self::callApi('POST', 'delivery_notes/', $request, true);
            $fileName = 'delivery_notes-' . implode('-', $orderIds) . '.pdf';

            if ($ret['status'] == 200) {
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . $fileName . '"');
                header('Content-Transfer-Encoding: binary');
                header('Content-Length: ' . strlen($ret['data']));
                header('Expires: 0');
                $fp = fopen('php://temp', 'wb+');
                // Saving data to file
                fputs($fp, $ret['data']);
                rewind($fp);
                fpassthru($fp);
                fclose($fp);
                die();
            }
            return FyndiqTranslation::get('unhandled-error-message');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public static function getFileWriter($file)
    {
        return new FyndiqCSVFeedWriter($file);
    }
}
