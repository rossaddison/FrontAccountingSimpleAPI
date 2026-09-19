<?php

declare(strict_types=1);

namespace FAAPI\Sales\Domain;

/**
 * Port: void a live document in the ledger.
 */
interface DocumentVoider
{
    /**
     * @return string|null why the ledger refused (for example the invoice was
     *                     already credited), or null when the document was voided
     */
    public function void(DocumentType $type, int $transNo, VoidReason $reason): ?string;
}
