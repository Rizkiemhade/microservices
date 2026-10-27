<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use PHPUnit\Framework\TestCase;

class OrderStatusTest extends TestCase
{
    public function test_it_allows_only_the_defined_status_transitions(): void
    {
        $allowedTransitions = [
            OrderStatus::Pending->value => [
                OrderStatus::Processing,
                OrderStatus::Cancelled,
            ],
            OrderStatus::Processing->value => [
                OrderStatus::Completed,
                OrderStatus::Cancelled,
            ],
            OrderStatus::Completed->value => [],
            OrderStatus::Cancelled->value => [],
        ];

        foreach ($allowedTransitions as $currentValue => $allowedTargets) {
            $current = OrderStatus::from($currentValue);

            foreach (OrderStatus::cases() as $target) {
                $expected = in_array($target, $allowedTargets, true);

                $this->assertSame(
                    $expected,
                    $current->canTransitionTo($target),
                    "{$current->value} -> {$target->value}",
                );
            }
        }
    }
}
