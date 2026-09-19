<?php

declare(strict_types=1);

namespace FAAPI\Sales\Application;

use FAAPI\Sales\Domain\DocumentMemo;
use FAAPI\Sales\Domain\DocumentType;

/**
 * Query: find the live document of this type that carries this memo.
 */
final readonly class FindDocumentByMemo
{
    public function __construct(
        public DocumentType $type,
        public DocumentMemo $memo,
    ) {
    }
}
