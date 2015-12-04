<?php

class FmOrderFetch extends FyndiqPaginatedFetch
{

    function __construct($fmPrestashop, $fmConfig, $fmOrder, $fmApiModel)
    {
        $this->fmPrestashop = $fmPrestashop;
        $this->fmConfig = $fmConfig;
        $this->fmOrder = $fmOrder;
        $this->fmApiModel = $fmApiModel;
        $this->storeId = $this->fmPrestashop->getStoreId();
    }

    function getInitialPath()
    {
        $url = 'orders/';
        $date = $this->fmConfig->get('import_date', $this->storeId);
        if (!empty($date)) {
            //$url .= '?min_date=' . urlencode($date);
        }
        return $url;
    }

    function getPageData($path)
    {
        $ret = $this->fmApiModel->callApi('GET', $path);
        return $ret['data'];
    }

    function processData($data)
    {
        $idOrderState = $this->fmConfig->get('import_state', $this->storeId);
        $taxAddressType = $this->fmPrestashop->getTaxAddressType();
        $skuTypeId = intval($this->fmConfig->get('sku_type_id', $this->storeId));
$torder = <<<TXT
{
    "id": 17,
    "created": "2015-12-04T07:56:05",
    "delivery_phone": "5551234567890",
    "delivery_company": "",
    "delivery_firstname": "John",
    "delivery_lastname": "Smith",
    "delivery_address": "Main Street 1",
    "delivery_co": "",
    "delivery_postalcode": "12345",
    "delivery_city": "Springfield",
    "delivery_country": "Sweden",
    "delivery_country_code": "SE",
    "order_rows": [{
        "sku": "poruduct1",
        "vat_percent": "25.00",
        "unit_price_currency": "SEK",
        "unit_price_amount": "1.00",
        "quantity": 1,
        "productId": "9",
        "combinationId": 0
    }, {
        "sku": "product3",
        "vat_percent": "25.00",
        "unit_price_currency": "SEK",
        "unit_price_amount": "2.00",
        "quantity": 1,
        "productId": "9",
        "combinationId": 0
    }
    ],
    "delivery_note": "https:\/\/cdn.fyndiq.se\/upload\/test_order\/DeliveryNote.pdf"
}
TXT;
$order = json_decode($torder);
return $this->fmOrder->create($order, $idOrderState, $taxAddressType, $skuTypeId);


        $errors = array();
        foreach ($data as $order) {
            if (!$this->fmOrder->orderExists($order->id)) {
                try{
                    $this->fmOrder->create($order, $idOrderState, $taxAddressType, $skuTypeId);
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }
        }
        if ($errors) {
             throw new Exception(implode("\n", $errors));
        }
        return true;
    }

    function getSleepIntervalSeconds()
    {
        return 1 / self::THROTTLE_ORDER_RPS;
    }
}
