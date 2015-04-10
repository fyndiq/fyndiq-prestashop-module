<?php

require_once('./service_init.php');
require_once('./helpers.php');
require_once('./models/config.php');


if (isset($_GET['order_id']) && is_numeric($_GET['order_id'])) {
    FmHelpers::streamBackDeliveryNotes(array($_GET['order_id']));
}
