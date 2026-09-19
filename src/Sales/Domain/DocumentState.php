<?php

declare(strict_types=1);

namespace FAAPI\Sales\Domain;

/**
 * Where a customer document stands in the ledger.
 */
enum DocumentState
{
    case Missing;
    case Live;
    case Voided;
}
