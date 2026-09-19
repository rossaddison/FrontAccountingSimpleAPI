<?php

declare(strict_types=1);

namespace FAAPI\Tests\Unit\Sales\Domain;

use FAAPI\Sales\Domain\DocumentMemo;
use FAAPI\Sales\Domain\DocumentType;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DocumentTypeAndMemoTest extends TestCase
{
    /**
     * @return array<string, array{int|string, DocumentType}>
     */
    public static function supportedTypes(): array
    {
        return [
            'invoice as int' => [10, DocumentType::SalesInvoice],
            'credit note as string' => ['11', DocumentType::CreditNote],
            'payment as string' => ['12', DocumentType::CustomerPayment],
        ];
    }

    #[DataProvider('supportedTypes')]
    public function testMapsFrontAccountingTransactionTypes(int|string $input, DocumentType $expected): void
    {
        self::assertSame($expected, DocumentType::fromTransType($input));
    }

    /**
     * @return array<string, array{int|string}>
     */
    public static function unsupportedTypes(): array
    {
        return [
            'a journal' => [0],
            'a delivery note' => [13],
            'not a number' => ['abc'],
            'negative' => ['-10'],
            'decimal' => ['10.5'],
            'empty' => [''],
        ];
    }

    #[DataProvider('unsupportedTypes')]
    public function testRejectsAnyOtherType(int|string $input): void
    {
        $this->expectException(InvalidArgumentException::class);

        DocumentType::fromTransType($input);
    }

    public function testTheMemoIsTrimmedButOtherwiseKeptExactly(): void
    {
        self::assertSame('INV-9-invoice', DocumentMemo::fromString("  INV-9-invoice \n")->value);
        self::assertSame("Y3I-7 O'Brien & Co", DocumentMemo::fromString("Y3I-7 O'Brien & Co")->value);
    }

    #[DataProvider('blankMemos')]
    public function testRejectsABlankMemo(string $memo): void
    {
        $this->expectException(InvalidArgumentException::class);

        DocumentMemo::fromString($memo);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function blankMemos(): array
    {
        return ['empty' => [''], 'spaces' => ['   '], 'whitespace' => ["\t\n"]];
    }
}
