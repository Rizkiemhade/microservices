<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'min:1'],
            'idempotency_key' => ['nullable', 'string', 'max:128'],
            'items' => ['prohibited'],
            'total_amount' => ['prohibited'],
            'unit_price' => ['prohibited'],
        ];
    }
}
