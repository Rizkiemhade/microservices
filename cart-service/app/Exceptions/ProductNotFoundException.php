<?php

namespace App\Exceptions;

use Exception;

class ProductNotFoundException extends Exception
{
    //
    public function __construct(int $product)
    {
        parent::__construct("Product dengan id {$product} tidak ditemukan",);
    }
}
