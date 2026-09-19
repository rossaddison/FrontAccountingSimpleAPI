<?php

declare(strict_types=1);

namespace FAAPI\Sales\Application;

use FAAPI\Sales\Domain\DocumentType;
use FAAPI\Sales\Domain\VoidReason;

/**
 * Command: void one customer document.
 */
final readonly class VoidDocument
{
    public function __construct(
        public DocumentType $type,
        public int $transNo,
        public VoidReason $reason,
    ) {
    }
}
