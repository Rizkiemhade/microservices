<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private CartServiceClient $cartService,
        private ProductServiceClient $productService,
        private OrderTotalCalculator $totalCalculator,
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

        $cartItems = [];

        foreach ($cart['items'] as $cartItem) {
            $product = $this->productService->getProduct((int) $cartItem['product_id']);
            $cartItems[] = [
                'product' => $product,
                'quantity' => (int) $cartItem['quantity'],
            ];
        }

        $totals = $this->totalCalculator->calculate($cartItems);

        try {
            return DB::transaction(function () use ($userId, $idempotencyKey, $totals): Order {
                $order = Order::create([
                    'user_id' => $userId,
                    'status' => 'pending',
                    'total_amount' => $totals['total_amount'],
                    'currency' => 'IDR',
                    'idempotency_key' => $idempotencyKey,
                ]);

                $order->items()->createMany($totals['items']);

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
}
