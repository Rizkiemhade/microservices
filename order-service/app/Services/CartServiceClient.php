<?php

namespace App\Services;

use App\Exceptions\CartNotFoundException;
use App\Exceptions\UpstreamServiceException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class CartServiceClient
{
    /**
     * @return array<string, mixed>
     */
    public function getCart(int $userId): array
    {
        try {
            $response = Http::acceptJson()
                ->connectTimeout(config('services.cart.connect_timeout'))
                ->timeout(config('services.cart.timeout'))
                ->get(rtrim(config('services.cart.url'), '/')."/carts/{$userId}");
        } catch (ConnectionException $exception) {
            throw new UpstreamServiceException('Cart Service', 'connection timed out or failed', previous: $exception);
        }

        if ($response->status() === 404) {
            throw new CartNotFoundException($userId);
        }

        if (! $response->successful()) {
            throw new UpstreamServiceException('Cart Service', 'unexpected response', $response->status());
        }

        $cart = $response->json('data');

        if (! is_array($cart)
            || ! isset($cart['user_id'])
            || ! is_numeric($cart['user_id'])
            || (int) $cart['user_id'] !== $userId
            || (float) $cart['user_id'] !== (float) (int) $cart['user_id']
            || ! isset($cart['items'])
            || ! is_array($cart['items'])) {
            throw new UpstreamServiceException('Cart Service', 'invalid response format');
        }

        foreach ($cart['items'] as $item) {
            if (! is_array($item)
                || ! isset($item['product_id'], $item['quantity'])
                || ! is_numeric($item['product_id'])
                || ! is_numeric($item['quantity'])
                || (int) $item['product_id'] < 1
                || (int) $item['quantity'] < 1
                || (float) $item['product_id'] !== (float) (int) $item['product_id']
                || (float) $item['quantity'] !== (float) (int) $item['quantity']) {
                throw new UpstreamServiceException('Cart Service', 'invalid cart item format');
            }
        }

        return $cart;
    }
}
