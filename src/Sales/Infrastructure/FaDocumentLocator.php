<?php

declare(strict_types=1);

namespace FAAPI\Sales\Infrastructure;

use FAAPI\Sales\Domain\DocumentLocator;
use FAAPI\Sales\Domain\DocumentMemo;
use FAAPI\Sales\Domain\DocumentType;
use FAAPI\Sales\Domain\LocatedDocument;
use FAAPI\Sales\Domain\Money;

/**
 * FrontAccounting adapter for DocumentLocator. The only place in this
 * component that touches FrontAccounting's global database functions.
 */
final class FaDocumentLocator implements DocumentLocator
{
    public function findLive(DocumentType $type, DocumentMemo $memo): ?LocatedDocument
    {
        $sql = LookupSql::build(
            $type,
            $memo,
            TB_PREF,
            static fn(string $value): string => (string) db_escape($value),
        );

        /** @var array{trans_no: string, reference: string, total: string}|false|null $row */
        $row = db_fetch(db_query($sql, 'could not look up the sales document'));
        if (!$row) {
            return null;
        }

        return new LocatedDocument(
            (int) $row['trans_no'],
            (string) $row['reference'],
            Money::fromDecimal($row['total']),
        );
    }
}
