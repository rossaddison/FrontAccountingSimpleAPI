<?php

declare(strict_types=1);

namespace FAAPI\Sales\Domain;

/**
 * A customer document that exists in FrontAccounting and has not been voided.
 */
final readonly class LocatedDocument
{
    public function __construct(
        public int $transNo,
        public string $reference,
        public Money $grossTotal,
    ) {
    }
}
