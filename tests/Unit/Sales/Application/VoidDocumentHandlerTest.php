<?php

declare(strict_types=1);

namespace FAAPI\Tests\Unit\Sales\Application;

use FAAPI\Sales\Application\VoidDocument;
use FAAPI\Sales\Application\VoidDocumentHandler;
use FAAPI\Sales\Domain\DocumentState;
use FAAPI\Sales\Domain\DocumentStateReader;
use FAAPI\Sales\Domain\DocumentType;
use FAAPI\Sales\Domain\DocumentVoider;
use FAAPI\Sales\Domain\VoidReason;
use FAAPI\Sales\Domain\VoidStatus;
use PHPUnit\Framework\TestCase;

final class VoidDocumentHandlerTest extends TestCase
{
    private function command(): VoidDocument
    {
        return new VoidDocument(DocumentType::SalesInvoice, 73, VoidReason::fromString('wrong client'));
    }

    public function testAMissingDocumentIsNotFoundAndNothingIsVoided(): void
    {
        $voider = new RecordingVoider(null);

        $outcome = (new VoidDocumentHandler(new FixedState(DocumentState::Missing), $voider))($this->command());

        self::assertSame(VoidStatus::NotFound, $outcome->status);
        self::assertSame([], $voider->calls);
    }

    public function testAnAlreadyVoidedDocumentSucceedsWithoutVoidingAgain(): void
    {
        $voider = new RecordingVoider(null);

        $outcome = (new VoidDocumentHandler(new FixedState(DocumentState::Voided), $voider))($this->command());

        self::assertSame(VoidStatus::AlreadyVoided, $outcome->status);
        self::assertTrue($outcome->isSuccess());
        self::assertSame([], $voider->calls);
    }

    public function testALiveDocumentIsVoidedWithTheGivenTypeNumberAndReason(): void
    {
        $voider = new RecordingVoider(null);

        $outcome = (new VoidDocumentHandler(new FixedState(DocumentState::Live), $voider))($this->command());

        self::assertSame(VoidStatus::Voided, $outcome->status);
        self::assertSame([[DocumentType::SalesInvoice, 73, 'wrong client']], $voider->calls);
    }

    public function testALedgerRefusalIsReportedWithItsReasonNotAsSuccess(): void
    {
        $voider = new RecordingVoider('This invoice cannot be voided because it was already credited.');

        $outcome = (new VoidDocumentHandler(new FixedState(DocumentState::Live), $voider))($this->command());

        self::assertSame(VoidStatus::Refused, $outcome->status);
        self::assertFalse($outcome->isSuccess());
        self::assertSame('This invoice cannot be voided because it was already credited.', $outcome->message);
    }
}

final class FixedState implements DocumentStateReader
{
    public function __construct(private DocumentState $state)
    {
    }

    public function stateOf(DocumentType $type, int $transNo): DocumentState
    {
        return $this->state;
    }
}

final class RecordingVoider implements DocumentVoider
{
    /** @var list<array{DocumentType, int, string}> */
    public array $calls = [];

    public function __construct(private ?string $refusal)
    {
    }

    public function void(DocumentType $type, int $transNo, VoidReason $reason): ?string
    {
        $this->calls[] = [$type, $transNo, $reason->text];

        return $this->refusal;
    }
}
