<?php

namespace App\Services;

use App\Exceptions\ProductNotFoundException;
use App\Exceptions\UpstreamServiceException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class ProductServiceClient
{
    /**
     * @return array<string, mixed>
     */
    public function getProduct(int $productId): array
    {
        try {
            $response = Http::acceptJson()
                ->connectTimeout(config('services.product.connect_timeout'))
                ->timeout(config('services.product.timeout'))
                ->get(rtrim(config('services.product.url'), '/')."/products/{$productId}");
        } catch (ConnectionException $exception) {
            throw new UpstreamServiceException('Product Service', 'connection timed out or failed', previous: $exception);
        }

        if ($response->status() === 404) {
            throw new ProductNotFoundException($productId);
        }

        if (! $response->successful()) {
            throw new UpstreamServiceException('Product Service', 'unexpected response', $response->status());
        }

        $product = $response->json('data');

        if (! is_array($product)
            || ! isset($product['id'], $product['name'], $product['price'])
            || ! is_numeric($product['id'])
            || (int) $product['id'] !== $productId
            || (float) $product['id'] !== (float) (int) $product['id']
            || ! is_string($product['name'])
            || ! is_numeric($product['price'])
            || (float) $product['price'] < 0) {
            throw new UpstreamServiceException('Product Service', 'invalid response format');
        }

        return $product;
    }
}
