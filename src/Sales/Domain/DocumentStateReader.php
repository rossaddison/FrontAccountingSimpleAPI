<?php

declare(strict_types=1);

namespace FAAPI\Sales\Domain;

/**
 * Port: is this document missing, live or already voided?
 */
interface DocumentStateReader
{
    public function stateOf(DocumentType $type, int $transNo): DocumentState;
}
