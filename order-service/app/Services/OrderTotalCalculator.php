<?php

namespace App\Services;

use App\Exceptions\UpstreamServiceException;
use Illuminate\Validation\ValidationException;

class OrderTotalCalculator
{
    private const MAX_AMOUNT_CENTS = 999_999_999_999;

    /**
     * @param  array<int, array{product: array<string, mixed>, quantity: int}>  $cartItems
     * @return array{items: array<int, array{product_id: int, product_name: string, unit_price: string, quantity: int}>, total_amount: string}
     */
    public function calculate(array $cartItems): array
    {
        $items = [];
        $totalCents = 0;

        foreach ($cartItems as $cartItem) {
            $product = $cartItem['product'];
            $unitPriceCents = $this->priceToCents($product['price'] ?? null);
            $quantity = $cartItem['quantity'];

            if ($unitPriceCents > 0 && $quantity > intdiv(self::MAX_AMOUNT_CENTS, $unitPriceCents)) {
                throw ValidationException::withMessages([
                    'cart' => 'The cart total exceeds the maximum order amount.',
                ]);
            }

            $lineTotalCents = $unitPriceCents * $quantity;

            if ($lineTotalCents > self::MAX_AMOUNT_CENTS - $totalCents) {
                throw ValidationException::withMessages([
                    'cart' => 'The cart total exceeds the maximum order amount.',
                ]);
            }

            $totalCents += $lineTotalCents;
            $items[] = [
                'product_id' => (int) $product['id'],
                'product_name' => $product['name'],
                'unit_price' => $this->centsToDecimal($unitPriceCents),
                'quantity' => $quantity,
            ];
        }

        return [
            'items' => $items,
            'total_amount' => $this->centsToDecimal($totalCents),
        ];
    }

    private function priceToCents(mixed $price): int
    {
        if ((! is_string($price) && ! is_int($price))
            || ! preg_match('/^\d{1,10}(?:\.\d{1,2})?$/D', (string) $price)) {
            throw new UpstreamServiceException('Product Service', 'invalid product price');
        }

        [$whole, $fraction] = array_pad(explode('.', (string) $price, 2), 2, '');
        $cents = ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');

        if ($cents > self::MAX_AMOUNT_CENTS) {
            throw new UpstreamServiceException('Product Service', 'product price exceeds the supported amount');
        }

        return $cents;
    }

    private function centsToDecimal(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
