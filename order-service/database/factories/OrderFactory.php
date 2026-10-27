<?php

namespace Database\Factories;

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
            'status' => 'pending',
            'total_amount' => fake()->randomFloat(2, 1, 1000000),
            'currency' => 'IDR',
            'idempotency_key' => null,
        ];
    }
}
