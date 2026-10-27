<?php

namespace App\Exceptions;

use RuntimeException;

class CartNotFoundException extends RuntimeException
{
    public function __construct(int $userId)
    {
        parent::__construct("Cart for user {$userId} was not found.");
    }
}
