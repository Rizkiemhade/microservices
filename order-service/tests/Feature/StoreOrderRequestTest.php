<?php

namespace Tests\Feature;

use App\Http\Requests\StoreOrderRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreOrderRequestTest extends TestCase
{
    public function test_valid_checkout_request_passes_validation(): void
    {
        $data = [
            'user_id' => 42,
            'idempotency_key' => 'checkout-42-001',
        ];

        $validator = Validator::make($data, (new StoreOrderRequest)->rules());

        $this->assertTrue($validator->passes());
        $validated = $validator->validated();
        $this->assertSame(42, $validated['user_id']);
        $this->assertSame('checkout-42-001', $validated['idempotency_key']);
    }

    public function test_checkout_requires_a_valid_user_id(): void
    {
        $validator = Validator::make([
            'user_id' => 0,
        ], (new StoreOrderRequest)->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('user_id', $validator->errors()->toArray());
    }

    public function test_client_cannot_supply_cart_items_or_prices(): void
    {
        $validator = Validator::make([
            'user_id' => 42,
            'total_amount' => 1,
            'items' => [['product_id' => 7, 'quantity' => 2]],
            'unit_price' => 1,
        ], (new StoreOrderRequest)->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('total_amount', $validator->errors()->toArray());
        $this->assertArrayHasKey('items', $validator->errors()->toArray());
        $this->assertArrayHasKey('unit_price', $validator->errors()->toArray());
    }
}
