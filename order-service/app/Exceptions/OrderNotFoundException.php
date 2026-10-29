<?php

namespace App\Exceptions;

use RuntimeException;

class OrderNotFoundException extends RuntimeException
{
    public function __construct(int|string $orderId)
    {
        parent::__construct("Pesanan dengan id {$orderId} tidak ditemukan");
    }
}
