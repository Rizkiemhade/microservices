<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderReadTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_order_detail_by_id(): void
    {
        $order = Order::factory()
            ->has(OrderItem::factory()->state([
                'product_id' => 1,
                'product_name' => 'Keyboard Mekanikal',
                'unit_price' => 450000,
                'quantity' => 2,
                'subtotal' => 900000,
            ]), 'items')
            ->create([
                'user_id' => 7,
                'status' => OrderStatus::PAID->value,
                'total_amount' => 900000,
            ]);

        $response = $this->getJson("/orders/{$order->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Berhasil mengambil pesanan')
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.user_id', 7)
            ->assertJsonPath('data.status', 'PAID')
            ->assertJsonPath('data.total_amount', 900000)
            ->assertJsonPath('data.items.0.product_id', 1)
            ->assertJsonPath('data.items.0.product_name', 'Keyboard Mekanikal')
            ->assertJsonPath('data.items.0.unit_price', 450000)
            ->assertJsonPath('data.items.0.quantity', 2)
            ->assertJsonPath('data.items.0.subtotal', 900000);
    }

    public function test_get_order_detail_returns_404_when_not_found(): void
    {
        $this->getJson('/orders/99')
            ->assertNotFound()
            ->assertJsonPath('message', 'Pesanan dengan id 99 tidak ditemukan');
    }

    public function test_get_order_detail_returns_404_when_id_is_not_a_number(): void
    {
        $this->getJson('/orders/abc')
            ->assertNotFound();
    }

    public function test_can_list_orders_for_a_user_ordered_newest_first(): void
    {
        $order1 = Order::factory()->create([
            'user_id' => 7,
            'status' => OrderStatus::PENDING->value,
            'created_at' => now()->subMinutes(10),
        ]);

        $order2 = Order::factory()->create([
            'user_id' => 7,
            'status' => OrderStatus::PAID->value,
            'created_at' => now(),
        ]);

        // Order from another user
        Order::factory()->create(['user_id' => 99]);

        $response = $this->getJson('/orders/user/7');

        $response->assertOk()
            ->assertJsonPath('message', 'Berhasil mengambil daftar pesanan')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $order2->id)
            ->assertJsonPath('data.1.id', $order1->id);
    }

    public function test_can_filter_user_orders_by_status(): void
    {
        Order::factory()->create([
            'user_id' => 7,
            'status' => OrderStatus::PENDING->value,
        ]);

        $paidOrder = Order::factory()->create([
            'user_id' => 7,
            'status' => OrderStatus::PAID->value,
        ]);

        $response = $this->getJson('/orders/user/7?status=PAID');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $paidOrder->id)
            ->assertJsonPath('data.0.status', 'PAID');
    }

    public function test_filter_user_orders_with_invalid_status_returns_422(): void
    {
        $response = $this->getJson('/orders/user/7?status=SHIPPED');

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Data yang dikirim tidak valid')
            ->assertJsonValidationErrors('status')
            ->assertJsonPath('errors.status.0', 'Status harus PENDING atau PAID');
    }
}
