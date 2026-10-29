<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\EmptyCartException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidOrderStatusTransitionException;
use App\Exceptions\OrderNotFoundException;
use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrderService
{
    public function __construct(
        private CartServiceClient $cartService,
        private ProductServiceClient $productService,
        private OrderTotalCalculator $totalCalculator,
    ) {}

    public function createFromCart(int $userId): Order
    {
        $cart = $this->cartService->getCart($userId);

        if (empty($cart['items'])) {
            throw new EmptyCartException('Keranjang belanja kosong');
        }

        $cartItems = [];

        foreach ($cart['items'] as $cartItem) {
            $product = $this->productService->getProduct((int) $cartItem['product_id']);
            $quantity = (int) $cartItem['quantity'];
            $stock = isset($product['stock']) ? (int) $product['stock'] : 0;

            if ($quantity > $stock) {
                throw new InsufficientStockException(
                    (string) $product['name'],
                    $quantity,
                    $stock,
                );
            }

            $cartItems[] = [
                'product' => $product,
                'quantity' => $quantity,
            ];
        }

        $totals = $this->totalCalculator->calculate($cartItems);

        $order = DB::transaction(function () use ($userId, $totals): Order {
            $order = Order::create([
                'user_id' => $userId,
                'status' => OrderStatus::PENDING->value,
                'total_amount' => $totals['total_amount'],
            ]);

            $order->items()->createMany($totals['items']);

            return $order;
        });

        // Kosongkan keranjang di cart-service setelah pesanan tersimpan di DB.
        // Jika gagal, pesanan TIDAK BOLEH dibatalkan, tetapi kegagalan dicatat di log.
        try {
            $this->cartService->clearCart($userId);
        } catch (Throwable $e) {
            Log::warning("Pesanan #{$order->id} berhasil dibuat, tetapi gagal mengosongkan keranjang user {$userId}: {$e->getMessage()}", [
                'user_id' => $userId,
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $order->load('items');
    }

    public function getOrderById(int $orderId): Order
    {
        $order = Order::with('items')->find($orderId);

        if (! $order) {
            throw new OrderNotFoundException($orderId);
        }

        return $order;
    }

    /**
     * @return Collection<int, Order>
     */
    public function getUserOrders(int $userId, ?string $status = null): Collection
    {
        $query = Order::query()
            ->where('user_id', $userId)
            ->with('items')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        return $query->get();
    }

    public function payOrder(int $orderId): Order
    {
        return DB::transaction(function () use ($orderId): Order {
            $order = Order::query()
                ->with('items')
                ->lockForUpdate()
                ->find($orderId);

            if (! $order) {
                throw new OrderNotFoundException($orderId);
            }

            if ($order->status !== OrderStatus::PENDING->value) {
                throw new InvalidOrderStatusTransitionException((string) $order->status);
            }

            $order->status = OrderStatus::PAID->value;
            $order->save();

            return $order->load('items');
        });
    }
}
