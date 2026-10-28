<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class ProductService
{
    public function getProduct(int $productId): ?array
    {
        $response = Http::acceptJson()
            ->connectTimeout(config('services.product.connect_timeout'))
            ->timeout(config('services.product.timeout'))
            ->get(rtrim(config('services.product.url'), '/')."/products/{$productId}");

        if ($response->status() === 404) {
            return null;
        }

        if (! $response->successful()) {
            throw new RuntimeException(
                "Product Service returned HTTP {$response->status()} for product {$productId}.",
            );
        }

        $product = $response->json('data');

        if (! is_array($product)
            || ! isset($product['id'])
            || ! is_numeric($product['id'])
            || (int) $product['id'] !== $productId) {
            throw new RuntimeException("Product Service returned invalid data for product {$productId}.");
        }

        return $product;
    }
}
