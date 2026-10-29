<?php

namespace Tests\Feature;

use App\Exceptions\ProductNotFoundException;
use App\Exceptions\UpstreamServiceException;
use App\Services\CartServiceClient;
use App\Services\ProductServiceClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ServiceClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.cart.url' => 'http://cart.test',
            'services.cart.timeout' => 5,
            'services.cart.connect_timeout' => 2,
            'services.product.url' => 'http://product.test',
            'services.product.timeout' => 5,
            'services.product.connect_timeout' => 2,
        ]);
    }

    public function test_cart_client_returns_cart_data_from_cart_service(): void
    {
        Http::fake([
            'http://cart.test/carts/42' => Http::response([
                'message' => 'Success',
                'data' => [
                    'user_id' => 42,
                    'items' => [
                        ['product_id' => 7, 'quantity' => 2],
                    ],
                    'total_quantity' => 2,
                ],
            ]),
        ]);

        $cart = (new CartServiceClient)->getCart(42);

        $this->assertSame(42, $cart['user_id']);
        $this->assertSame([['product_id' => 7, 'quantity' => 2]], $cart['items']);
        Http::assertSent(fn ($request) => $request->url() === 'http://cart.test/carts/42');
    }

    public function test_cart_client_can_clear_cart(): void
    {
        Http::fake([
            'http://cart.test/carts/42' => Http::response([
                'message' => 'Keranjang berhasil dikosongkan',
                'data' => ['deleted_items' => 2],
            ]),
        ]);

        $result = (new CartServiceClient)->clearCart(42);

        $this->assertTrue($result);
        Http::assertSent(fn ($request) => $request->method() === 'DELETE' && $request->url() === 'http://cart.test/carts/42');
    }

    public function test_product_client_returns_product_data_from_product_service(): void
    {
        Http::fake([
            'http://product.test/products/7' => Http::response([
                'message' => 'Success',
                'data' => ['id' => 7, 'name' => 'Keyboard', 'price' => '25000.00', 'stock' => 10],
            ]),
        ]);

        $product = (new ProductServiceClient)->getProduct(7);

        $this->assertSame(7, $product['id']);
        $this->assertSame('Keyboard', $product['name']);
        $this->assertSame('25000.00', $product['price']);
        Http::assertSent(fn ($request) => $request->url() === 'http://product.test/products/7');
    }

    public function test_product_client_reports_a_missing_product(): void
    {
        Http::fake([
            'http://product.test/products/7' => Http::response(['message' => 'Not found'], 404),
        ]);

        $this->expectException(ProductNotFoundException::class);

        (new ProductServiceClient)->getProduct(7);
    }

    public function test_cart_client_reports_upstream_server_errors(): void
    {
        Http::fake([
            'http://cart.test/carts/42' => Http::response(['message' => 'Unavailable'], 503),
        ]);

        try {
            (new CartServiceClient)->getCart(42);
            $this->fail('Expected Cart Service failure to be reported.');
        } catch (UpstreamServiceException $exception) {
            $this->assertSame('Cart service', $exception->serviceName);
        }
    }

    public function test_product_client_reports_connection_failures(): void
    {
        Http::fake([
            'http://product.test/products/7' => function () {
                throw new ConnectionException('cURL error 28: Operation timed out.');
            },
        ]);

        try {
            (new ProductServiceClient)->getProduct(7);
            $this->fail('Expected Product Service connection failure to be reported.');
        } catch (UpstreamServiceException $exception) {
            $this->assertSame('Product service', $exception->serviceName);
            $this->assertInstanceOf(ConnectionException::class, $exception->getPrevious());
        }
    }
}
