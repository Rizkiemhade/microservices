<?php

namespace App\Http\Resources;

use App\Enums\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $statusValue = $this->status instanceof OrderStatus
            ? $this->status->value
            : (string) $this->status;

        return [
            'id' => (int) $this->id,
            'user_id' => (int) $this->user_id,
            'status' => $statusValue,
            'total_amount' => $this->formatNumber($this->total_amount),
            'items' => $this->relationLoaded('items')
                ? $this->items->map(function ($item): array {
                    return [
                        'product_id' => (int) $item->product_id,
                        'product_name' => (string) $item->product_name,
                        'unit_price' => $this->formatNumber($item->unit_price),
                        'quantity' => (int) $item->quantity,
                        'subtotal' => $this->formatNumber($item->subtotal),
                    ];
                })->values()->all()
                : [],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    private function formatNumber(mixed $value): int|float
    {
        $floatVal = (float) $value;
        return ((float) (int) $floatVal === $floatVal) ? (int) $floatVal : $floatVal;
    }
}
