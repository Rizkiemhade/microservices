<?php

namespace App\Http\Controllers;

use App\Exceptions\CartNotFoundException;
use App\Exceptions\ProductNotFoundException;
use App\Exceptions\UpstreamServiceException;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;

class StoreOrderController extends Controller
{
    public function __invoke(StoreOrderRequest $request, OrderService $orderService): JsonResponse
    {
        $data = $request->validated();

        try {
            $order = $orderService->createFromCart(
                $data['user_id'],
                $data['idempotency_key'] ?? null,
            );
        } catch (CartNotFoundException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        } catch (ProductNotFoundException $exception) {
            return response()->json([
                'message' => 'A product in the cart is no longer available.',
                'errors' => ['cart' => [$exception->getMessage()]],
            ], 422);
        } catch (UpstreamServiceException $exception) {
            report($exception);

            return response()->json([
                'message' => 'A dependent service could not complete the order request.',
                'service' => $exception->service,
            ], 503);
        }

        return response()->json([
            'data' => $this->orderResponse($order),
        ], $order->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * @return array<string, mixed>
     */
    private function orderResponse(Order $order): array
    {
        return [
            'id' => $order->id,
            'user_id' => $order->user_id,
            'status' => $order->status,
            'total_amount' => $order->total_amount,
            'currency' => $order->currency,
            'items' => $order->items->map(fn ($item): array => [
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'unit_price' => $item->unit_price,
                'quantity' => $item->quantity,
            ])->all(),
        ];
    }
}
