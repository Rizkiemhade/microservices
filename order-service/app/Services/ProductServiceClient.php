<?php

namespace App\Services;

use App\Exceptions\ProductNotFoundException;
use App\Exceptions\UpstreamServiceException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

class ProductServiceClient
{
    /**
     * @return array<string, mixed>
     */
    public function getProduct(int $productId): array
    {
        $baseUrl = rtrim(config('services.product.url', 'http://localhost:3000'), '/');
        $timeout = min((int) config('services.product.timeout', 5), 5);
        $connectTimeout = (int) config('services.product.connect_timeout', 2);

        try {
            $response = Http::acceptJson()
                ->connectTimeout($connectTimeout)
                ->timeout($timeout)
                ->get("{$baseUrl}/products/{$productId}");
        } catch (ConnectionException $exception) {
            throw new UpstreamServiceException(
                'Product service',
                'Product service tidak dapat dihubungi, coba lagi nanti',
                "Connection failure to Product Service at {$baseUrl}: {$exception->getMessage()}",
                $exception,
            );
        } catch (Throwable $exception) {
            throw new UpstreamServiceException(
                'Product service',
                'Product service tidak dapat dihubungi, coba lagi nanti',
                "Unexpected failure calling Product Service: {$exception->getMessage()}",
                $exception,
            );
        }

        if ($response->status() === 404) {
            throw new ProductNotFoundException($productId, 'Produk tidak ditemukan di product-service');
        }

        if (! $response->successful()) {
            throw new UpstreamServiceException(
                'Product service',
                'Product service tidak dapat dihubungi, coba lagi nanti',
                "Product Service returned HTTP {$response->status()} for product {$productId}",
            );
        }

        $product = $response->json('data');

        if (! is_array($product) || ! isset($product['id'], $product['name'], $product['price'])) {
            throw new UpstreamServiceException(
                'Product service',
                'Product service tidak dapat dihubungi, coba lagi nanti',
                "Product Service returned invalid payload format for product {$productId}",
            );
        }

        return $product;
    }
}
