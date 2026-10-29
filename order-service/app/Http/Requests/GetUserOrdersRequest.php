<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class GetUserOrdersRequest extends FormRequest
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
            'status' => ['sometimes', 'nullable', 'in:PENDING,PAID'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.in' => 'Status harus PENDING atau PAID',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->query('status'),
        ]);
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Data yang dikirim tidak valid',
            'errors' => $validator->errors(),
        ], 422));
    }
}
