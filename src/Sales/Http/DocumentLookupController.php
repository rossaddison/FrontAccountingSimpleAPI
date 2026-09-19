<?php

declare(strict_types=1);

namespace FAAPI\Sales\Http;

use FAAPI\Sales\Application\FindDocumentByMemo;
use FAAPI\Sales\Application\FindDocumentByMemoHandler;
use FAAPI\Sales\Domain\DocumentMemo;
use FAAPI\Sales\Domain\DocumentType;
use InvalidArgumentException;

/**
 * GET /sales/lookup/?trans_type=10&comments=INV-9-invoice
 *
 * Answers "is there a live document of this type with this memo?", so a
 * caller can check before posting and never create a duplicate.
 *
 * @SWG\Get(
 *     path="/sales/lookup",
 *     summary="Find a live sales document by trans_type and comments",
 *     tags={"sales"},
 *     operationId="lookupSale",
 *     produces={"application/json"},
 *     @SWG\Response(response=200, description="found: trans_no, reference and gross total"),
 *     @SWG\Response(response=404, description="no such live document"),
 *     @SWG\Response(response=412, description="trans_type or comments missing or invalid"),
 *     deprecated=false
 * )
 */
final readonly class DocumentLookupController
{
    public function __construct(private FindDocumentByMemoHandler $handler)
    {
    }

    /**
     * @param array<string, mixed> $query the request's query parameters
     */
    public function handle(array $query): HttpResult
    {
        $transType = $query['trans_type'] ?? '';
        $comments = $query['comments'] ?? '';
        if (!is_scalar($transType) || !is_string($comments) || $transType === '' || trim($comments) === '') {
            return HttpResult::error(412, 'trans_type and comments are required');
        }

        try {
            $document = ($this->handler)(new FindDocumentByMemo(
                DocumentType::fromTransType($transType),
                DocumentMemo::fromString($comments),
            ));
        } catch (InvalidArgumentException $e) {
            return HttpResult::error(412, $e->getMessage());
        }

        if ($document === null) {
            return HttpResult::error(404, 'Not found');
        }

        return new HttpResult(200, [
            'trans_no' => $document->transNo,
            'reference' => $document->reference,
            'total' => $document->grossTotal->toDecimal(),
        ]);
    }
}
