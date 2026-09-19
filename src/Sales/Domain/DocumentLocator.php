<?php

declare(strict_types=1);

namespace FAAPI\Sales\Domain;

/**
 * Port: how the domain asks the ledger for a document. Implemented against
 * FrontAccounting's database in Infrastructure, and by an in-memory fake in
 * tests.
 */
interface DocumentLocator
{
    /**
     * The newest non-voided document of this type carrying this memo, or null.
     */
    public function findLive(DocumentType $type, DocumentMemo $memo): ?LocatedDocument;
}
