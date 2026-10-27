<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderStatusTransitionException;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrderStatusService
{
    public function transition(int $userId, int $orderId, OrderStatus $targetStatus): Order
    {
        return DB::transaction(function () use ($userId, $orderId, $targetStatus): Order {
            $order = Order::query()
                ->where('user_id', $userId)
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($orderId);

            /** @var OrderStatus $currentStatus */
            $currentStatus = $order->status;

            if (! $currentStatus->canTransitionTo($targetStatus)) {
                throw new InvalidOrderStatusTransitionException($currentStatus, $targetStatus);
            }

            $order->status = $targetStatus;
            $order->save();

            return $order->refresh()->load('items');
        });
    }
}
