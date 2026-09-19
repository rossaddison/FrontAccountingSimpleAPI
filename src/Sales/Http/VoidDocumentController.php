<?php

declare(strict_types=1);

namespace FAAPI\Sales\Http;

use FAAPI\Sales\Application\VoidDocument;
use FAAPI\Sales\Application\VoidDocumentHandler;
use FAAPI\Sales\Domain\DocumentType;
use FAAPI\Sales\Domain\VoidReason;
use FAAPI\Sales\Domain\VoidStatus;
use InvalidArgumentException;

/**
 * POST /sales/void/  with trans_type, trans_no and an optional memo.
 *
 * Voids a customer document using FrontAccounting's own void. Idempotent:
 * voiding a document that is already void succeeds. If FrontAccounting
 * refuses (for example an invoice that was already credited) the answer is
 * 409 with its reason, never a false "voided".
 *
 * @SWG\Post(
 *     path="/sales/void",
 *     summary="Void a sales invoice, credit note or customer payment",
 *     tags={"sales"},
 *     operationId="voidSale",
 *     produces={"application/json"},
 *     @SWG\Response(response=200, description="voided, or already void"),
 *     @SWG\Response(response=404, description="no such document"),
 *     @SWG\Response(response=409, description="FrontAccounting refused to void it"),
 *     @SWG\Response(response=412, description="trans_type or trans_no missing or invalid"),
 *     deprecated=false
 * )
 */
final readonly class VoidDocumentController
{
    public function __construct(private VoidDocumentHandler $handler)
    {
    }

    /**
     * @param array<string, mixed> $body the request's form or JSON fields
     */
    public function handle(array $body): HttpResult
    {
        $transType = $body['trans_type'] ?? '';
        $transNo = $body['trans_no'] ?? '';
        $memo = $body['memo'] ?? '';
        if (!is_scalar($transType) || !is_scalar($transNo) || !is_string($memo)) {
            return HttpResult::error(412, 'trans_type and trans_no are required');
        }
        if ($transType === '' || $transNo === '') {
            return HttpResult::error(412, 'trans_type and trans_no are required');
        }
        if (!ctype_digit((string) $transNo) || (int) $transNo < 1) {
            return HttpResult::error(412, 'trans_no must be a positive whole number');
        }

        try {
            $type = DocumentType::fromTransType(is_int($transType) ? $transType : (string) $transType);
            $outcome = ($this->handler)(new VoidDocument($type, (int) $transNo, VoidReason::fromString($memo)));
        } catch (InvalidArgumentException $e) {
            return HttpResult::error(412, $e->getMessage());
        }

        return match ($outcome->status) {
            VoidStatus::Voided => new HttpResult(200, $this->success($type, (int) $transNo, false)),
            VoidStatus::AlreadyVoided => new HttpResult(200, $this->success($type, (int) $transNo, true)),
            VoidStatus::NotFound => HttpResult::error(404, 'Not found'),
            VoidStatus::Refused => HttpResult::error(409, $outcome->message),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function success(DocumentType $type, int $transNo, bool $already): array
    {
        return [
            'voided' => true,
            'already_voided' => $already,
            'trans_type' => $type->value,
            'trans_no' => $transNo,
        ];
    }
}
