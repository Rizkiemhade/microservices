<?php

namespace App\Services;

use App\Exceptions\UpstreamServiceException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

class CartServiceClient
{
    /**
     * @return array<string, mixed>
     */
    public function getCart(int $userId): array
    {
        $baseUrl = rtrim(config('services.cart.url', 'http://localhost:3001'), '/');
        $timeout = min((int) config('services.cart.timeout', 5), 5);
        $connectTimeout = (int) config('services.cart.connect_timeout', 2);

        try {
            $response = Http::acceptJson()
                ->connectTimeout($connectTimeout)
                ->timeout($timeout)
                ->get("{$baseUrl}/carts/{$userId}");
        } catch (ConnectionException $exception) {
            throw new UpstreamServiceException(
                'Cart service',
                'Cart service tidak dapat dihubungi, coba lagi nanti',
                "Connection failure to Cart Service at {$baseUrl}: {$exception->getMessage()}",
                $exception,
            );
        } catch (Throwable $exception) {
            throw new UpstreamServiceException(
                'Cart service',
                'Cart service tidak dapat dihubungi, coba lagi nanti',
                "Unexpected failure calling Cart Service: {$exception->getMessage()}",
                $exception,
            );
        }

        if (! $response->successful()) {
            throw new UpstreamServiceException(
                'Cart service',
                'Cart service tidak dapat dihubungi, coba lagi nanti',
                "Cart Service returned HTTP {$response->status()} for user {$userId}",
            );
        }

        $cart = $response->json('data');

        if (! is_array($cart) || ! isset($cart['items']) || ! is_array($cart['items'])) {
            throw new UpstreamServiceException(
                'Cart service',
                'Cart service tidak dapat dihubungi, coba lagi nanti',
                "Cart Service returned invalid payload format for user {$userId}",
            );
        }

        return $cart;
    }

    public function clearCart(int $userId): bool
    {
        $baseUrl = rtrim(config('services.cart.url', 'http://localhost:3001'), '/');
        $timeout = min((int) config('services.cart.timeout', 5), 5);
        $connectTimeout = (int) config('services.cart.connect_timeout', 2);

        try {
            $response = Http::acceptJson()
                ->connectTimeout($connectTimeout)
                ->timeout($timeout)
                ->delete("{$baseUrl}/carts/{$userId}");
        } catch (ConnectionException $exception) {
            throw new UpstreamServiceException(
                'Cart service',
                'Cart service tidak dapat dihubungi, coba lagi nanti',
                "Connection failure when clearing Cart Service for user {$userId}: {$exception->getMessage()}",
                $exception,
            );
        } catch (Throwable $exception) {
            throw new UpstreamServiceException(
                'Cart service',
                'Cart service tidak dapat dihubungi, coba lagi nanti',
                "Unexpected failure clearing Cart Service for user {$userId}: {$exception->getMessage()}",
                $exception,
            );
        }

        if (! $response->successful()) {
            throw new UpstreamServiceException(
                'Cart service',
                'Cart service tidak dapat dihubungi, coba lagi nanti',
                "Cart Service returned HTTP {$response->status()} when clearing cart for user {$userId}",
            );
        }

        return true;
    }
}
