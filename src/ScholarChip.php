<?php

namespace Itacs\ScholarChip;


interface ScholarChip
{
    public function createOrder($orderId, $callbackUrl);
}
