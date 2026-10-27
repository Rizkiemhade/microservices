<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_id' => fake()->numberBetween(1, 10000),
            'product_name' => fake()->words(3, true),
            'unit_price' => fake()->randomFloat(2, 1, 1000000),
            'quantity' => fake()->numberBetween(1, 20),
        ];
    }
}
