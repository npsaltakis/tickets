<?php

use App\Libraries\BookingService;
use App\Models\DiscountCodeModel;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class BookingRulesTest extends CIUnitTestCase
{
    public function testSplitAmountNeverLosesCents(): void
    {
        $parts = BookingService::splitAmount(10.00, 3);

        $this->assertSame(['3.34', '3.33', '3.33'], $parts);
        $this->assertEqualsWithDelta(10.00, array_sum(array_map('floatval', $parts)), 0.0001);
    }

    public function testSplitAmountSingleSeat(): void
    {
        $this->assertSame(['7.50'], BookingService::splitAmount(7.5, 1));
    }

    public function testAmountsMatchToleratesFloatNoise(): void
    {
        $this->assertTrue(BookingService::amountsMatch(0.1 + 0.2, 0.30));
        $this->assertFalse(BookingService::amountsMatch(10.00, 9.99));
    }

    public function testPercentDiscount(): void
    {
        $model = new DiscountCodeModel();

        $this->assertSame(80.0, $model->applyDiscount(['type' => 'percent', 'value' => 20], 100.0));
        $this->assertSame(0.0, $model->applyDiscount(['type' => 'percent', 'value' => 150], 100.0));
    }

    public function testFixedDiscountNeverGoesNegative(): void
    {
        $model = new DiscountCodeModel();

        $this->assertSame(15.0, $model->applyDiscount(['type' => 'fixed', 'value' => 5], 20.0));
        $this->assertSame(0.0, $model->applyDiscount(['type' => 'fixed', 'value' => 50], 20.0));
    }

    public function testCsvSafeNeutralisesFormulas(): void
    {
        $this->assertSame("'=SUM(A1:A9)", csv_safe('=SUM(A1:A9)'));
        $this->assertSame("'+cmd", csv_safe('+cmd'));
        $this->assertSame("'@evil", csv_safe('@evil'));
        $this->assertSame("'-2+3", csv_safe('-2+3'));
        $this->assertSame("'\tTab", csv_safe("\tTab"));
        $this->assertSame("'\rCR", csv_safe("\rCR"));
    }

    public function testCsvSafeLeavesNormalValuesAlone(): void
    {
        $this->assertSame('John Doe', csv_safe('John Doe'));
        $this->assertSame('-5', csv_safe('-5'));
        $this->assertSame('12.5', csv_safe(12.5));
        $this->assertSame('', csv_safe(''));
    }
}
