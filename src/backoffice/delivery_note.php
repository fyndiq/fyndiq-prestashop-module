<?php

require_once('./service_init.php');
require_once('./helpers.php');
require_once('./models/config.php');

$cookie = new Cookie('psAdmin');
if ($cookie->id_employee) {
    if (isset($_GET['order_id']) && is_numeric($_GET['order_id'])) {
        FmHelpers::streamBackDeliveryNotes(array($_GET['order_id']));
    }
    exit();
}
header('HTTP/1.0 401 Unauthorized');
