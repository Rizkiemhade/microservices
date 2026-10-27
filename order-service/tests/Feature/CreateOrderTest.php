<?php

namespace Tests\Feature;

use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class CreateOrderTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_checkout_creates_order_with_product_snapshots_and_server_calculated_total(): void
    {
        $this->fakeCart([
            ['product_id' => 7, 'quantity' => 2],
            ['product_id' => 8, 'quantity' => 3],
        ]);
        $this->fakeProduct(7, 'Keyboard', '19.99');
        $this->fakeProduct(8, 'Cable', '0.01');

        $response = $this->postJson('/api/orders', [
            'user_id' => 42,
            'idempotency_key' => 'checkout-42-001',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.user_id', 42)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.total_amount', '40.01')
            ->assertJsonPath('data.currency', 'IDR')
            ->assertJsonPath('data.items.0.product_name', 'Keyboard')
            ->assertJsonPath('data.items.0.unit_price', '19.99')
            ->assertJsonPath('data.items.0.quantity', 2)
            ->assertJsonPath('data.items.1.product_name', 'Cable')
            ->assertJsonPath('data.items.1.unit_price', '0.01')
            ->assertJsonMissingPath('data.idempotency_key');

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 2);
        $this->assertDatabaseHas('orders', [
            'user_id' => 42,
            'total_amount' => '40.01',
            'idempotency_key' => 'checkout-42-001',
        ]);
        $this->assertDatabaseHas('order_items', [
            'product_id' => 7,
            'product_name' => 'Keyboard',
            'unit_price' => '19.99',
            'quantity' => 2,
        ]);
    }

    public function test_checkout_rejects_an_empty_cart_without_creating_an_order(): void
    {
        $this->fakeCart([]);

        $this->postJson('/api/orders', ['user_id' => 42])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cart');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/products/'));
    }

    public function test_checkout_rejects_client_supplied_items_and_prices_before_calling_services(): void
    {
        $this->postJson('/api/orders', [
            'user_id' => 42,
            'items' => [['product_id' => 7, 'quantity' => 1]],
            'total_amount' => 1,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items', 'total_amount']);

        $this->assertDatabaseCount('orders', 0);
        Http::assertNothingSent();
    }

    public function test_checkout_reports_a_missing_product_without_creating_an_order(): void
    {
        $this->fakeCart([['product_id' => 7, 'quantity' => 1]]);
        Http::fake([
            'http://product.test/products/7' => Http::response(['message' => 'Not found'], 404),
        ]);

        $this->postJson('/api/orders', ['user_id' => 42])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cart');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
    }

    public function test_checkout_reports_a_missing_cart_without_creating_an_order(): void
    {
        Http::fake([
            'http://cart.test/carts/42' => Http::response(['message' => 'Not found'], 404),
        ]);

        $this->postJson('/api/orders', ['user_id' => 42])
            ->assertNotFound()
            ->assertJsonPath('message', 'Cart for user 42 was not found.');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
    }

    public function test_checkout_reports_product_service_timeout_without_creating_an_order(): void
    {
        $this->fakeCart([['product_id' => 7, 'quantity' => 1]]);
        Http::fake([
            'http://product.test/products/7' => function () {
                throw new ConnectionException('cURL error 28: Operation timed out.');
            },
        ]);

        $this->postJson('/api/orders', ['user_id' => 42])
            ->assertServiceUnavailable()
            ->assertJsonPath('service', 'Product Service');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
    }

    public function test_checkout_rejects_a_total_larger_than_the_order_column_can_store(): void
    {
        $this->fakeCart([['product_id' => 7, 'quantity' => 2]]);
        $this->fakeProduct(7, 'Expensive item', '9999999999.99');

        $this->postJson('/api/orders', ['user_id' => 42])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cart');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
    }

    public function test_checkout_reports_upstream_failure_without_creating_an_order(): void
    {
        Http::fake([
            'http://cart.test/carts/42' => Http::response(['message' => 'Unavailable'], 503),
        ]);

        $this->postJson('/api/orders', ['user_id' => 42])
            ->assertServiceUnavailable()
            ->assertJsonPath('service', 'Cart Service');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
    }

    public function test_checkout_replays_an_existing_order_for_the_same_idempotency_key(): void
    {
        $this->fakeCart([['product_id' => 7, 'quantity' => 1]]);
        $this->fakeProduct(7, 'Keyboard', '25.00');

        $payload = ['user_id' => 42, 'idempotency_key' => 'retry-001'];
        $firstResponse = $this->postJson('/api/orders', $payload)->assertCreated();
        $orderId = $firstResponse->json('data.id');

        $this->postJson('/api/orders', $payload)
            ->assertOk()
            ->assertJsonPath('data.id', $orderId);

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
        Http::assertSentCount(2);
    }

    public function test_order_and_items_are_rolled_back_if_an_item_cannot_be_saved(): void
    {
        $this->fakeCart([['product_id' => 7, 'quantity' => 1]]);
        $this->fakeProduct(7, 'Keyboard', '25.00');

        DB::connection()->beforeExecuting(function (string $query): void {
            if (str_contains($query, 'insert into "order_items"')) {
                throw new RuntimeException('Simulated order item insert failure.');
            }
        });

        try {
            app(OrderService::class)->createFromCart(42, null);
            $this->fail('Expected the item insert failure to escape the transaction.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated order item insert failure.', $exception->getMessage());
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
    }

    private function fakeCart(array $items): void
    {
        Http::fake([
            'http://cart.test/carts/42' => Http::response([
                'data' => [
                    'user_id' => 42,
                    'items' => $items,
                    'total_quantity' => array_sum(array_column($items, 'quantity')),
                ],
            ]),
        ]);
    }

    private function fakeProduct(int $id, string $name, string $price): void
    {
        Http::fake([
            "http://product.test/products/{$id}" => Http::response([
                'data' => ['id' => $id, 'name' => $name, 'price' => $price],
            ]),
        ]);
    }
}
