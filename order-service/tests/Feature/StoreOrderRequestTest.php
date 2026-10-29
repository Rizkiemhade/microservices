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
            'user_id' => 7,
        ];

        $request = new StoreOrderRequest;
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->passes());
        $validated = $validator->validated();
        $this->assertSame(7, $validated['user_id']);
    }

    public function test_checkout_requires_user_id(): void
    {
        $request = new StoreOrderRequest;
        $validator = Validator::make([], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertSame('Field user_id wajib diisi', $validator->errors()->first('user_id'));
    }

    public function test_checkout_requires_a_positive_integer_user_id(): void
    {
        $request = new StoreOrderRequest;
        $validator = Validator::make(['user_id' => 0], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('user_id', $validator->errors()->toArray());
    }
}
