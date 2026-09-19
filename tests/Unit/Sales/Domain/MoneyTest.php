<?php

declare(strict_types=1);

namespace FAAPI\Tests\Unit\Sales\Domain;

use FAAPI\Sales\Domain\Money;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    /**
     * @return array<string, array{float|int|string, int}>
     */
    public static function amounts(): array
    {
        return [
            'whole number' => [100, 10000],
            'two decimals' => [95.24, 9524],
            'numeric string' => ['203.98', 20398],
            'negative credit' => [-120.5, -12050],
            'floating point noise' => [0.1 + 0.2, 30],
            'rounds half up' => [1.005, 101],
            'zero' => [0, 0],
        ];
    }

    #[DataProvider('amounts')]
    public function testHoldsWholeMinorUnits(float|int|string $amount, int $expected): void
    {
        self::assertSame($expected, Money::fromDecimal($amount)->minorUnits);
    }

    public function testConvertsBackToADecimal(): void
    {
        self::assertSame(203.98, Money::fromDecimal('203.98')->toDecimal());
        self::assertSame(100.0, Money::fromDecimal(100)->toDecimal());
    }

    public function testComparesByValue(): void
    {
        self::assertTrue(Money::fromDecimal(0.1 + 0.2)->equals(Money::fromDecimal(0.3)));
        self::assertFalse(Money::fromDecimal(1)->equals(Money::fromDecimal(1.01)));
    }

    public function testRejectsANonNumericAmount(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromDecimal('twelve');
    }
}
