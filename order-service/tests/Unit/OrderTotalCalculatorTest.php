<?php

namespace Tests\Unit;

use App\Exceptions\UpstreamServiceException;
use App\Services\OrderTotalCalculator;
use PHPUnit\Framework\TestCase;

class OrderTotalCalculatorTest extends TestCase
{
    public function test_it_calculates_totals_in_cents_and_preserves_product_snapshots(): void
    {
        $result = (new OrderTotalCalculator)->calculate([
            [
                'product' => ['id' => 7, 'name' => 'Keyboard', 'price' => '19.99'],
                'quantity' => 2,
            ],
            [
                'product' => ['id' => 8, 'name' => 'Cable', 'price' => '0.01'],
                'quantity' => 3,
            ],
        ]);

        $this->assertSame('40.01', $result['total_amount']);
        $this->assertSame([
            [
                'product_id' => 7,
                'product_name' => 'Keyboard',
                'unit_price' => '19.99',
                'quantity' => 2,
            ],
            [
                'product_id' => 8,
                'product_name' => 'Cable',
                'unit_price' => '0.01',
                'quantity' => 3,
            ],
        ], $result['items']);
    }

    public function test_it_supports_whole_number_and_zero_prices(): void
    {
        $result = (new OrderTotalCalculator)->calculate([
            [
                'product' => ['id' => 1, 'name' => 'Free sample', 'price' => '0'],
                'quantity' => 4,
            ],
            [
                'product' => ['id' => 2, 'name' => 'Book', 'price' => 100],
                'quantity' => 1,
            ],
        ]);

        $this->assertSame('100.00', $result['total_amount']);
        $this->assertSame('0.00', $result['items'][0]['unit_price']);
        $this->assertSame('100.00', $result['items'][1]['unit_price']);
    }

    public function test_it_rejects_prices_that_cannot_be_represented_as_currency_cents(): void
    {
        foreach (['1.999', '1e3', -1, 1.25, null] as $price) {
            try {
                (new OrderTotalCalculator)->calculate([
                    [
                        'product' => ['id' => 1, 'name' => 'Invalid', 'price' => $price],
                        'quantity' => 1,
                    ],
                ]);
                $this->fail('Expected invalid price to be rejected.');
            } catch (UpstreamServiceException $exception) {
                $this->assertSame('Product Service', $exception->service);
            }
        }
    }

    public function test_it_accepts_the_maximum_order_total_without_rounding(): void
    {
        $result = (new OrderTotalCalculator)->calculate([
            [
                'product' => ['id' => 1, 'name' => 'Maximum item', 'price' => '9999999999.99'],
                'quantity' => 1,
            ],
        ]);

        $this->assertSame('9999999999.99', $result['total_amount']);
    }
}
