<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_can_transition_through_every_allowed_state_change(): void
    {
        foreach ([
            [OrderStatus::Pending, OrderStatus::Processing],
            [OrderStatus::Pending, OrderStatus::Cancelled],
            [OrderStatus::Processing, OrderStatus::Completed],
            [OrderStatus::Processing, OrderStatus::Cancelled],
        ] as [$current, $target]) {
            $order = Order::factory()->create([
                'user_id' => 42,
                'status' => $current,
            ]);

            $this->patchJson("/api/users/42/orders/{$order->id}/status", [
                'status' => $target->value,
            ])
                ->assertOk()
                ->assertJsonPath('data.id', $order->id)
                ->assertJsonPath('data.status', $target->value);

            $this->assertDatabaseHas('orders', [
                'id' => $order->id,
                'status' => $target->value,
            ]);
        }
    }

    public function test_order_rejects_forbidden_state_changes(): void
    {
        foreach ([
            [OrderStatus::Pending, OrderStatus::Completed],
            [OrderStatus::Processing, OrderStatus::Pending],
            [OrderStatus::Completed, OrderStatus::Cancelled],
            [OrderStatus::Cancelled, OrderStatus::Processing],
            [OrderStatus::Pending, OrderStatus::Pending],
        ] as [$current, $target]) {
            $order = Order::factory()->create([
                'user_id' => 42,
                'status' => $current,
            ]);

            $this->patchJson("/api/users/42/orders/{$order->id}/status", [
                'status' => $target->value,
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('status');

            $this->assertDatabaseHas('orders', [
                'id' => $order->id,
                'status' => $current->value,
            ]);
        }
    }

    public function test_status_endpoint_rejects_unknown_status_values(): void
    {
        $order = Order::factory()->create([
            'user_id' => 42,
            'status' => OrderStatus::Pending,
        ]);

        $this->patchJson("/api/users/42/orders/{$order->id}/status", [
            'status' => 'refunded',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Pending->value,
        ]);
    }

    public function test_user_cannot_change_another_users_order_status(): void
    {
        $order = Order::factory()->create([
            'user_id' => 42,
            'status' => OrderStatus::Pending,
        ]);

        $this->patchJson("/api/users/99/orders/{$order->id}/status", [
            'status' => OrderStatus::Processing->value,
        ])->assertNotFound();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Pending->value,
        ]);
    }
}
