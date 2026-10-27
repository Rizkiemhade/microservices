<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderStatusTransitionException;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderStatusService;
use Illuminate\Http\JsonResponse;
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

    public function updateStatus(
        UpdateOrderStatusRequest $request,
        int $userId,
        int $orderId,
        OrderStatusService $orderStatusService,
    ): OrderResource|JsonResponse {
        try {
            $order = $orderStatusService->transition(
                $userId,
                $orderId,
                OrderStatus::from($request->validated('status')),
            );
        } catch (InvalidOrderStatusTransitionException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => ['status' => [$exception->getMessage()]],
            ], 422);
        }

        return new OrderResource($order);
    }
}
