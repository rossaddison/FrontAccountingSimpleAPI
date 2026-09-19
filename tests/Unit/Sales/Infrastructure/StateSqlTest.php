<?php

declare(strict_types=1);

namespace FAAPI\Tests\Unit\Sales\Infrastructure;

use FAAPI\Sales\Domain\DocumentType;
use FAAPI\Sales\Infrastructure\StateSql;
use PHPUnit\Framework\TestCase;

final class StateSqlTest extends TestCase
{
    public function testReadsTheDocumentAndItsVoidedMarkerInOneQuery(): void
    {
        $sql = StateSql::build(DocumentType::SalesInvoice, 73, '0_');

        self::assertStringContainsString('SELECT dt.trans_no, v.id AS voided_id', $sql);
        self::assertStringContainsString('FROM 0_debtor_trans dt', $sql);
        self::assertStringContainsString('LEFT JOIN 0_voided v ON v.type = dt.type AND v.id = dt.trans_no', $sql);
        self::assertStringContainsString('WHERE dt.type = 10 AND dt.trans_no = 73', $sql);
        self::assertStringEndsWith('LIMIT 1', $sql);
    }

    public function testTheTypeAndNumberAreIntegersOnly(): void
    {
        $sql = StateSql::build(DocumentType::CustomerPayment, 5, '');

        self::assertStringContainsString('dt.type = 12 AND dt.trans_no = 5', $sql);
        self::assertStringContainsString('FROM debtor_trans dt', $sql);
    }
}
