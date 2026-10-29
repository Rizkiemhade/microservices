<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
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

    public function test_health_check_endpoint(): void
    {
        $response = $this->getJson('/health');

        $response->assertOk()
            ->assertJson([
                'status' => 'ok',
                'service' => 'order-service',
            ]);
    }

    public function test_checkout_creates_order_successfully(): void
    {
        $this->fakeCart(7, [
            ['product_id' => 1, 'quantity' => 2],
            ['product_id' => 2, 'quantity' => 1],
        ]);
        $this->fakeProduct(1, 'Keyboard Mekanikal', '450000.00', 25);
        $this->fakeProduct(2, 'Mouse Wireless', '150000.00', 40);
        $this->fakeClearCart(7);

        $response = $this->postJson('/orders', [
            'user_id' => 7,
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Pesanan berhasil dibuat')
            ->assertJsonPath('data.user_id', 7)
            ->assertJsonPath('data.status', 'PENDING')
            ->assertJsonPath('data.total_amount', 1050000)
            ->assertJsonPath('data.items.0.product_id', 1)
            ->assertJsonPath('data.items.0.product_name', 'Keyboard Mekanikal')
            ->assertJsonPath('data.items.0.unit_price', 450000)
            ->assertJsonPath('data.items.0.quantity', 2)
            ->assertJsonPath('data.items.0.subtotal', 900000)
            ->assertJsonPath('data.items.1.product_id', 2)
            ->assertJsonPath('data.items.1.product_name', 'Mouse Wireless')
            ->assertJsonPath('data.items.1.unit_price', 150000)
            ->assertJsonPath('data.items.1.quantity', 1)
            ->assertJsonPath('data.items.1.subtotal', 150000);

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 2);
        $this->assertDatabaseHas('orders', [
            'user_id' => 7,
            'status' => 'PENDING',
            'total_amount' => 1050000,
        ]);
        $this->assertDatabaseHas('order_items', [
            'product_id' => 1,
            'product_name' => 'Keyboard Mekanikal',
            'unit_price' => 450000,
            'quantity' => 2,
            'subtotal' => 900000,
        ]);

        // Verifikasi cart-service dipanggil untuk mengosongkan keranjang
        Http::assertSent(fn ($request) => $request->method() === 'DELETE' && $request->url() === 'http://cart.test/carts/7');
    }

    public function test_checkout_fails_validation_when_body_missing_user_id(): void
    {
        $response = $this->postJson('/orders', []);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Data yang dikirim tidak valid')
            ->assertJsonValidationErrors('user_id')
            ->assertJsonPath('errors.user_id.0', 'Field user_id wajib diisi');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_fails_with_409_when_cart_is_empty(): void
    {
        $this->fakeCart(7, []);

        $response = $this->postJson('/orders', ['user_id' => 7]);

        $response->assertStatus(409)
            ->assertJsonPath('message', 'Keranjang belanja kosong');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_fails_with_409_when_product_not_found_in_product_service(): void
    {
        $this->fakeCart(7, [['product_id' => 99, 'quantity' => 1]]);
        Http::fake([
            'http://product.test/products/99' => Http::response(['message' => 'Produk tidak ditemukan'], 404),
        ]);

        $response = $this->postJson('/orders', ['user_id' => 7]);

        $response->assertStatus(409);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_fails_with_409_when_stock_is_insufficient(): void
    {
        $this->fakeCart(7, [['product_id' => 3, 'quantity' => 11]]);
        $this->fakeProduct(3, 'Monitor 24 Inch', '2000000.00', 10);

        $response = $this->postJson('/orders', ['user_id' => 7]);

        $response->assertStatus(409)
            ->assertJsonPath('message', 'Stok Monitor 24 Inch tidak mencukupi: diminta 11, tersedia 10');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_fails_with_503_when_product_service_is_down(): void
    {
        $this->fakeCart(7, [['product_id' => 1, 'quantity' => 1]]);
        Http::fake([
            'http://product.test/products/1' => function () {
                throw new ConnectionException('cURL error 28: Operation timed out.');
            },
        ]);

        $response = $this->postJson('/orders', ['user_id' => 7]);

        $response->assertStatus(503)
            ->assertJsonPath('message', 'Product service tidak dapat dihubungi, coba lagi nanti');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_fails_with_503_when_cart_service_is_down(): void
    {
        Http::fake([
            'http://cart.test/carts/7' => function () {
                throw new ConnectionException('cURL error 7: Failed to connect.');
            },
        ]);

        $response = $this->postJson('/orders', ['user_id' => 7]);

        $response->assertStatus(503)
            ->assertJsonPath('message', 'Cart service tidak dapat dihubungi, coba lagi nanti');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_snapshots_price_correctly(): void
    {
        $this->fakeCart(7, [['product_id' => 1, 'quantity' => 1]]);
        $this->fakeProduct(1, 'Keyboard Mekanikal', '450000.00', 25);
        $this->fakeClearCart(7);

        $response = $this->postJson('/orders', ['user_id' => 7]);
        $response->assertCreated();

        $orderId = $response->json('data.id');

        // Simulasi perubahan harga di product-service setelah pesanan dibuat
        $this->fakeProduct(1, 'Keyboard Mekanikal', '999999.00', 25);

        // Detail pesanan lama harus tetap memakai harga snapshot awal
        $getDetail = $this->getJson("/orders/{$orderId}");
        $getDetail->assertOk()
            ->assertJsonPath('data.total_amount', 450000)
            ->assertJsonPath('data.items.0.unit_price', 450000);
    }

    public function test_order_is_not_rolled_back_if_cart_clear_fails(): void
    {
        $this->fakeCart(7, [['product_id' => 1, 'quantity' => 1]]);
        $this->fakeProduct(1, 'Keyboard Mekanikal', '450000.00', 25);

        // Simulasi kegagalan DELETE cart-service
        Http::fake([
            'http://cart.test/carts/7' => function ($request) {
                if ($request->method() === 'DELETE') {
                    throw new ConnectionException('Cart service unavailable on delete');
                }
                return Http::response([
                    'data' => [
                        'user_id' => 7,
                        'items' => [['product_id' => 1, 'quantity' => 1]],
                        'total_quantity' => 1,
                    ],
                ]);
            },
            'http://product.test/products/1' => Http::response([
                'data' => ['id' => 1, 'name' => 'Keyboard Mekanikal', 'price' => '450000.00', 'stock' => 25],
            ]),
        ]);

        // Pesanan harus TETAP berhasil dibuat (201)
        $response = $this->postJson('/orders', ['user_id' => 7]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Pesanan berhasil dibuat');

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
    }

    private function fakeCart(int $userId, array $items): void
    {
        Http::fake([
            "http://cart.test/carts/{$userId}" => Http::response([
                'data' => [
                    'user_id' => $userId,
                    'items' => $items,
                    'total_quantity' => array_sum(array_column($items, 'quantity')),
                ],
            ]),
        ]);
    }

    private function fakeClearCart(int $userId): void
    {
        Http::fake([
            "http://cart.test/carts/{$userId}" => function ($request) {
                if ($request->method() === 'DELETE') {
                    return Http::response([
                        'message' => 'Keranjang berhasil dikosongkan',
                        'data' => ['deleted_items' => 2],
                    ], 200);
                }
                return Http::response([], 404);
            },
        ]);
    }

    private function fakeProduct(int $id, string $name, string $price, int $stock = 25): void
    {
        Http::fake([
            "http://product.test/products/{$id}" => Http::response([
                'data' => [
                    'id' => $id,
                    'name' => $name,
                    'price' => $price,
                    'stock' => $stock,
                ],
            ]),
        ]);
    }
}
