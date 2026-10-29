<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use PHPUnit\Framework\TestCase;

class OrderStatusTest extends TestCase
{
    public function test_order_status_cases(): void
    {
        $this->assertSame('PENDING', OrderStatus::PENDING->value);
        $this->assertSame('PAID', OrderStatus::PAID->value);
    }
}
