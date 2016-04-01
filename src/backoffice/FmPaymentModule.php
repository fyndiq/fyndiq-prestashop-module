<?php

class FmPaymentModule extends PaymentModule
{
    public $active = 1;
    public $name = 'fyndiq_paymentModule';

    public function __construct()
    {
        $this->displayName = 'Fyndiq';
    }
}
