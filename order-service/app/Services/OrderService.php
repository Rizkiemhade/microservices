<?php

namespace App\Services;

use App\Exceptions\UpstreamServiceException;
use App\Models\Order;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    private const MAX_AMOUNT_CENTS = 999_999_999_999;

    public function __construct(
        private CartServiceClient $cartService,
        private ProductServiceClient $productService,
    ) {}

    public function createFromCart(int $userId, ?string $idempotencyKey): Order
    {
        if ($idempotencyKey !== null) {
            $existingOrder = $this->findByIdempotencyKey($userId, $idempotencyKey);

            if ($existingOrder !== null) {
                return $existingOrder;
            }
        }

        $cart = $this->cartService->getCart($userId);

        if ($cart['items'] === []) {
            throw ValidationException::withMessages([
                'cart' => 'Cannot create an order from an empty cart.',
            ]);
        }

        $items = [];
        $totalCents = 0;

        foreach ($cart['items'] as $cartItem) {
            $product = $this->productService->getProduct((int) $cartItem['product_id']);
            $unitPriceCents = $this->priceToCents($product['price']);
            $quantity = (int) $cartItem['quantity'];

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

        try {
            return DB::transaction(function () use ($userId, $idempotencyKey, $items, $totalCents): Order {
                $order = Order::create([
                    'user_id' => $userId,
                    'status' => 'pending',
                    'total_amount' => $this->centsToDecimal($totalCents),
                    'currency' => 'IDR',
                    'idempotency_key' => $idempotencyKey,
                ]);

                $order->items()->createMany($items);

                return $order->load('items');
            });
        } catch (QueryException $exception) {
            if ($idempotencyKey !== null) {
                $existingOrder = $this->findByIdempotencyKey($userId, $idempotencyKey);

                if ($existingOrder !== null) {
                    return $existingOrder;
                }
            }

            throw $exception;
        }
    }

    private function findByIdempotencyKey(int $userId, string $idempotencyKey): ?Order
    {
        return Order::query()
            ->where('user_id', $userId)
            ->where('idempotency_key', $idempotencyKey)
            ->with('items')
            ->first();
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
