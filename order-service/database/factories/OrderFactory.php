<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => fake()->numberBetween(1, 10000),
            'status' => OrderStatus::PENDING->value,
            'total_amount' => fake()->randomFloat(2, 10000, 1000000),
        ];
    }
}
