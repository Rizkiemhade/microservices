<?php

namespace Tests\Unit;

use App\Services\OrderTotalCalculator;
use PHPUnit\Framework\TestCase;

class OrderTotalCalculatorTest extends TestCase
{
    public function test_it_calculates_totals_and_subtotals_correctly(): void
    {
        $result = (new OrderTotalCalculator)->calculate([
            [
                'product' => ['id' => 1, 'name' => 'Keyboard Mekanikal', 'price' => '450000.00'],
                'quantity' => 2,
            ],
            [
                'product' => ['id' => 2, 'name' => 'Mouse Wireless', 'price' => '150000.00'],
                'quantity' => 1,
            ],
        ]);

        $this->assertEquals(1050000, $result['total_amount']);
        $this->assertCount(2, $result['items']);

        $this->assertSame(1, $result['items'][0]['product_id']);
        $this->assertSame('Keyboard Mekanikal', $result['items'][0]['product_name']);
        $this->assertEquals(450000, $result['items'][0]['unit_price']);
        $this->assertSame(2, $result['items'][0]['quantity']);
        $this->assertEquals(900000, $result['items'][0]['subtotal']);

        $this->assertSame(2, $result['items'][1]['product_id']);
        $this->assertSame('Mouse Wireless', $result['items'][1]['product_name']);
        $this->assertEquals(150000, $result['items'][1]['unit_price']);
        $this->assertSame(1, $result['items'][1]['quantity']);
        $this->assertEquals(150000, $result['items'][1]['subtotal']);
    }
}
