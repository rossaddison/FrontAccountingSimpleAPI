<?php

declare(strict_types=1);

namespace FAAPI\Tests\Unit\Sales\Http;

use FAAPI\Sales\Application\FindDocumentByMemoHandler;
use FAAPI\Sales\Domain\DocumentLocator;
use FAAPI\Sales\Domain\DocumentMemo;
use FAAPI\Sales\Domain\DocumentType;
use FAAPI\Sales\Domain\LocatedDocument;
use FAAPI\Sales\Domain\Money;
use FAAPI\Sales\Http\DocumentLookupController;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DocumentLookupControllerTest extends TestCase
{
    /** @var list<array{DocumentType, string}> */
    private array $asked = [];

    private function controller(?LocatedDocument $answer): DocumentLookupController
    {
        $this->asked = [];
        $asked = &$this->asked;

        $locator = new class ($answer, $asked) implements DocumentLocator {
            /**
             * @param list<array{DocumentType, string}> $asked
             */
            public function __construct(private ?LocatedDocument $answer, private array &$asked)
            {
            }

            public function findLive(DocumentType $type, DocumentMemo $memo): ?LocatedDocument
            {
                $this->asked[] = [$type, $memo->value];

                return $this->answer;
            }
        };

        return new DocumentLookupController(new FindDocumentByMemoHandler($locator));
    }

    public function testAFoundDocumentIsReturnedWithTheGrossTotalAsADecimal(): void
    {
        $result = $this->controller(new LocatedDocument(73, '036/2026', Money::fromDecimal('203.98')))
            ->handle(['trans_type' => '10', 'comments' => 'INV-9-invoice']);

        self::assertSame(200, $result->status);
        self::assertSame(['trans_no' => 73, 'reference' => '036/2026', 'total' => 203.98], $result->body);
        self::assertSame([[DocumentType::SalesInvoice, 'INV-9-invoice']], $this->asked);
    }

    public function testAMissingDocumentIs404(): void
    {
        $result = $this->controller(null)->handle(['trans_type' => '12', 'comments' => 'INV-9-payment']);

        self::assertSame(404, $result->status);
        self::assertSame(['code' => 404, 'success' => 0, 'msg' => 'Not found'], $result->body);
    }

    public function testTheMemoIsTrimmedBeforeLookingUp(): void
    {
        $this->controller(null)->handle(['trans_type' => '11', 'comments' => '  CN-3-creditnote  ']);

        self::assertSame([[DocumentType::CreditNote, 'CN-3-creditnote']], $this->asked);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function badRequests(): array
    {
        return [
            'nothing at all' => [[], 'trans_type and comments are required'],
            'no memo' => [['trans_type' => '10'], 'trans_type and comments are required'],
            'blank memo' => [['trans_type' => '10', 'comments' => '   '], 'trans_type and comments are required'],
            'no type' => [['comments' => 'INV-9'], 'trans_type and comments are required'],
            'memo as an array' => [['trans_type' => '10', 'comments' => ['a']], 'trans_type and comments are required'],
            'unsupported type' => [['trans_type' => '13', 'comments' => 'INV-9'], 'trans_type must be 10 (invoice), 11 (credit note) or 12 (payment).'],
            'type not a number' => [['trans_type' => 'abc', 'comments' => 'INV-9'], 'trans_type must be 10 (invoice), 11 (credit note) or 12 (payment).'],
        ];
    }

    /**
     * @param array<string, mixed> $query
     */
    #[DataProvider('badRequests')]
    public function testABadRequestIs412AndNeverReachesTheLedger(array $query, string $message): void
    {
        $result = $this->controller(null)->handle($query);

        self::assertSame(412, $result->status);
        self::assertSame(['code' => 412, 'success' => 0, 'msg' => $message], $result->body);
        self::assertSame([], $this->asked);
    }
}
