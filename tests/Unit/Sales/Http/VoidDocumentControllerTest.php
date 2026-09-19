<?php

declare(strict_types=1);

namespace FAAPI\Tests\Unit\Sales\Http;

use FAAPI\Sales\Application\VoidDocumentHandler;
use FAAPI\Sales\Domain\DocumentState;
use FAAPI\Sales\Domain\DocumentStateReader;
use FAAPI\Sales\Domain\DocumentType;
use FAAPI\Sales\Domain\DocumentVoider;
use FAAPI\Sales\Domain\VoidReason;
use FAAPI\Sales\Http\VoidDocumentController;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class VoidDocumentControllerTest extends TestCase
{
    /** @var list<array{DocumentType, int, string}> */
    private array $voided = [];

    private function controller(DocumentState $state, ?string $refusal = null): VoidDocumentController
    {
        $this->voided = [];
        $voided = &$this->voided;

        $states = new class ($state) implements DocumentStateReader {
            public function __construct(private DocumentState $state)
            {
            }

            public function stateOf(DocumentType $type, int $transNo): DocumentState
            {
                return $this->state;
            }
        };
        $voider = new class ($refusal, $voided) implements DocumentVoider {
            /**
             * @param list<array{DocumentType, int, string}> $voided
             */
            public function __construct(private ?string $refusal, private array &$voided)
            {
            }

            public function void(DocumentType $type, int $transNo, VoidReason $reason): ?string
            {
                $this->voided[] = [$type, $transNo, $reason->text];

                return $this->refusal;
            }
        };

        return new VoidDocumentController(new VoidDocumentHandler($states, $voider));
    }

    public function testVoidingALiveInvoiceIs200(): void
    {
        $result = $this->controller(DocumentState::Live)->handle(['trans_type' => '10', 'trans_no' => '73', 'memo' => 'wrong client']);

        self::assertSame(200, $result->status);
        self::assertSame(
            ['voided' => true, 'already_voided' => false, 'trans_type' => 10, 'trans_no' => 73],
            $result->body,
        );
        self::assertSame([[DocumentType::SalesInvoice, 73, 'wrong client']], $this->voided);
    }

    public function testVoidingAnAlreadyVoidedDocumentIsStill200AndSaysSo(): void
    {
        $result = $this->controller(DocumentState::Voided)->handle(['trans_type' => 11, 'trans_no' => 5]);

        self::assertSame(200, $result->status);
        self::assertTrue($result->body['already_voided']);
        self::assertSame([], $this->voided);
    }

    public function testAMissingDocumentIs404(): void
    {
        $result = $this->controller(DocumentState::Missing)->handle(['trans_type' => '12', 'trans_no' => '99']);

        self::assertSame(404, $result->status);
        self::assertSame(['code' => 404, 'success' => 0, 'msg' => 'Not found'], $result->body);
    }

    public function testARefusalIs409WithFrontAccountingsReasonNeverAFalseSuccess(): void
    {
        $result = $this->controller(DocumentState::Live, 'This invoice cannot be voided because it was already credited.')
            ->handle(['trans_type' => '10', 'trans_no' => '73']);

        self::assertSame(409, $result->status);
        self::assertSame(
            ['code' => 409, 'success' => 0, 'msg' => 'This invoice cannot be voided because it was already credited.'],
            $result->body,
        );
    }

    public function testABlankMemoUsesTheDefaultReason(): void
    {
        $this->controller(DocumentState::Live)->handle(['trans_type' => '10', 'trans_no' => '1']);

        self::assertSame([[DocumentType::SalesInvoice, 1, VoidReason::DEFAULT_TEXT]], $this->voided);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function badRequests(): array
    {
        $required = 'trans_type and trans_no are required';
        $positive = 'trans_no must be a positive whole number';
        $type = 'trans_type must be 10 (invoice), 11 (credit note) or 12 (payment).';

        return [
            'nothing' => [[], $required],
            'no number' => [['trans_type' => '10'], $required],
            'no type' => [['trans_no' => '5'], $required],
            'number as array' => [['trans_type' => '10', 'trans_no' => ['5']], $required],
            'memo as array' => [['trans_type' => '10', 'trans_no' => '5', 'memo' => ['x']], $required],
            'zero' => [['trans_type' => '10', 'trans_no' => '0'], $positive],
            'negative' => [['trans_type' => '10', 'trans_no' => '-3'], $positive],
            'decimal' => [['trans_type' => '10', 'trans_no' => '3.5'], $positive],
            'not a number' => [['trans_type' => '10', 'trans_no' => 'abc'], $positive],
            'unsupported type' => [['trans_type' => '13', 'trans_no' => '5'], $type],
            'memo too long' => [['trans_type' => '10', 'trans_no' => '5', 'memo' => str_repeat('x', 501)], 'A void reason can be at most 500 characters.'],
        ];
    }

    /**
     * @param array<string, mixed> $body
     */
    #[DataProvider('badRequests')]
    public function testABadRequestIs412AndNothingIsVoided(array $body, string $message): void
    {
        $result = $this->controller(DocumentState::Live)->handle($body);

        self::assertSame(412, $result->status);
        self::assertSame(['code' => 412, 'success' => 0, 'msg' => $message], $result->body);
        self::assertSame([], $this->voided);
    }
}
