<?php

declare(strict_types=1);

namespace FAAPI\Tests\Unit\Sales\Application;

use FAAPI\Sales\Application\FindDocumentByMemo;
use FAAPI\Sales\Application\FindDocumentByMemoHandler;
use FAAPI\Sales\Domain\DocumentLocator;
use FAAPI\Sales\Domain\DocumentMemo;
use FAAPI\Sales\Domain\DocumentType;
use FAAPI\Sales\Domain\LocatedDocument;
use FAAPI\Sales\Domain\Money;
use PHPUnit\Framework\TestCase;

final class FindDocumentByMemoHandlerTest extends TestCase
{
    public function testReturnsTheDocumentTheLocatorFinds(): void
    {
        $document = new LocatedDocument(73, '036/2026', Money::fromDecimal(100));
        $locator = new InMemoryDocumentLocator(['10|INV-9-invoice' => $document]);

        $found = (new FindDocumentByMemoHandler($locator))(new FindDocumentByMemo(
            DocumentType::SalesInvoice,
            DocumentMemo::fromString('INV-9-invoice'),
        ));

        self::assertSame($document, $found);
    }

    public function testReturnsNullWhenNothingMatches(): void
    {
        $handler = new FindDocumentByMemoHandler(new InMemoryDocumentLocator([]));

        self::assertNull($handler(new FindDocumentByMemo(DocumentType::SalesInvoice, DocumentMemo::fromString('nope'))));
    }

    public function testTheTypeIsPartOfTheKey(): void
    {
        $invoice = new LocatedDocument(73, '036/2026', Money::fromDecimal(100));
        $handler = new FindDocumentByMemoHandler(new InMemoryDocumentLocator(['10|INV-9' => $invoice]));

        self::assertNull($handler(new FindDocumentByMemo(DocumentType::CreditNote, DocumentMemo::fromString('INV-9'))));
        self::assertSame($invoice, $handler(new FindDocumentByMemo(DocumentType::SalesInvoice, DocumentMemo::fromString('INV-9'))));
    }
}

/**
 * In-memory stand-in for the FrontAccounting adapter.
 */
final class InMemoryDocumentLocator implements DocumentLocator
{
    /**
     * @param array<string, LocatedDocument> $documents keyed "type|memo"
     */
    public function __construct(private array $documents)
    {
    }

    public function findLive(DocumentType $type, DocumentMemo $memo): ?LocatedDocument
    {
        return $this->documents[$type->value . '|' . $memo->value] ?? null;
    }
}
