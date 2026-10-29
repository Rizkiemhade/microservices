<?php

namespace App\Http\Controllers;

use App\Exceptions\EmptyCartException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidOrderStatusTransitionException;
use App\Exceptions\OrderNotFoundException;
use App\Exceptions\ProductNotFoundException;
use App\Exceptions\UpstreamServiceException;
use App\Http\Requests\GetUserOrdersRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService,
    ) {}

    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'order-service',
        ]);
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $userId = (int) $request->validated('user_id');

        try {
            $order = $this->orderService->createFromCart($userId);

            return response()->json([
                'message' => 'Pesanan berhasil dibuat',
                'data' => new OrderResource($order),
            ], 201);
        } catch (EmptyCartException|ProductNotFoundException|InsufficientStockException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 409);
        } catch (UpstreamServiceException $exception) {
            return $this->handleUpstreamError($exception);
        } catch (Throwable $exception) {
            return $this->handleServerError($exception, 'membuat pesanan');
        }
    }

    public function show(string $id): JsonResponse
    {
        if (! ctype_digit($id)) {
            return response()->json([
                'message' => "Pesanan dengan id {$id} tidak ditemukan",
            ], 404);
        }

        try {
            $order = $this->orderService->getOrderById((int) $id);

            return response()->json([
                'message' => 'Berhasil mengambil pesanan',
                'data' => new OrderResource($order),
            ]);
        } catch (OrderNotFoundException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 404);
        } catch (Throwable $exception) {
            return $this->handleServerError($exception, 'mengambil pesanan');
        }
    }

    public function userOrders(GetUserOrdersRequest $request, string $userId): JsonResponse
    {
        if (! ctype_digit($userId)) {
            return response()->json([
                'message' => 'User tidak ditemukan',
            ], 404);
        }

        try {
            $orders = $this->orderService->getUserOrders(
                (int) $userId,
                $request->validated('status'),
            );

            return response()->json([
                'message' => 'Berhasil mengambil daftar pesanan',
                'data' => OrderResource::collection($orders),
            ]);
        } catch (Throwable $exception) {
            return $this->handleServerError($exception, 'mengambil daftar pesanan');
        }
    }

    public function pay(string $id): JsonResponse
    {
        if (! ctype_digit($id)) {
            return response()->json([
                'message' => "Pesanan dengan id {$id} tidak ditemukan",
            ], 404);
        }

        try {
            $order = $this->orderService->payOrder((int) $id);

            return response()->json([
                'message' => 'Pesanan berhasil dibayar',
                'data' => new OrderResource($order),
            ]);
        } catch (OrderNotFoundException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 404);
        } catch (InvalidOrderStatusTransitionException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 409);
        } catch (Throwable $exception) {
            return $this->handleServerError($exception, 'membayar pesanan');
        }
    }

    private function handleUpstreamError(UpstreamServiceException $e): JsonResponse
    {
        Log::error("Kegagalan memanggil service upstream ({$e->serviceName}): {$e->getMessage()}", [
            'service' => $e->serviceName,
            'exception' => $e,
        ]);

        $body = [
            'message' => $e->clientMessage,
        ];

        if (config('app.debug')) {
            $body['error'] = $e->getMessage();
            $body['location'] = $e->getFile().':'.$e->getLine();
        }

        return response()->json($body, 503);
    }

    private function handleServerError(Throwable $e, string $action): JsonResponse
    {
        report($e);

        $body = [
            'message' => "Gagal {$action}",
        ];

        if (config('app.debug')) {
            $body['error'] = $e->getMessage();
            $body['location'] = $e->getFile().':'.$e->getLine();
        }

        return response()->json($body, 500);
    }
}
