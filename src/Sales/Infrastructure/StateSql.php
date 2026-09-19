<?php

declare(strict_types=1);

namespace FAAPI\Sales\Infrastructure;

use FAAPI\Sales\Domain\DocumentType;

/**
 * Builds the query that says whether a document exists and whether it has
 * been voided. Pure, so it can be tested without a database.
 */
final class StateSql
{
    public static function build(DocumentType $type, int $transNo, string $tablePrefix): string
    {
        return 'SELECT dt.trans_no, v.id AS voided_id'
            . ' FROM ' . $tablePrefix . 'debtor_trans dt'
            . ' LEFT JOIN ' . $tablePrefix . 'voided v ON v.type = dt.type AND v.id = dt.trans_no'
            . ' WHERE dt.type = ' . $type->value
            . ' AND dt.trans_no = ' . $transNo
            . ' LIMIT 1';
    }
}
