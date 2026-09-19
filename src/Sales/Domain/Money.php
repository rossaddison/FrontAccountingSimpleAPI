<?php

declare(strict_types=1);

namespace FAAPI\Sales\Domain;

use InvalidArgumentException;

/**
 * An amount held as whole minor units (pence, cents), so comparisons and
 * totals never depend on floating point.
 */
final readonly class Money
{
    private function __construct(public int $minorUnits)
    {
    }

    public static function fromDecimal(float|int|string $amount): self
    {
        if (!is_numeric($amount)) {
            throw new InvalidArgumentException('An amount must be numeric.');
        }

        // Round to the currency's two decimals first: multiplying a value like
        // 1.005 by 100 gives 100.49999999999999, which would round the wrong way.
        return new self((int) round(round((float) $amount, 2) * 100));
    }

    public function toDecimal(): float
    {
        return $this->minorUnits / 100;
    }

    public function equals(self $other): bool
    {
        return $this->minorUnits === $other->minorUnits;
    }
}
