<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_pay_order_changes_pending_to_paid(): void
    {
        $order = Order::factory()
            ->has(OrderItem::factory()->count(1), 'items')
            ->create([
                'user_id' => 7,
                'status' => OrderStatus::PENDING->value,
                'total_amount' => 150000,
            ]);

        $response = $this->patchJson("/orders/{$order->id}/pay");

        $response->assertOk()
            ->assertJsonPath('message', 'Pesanan berhasil dibayar')
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.status', 'PAID')
            ->assertJsonPath('data.total_amount', 150000);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'PAID',
        ]);
    }

    public function test_pay_order_rejects_already_paid_order_with_409(): void
    {
        $order = Order::factory()->create([
            'user_id' => 7,
            'status' => OrderStatus::PAID->value,
        ]);

        $response = $this->patchJson("/orders/{$order->id}/pay");

        $response->assertStatus(409)
            ->assertJsonPath('message', 'Pesanan berstatus PAID, hanya pesanan PENDING yang dapat dibayar');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'PAID',
        ]);
    }

    public function test_pay_order_returns_404_if_order_not_found(): void
    {
        $response = $this->patchJson('/orders/99/pay');

        $response->assertNotFound()
            ->assertJsonPath('message', 'Pesanan dengan id 99 tidak ditemukan');
    }

    public function test_pay_order_returns_404_if_id_is_not_a_number(): void
    {
        $response = $this->patchJson('/orders/abc/pay');

        $response->assertNotFound();
    }
}
