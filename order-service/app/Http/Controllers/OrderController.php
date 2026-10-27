<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    public function index(int $userId): AnonymousResourceCollection
    {
        $orders = Order::query()
            ->where('user_id', $userId)
            ->with('items')
            ->latest('id')
            ->paginate(15);

        return OrderResource::collection($orders);
    }

    public function show(int $userId, int $orderId): OrderResource
    {
        $order = Order::query()
            ->where('user_id', $userId)
            ->with('items')
            ->findOrFail($orderId);

        return new OrderResource($order);
    }
}
