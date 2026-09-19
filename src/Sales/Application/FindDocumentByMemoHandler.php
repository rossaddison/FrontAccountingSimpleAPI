<?php

declare(strict_types=1);

namespace FAAPI\Sales\Application;

use FAAPI\Sales\Domain\DocumentLocator;
use FAAPI\Sales\Domain\LocatedDocument;

final readonly class FindDocumentByMemoHandler
{
    public function __construct(private DocumentLocator $locator)
    {
    }

    public function __invoke(FindDocumentByMemo $query): ?LocatedDocument
    {
        return $this->locator->findLive($query->type, $query->memo);
    }
}
