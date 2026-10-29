<?php

namespace App\Exceptions;

use DomainException;

class InvalidOrderStatusTransitionException extends DomainException
{
    public function __construct(string $currentStatus)
    {
        parent::__construct("Pesanan berstatus {$currentStatus}, hanya pesanan PENDING yang dapat dibayar");
    }
}
