<?php

namespace App\Exceptions;

use App\Enums\OrderStatus;
use DomainException;

class InvalidOrderStatusTransitionException extends DomainException
{
    public function __construct(OrderStatus $current, OrderStatus $target)
    {
        parent::__construct("Order cannot transition from {$current->value} to {$target->value}.");
    }
}
