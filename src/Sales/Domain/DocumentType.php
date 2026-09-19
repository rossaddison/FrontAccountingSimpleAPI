<?php

declare(strict_types=1);

namespace FAAPI\Sales\Domain;

use InvalidArgumentException;

/**
 * The customer documents an integration needs to find again. The values are
 * FrontAccounting's own transaction type numbers (ST_SALESINVOICE and so on).
 */
enum DocumentType: int
{
    case SalesInvoice = 10;
    case CreditNote = 11;
    case CustomerPayment = 12;

    public static function fromTransType(int|string $transType): self
    {
        $type = is_int($transType) || ctype_digit($transType) ? self::tryFrom((int) $transType) : null;
        if ($type === null) {
            throw new InvalidArgumentException('trans_type must be 10 (invoice), 11 (credit note) or 12 (payment).');
        }

        return $type;
    }
}
