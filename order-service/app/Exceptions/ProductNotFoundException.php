<?php

namespace App\Exceptions;

use RuntimeException;

class ProductNotFoundException extends RuntimeException
{
    public function __construct(int $productId, string $message = '')
    {
        parent::__construct($message ?: "Produk dengan id {$productId} tidak ditemukan di product-service");
    }
}
