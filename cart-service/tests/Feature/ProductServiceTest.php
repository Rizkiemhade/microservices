<?php

namespace Tests\Feature;

use App\Services\ProductService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class ProductServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.product.url' => 'http://product.test',
            'services.product.timeout' => 5,
            'services.product.connect_timeout' => 2,
        ]);
    }

    public function test_it_fetches_the_product_endpoint_and_returns_the_data_envelope(): void
    {
        Http::fake([
            'http://product.test/products/7' => Http::response([
                'message' => 'Success',
                'data' => ['id' => 7, 'name' => 'Keyboard', 'price' => '25000.00'],
            ]),
        ]);

        $product = (new ProductService)->getProduct(7);

        $this->assertSame(7, $product['id']);
        $this->assertSame('Keyboard', $product['name']);
        Http::assertSent(fn ($request) => $request->url() === 'http://product.test/products/7');
    }

    public function test_it_returns_null_when_the_product_does_not_exist(): void
    {
        Http::fake([
            'http://product.test/products/7' => Http::response(['message' => 'Not found'], 404),
        ]);

        $this->assertNull((new ProductService)->getProduct(7));
    }

    public function test_it_surfaces_product_service_server_errors(): void
    {
        Http::fake([
            'http://product.test/products/7' => Http::response(['message' => 'Unavailable'], 503),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Product Service returned HTTP 503');

        (new ProductService)->getProduct(7);
    }

    public function test_it_surfaces_product_service_connection_failures(): void
    {
        Http::fake([
            'http://product.test/products/7' => function () {
                throw new ConnectionException('Connection timed out.');
            },
        ]);

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Connection timed out.');

        (new ProductService)->getProduct(7);
    }
}
