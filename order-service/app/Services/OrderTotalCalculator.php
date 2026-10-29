<?php

namespace App\Services;

class OrderTotalCalculator
{
    /**
     * @param array<int, array{product: array<string, mixed>, quantity: int}> $cartItems
     * @return array{items: array<int, array{product_id: int, product_name: string, unit_price: float, quantity: int, subtotal: float}>, total_amount: float}
     */
    public function calculate(array $cartItems): array
    {
        $items = [];
        $totalAmount = 0.0;

        foreach ($cartItems as $item) {
            $product = $item['product'];
            $quantity = (int) $item['quantity'];
            $unitPrice = (float) $product['price'];
            $subtotal = round($unitPrice * $quantity, 2);

            $totalAmount += $subtotal;

            $items[] = [
                'product_id' => (int) $product['id'],
                'product_name' => (string) $product['name'],
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'subtotal' => $subtotal,
            ];
        }

        return [
            'items' => $items,
            'total_amount' => round($totalAmount, 2),
        ];
    }
}
