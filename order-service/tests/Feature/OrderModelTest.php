<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_has_many_items_and_item_belongs_to_order(): void
    {
        $order = Order::factory()
            ->has(OrderItem::factory()->count(2), 'items')
            ->create();

        $this->assertCount(2, $order->items);
        $this->assertTrue($order->items->every(
            fn (OrderItem $item) => $item->order->is($order),
        ));
    }

    public function test_order_amounts_are_cast_to_two_decimal_places(): void
    {
        $order = Order::factory()
            ->has(OrderItem::factory()->state([
                'unit_price' => 12500,
                'quantity' => 2,
            ]), 'items')
            ->create(['total_amount' => 25000]);

        $this->assertSame('25000.00', $order->total_amount);
        $this->assertSame('12500.00', $order->items->first()->unit_price);
        $this->assertSame(2, $order->items->first()->quantity);
    }
}
