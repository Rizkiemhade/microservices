<?php

namespace Tests\Feature;

use App\Http\Requests\StoreOrderRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreOrderRequestTest extends TestCase
{
    public function test_valid_checkout_data_passes_validation(): void
    {
        $data = [
            'user_id' => 42,
            'items' => [
                ['product_id' => 7, 'quantity' => 2],
            ],
            'idempotency_key' => 'checkout-42-001',
        ];

        $validator = Validator::make($data, (new StoreOrderRequest)->rules());

        $this->assertTrue($validator->passes());
        $validated = $validator->validated();
        $this->assertSame(42, $validated['user_id']);
        $this->assertSame([['product_id' => 7, 'quantity' => 2]], $validated['items']);
        $this->assertSame('checkout-42-001', $validated['idempotency_key']);
    }

    public function test_checkout_requires_user_and_at_least_one_valid_item(): void
    {
        $validator = Validator::make([
            'user_id' => 0,
            'items' => [
                ['product_id' => 7, 'quantity' => 0],
                ['product_id' => 7, 'quantity' => 1],
            ],
        ], (new StoreOrderRequest)->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('user_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('items.0.quantity', $validator->errors()->toArray());
        $this->assertArrayHasKey('items.1.product_id', $validator->errors()->toArray());
    }

    public function test_client_cannot_supply_order_or_item_prices(): void
    {
        $validator = Validator::make([
            'user_id' => 42,
            'total_amount' => 1,
            'items' => [
                ['product_id' => 7, 'quantity' => 2, 'unit_price' => 1],
            ],
        ], (new StoreOrderRequest)->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('total_amount', $validator->errors()->toArray());
        $this->assertArrayHasKey('items.0', $validator->errors()->toArray());
    }
}
