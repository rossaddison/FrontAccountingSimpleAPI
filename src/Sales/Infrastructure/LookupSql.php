<?php

declare(strict_types=1);

namespace FAAPI\Sales\Infrastructure;

use Closure;
use FAAPI\Sales\Domain\DocumentMemo;
use FAAPI\Sales\Domain\DocumentType;

/**
 * Builds the query that finds a live document by memo. Kept separate from
 * the class that runs it, so the SQL (and that every value goes through the
 * escaper) can be tested without a FrontAccounting database.
 */
final class LookupSql
{
    /**
     * @param Closure(string): string $quote escapes and quotes one value, e.g. FrontAccounting's db_escape()
     */
    public static function build(DocumentType $type, DocumentMemo $memo, string $tablePrefix, Closure $quote): string
    {
        return 'SELECT dt.trans_no, dt.reference,'
            . ' (dt.ov_amount + dt.ov_gst + dt.ov_freight + dt.ov_freight_tax + dt.ov_discount) AS total'
            . ' FROM ' . $tablePrefix . 'debtor_trans dt'
            . ' JOIN ' . $tablePrefix . 'comments c ON c.type = dt.type AND c.id = dt.trans_no'
            . ' LEFT JOIN ' . $tablePrefix . 'voided v ON v.type = dt.type AND v.id = dt.trans_no'
            . ' WHERE dt.type = ' . $type->value
            . ' AND c.memo_ = ' . $quote($memo->value)
            . ' AND v.id IS NULL'
            . ' ORDER BY dt.trans_no DESC LIMIT 1';
    }
}
