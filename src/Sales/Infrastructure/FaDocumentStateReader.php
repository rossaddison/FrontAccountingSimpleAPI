<?php

declare(strict_types=1);

namespace FAAPI\Sales\Infrastructure;

use FAAPI\Sales\Domain\DocumentState;
use FAAPI\Sales\Domain\DocumentStateReader;
use FAAPI\Sales\Domain\DocumentType;

/**
 * FrontAccounting adapter for DocumentStateReader.
 */
final class FaDocumentStateReader implements DocumentStateReader
{
    public function stateOf(DocumentType $type, int $transNo): DocumentState
    {
        /** @var array{trans_no: string, voided_id: string|null}|false|null $row */
        $row = db_fetch(db_query(StateSql::build($type, $transNo, TB_PREF), 'could not read the document state'));
        if (!$row) {
            return DocumentState::Missing;
        }

        return $row['voided_id'] === null ? DocumentState::Live : DocumentState::Voided;
    }
}
