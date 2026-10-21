<?php

namespace App\Services;

use illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductService
{
    public function getProduct(int $productId): ?array
    {
        try {
            $response = Http::get(config('services.product.url'))
                ->get("/products/{$productId}");

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (Throwable $e) {
            Log::error('Gagal mengambil produk' , [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);
            
            return null;


        }
    }
}