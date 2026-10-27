<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderReadTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_order_details_with_items_and_status(): void
    {
        $order = Order::factory()
            ->has(OrderItem::factory()->state([
                'product_id' => 7,
                'product_name' => 'Keyboard',
                'unit_price' => '25.00',
                'quantity' => 2,
            ]), 'items')
            ->create([
                'user_id' => 42,
                'status' => 'processing',
                'total_amount' => '50.00',
            ]);

        $this->getJson("/api/users/42/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.user_id', 42)
            ->assertJsonPath('data.status', 'processing')
            ->assertJsonPath('data.total_amount', '50.00')
            ->assertJsonPath('data.items.0.product_id', 7)
            ->assertJsonPath('data.items.0.product_name', 'Keyboard')
            ->assertJsonPath('data.items.0.unit_price', '25.00')
            ->assertJsonPath('data.items.0.quantity', 2);
    }

    public function test_user_can_list_only_their_orders_with_items_and_status(): void
    {
        $userOrder = Order::factory()
            ->has(OrderItem::factory()->state([
                'product_name' => 'User product',
            ]), 'items')
            ->create([
                'user_id' => 42,
                'status' => 'pending',
            ]);

        Order::factory()
            ->has(OrderItem::factory()->state([
                'product_name' => 'Another user product',
            ]), 'items')
            ->create(['user_id' => 99]);

        $this->getJson('/api/users/42/orders')
            ->assertOk()
            ->assertJsonPath('data.0.id', $userOrder->id)
            ->assertJsonPath('data.0.user_id', 42)
            ->assertJsonPath('data.0.status', 'pending')
            ->assertJsonPath('data.0.items.0.product_name', 'User product')
            ->assertJsonCount(1, 'data')
            ->assertJsonMissing(['product_name' => 'Another user product'])
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_order_details_return_not_found_for_missing_or_another_users_order(): void
    {
        $order = Order::factory()->create(['user_id' => 42]);

        $this->getJson("/api/users/99/orders/{$order->id}")
            ->assertNotFound();

        $this->getJson('/api/users/42/orders/999999')
            ->assertNotFound();
    }

    public function test_user_with_no_orders_receives_an_empty_paginated_list(): void
    {
        $this->getJson('/api/users/42/orders')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonStructure(['data', 'links', 'meta']);
    }
}
