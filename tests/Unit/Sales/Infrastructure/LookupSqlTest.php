<?php

declare(strict_types=1);

namespace FAAPI\Tests\Unit\Sales\Infrastructure;

use FAAPI\Sales\Domain\DocumentMemo;
use FAAPI\Sales\Domain\DocumentType;
use FAAPI\Sales\Infrastructure\LookupSql;
use PHPUnit\Framework\TestCase;

final class LookupSqlTest extends TestCase
{
    private function quote(): \Closure
    {
        return static fn(string $value): string => "'" . str_replace("'", "''", $value) . "'";
    }

    public function testFindsANonVoidedDocumentOfTheRightTypeByMemo(): void
    {
        $sql = LookupSql::build(DocumentType::SalesInvoice, DocumentMemo::fromString('INV-9-invoice'), '0_', $this->quote());

        self::assertStringContainsString('FROM 0_debtor_trans dt', $sql);
        self::assertStringContainsString('JOIN 0_comments c ON c.type = dt.type AND c.id = dt.trans_no', $sql);
        self::assertStringContainsString('LEFT JOIN 0_voided v ON v.type = dt.type AND v.id = dt.trans_no', $sql);
        self::assertStringContainsString('WHERE dt.type = 10', $sql);
        self::assertStringContainsString("c.memo_ = 'INV-9-invoice'", $sql);
        self::assertStringContainsString('v.id IS NULL', $sql);
        self::assertStringContainsString('ORDER BY dt.trans_no DESC LIMIT 1', $sql);
    }

    public function testTheTotalIsTheGrossOfEveryAmountColumn(): void
    {
        $sql = LookupSql::build(DocumentType::CreditNote, DocumentMemo::fromString('CN-1'), '', $this->quote());

        self::assertStringContainsString(
            '(dt.ov_amount + dt.ov_gst + dt.ov_freight + dt.ov_freight_tax + dt.ov_discount) AS total',
            $sql,
        );
        self::assertStringContainsString('WHERE dt.type = 11', $sql);
    }

    public function testTheMemoOnlyEverAppearsThroughTheEscaper(): void
    {
        $hostile = "x' OR '1'='1";

        $sql = LookupSql::build(DocumentType::CustomerPayment, DocumentMemo::fromString($hostile), '', $this->quote());

        self::assertStringContainsString("c.memo_ = 'x'' OR ''1''=''1'", $sql);
        self::assertStringNotContainsString("c.memo_ = 'x' OR", $sql);
    }

    public function testAnEmptyPrefixIsAllowed(): void
    {
        $sql = LookupSql::build(DocumentType::SalesInvoice, DocumentMemo::fromString('A'), '', $this->quote());

        self::assertStringContainsString('FROM debtor_trans dt', $sql);
    }
}
